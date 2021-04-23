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
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use HeimrichHannot\FieldpaletteBundle\Model\FieldPaletteModel;
use HeimrichHannot\IsotopeSubscriptionsBundle\Model\SubscriptionArchiveModel;
use HeimrichHannot\IsotopeSubscriptionsBundle\Model\SubscriptionModel;
use HeimrichHannot\RequestBundle\Component\HttpFoundation\Request;
use HeimrichHannot\UtilsBundle\Arrays\ArrayUtil;
use HeimrichHannot\UtilsBundle\Model\ModelUtil;
use HeimrichHannot\UtilsBundle\Url\UrlUtil;
use Isotope\Model\Product\Standard;
use Isotope\Model\ProductCollection\Order;
use NotificationCenter\Model\Notification;
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
            switch ($module->iso_direct_checkout_product_mode) {
                case 'product_type':
                    $objFieldpalette = $this->framework->getAdapter(FieldPaletteModel::class)->findBy('iso_direct_checkout_product_type', $this->framework->getAdapter(Standard::class)->findAvailableByIdOrAlias($item->product_id)->type);

                    break;

                default:
                    $objFieldpalette = $this->framework->getAdapter(FieldPaletteModel::class)->findBy('iso_direct_checkout_product', $item->product_id);

                    break;
            }

            if ((!$objFieldpalette->iso_addSubscriptionCheckbox || \Input::post('subscribeToProduct_'.$item->product_id)) && $objFieldpalette->iso_addSubscription && $objFieldpalette->iso_subscriptionArchive
                && null !== ($objSubscriptionArchive = $this->framework->getAdapter(SubscriptionArchiveModel::class)->findByPk($objFieldpalette->iso_subscriptionArchive))) {
                if (null !== $this->framework->getAdapter(SubscriptionModel::class)->findBy(['email=?', 'pid=?', 'disable!=?'], [$email, $objSubscriptionArchive->id, 1])) {
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
            switch ($module->iso_direct_checkout_product_mode) {
                case 'product_type':
                    $objFieldpalette = $this->framework->getAdapter(FieldPaletteModel::class)->findBy('iso_direct_checkout_product_type', $this->framework->getAdapter(Standard::class)->findPublishedByIdOrAlias($item->product_id)->type);

                    break;

                default:
                    $objFieldpalette = $this->framework->getAdapter(FieldPaletteModel::class)->findBy('iso_direct_checkout_product', $item->product_id);

                    break;
            }

            if (null !== $objFieldpalette && $objFieldpalette->iso_addSubscription) {
                if ($objFieldpalette->iso_subscriptionArchive && (!$objFieldpalette->iso_addSubscriptionCheckbox || \Input::post('subscribeToProduct_'.$item->product_id))) {
                    $objSubscription = $this->framework->getAdapter(SubscriptionModel::class)->findOneBy(['email=?', 'pid=?', 'activation!=?', 'disable=?'], [$email, $objFieldpalette->iso_subscriptionArchive, '', 1]);

                    if (!$objSubscription) {
                        $objSubscription = new SubscriptionModel();
                    }

                    if ($objFieldpalette->iso_addActivation) {
                        $strToken = md5(uniqid(mt_rand(), true));

                        $objSubscription->disable = true;
                        $objSubscription->activation = $strToken;

                        if (null !== ($objNotification = $this->framework->getAdapter(Notification::class)->findByPk($objFieldpalette->iso_activationNotification))) {
                            if ($objFieldpalette->iso_activationJumpTo
                                && null !== ($objPageRedirect = $this->framework->getAdapter(PageModel::class)->findPublishedById($objFieldpalette->iso_activationJumpTo))) {
                                $tokens['link'] = $this->urlUtil->addQueryString('token='.$strToken, $objPageRedirect->getAbsoluteUrl());
                            }

                            $objNotification->send($tokens, $GLOBALS['TL_LANGUAGE']);
                        }
                    }

                    $arrAddressFields = Config::get('iso_addressFields');

                    if (null === $arrAddressFields) {
                        $arrAddressFields = serialize(array_keys($this->getIsotopeAddressFields()));
                    }

                    foreach (StringUtil::deserialize($arrAddressFields, true) as $strName) {
                        $objSubscription->{$strName} = $address->{$strName};
                    }

                    $objSubscription->email = $email;
                    $objSubscription->pid = $objFieldpalette->iso_subscriptionArchive;
                    $objSubscription->tstamp = $objSubscription->dateAdded = time();
                    $objSubscription->quantity = \Input::post('quantity');
                    $objSubscription->order_id = $order->id;

                    $objSubscription->save();
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
}
