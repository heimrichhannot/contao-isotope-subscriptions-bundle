<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeSubscriptionsBundle\Manager;

use Contao\Config;
use Contao\Controller;
use Contao\Module;
use Contao\StringUtil;
use Contao\System;
use HeimrichHannot\IsotopeSubscriptionsBundle\Model\SubscriptionModel;
use HeimrichHannot\RequestBundle\Component\HttpFoundation\Request;
use HeimrichHannot\UtilsBundle\Arrays\ArrayUtil;
use HeimrichHannot\UtilsBundle\Model\ModelUtil;
use HeimrichHannot\UtilsBundle\Url\UrlUtil;
use Isotope\Model\ProductCollection\Order;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SubscriptionManager
{
    protected ModelUtil        $modelUtil;
    protected SessionInterface $session;
    protected Request          $request;
    protected ArrayUtil        $arrayUtil;
    protected UrlUtil          $urlUtil;

    public function __construct(SessionInterface $session,
        Request $request,
        ModelUtil $modelUtil,
        ArrayUtil $arrayUtil,
        UrlUtil $urlUtil
    ) {
        $this->modelUtil = $modelUtil;
        $this->session = $session;
        $this->request = $request;
        $this->arrayUtil = $arrayUtil;
        $this->urlUtil = $urlUtil;
    }

    public function setCheckoutModuleIdSubscriptions(Order $order, Module $module)
    {
        $this->session->set('isotopeCheckoutModuleIdSubscriptions', $module->id);
    }

    /**
     * @return bool
     */
    public function checkForExistingSubscription(Order $order, Module $module)
    {
        $email = $order->getBillingAddress()->email;

        $items = $order->getItems();

        foreach ($items as $item) {
            $config = null;

            switch ($module->iso_direct_checkout_product_mode) {
                case 'product_type':
                    $product = $this->modelUtil->findModelInstanceByPk('tl_iso_product', $item->product_id);

                    if (null === $product) {
                        continue 2;
                    }

                    foreach (StringUtil::deserialize($module->iso_direct_checkout_product_types, true) as $row) {
                        if ($row['iso_direct_checkout_product_type'] === $product->type) {
                            $config = $row;

                            break;
                        }
                    }

                    break;

                default:
                    foreach (StringUtil::deserialize($module->iso_direct_checkout_products, true) as $row) {
                        if ($row['iso_direct_checkout_product'] === $item->product_id) {
                            $config = $row;

                            break;
                        }
                    }

                    break;
            }

            if (null !== $config && (!$config['iso_addSubscriptionCheckbox'] || $this->request->getPost('subscribeToProduct_'.$item->product_id)) &&
                $config['iso_addSubscription'] && $config['iso_subscriptionArchive']
                && null !== ($archive = $this->modelUtil->findModelInstanceByPk('tl_iso_subscription_archive', $config['iso_subscriptionArchive']))) {
                if (null !== $this->modelUtil->findModelInstancesBy('tl_iso_subscription', [
                    'tl_iso_subscription.email=?', 'tl_iso_subscription.pid=?', 'tl_iso_subscription.disable!=?',
                    ], [$email, $archive->id, 1])) {
                    $_SESSION['ISO_ERROR'][] = sprintf($GLOBALS['TL_LANG']['MSC']['iso_subscriptionAlreadyExists'], $email, $item->name);

                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @return bool
     */
    public function addSubscriptions(Order $order, array $tokens)
    {
        $email = $order->getBillingAddress()->email;
        $address = $order->getShippingAddress() ?: $order->getBillingAddress();
        $items = $order->getItems();

        if (!($module = $this->session->get('isotopeCheckoutModuleIdSubscriptions'))) {
            return true;
        }

        $this->session->remove('isotopeCheckoutModuleIdSubscriptions');

        $module = $this->modelUtil->findModelInstanceByPk('tl_module', $module);

        foreach ($items as $item) {
            $config = null;

            switch ($module->iso_direct_checkout_product_mode) {
                case 'product_type':
                    $product = $this->modelUtil->findModelInstanceByPk('tl_iso_product', $item->product_id);

                    if (null === $product) {
                        continue 2;
                    }

                    foreach (StringUtil::deserialize($module->iso_direct_checkout_product_types, true) as $row) {
                        if ($row['iso_direct_checkout_product_type'] === $product->type) {
                            $config = $row;

                            break;
                        }
                    }

                    break;

                default:
                    foreach (StringUtil::deserialize($module->iso_direct_checkout_products, true) as $row) {
                        if ($row['iso_direct_checkout_product'] === $item->product_id) {
                            $config = $row;

                            break;
                        }
                    }

                    break;
            }

            if (null !== $config && $config['iso_addSubscription']) {
                if ($config['iso_subscriptionArchive'] && (!$config['iso_addSubscriptionCheckbox'] || $this->request->getPost('subscribeToProduct_'.$item->product_id))) {
                    $subscription = $this->modelUtil->findOneModelInstanceBy('tl_iso_subscription', [
                            'tl_iso_subscription.email=?', 'tl_iso_subscription.pid=?', 'activation!=?', 'tl_iso_subscription.disable=?',
                        ], [$email, $config['iso_subscriptionArchive'], '', 1]);

                    if (!$subscription) {
                        $subscription = new SubscriptionModel();
                    }

                    if ($config['iso_addActivation']) {
                        $strToken = md5(uniqid(mt_rand(), true));

                        $subscription->disable = true;
                        $subscription->activation = $strToken;

                        if (null !== ($notification = $this->modelUtil->findModelInstanceByPk('tl_nc_notification', $config['iso_activationNotification']))) {
                            if ($config['iso_activationJumpTo']
                                && null !== ($objPageRedirect = $this->modelUtil->callModelMethod('tl_page', 'findPublishedById', $config['iso_activationJumpTo']))) {
                                $tokens['link'] = $this->urlUtil->addQueryString('token='.$strToken, $objPageRedirect->getAbsoluteUrl());
                            }

                            $notification->send($tokens, $GLOBALS['TL_LANGUAGE']);
                        }
                    }

                    $addressFields = Config::get('iso_addressFields');

                    if (null === $addressFields) {
                        $addressFields = serialize(array_keys($this->getIsotopeAddressFields()));
                    }

                    foreach (StringUtil::deserialize($addressFields, true) as $strName) {
                        $subscription->{$strName} = $address->{$strName};
                    }

                    $subscription->email = $email;
                    $subscription->pid = $config['iso_subscriptionArchive'];
                    $subscription->tstamp = $subscription->dateAdded = time();
                    $subscription->quantity = $this->request->getPost('quantity_'.$item->product_id);
                    $subscription->order_id = $order->id;

                    $subscription->save();

                    $this->addPrivacyProtocolEntry($config['iso_privacyEntryConfig'], $module, $subscription->row());
                }
            }
        }

        return true;
    }

    /**
     * @return array
     */
    public function getIsotopeAddressFields()
    {
        Controller::loadDataContainer('tl_iso_address');
        System::loadLanguageFile('tl_iso_address');
        $arrOptions = [];
        $arrSkipFields = ['id', 'pid', 'tstamp', 'ptable', 'label', 'store_id', 'isDefaultBilling', 'isDefaultShipping'];

        foreach ($GLOBALS['TL_DCA']['tl_iso_address']['fields'] as $strName => $arrData) {
            if (!\in_array($strName, $arrSkipFields, true)) {
                $arrOptions[$strName] = $GLOBALS['TL_LANG']['tl_iso_address'][$strName][0] ?: $strName;
            }
        }

        return $arrOptions;
    }

    public function importIsotopeAddressFields()
    {
        $arrDca = &$GLOBALS['TL_DCA']['tl_iso_subscription'];

        Controller::loadDataContainer('tl_iso_address');
        System::loadLanguageFile('tl_iso_address');

        // fields
        $blnChangeMandatoryAddressFields = Config::get('iso_changeMandatoryAddressFields');
        $arrMandatoryAddressFields = StringUtil::deserialize(Config::get('iso_mandatoryAddressFields'), true);
        $arrAddressFields = Config::get('iso_addressFields');

        if (null === $arrAddressFields) {
            $arrAddressFields = serialize(array_keys($this->getIsotopeAddressFields()));
        }

        $arrFields = [];

        foreach (StringUtil::deserialize($arrAddressFields, true) as $strName) {
            $arrFields[$strName] = $GLOBALS['TL_DCA']['tl_iso_address']['fields'][$strName];

            if ('gender' == $strName) {
                $arrFields[$strName]['reference'] = &$GLOBALS['TL_LANG']['tl_iso_address']['gender'];
            }

            if ('email' == $strName) {
                $arrFields[$strName]['eval']['unique'] = true;
            }

            if ($blnChangeMandatoryAddressFields && \is_array($arrMandatoryAddressFields)) {
                $arrFields[$strName]['eval']['mandatory'] = \in_array($strName, $arrMandatoryAddressFields, true);
            }
        }

        $this->arrayUtil->insertInArrayByName($arrDca['fields'], 'tstamp', $arrFields, 1);

        // palette
        $strInitialPalette = $arrDca['palettes']['default'];
        $strFeGroup = $arrDca['palettes']['default'] = '';
        $i = 0;

        foreach ($arrFields as $strName => $arrData) {
            if (!$strFeGroup || $GLOBALS['TL_DCA']['tl_iso_address']['fields'][$strName]['eval']['feGroup'] != $strFeGroup) {
                $strFeGroup = $GLOBALS['TL_DCA']['tl_iso_address']['fields'][$strName]['eval']['feGroup'];
                $arrDca['palettes']['default'] = rtrim($arrDca['palettes']['default'], ',');
                $arrDca['palettes']['default'] .= (0 == $i ? '' : ';').'{'.$strFeGroup.'_legend},';
            }

            $arrDca['palettes']['default'] .= $strName.',';

            ++$i;
        }

        $arrDca['palettes']['default'] = rtrim($arrDca['palettes']['default'], ',');
        $arrDca['palettes']['default'] .= ';'.$strInitialPalette;
    }

    public function addPrivacyProtocolEntry($config, $module, $data)
    {
        if (!class_exists('\HeimrichHannot\PrivacyBundle\HeimrichHannotPrivacyBundle')) {
            return;
        }

        $protocolManager = System::getContainer()->get(\HeimrichHannot\PrivacyBundle\Manager\ProtocolManager::class);

        $protocolManager->addEntryFromModuleByConfig(
            $config,
            $data,
            $module,
            'heimrichhannot/contao-isotope-subscriptions-bundle'
        );
    }
}
