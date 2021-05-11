<?php

$dca = &$GLOBALS['TL_DCA']['tl_settings'];

/**
 * Palette
 */
$dca['palettes']['__selector__'][] = 'iso_changeMandatoryAddressFields';

$dca['palettes']['default'] .= ';{isotope_subscriptions_legend},iso_addressFields,iso_changeMandatoryAddressFields;';

/**
 * Subpalettes
 */
$dca['subpalettes']['iso_changeMandatoryAddressFields'] = 'iso_mandatoryAddressFields';

/**
 * Fields
 */
$fields = [
    'iso_addressFields'                => [
        'exclude'          => true,
        'inputType'        => 'checkbox',
        'options_callback' => [\HeimrichHannot\IsotopeSubscriptionsBundle\Manager\SubscriptionManager::class, 'getIsotopeAddressFields'],
        'eval'             => ['multiple' => true, 'tl_class' => 'w50 clr'],
    ],
    'iso_changeMandatoryAddressFields' => [
        'exclude'   => true,
        'inputType' => 'checkbox',
        'eval'      => ['tl_class' => 'w50', 'submitOnChange' => true],
    ],
    'iso_mandatoryAddressFields'       => [
        'exclude'          => true,
        'inputType'        => 'checkbox',
        'options_callback' => [\HeimrichHannot\IsotopeSubscriptionsBundle\Manager\SubscriptionManager::class, 'getIsotopeAddressFields'],
        'eval'             => ['multiple' => true, 'tl_class' => 'w50 clr'],
    ]
];
