<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeSubscriptionsBundle\DataContainer;

use HeimrichHannot\IsotopeSubscriptionsBundle\Manager\SubscriptionManager;
use HeimrichHannot\RequestBundle\Component\HttpFoundation\Request;
use HeimrichHannot\UtilsBundle\Model\ModelUtil;
use HeimrichHannot\UtilsBundle\String\StringUtil;

class MemberContainer
{
    /**
     * @var ModelUtil
     */
    protected SubscriptionManager $subscriptionManager;
    protected Request             $request;
    protected StringUtil          $stringUtil;
    protected ModelUtil           $modelUtil;

    public function __construct(
        SubscriptionManager $subscriptionManager,
        Request $request,
        StringUtil $stringUtil,
        ModelUtil $modelUtil
    ) {
        $this->subscriptionManager = $subscriptionManager;
        $this->request = $request;
        $this->stringUtil = $stringUtil;
        $this->modelUtil = $modelUtil;
    }

    public function modifyFieldPalette()
    {
        if (null === ($objFieldPalette = \Contao\System::getContainer()->get('contao.framework')->getAdapter(\HeimrichHannot\FieldpaletteBundle\Model\FieldPaletteModel::class)->findByPk(\Contao\System::getContainer()->get('huh.request')->getGet('id')))) {
            return;
        }

        $objModule = \Contao\System::getContainer()->get('contao.framework')->getAdapter(\Contao\ModuleModel::class)->findByPk($objFieldPalette->pid);
        $dca = &$GLOBALS['TL_DCA']['tl_fieldpalette'];

        \Contao\Controller::loadDataContainer('tl_module');

        switch ($objModule->type) {
            case 'iso_direct_checkout':
                if (class_exists('HeimrichHannot\IsotopeBundle\HeimrichHannotIsotopeBundle')) {
                    $dca['subpalettes']['iso_addSubscription'] = str_replace('iso_subscriptionArchive', 'iso_subscriptionArchive,iso_addSubscriptionCheckbox', $GLOBALS['TL_DCA']['tl_module']['subpalettes']['iso_addSubscription']);
                }
            // no break!
            case 'iso_checkout':
                if ($objFieldPalette->iso_addActivation) {
                    $dca['subpalettes']['iso_addSubscription'] = str_replace('iso_addActivation', 'iso_addActivation,iso_activationNotification,iso_activationJumpTo', $GLOBALS['TL_DCA']['tl_module']['subpalettes']['iso_addSubscription']);
                }

                break;
        }
    }
}
