<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeSubscriptionsBundle\EventListener\Contao;

use Contao\CoreBundle\ServiceAnnotation\Hook;
use HeimrichHannot\IsotopeSubscriptionsBundle\Manager\SubscriptionManager;

/**
 * @Hook("loadDataContainer", priority=1)
 */
class LoadDataContainerListener
{
    protected SubscriptionManager $subscriptionManager;

    public function __construct(SubscriptionManager $subscriptionManager)
    {
        $this->subscriptionManager = $subscriptionManager;
    }

    public function __invoke(string $table): void
    {
        switch ($table) {
            case 'tl_iso_subscription':
                // if not set, all fields are used
                $this->subscriptionManager->importIsotopeAddressFields();

                break;

            case 'tl_module':
                // caution: priority of the listener needs to set to 1 so that it runs before any legacy loadDataContainer hook (multi column editor -> loadDataContainerHook!)
                if (!class_exists('HeimrichHannot\IsotopeExtensionBundle\HeimrichHannotIsotopeExtensionBundle')) {
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
                    'iso_activationJumpTo' => [
                        'label' => &$GLOBALS['TL_LANG']['tl_module']['iso_activationJumpTo'],
                        'exclude' => true,
                        'inputType' => 'pageTree',
                        'foreignKey' => 'tl_page.title',
                        'eval' => ['fieldType' => 'radio', 'tl_class' => 'w50'],
                        'sql' => 'int(10) unsigned NOT NULL default 0',
                        'relation' => ['type' => 'hasOne', 'load' => 'lazy'],
                    ],
                    'iso_checkForExitingSubscription' => [
                        'label' => &$GLOBALS['TL_LANG']['tl_module']['iso_checkForExitingSubscription'],
                        'exclude' => true,
                        'filter' => true,
                        'inputType' => 'checkbox',
                        'eval' => ['tl_class' => 'w50'],
                        'sql' => "char(1) NOT NULL default ''",
                    ],
                    'iso_activationNotification' => [
                        'label' => &$GLOBALS['TL_LANG']['tl_module']['iso_activationNotification'],
                        'exclude' => true,
                        'inputType' => 'select',
                        'options_callback' => ['NotificationCenter\tl_module', 'getNotificationChoices'],
                        'eval' => ['includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w50', 'mandatory' => true],
                        'sql' => "int(10) unsigned NOT NULL default '0'",
                        'relation' => ['type' => 'hasOne', 'load' => 'lazy', 'table' => 'tl_nc_notification'],
                    ],
                    'iso_addSubscriptionCheckbox' => [
                        'label' => &$GLOBALS['TL_LANG']['tl_module']['iso_addSubscriptionCheckbox'],
                        'exclude' => true,
                        'filter' => true,
                        'inputType' => 'checkbox',
                        'eval' => ['tl_class' => 'w50'],
                        'sql' => "char(1) NOT NULL default ''",
                    ],
                ];

                foreach (['iso_direct_checkout_products', 'iso_direct_checkout_product_types'] as $field) {
                    $dca['fields'][$field]['eval']['multiColumnEditor']['fields'] = array_merge($dca['fields'][$field]['eval']['multiColumnEditor']['fields'], $fields);

                    $dca['fields'][$field]['eval']['multiColumnEditor']['palettes']['__selector__'][] = 'iso_addActivation';
                    $dca['fields'][$field]['eval']['multiColumnEditor']['palettes']['__selector__'][] = 'iso_addSubscription';

                    $dca['fields'][$field]['eval']['multiColumnEditor']['palettes']['default'] .= ',iso_addSubscription';

                    $dca['fields'][$field]['eval']['multiColumnEditor']['subpalettes']['iso_addSubscription'] = 'iso_subscriptionArchive,iso_addSubscriptionCheckbox,iso_addActivation';
                    $dca['fields'][$field]['eval']['multiColumnEditor']['subpalettes']['iso_addActivation'] = 'iso_activationNotification,iso_activationJumpTo';
                }

                break;
        }
    }
}
