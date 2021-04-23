<?php

/**
 * Backend modules
 */
\Contao\System::getContainer()->get(\HeimrichHannot\UtilsBundle\Arrays\ArrayUtil::class)->insertInArrayByName(
    $GLOBALS['BE_MOD']['isotope'], 'iso_products', [
    'iso_subscriptions' => [
        'tables'     => ['tl_iso_subscription_archive', 'tl_iso_subscription'],
        'export_xls' => ['huh.exporter.action.backendexport', 'export'],
    ],
]);

/**
 * Hooks
 */
$GLOBALS['ISO_HOOKS']['preCheckout']['setCheckoutModuleIdSubscriptions']        =
    [\HeimrichHannot\IsotopeSubscriptionsBundle\Manager\SubscriptionManager::class, 'setCheckoutModuleIdSubscriptions'];
$GLOBALS['ISO_HOOKS']['preCheckout']['checkForExistingSubscription']            =
    [\HeimrichHannot\IsotopeSubscriptionsBundle\Manager\SubscriptionManager::class, 'checkForExistingSubscription'];
$GLOBALS['ISO_HOOKS']['postCheckout']['addSubscriptions']                       =
    [\HeimrichHannot\IsotopeSubscriptionsBundle\Manager\SubscriptionManager::class, 'addSubscriptions'];

/**
 * Notification center notification types
 */
$notifications = &$GLOBALS['NOTIFICATION_CENTER']['NOTIFICATION_TYPE']['isotope'];

$notifications['iso_subscription_activation']                 = $notifications['iso_order_status_change'];
$notifications['iso_subscription_activation']['email_text'][] = 'link';

/**
 * Models
 */
$GLOBALS['TL_MODELS']['tl_iso_subscription']         = 'HeimrichHannot\IsotopeSubscriptionsBundle\Model\SubscriptionModel';
$GLOBALS['TL_MODELS']['tl_iso_subscription_archive'] = 'HeimrichHannot\IsotopeSubscriptionsBundle\Model\SubscriptionArchiveModel';


/**
 * Add permissions
 */
$GLOBALS['TL_PERMISSIONS'][] = 'subscriptions';
$GLOBALS['TL_PERMISSIONS'][] = 'subscriptionp';
