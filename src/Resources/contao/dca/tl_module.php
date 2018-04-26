<?php

$arrDca = &$GLOBALS['TL_DCA']['tl_module'];

/**
 * Palettes
 */
$arrDca['palettes']['iso_activation']          = '{title_legend},name,headline,type;{redirect_legend],jumpTo;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space;';
$arrDca['palettes']['iso_cancellation']        = '{title_legend},name,headline,type;{config_legend},iso_cancellationArchives,nc_notification;{redirect_legend],jumpTo;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space;';
$arrDca['palettes']['login_registration_plus'] = str_replace('newsletters;', 'newsletters,iso_checkForExitingSubscription;', $arrDca['palettes']['login_registration_plus']);

$arrDca['palettes']['registration_plus'] = str_replace('newsletters;', 'newsletters,iso_checkForExitingSubscription;', $arrDca['palettes']['registration_plus']);

/**
 * Subpalettes
 */
$arrDca['palettes']['__selector__'][]         = 'iso_addSubscription';
$arrDca['subpalettes']['iso_addSubscription'] = 'iso_subscriptionArchive,iso_addActivation';

/**
 * Callbacks
 */
$arrDca['config']['onload_callback'][] = ['tl_module_isotope_subscriptions', 'modifyPalette'];

/**
 * Fields
 */
$arrFields = [
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
    'iso_activationJumpTo'            => $arrDca['fields']['jumpTo'],
    'iso_checkForExitingSubscription' => [
        'label'     => &$GLOBALS['TL_LANG']['tl_module']['iso_checkForExitingSubscription'],
        'exclude'   => true,
        'filter'    => true,
        'inputType' => 'checkbox',
        'eval'      => ['tl_class' => 'w50'],
        'sql'       => "char(1) NOT NULL default ''",
    ],
];

$arrFields['iso_activationJumpTo']['label']            = &$GLOBALS['TL_LANG']['tl_module']['iso_activationJumpTo'];
$arrFields['iso_activationJumpTo']['eval']['tl_class'] = 'w50';

$arrFields['iso_activationNotification']                      = $arrDca['fields']['nc_notification'];
$arrFields['iso_activationNotification']['label']             = &$GLOBALS['TL_LANG']['tl_module']['iso_activationNotification'];
$arrFields['iso_activationNotification']['eval']['mandatory'] = true;

$arrFields['iso_addSubscriptionCheckbox'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_module']['iso_addSubscriptionCheckbox'],
    'exclude'   => true,
    'filter'    => true,
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'w50'],
    'sql'       => "char(1) NOT NULL default ''",
];

$arrDca['fields'] = array_merge($arrDca['fields'], $arrFields);

if (in_array('isotope_plus', \Contao\System::getContainer()->get('huh.utils.container')->getActiveBundles())) {
    $arrDca['fields']['iso_direct_checkout_products']['fieldpalette']['fields']                             = array_merge($arrDca['fields']['iso_direct_checkout_products']['fieldpalette']['fields'], $arrFields);
    $arrDca['fields']['iso_direct_checkout_products']['fieldpalette']['config']['onload_callback']          = ['modifyPalette' => ['tl_module_isotope_subscriptions', 'modifyFieldPalette']];
    $arrDca['fields']['iso_direct_checkout_products']['fieldpalette']['palettes']['__selector__'][]         = 'iso_addSubscription';
    $arrDca['fields']['iso_direct_checkout_products']['fieldpalette']['palettes']['default']                .= ',iso_addSubscription';
    $arrDca['fields']['iso_direct_checkout_products']['fieldpalette']['subpalettes']['iso_addSubscription'] = 'iso_subscriptionArchive,iso_addActivation';

    $arrDca['fields']['iso_direct_checkout_product_types']['fieldpalette']['fields']                             = array_merge($arrDca['fields']['iso_direct_checkout_product_types']['fieldpalette']['fields'], $arrFields);
    $arrDca['fields']['iso_direct_checkout_product_types']['fieldpalette']['config']['onload_callback']          = ['modifyPalette' => ['tl_module_isotope_subscriptions', 'modifyFieldPalette']];
    $arrDca['fields']['iso_direct_checkout_product_types']['fieldpalette']['palettes']['__selector__'][]         = 'iso_addSubscription';
    $arrDca['fields']['iso_direct_checkout_product_types']['fieldpalette']['palettes']['default']                .= ',iso_addSubscription';
    $arrDca['fields']['iso_direct_checkout_product_types']['fieldpalette']['subpalettes']['iso_addSubscription'] = 'iso_subscriptionArchive,iso_addActivation';
    echo '';
}


class tl_module_isotope_subscriptions
{
    public function modifyPalette()
    {
        $objModule = \ModuleModel::findByPk(\Input::get('id'));
        $arrDca    = &$GLOBALS['TL_DCA']['tl_module'];

        switch ($objModule->type) {
            case 'iso_direct_checkout':
                if (in_array('isotope_plus', \ModuleLoader::getActive())) {
                    $arrDca['subpalettes']['iso_addSubscription'] = str_replace('iso_subscriptionArchive', 'iso_subscriptionArchive,iso_addSubscriptionCheckbox', $arrDca['subpalettes']['iso_addSubscription']);
                }
            // no break!
            case 'iso_checkout':
                if ($objModule->iso_addActivation) {
                    $arrDca['subpalettes']['iso_addSubscription'] = str_replace('iso_addActivation', 'iso_addActivation,iso_activationNotification,iso_activationJumpTo', $arrDca['subpalettes']['iso_addSubscription']);
                }
                break;
        }
    }

    public function modifyFieldPalette()
    {
        if (($objFieldPalette = \HeimrichHannot\FieldPalette\FieldPaletteModel::findByPk(\Input::get('id'))) === null) {
            return;
        }

        $objModule = \ModuleModel::findByPk($objFieldPalette->pid);
        $arrDca    = &$GLOBALS['TL_DCA']['tl_fieldpalette'];

        \Controller::loadDataContainer('tl_module');

        switch ($objModule->type) {
            case 'iso_direct_checkout':
                if (in_array('isotope_plus', \ModuleLoader::getActive())) {
                    $arrDca['subpalettes']['iso_addSubscription'] = str_replace('iso_subscriptionArchive', 'iso_subscriptionArchive,iso_addSubscriptionCheckbox', $GLOBALS['TL_DCA']['tl_module']['subpalettes']['iso_addSubscription']);
                }
            // no break!
            case 'iso_checkout':
                if ($objFieldPalette->iso_addActivation) {
                    $arrDca['subpalettes']['iso_addSubscription'] = str_replace('iso_addActivation', 'iso_addActivation,iso_activationNotification,iso_activationJumpTo', $GLOBALS['TL_DCA']['tl_module']['subpalettes']['iso_addSubscription']);
                }
                break;
        }
    }

}

