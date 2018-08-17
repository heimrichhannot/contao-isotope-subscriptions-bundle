<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeSubscriptionsBundle\Manager;

use Contao\Config;
use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\Environment;
use Contao\MemberModel;
use Contao\Module;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use HeimrichHannot\FieldpaletteBundle\Model\FieldPaletteModel;
use HeimrichHannot\IsotopeSubscriptionsBundle\Model\Subscription;
use HeimrichHannot\IsotopeSubscriptionsBundle\Model\SubscriptionArchive;
use Isotope\Model\Product\Standard;
use Isotope\Model\ProductCollection\Order;
use NotificationCenter\Model\Notification;

class IsotopeSubscriptions
{
    /**
     * @var ContaoFrameworkInterface
     */
    protected $framework;

    public function __construct(ContaoFrameworkInterface $framework)
    {
        $this->framework = $framework;
    }

    /**
     * @param Order  $oder
     * @param Module $module
     */
    public function setCheckoutModuleIdSubscriptions(Order $oder, Module $module)
    {
        System::getContainer()->get('session')->set('isotopeCheckoutModuleIdSubscriptions', $module->id);
    }

    /**
     * @param Order  $order
     * @param Module $module
     *
     * @return bool
     */
    public function checkForExistingSubscription(Order $order, Module $module)
    {
        $strEmail = $order->getBillingAddress()->email;

        $arrItems = $order->getItems();

        foreach ($arrItems as $item) {
            switch ($module->iso_direct_checkout_product_mode) {
                case 'product_type':
                    $objFieldpalette = $this->framework->getAdapter(FieldPaletteModel::class)->findBy('iso_direct_checkout_product_type', $this->framework->getAdapter(Standard::class)->findAvailableByIdOrAlias($item->product_id)->type);
                    break;
                default:
                    $objFieldpalette = $this->framework->getAdapter(FieldPaletteModel::class)->findBy('iso_direct_checkout_product', $item->product_id);
                    break;
            }

            if ((!$objFieldpalette->iso_addSubscriptionCheckbox || \Input::post('subscribeToProduct_'.$item->product_id)) && $objFieldpalette->iso_addSubscription && $objFieldpalette->iso_subscriptionArchive
                && null !== ($objSubscriptionArchive = $this->framework->getAdapter(SubscriptionArchive::class)->findByPk($objFieldpalette->iso_subscriptionArchive))) {
                if (null !== $this->framework->getAdapter(Subscription::class)->findBy(['email=?', 'pid=?', 'disable!=?'], [$strEmail, $objSubscriptionArchive->id, 1])) {
                    $_SESSION['ISO_ERROR'][] = sprintf($GLOBALS['TL_LANG']['MSC']['iso_subscriptionAlreadyExists'], $strEmail, $item->name);

                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param Order $order
     * @param array $tokens
     *
     * @return bool
     */
    public function addSubscriptions(Order $order, array $tokens)
    {
        $strEmail = $order->getBillingAddress()->email;
        $objAddress = $order->getShippingAddress() ?: $order->getBillingAddress();
        $arrItems = $order->getItems();

        $objSession = System::getContainer()->get('session');

        if (!($intModule = $objSession->get('isotopeCheckoutModuleIdSubscriptions'))) {
            return true;
        }

        $objSession->remove('isotopeCheckoutModuleIdSubscriptions');

        $objModule = $this->framework->getAdapter(ModuleModel::class)->findByPk($intModule);
        foreach ($arrItems as $item) {
            switch ($objModule->iso_direct_checkout_product_mode) {
                case 'product_type':
                    $objFieldpalette = $this->framework->getAdapter(FieldPaletteModel::class)->findBy('iso_direct_checkout_product_type', $this->framework->getAdapter(Standard::class)->findAvailableByIdOrAlias($item->product_id)->type);
                    break;
                default:
                    $objFieldpalette = $this->framework->getAdapter(FieldPaletteModel::class)->findBy('iso_direct_checkout_product', $item->product_id);
                    break;
            }

            if (null !== $objFieldpalette && $objFieldpalette->iso_addSubscription) {
                if ($objFieldpalette->iso_subscriptionArchive && (!$objFieldpalette->iso_addSubscriptionCheckbox || \Input::post('subscribeToProduct_'.$item->product_id))) {
                    $objSubscription = $this->framework->getAdapter(Subscription::class)->findOneBy(['email=?', 'pid=?', 'activation!=?', 'disable=?'], [$strEmail, $objFieldpalette->iso_subscriptionArchive, '', 1]);

                    if (!$objSubscription) {
                        $objSubscription = new Subscription();
                    }

                    if ($objFieldpalette->iso_addActivation) {
                        $strToken = md5(uniqid(mt_rand(), true));

                        $objSubscription->disable = true;
                        $objSubscription->activation = $strToken;

                        if (null !== ($objNotification = $this->framework->getAdapter(Notification::class)->findByPk($objFieldpalette->iso_activationNotification))) {
                            if ($objFieldpalette->iso_activationJumpTo
                                && null !== ($objPageRedirect = $this->framework->getAdapter(PageModel::class)->findPublishedById($objFieldpalette->iso_activationJumpTo))) {
                                $tokens['link'] = Environment::get('url').'/'.$objPageRedirect->getFrontendUrl().'?token='.$strToken;
                            }

                            $objNotification->send($tokens, $GLOBALS['TL_LANGUAGE']);
                        }
                    }

                    $arrAddressFields = Config::get('iso_addressFields');

                    if (null === $arrAddressFields) {
                        $arrAddressFields = serialize(array_keys($this->getIsotopeAddressFields()));
                    }

                    foreach (StringUtil::deserialize($arrAddressFields, true) as $strName) {
                        $objSubscription->{$strName} = $objAddress->{$strName};
                    }

                    $objSubscription->email = $strEmail;
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
            if (!in_array($strName, $arrSkipFields, true)) {
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

            if ($blnChangeMandatoryAddressFields && is_array($arrMandatoryAddressFields)) {
                $arrFields[$strName]['eval']['mandatory'] = in_array($strName, $arrMandatoryAddressFields, true);
            }
        }

        System::getContainer()->get('huh.utils.array')->insertInArrayByName($arrDca['fields'], 'tstamp', $arrFields, 1);

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

    /**
     * @param $module
     */
    public function checkUsernameForIsoSubscription($module)
    {
        $request = System::getContainer()->get('huh.request');
        $username = $request->getPost('username') ? $request->getPost('username') : $request->getPost('email');

        // check if user has a subscribtion
        if ($module->iso_checkForExitingSubscription && null === ($objSubscription = $this->framework->getAdapter(Subscription::class)->findBy('email', $username))) {
            $_SESSION['LOGIN_ERROR'] = $GLOBALS['TL_LANG']['MSC']['noAbonement'];
            Controller::reload();
        }

        if (null !== ($objMember = $this->framework->getAdapter(MemberModel::class)->findByUsername($username))) {
            $arrGroups = StringUtil::deserialize($objMember->groups);

            foreach (StringUtil::deserialize($module->reg_groups) as $group) {
                if (!in_array($group, $arrGroups, true)) {
                    $arrGroups[] = $group;
                }
            }

            $objMember->groups = serialize($arrGroups);

            $objMember->save();
        }
    }
}
