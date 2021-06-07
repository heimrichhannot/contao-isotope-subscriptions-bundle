<?php

$dca = &$GLOBALS['TL_DCA']['tl_module'];

/**
 * Palettes
 */
$dca['palettes']['__selector__'][] = 'iso_addActivation';

$dca['palettes'][\HeimrichHannot\IsotopeSubscriptionsBundle\Controller\FrontendModule\IsoActivationModuleController::TYPE] =
    '{title_legend},name,headline,type;{redirect_legend],jumpTo;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space;';

$dca['palettes'][\HeimrichHannot\IsotopeSubscriptionsBundle\Controller\FrontendModule\IsoCancellationModuleController::TYPE] =
    '{title_legend},name,headline,type;{config_legend},iso_cancellationArchive,iso_addActivation;{redirect_legend],jumpTo;' .
    '{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space;';

/**
 * Subpalettes
 */
$dca['subpalettes']['iso_addActivation'] = 'iso_activationNotification,iso_activationJumpTo,iso_activationLinkSentJumpTo';

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
    'iso_cancellationArchive'        => [
        'label'      => &$GLOBALS['TL_LANG']['tl_module']['iso_cancellationArchive'],
        'exclude'    => true,
        'inputType'  => 'select',
        'foreignKey' => 'tl_iso_subscription_archive.title',
        'eval'       => ['tl_class' => 'w50', 'mandatory' => true, 'chosen' => true, 'includeBlankOption' => true],
        'sql'        => "int(10) unsigned NOT NULL default '0'",
    ],
    'iso_addActivation'               => [
        'label'     => &$GLOBALS['TL_LANG']['tl_module']['iso_addActivation'],
        'exclude'   => true,
        'inputType' => 'checkbox',
        'eval'      => ['tl_class' => 'w50 clr', 'submitOnChange' => true],
        'sql'       => "char(1) NOT NULL default ''",
    ],
    'iso_activationJumpTo'            => [
        'label'      => &$GLOBALS['TL_LANG']['tl_module']['iso_activationJumpTo'],
        'exclude'    => true,
        'inputType'  => 'pageTree',
        'foreignKey' => 'tl_page.title',
        'eval'       => ['fieldType' => 'radio', 'tl_class' => 'w50'],
        'sql'        => 'int(10) unsigned NOT NULL default 0',
        'relation'   => ['type' => 'hasOne', 'load' => 'lazy'],
    ],
    'iso_activationLinkSentJumpTo'            => [
        'label'      => &$GLOBALS['TL_LANG']['tl_module']['iso_activationLinkSentJumpTo'],
        'exclude'    => true,
        'inputType'  => 'pageTree',
        'foreignKey' => 'tl_page.title',
        'eval'       => ['fieldType' => 'radio', 'tl_class' => 'w50'],
        'sql'        => 'int(10) unsigned NOT NULL default 0',
        'relation'   => ['type' => 'hasOne', 'load' => 'lazy'],
    ],
    'iso_checkForExitingSubscription' => [
        'label'     => &$GLOBALS['TL_LANG']['tl_module']['iso_checkForExitingSubscription'],
        'exclude'   => true,
        'filter'    => true,
        'inputType' => 'checkbox',
        'eval'      => ['tl_class' => 'w50'],
        'sql'       => "char(1) NOT NULL default ''",
    ],
    'iso_activationNotification'      => [
        'label'            => &$GLOBALS['TL_LANG']['tl_module']['iso_activationNotification'],
        'exclude'          => true,
        'inputType'        => 'select',
        'options_callback' => ['NotificationCenter\tl_module', 'getNotificationChoices'],
        'eval'             => ['includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w50', 'mandatory' => true],
        'sql'              => "int(10) unsigned NOT NULL default '0'",
        'relation'         => ['type' => 'hasOne', 'load' => 'lazy', 'table' => 'tl_nc_notification'],
    ],
    'iso_addSubscriptionCheckbox'     => [
        'label'     => &$GLOBALS['TL_LANG']['tl_module']['iso_addSubscriptionCheckbox'],
        'exclude'   => true,
        'filter'    => true,
        'inputType' => 'checkbox',
        'eval'      => ['tl_class' => 'w50'],
        'sql'       => "char(1) NOT NULL default ''",
    ],
];

$dca['fields'] = array_merge(is_array($dca['fields']) ? $dca['fields'] : [], $fields);
