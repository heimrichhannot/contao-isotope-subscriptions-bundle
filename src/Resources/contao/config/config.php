<?php

/**
 * Backend modules
 */
\Contao\System::getContainer()->get('huh.utils.array')->insertInArrayByName($GLOBALS['BE_MOD']['isotope'], 'iso_rules', [
    'iso_subscriptions' => [
        'tables'     => ['tl_iso_subscription_archive', 'tl_iso_subscription'],
        'icon'       => 'system/modules/isotope_subscriptions/assets/img/icon.png',
        'export_xls' => ['huh.exporter.action.backendexport', 'export'],
    ],
]);

/**
 * Frontend modules
 */
$GLOBALS['FE_MOD']['isotope_subscriptions'] = [
    'iso_activation'   => 'HeimrichHannot\IsotopeSubscriptionsBundle\Module\Activation',
    'iso_cancellation' => 'HeimrichHannot\IsotopeSubscriptionsBundle\Module\Cancellation',
];

/**
 * Hooks
 */
$GLOBALS['ISO_HOOKS']['preCheckout']['setCheckoutModuleIdSubscriptions']        = ['huh.isotope_subscriptions.manager.subscriptions', 'setCheckoutModuleIdSubscriptions'];
$GLOBALS['ISO_HOOKS']['preCheckout']['checkForExistingSubscription']            = ['huh.isotope_subscriptions.manager.subscriptions', 'checkForExistingSubscription'];
$GLOBALS['ISO_HOOKS']['postCheckout']['addSubscriptions']                       = ['huh.isotope_subscriptions.manager.subscriptions', 'addSubscriptions'];
$GLOBALS['TL_HOOKS']['preLoginRegistration']['checkUsernameForIsoSubscription'] = ['huh.isotope_subscriptions.manager.subscriptions', 'checkUsernameForIsoSubscription'];
$GLOBALS['TL_HOOKS']['preRegistration']['checkUsernameForIsoSubscription']      = ['huh.isotope_subscriptions.manager.subscriptions', 'checkUsernameForIsoSubscription'];

/**
 * Notification center notification types
 */
$arrNotifications = &$GLOBALS['NOTIFICATION_CENTER']['NOTIFICATION_TYPE']['isotope'];

$arrNotifications['iso_subscription_activation']                 = $arrNotifications['iso_order_status_change'];
$arrNotifications['iso_subscription_activation']['email_text'][] = 'link';

/**
 * Models
 */
$GLOBALS['TL_MODELS'][HeimrichHannot\IsotopeSubscriptionsBundle\Model\Subscription::getTable()]        = 'HeimrichHannot\IsotopeSubscriptionsBundle\Model\Subscription';
$GLOBALS['TL_MODELS'][HeimrichHannot\IsotopeSubscriptionsBundle\Model\SubscriptionArchive::getTable()] = 'HeimrichHannot\IsotopeSubscriptionsBundle\Model\SubscriptionArchive';


/**
 * Add permissions
 */
$GLOBALS['TL_PERMISSIONS'][] = 'subscriptions';
$GLOBALS['TL_PERMISSIONS'][] = 'subscriptionp';
