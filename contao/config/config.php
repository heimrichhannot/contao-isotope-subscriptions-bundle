<?php

/**
 * Backend modules
 */

use HeimrichHannot\IsotopeSubscriptionsBundle\Manager\SubscriptionManager;

\Contao\System::getContainer()->get(\HeimrichHannot\UtilsBundle\Arrays\ArrayUtil::class)->insertInArrayByName(
    $GLOBALS['BE_MOD']['isotope'], 'iso_products', [
    'iso_subscriptions' => [
        'tables'     => ['tl_iso_subscription_archive', 'tl_iso_subscription'],
        'export_xls' => ['huh.exporter.action.backendexport', 'export'],
    ],
]);

/**
 * Isotope hooks
 */
$GLOBALS['ISO_HOOKS']['preCheckout']['setCheckoutModuleIdSubscriptions'] = [SubscriptionManager::class, 'setCheckoutModuleIdSubscriptions'];
$GLOBALS['ISO_HOOKS']['preCheckout']['checkForExistingSubscription'] = [SubscriptionManager::class, 'checkForExistingSubscription'];
$GLOBALS['ISO_HOOKS']['postCheckout']['addSubscriptions'] = [SubscriptionManager::class, 'addSubscriptions'];

/**
 * Notification center notification types
 */
$notifications = &$GLOBALS['NOTIFICATION_CENTER']['NOTIFICATION_TYPE']['isotope'];

$notifications['iso_subscription_activation']                 = $notifications['iso_order_status_change'];
$notifications['iso_subscription_activation']['email_text'][] = 'link';
$notifications['iso_subscription_activation']['email_html'][] = 'link';

$cancellationType = System::getContainer()->get(\HeimrichHannot\UtilsBundle\Dca\DcaUtil::class)
    ->getNewNotificationTypeArray(true);

$cancellationType['email_text'][] = 'link';
$cancellationType['email_html'][] = 'link';

foreach ($cancellationType as $strField => $arrTokens) {
    $cancellationType[$strField] = array_unique(array_merge(['form_*'], $arrTokens));
}

System::getContainer()->get(\HeimrichHannot\UtilsBundle\Dca\DcaUtil::class)->activateNotificationType(
    'isotope',
    'iso_subscription_cancellation',
    $cancellationType
);

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
