<?php

/**
 * Backend modules
 */
\Contao\System::getContainer()->get('huh.utils.array')->insertInArrayByName($GLOBALS['BE_MOD']['isotope'], 'iso_rules', [
    'iso_subscriptions' => [
        'tables'     => ['tl_iso_subscription_archive', 'tl_iso_subscription'],
        'icon'       => 'system/modules/isotope_subscriptions/assets/img/icon.png',
        'export_xls' => \HeimrichHannot\Exporter\ModuleExporter::getBackendModule(),
    ],
]);

/**
 * Frontend modules
 */
$GLOBALS['FE_MOD']['isotope_subscriptions'] = [
    'iso_activation'   => 'Isotope\Module\Activation',
    'iso_cancellation' => 'Isotope\Module\Cancellation',
];

/**
 * Hooks
 */
$GLOBALS['ISO_HOOKS']['preCheckout']['setCheckoutModuleIdSubscriptions']        = ['Isotope\IsotopeSubscriptions', 'setCheckoutModuleIdSubscriptions'];
$GLOBALS['ISO_HOOKS']['preCheckout']['checkForExistingSubscription']            = ['Isotope\IsotopeSubscriptions', 'checkForExistingSubscription'];
$GLOBALS['ISO_HOOKS']['postCheckout']['addSubscriptions']                       = ['Isotope\IsotopeSubscriptions', 'addSubscriptions'];
$GLOBALS['TL_HOOKS']['preLoginRegistration']['checkUsernameForIsoSubscription'] = ['Isotope\IsotopeSubscriptions', 'checkUsernameForIsoSubscription'];
$GLOBALS['TL_HOOKS']['preRegistration']['checkUsernameForIsoSubscription']      = ['Isotope\IsotopeSubscriptions', 'checkUsernameForIsoSubscription'];

/**
 * Notification center notification types
 */
$arrNotifications = &$GLOBALS['NOTIFICATION_CENTER']['NOTIFICATION_TYPE']['isotope'];

$arrNotifications['iso_subscription_activation']                 = $arrNotifications['iso_order_status_change'];
$arrNotifications['iso_subscription_activation']['email_text'][] = 'link';

/**
 * Models
 */
$GLOBALS['TL_MODELS'][\Isotope\Model\Subscription::getTable()]        = 'Isotope\Model\Subscription';
$GLOBALS['TL_MODELS'][\Isotope\Model\SubscriptionArchive::getTable()] = 'Isotope\Model\SubscriptionArchive';


/**
 * Add permissions
 */
$GLOBALS['TL_PERMISSIONS'][] = 'subscriptions';
$GLOBALS['TL_PERMISSIONS'][] = 'subscriptionp';
