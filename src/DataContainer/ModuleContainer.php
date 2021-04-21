<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeSubscriptionsBundle\DataContainer;

use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\DataContainer;
use HeimrichHannot\UtilsBundle\Model\ModelUtil;

class ModuleContainer
{
    /**
     * @var ModelUtil
     */
    protected ModelUtil $modelUtil;

    public function __construct(ModelUtil $modelUtil)
    {
        $this->modelUtil = $modelUtil;
    }

    /**
     * @Callback(table="tl_module", target="config.onload")
     */
    public function modifyPalette(DataContainer $dc)
    {
        if (null === ($module = $this->modelUtil->findModelInstanceByPk('tl_module', $dc->id))) {
            return;
        }

        $dca = &$GLOBALS['TL_DCA']['tl_module'];

        if ('iso_direct_checkout' === $module->type && class_exists('HeimrichHannot\IsotopeBundle\HeimrichHannotIsotopeBundle')) {
            $dca['subpalettes']['iso_addSubscription'] = str_replace('iso_subscriptionArchive', 'iso_subscriptionArchive,iso_addSubscriptionCheckbox', $dca['subpalettes']['iso_addSubscription']);
        }

        if ('iso_checkout' === $module->type && $module->iso_addActivation) {
            $dca['subpalettes']['iso_addSubscription'] = str_replace('iso_addActivation', 'iso_addActivation,iso_activationNotification,iso_activationJumpTo', $dca['subpalettes']['iso_addSubscription']);
        }
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
