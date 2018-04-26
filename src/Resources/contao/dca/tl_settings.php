<?php

$arrDca = &$GLOBALS['TL_DCA']['tl_settings'];

/**
 * Palette
 */
$arrDca['palettes']['default'] .= ';{isotope_subscriptions_legend},iso_addressFields,iso_changeMandatoryAddressFields;';

/**
 * Subpalettes
 */
$arrDca['palettes']['__selector__'][]                      = 'iso_changeMandatoryAddressFields';
$arrDca['subpalettes']['iso_changeMandatoryAddressFields'] = 'iso_mandatoryAddressFields';

/**
 * Fields
 */
$arrDca['fields']['iso_addressFields'] = [
    'label'            => &$GLOBALS['TL_LANG']['tl_settings']['iso_addressFields'],
    'exclude'          => true,
    'inputType'        => 'checkbox',
    'options_callback' => ['huh.isotope_subscriptions.manager.subscriptions', 'getIsotopeAddressFields'],
    'eval'             => ['multiple' => true, 'tl_class' => 'w50 clr'],
];

$arrDca['fields']['iso_changeMandatoryAddressFields'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_settings']['iso_changeMandatoryAddressFields'],
    'exclude'   => true,
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'w50', 'submitOnChange' => true],
];

$arrDca['fields']['iso_mandatoryAddressFields'] = [
    'label'            => &$GLOBALS['TL_LANG']['tl_settings']['iso_mandatoryAddressFields'],
    'exclude'          => true,
    'inputType'        => 'checkbox',
    'options_callback' => ['huh.isotope_subscriptions.manager.subscriptions', 'getIsotopeAddressFields'],
    'eval'             => ['multiple' => true, 'tl_class' => 'w50 clr'],
];