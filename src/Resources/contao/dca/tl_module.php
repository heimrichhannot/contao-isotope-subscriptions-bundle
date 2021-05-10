<?php

use \HeimrichHannot\IsotopeSubscriptionsBundle\Controller\FrontendModule\IsoActivationModuleController;
use \HeimrichHannot\IsotopeSubscriptionsBundle\Controller\FrontendModule\IsoCancellationModuleController;

$dca = &$GLOBALS['TL_DCA']['tl_module'];

/**
 * Palettes
 */
$dca['palettes'][IsoActivationModuleController::TYPE] =
    '{title_legend},name,headline,type;{redirect_legend],jumpTo;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space;';

$dca['palettes'][IsoCancellationModuleController::TYPE] =
    '{title_legend},name,headline,type;{config_legend},iso_cancellationArchives,nc_notification;{redirect_legend],jumpTo;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space;';

/**
 * Fields
 */
// added in ModuleContainer::modifyDca onload_callback
