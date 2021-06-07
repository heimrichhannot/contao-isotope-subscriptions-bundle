<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeSubscriptionsBundle\EventListener\Contao;

use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\System;
use HeimrichHannot\IsotopeSubscriptionsBundle\Controller\FrontendModule\IsoActivationModuleController;
use HeimrichHannot\IsotopeSubscriptionsBundle\Controller\FrontendModule\IsoCancellationModuleController;
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
                    'iso_addSubscription' => $dca['fields']['iso_addSubscription'],
                    'iso_subscriptionArchive' => $dca['fields']['iso_subscriptionArchive'],
                    'iso_cancellationArchive' => $dca['fields']['iso_cancellationArchive'],
                    'iso_addActivation' => $dca['fields']['iso_addActivation'],
                    'iso_activationJumpTo' => $dca['fields']['iso_activationJumpTo'],
                    'iso_checkForExitingSubscription' => $dca['fields']['iso_checkForExitingSubscription'],
                    'iso_activationNotification' => $dca['fields']['iso_activationNotification'],
                    'iso_addSubscriptionCheckbox' => $dca['fields']['iso_addSubscriptionCheckbox'],
                ];

                foreach (['iso_direct_checkout_products', 'iso_direct_checkout_product_types'] as $field) {
                    $dca['fields'][$field]['eval']['multiColumnEditor']['fields'] = array_merge($dca['fields'][$field]['eval']['multiColumnEditor']['fields'], $fields);

                    $dca['fields'][$field]['eval']['multiColumnEditor']['palettes']['__selector__'][] = 'iso_addActivation';
                    $dca['fields'][$field]['eval']['multiColumnEditor']['palettes']['__selector__'][] = 'iso_addSubscription';

                    $dca['fields'][$field]['eval']['multiColumnEditor']['palettes']['default'] .= ',iso_addSubscription';

                    $dca['fields'][$field]['eval']['multiColumnEditor']['subpalettes']['iso_addSubscription'] = 'iso_subscriptionArchive,iso_addSubscriptionCheckbox,iso_addActivation';
                    $dca['fields'][$field]['eval']['multiColumnEditor']['subpalettes']['iso_addActivation'] = 'iso_activationNotification,iso_activationJumpTo';
                }

                // privacy
                if (class_exists('\HeimrichHannot\PrivacyBundle\HeimrichHannotPrivacyBundle')) {
                    $protocolManager = System::getContainer()->get(\HeimrichHannot\PrivacyBundle\Manager\ProtocolManager::class);

                    $dca['fields']['iso_privacyEntryConfig'] = $protocolManager->getConfigFieldDca();
                    $dca['fields']['iso_secondPrivacyEntryConfig'] = $protocolManager->getConfigFieldDca();

                    // cancel
                    $dca['palettes'][IsoCancellationModuleController::TYPE] = str_replace('iso_cancellationArchive',
                        'iso_cancellationArchive,iso_privacyEntryConfig', $dca['palettes'][IsoCancellationModuleController::TYPE]);

                    $dca['subpalettes']['iso_addActivation'] = str_replace('iso_activationLinkSentJumpTo',
                        'iso_activationLinkSentJumpTo,iso_secondPrivacyEntryConfig', $dca['subpalettes']['iso_addActivation']);

                    // activate
                    $dca['palettes'][IsoActivationModuleController::TYPE] = str_replace('jumpTo',
                        'jumpTo,iso_privacyEntryConfig', $dca['palettes'][IsoActivationModuleController::TYPE]);

                    $fields = [
                        'iso_privacyEntryConfig' => $dca['fields']['iso_privacyEntryConfig'],
                    ];

                    foreach (['iso_direct_checkout_products', 'iso_direct_checkout_product_types'] as $field) {
                        $dca['fields'][$field]['eval']['multiColumnEditor']['fields'] = array_merge($dca['fields'][$field]['eval']['multiColumnEditor']['fields'], $fields);

                        $dca['fields'][$field]['eval']['multiColumnEditor']['subpalettes']['iso_addSubscription'] = str_replace(
                            'iso_addActivation',
                            'iso_privacyEntryConfig,iso_addActivation',
                            $dca['fields'][$field]['eval']['multiColumnEditor']['subpalettes']['iso_addSubscription']
                        );
                    }
                }

                break;
        }
    }
}
