<?php

$dca = &$GLOBALS['TL_DCA']['tl_module'];

/**
 * Palettes
 */
$dca['palettes']['__selector__'][] = 'iso_addSubscription';

$dca['palettes'][\HeimrichHannot\IsotopeSubscriptionsBundle\Controller\FrontendModule\IsoActivationFrontendModuleController::TYPE] =
    '{title_legend},name,headline,type;{redirect_legend],jumpTo;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space;';

$dca['palettes'][\HeimrichHannot\IsotopeSubscriptionsBundle\Controller\FrontendModule\IsoCancellationFrontendModuleController::TYPE] =
    '{title_legend},name,headline,type;{config_legend},iso_cancellationArchives,nc_notification;{redirect_legend],jumpTo;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space;';

// TODO: Registrierung -> plus wirklich notwendig??
$dca['palettes']['login_registration_plus'] = str_replace('newsletters;', 'newsletters,iso_checkForExitingSubscription;', $dca['palettes']['login_registration_plus']);
$dca['palettes']['registration_plus']       = str_replace('newsletters;', 'newsletters,iso_checkForExitingSubscription;', $dca['palettes']['registration_plus']);

/**
 * Subpalettes
 */
$dca['subpalettes']['iso_addSubscription'] = 'iso_subscriptionArchive,iso_addActivation';

/**
 * Fields
 */
$fields = [
    'iso_addSubscription'             => [
        'label'     => &$GLOBALS['TL_LANG']['tl_module']['iso_addSubscription'],
        'exclude'   => true,
        'inputType' => 'checkbox',
        'eval'      => ['tl_class' => 'w50 long', 'submitOnChange' => true],
        'sql'       => "char(1) NOT NULL default ''",
    ],
    'iso_subscriptionArchive'         => [
        'label'      => &$GLOBALS['TL_LANG']['tl_module']['iso_subscriptionArchive'],
        'exclude'    => true,
        'inputType'  => 'select',
        'foreignKey' => 'tl_iso_subscription_archive.title',
        'eval'       => ['tl_class' => 'w50', 'mandatory' => true],
        'sql'        => "int(10) unsigned NOT NULL default '0'",
    ],
    'iso_cancellationArchives'        => [
        'label'      => &$GLOBALS['TL_LANG']['tl_module']['iso_cancellationArchives'],
        'exclude'    => true,
        'inputType'  => 'select',
        'foreignKey' => 'tl_iso_subscription_archive.title',
        'eval'       => ['tl_class' => 'w50', 'mandatory' => true, 'multiple' => true, 'chosen' => true],
        'sql'        => "blob NULL",
    ],
    'iso_addActivation'               => [
        'label'     => &$GLOBALS['TL_LANG']['tl_module']['iso_addActivation'],
        'exclude'   => true,
        'inputType' => 'checkbox',
        'eval'      => ['tl_class' => 'w50 clr', 'submitOnChange' => true],
        'sql'       => "char(1) NOT NULL default ''",
    ],
    'iso_activationJumpTo'            => $dca['fields']['jumpTo'],
    'iso_checkForExitingSubscription' => [
        'label'     => &$GLOBALS['TL_LANG']['tl_module']['iso_checkForExitingSubscription'],
        'exclude'   => true,
        'filter'    => true,
        'inputType' => 'checkbox',
        'eval'      => ['tl_class' => 'w50'],
        'sql'       => "char(1) NOT NULL default ''",
    ],
];

$fields['iso_activationJumpTo']['label']            = &$GLOBALS['TL_LANG']['tl_module']['iso_activationJumpTo'];
$fields['iso_activationJumpTo']['eval']['tl_class'] = 'w50';

$fields['iso_activationNotification']                      = $dca['fields']['nc_notification'];
$fields['iso_activationNotification']['label']             = &$GLOBALS['TL_LANG']['tl_module']['iso_activationNotification'];
$fields['iso_activationNotification']['eval']['mandatory'] = true;

$fields['iso_addSubscriptionCheckbox'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_module']['iso_addSubscriptionCheckbox'],
    'exclude'   => true,
    'filter'    => true,
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'w50'],
    'sql'       => "char(1) NOT NULL default ''",
];

$dca['fields'] = array_merge(is_array($dca['fields']) ? $dca['fields'] : [], $fields);

if (class_exists('HeimrichHannot\IsotopeBundle\HeimrichHannotIsotopeBundle')) {
    // TODO to mce
//    $dca['fields']['iso_direct_checkout_products']['fieldpalette']['fields']                             = array_merge($dca['fields']['iso_direct_checkout_products']['fieldpalette']['fields'], $fields);
//    $dca['fields']['iso_direct_checkout_products']['fieldpalette']['config']['onload_callback']          = ['modifyPalette' => ['tl_module_isotope_subscriptions', 'modifyFieldPalette']];
//    $dca['fields']['iso_direct_checkout_products']['fieldpalette']['palettes']['__selector__'][]         = 'iso_addSubscription';
//    $dca['fields']['iso_direct_checkout_products']['fieldpalette']['palettes']['default']                .= ',iso_addSubscription';
//    $dca['fields']['iso_direct_checkout_products']['fieldpalette']['subpalettes']['iso_addSubscription'] = 'iso_subscriptionArchive,iso_addActivation';
//
//    $dca['fields']['iso_direct_checkout_product_types']['fieldpalette']['fields']                             = array_merge($dca['fields']['iso_direct_checkout_product_types']['fieldpalette']['fields'], $fields);
//    $dca['fields']['iso_direct_checkout_product_types']['fieldpalette']['config']['onload_callback']          = ['modifyPalette' => ['tl_module_isotope_subscriptions', 'modifyFieldPalette']];
//    $dca['fields']['iso_direct_checkout_product_types']['fieldpalette']['palettes']['__selector__'][]         = 'iso_addSubscription';
//    $dca['fields']['iso_direct_checkout_product_types']['fieldpalette']['palettes']['default']                .= ',iso_addSubscription';
//    $dca['fields']['iso_direct_checkout_product_types']['fieldpalette']['subpalettes']['iso_addSubscription'] = 'iso_subscriptionArchive,iso_addActivation';
}
