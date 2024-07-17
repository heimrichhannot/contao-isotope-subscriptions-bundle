<?php

$arrDca = &$GLOBALS['TL_DCA']['tl_user_group'];

/**
 * Extend default palette
 */
$arrDca['palettes']['default'] = str_replace('iso_configs', 'subscriptions,subscriptionp,iso_configs', $arrDca['palettes']['default']);


/**
 * Add fields to tl_user_group
 */
$arrDca['fields']['subscriptions'] = [
    'label'      => &$GLOBALS['TL_LANG']['tl_user']['subscriptions'],
    'exclude'    => true,
    'inputType'  => 'checkbox',
    'foreignKey' => 'tl_iso_subscription_archive.title',
    'eval'       => ['multiple' => true, 'tl_class' => 'w50 w50h'],
    'sql'        => "blob NULL",
];

$arrDca['fields']['subscriptionp'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_user']['subscriptionp'],
    'exclude'   => true,
    'inputType' => 'checkbox',
    'options'   => ['create', 'delete'],
    'reference' => &$GLOBALS['TL_LANG']['MSC'],
    'eval'      => ['multiple' => true, 'tl_class' => 'w50 w50h'],
    'sql'       => "blob NULL",
];