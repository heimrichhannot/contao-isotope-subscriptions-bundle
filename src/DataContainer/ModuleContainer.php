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
    public function modifyDca(DataContainer $dc)
    {
        if (!class_exists('HeimrichHannot\IsotopeExtensionBundle\HeimrichHannotIsotopeExtensionBundle')) {
            return;
        }

        if (null === ($module = $this->modelUtil->findModelInstanceByPk('tl_module', $dc->id))) {
            return;
        }

        $dca = &$GLOBALS['TL_DCA']['tl_module'];

        /**
         * Fields.
         */
        $fields = [
            'iso_addSubscription' => [
                'label' => &$GLOBALS['TL_LANG']['tl_module']['iso_addSubscription'],
                'exclude' => true,
                'inputType' => 'checkbox',
                'eval' => ['tl_class' => 'w50 long', 'submitOnChange' => true],
                'sql' => "char(1) NOT NULL default ''",
            ],
            'iso_subscriptionArchive' => [
                'label' => &$GLOBALS['TL_LANG']['tl_module']['iso_subscriptionArchive'],
                'exclude' => true,
                'inputType' => 'select',
                'foreignKey' => 'tl_iso_subscription_archive.title',
                'eval' => ['tl_class' => 'w50', 'mandatory' => true],
                'sql' => "int(10) unsigned NOT NULL default '0'",
            ],
            'iso_cancellationArchives' => [
                'label' => &$GLOBALS['TL_LANG']['tl_module']['iso_cancellationArchives'],
                'exclude' => true,
                'inputType' => 'select',
                'foreignKey' => 'tl_iso_subscription_archive.title',
                'eval' => ['tl_class' => 'w50', 'mandatory' => true, 'multiple' => true, 'chosen' => true],
                'sql' => 'blob NULL',
            ],
            'iso_addActivation' => [
                'label' => &$GLOBALS['TL_LANG']['tl_module']['iso_addActivation'],
                'exclude' => true,
                'inputType' => 'checkbox',
                'eval' => ['tl_class' => 'w50 clr', 'submitOnChange' => true],
                'sql' => "char(1) NOT NULL default ''",
            ],
            'iso_activationJumpTo' => $dca['fields']['jumpTo'],
            'iso_checkForExitingSubscription' => [
                'label' => &$GLOBALS['TL_LANG']['tl_module']['iso_checkForExitingSubscription'],
                'exclude' => true,
                'filter' => true,
                'inputType' => 'checkbox',
                'eval' => ['tl_class' => 'w50'],
                'sql' => "char(1) NOT NULL default ''",
            ],
        ];

        $fields['iso_activationJumpTo']['label'] = &$GLOBALS['TL_LANG']['tl_module']['iso_activationJumpTo'];
        $fields['iso_activationJumpTo']['eval']['tl_class'] = 'w50';

        $fields['iso_activationNotification'] = $dca['fields']['nc_notification'];
        $fields['iso_activationNotification']['label'] = &$GLOBALS['TL_LANG']['tl_module']['iso_activationNotification'];
        $fields['iso_activationNotification']['eval']['mandatory'] = true;

        $fields['iso_addSubscriptionCheckbox'] = [
            'label' => &$GLOBALS['TL_LANG']['tl_module']['iso_addSubscriptionCheckbox'],
            'exclude' => true,
            'filter' => true,
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50'],
            'sql' => "char(1) NOT NULL default ''",
        ];

        if ('iso_direct_checkout' === $module->type) {
            foreach (['iso_direct_checkout_products', 'iso_direct_checkout_product_types'] as $field) {
                $dca['fields'][$field]['eval']['multiColumnEditor']['fields'] = array_merge($dca['fields'][$field]['eval']['multiColumnEditor']['fields'], $fields);

                $dca['fields'][$field]['eval']['multiColumnEditor']['palettes']['__selector__'][] = 'iso_addActivation';
                $dca['fields'][$field]['eval']['multiColumnEditor']['palettes']['__selector__'][] = 'iso_addSubscription';

                $dca['fields'][$field]['eval']['multiColumnEditor']['palettes']['default'] .= ',iso_addSubscription';

                $dca['fields'][$field]['eval']['multiColumnEditor']['subpalettes']['iso_addSubscription'] = 'iso_subscriptionArchive,iso_addSubscriptionCheckbox,iso_addActivation';
                $dca['fields'][$field]['eval']['multiColumnEditor']['subpalettes']['iso_addActivation'] = 'iso_activationNotification,iso_activationJumpTo';
            }
        }
    }
}
