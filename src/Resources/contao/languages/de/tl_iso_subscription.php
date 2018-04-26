<?php

$arrLang = &$GLOBALS['TL_LANG']['tl_iso_subscription'];

\System::loadLanguageFile('tl_member');

/**
 * Fields
 */
$arrLang['quantity'] = array('Bestellmenge', 'Geben Sie hier die Bestellmenge ein.');
$arrLang['order_id'] = array('Bestellungen', 'Wählen Sie hier die Bestellungen, die dem Abo zugeordnet sind. Hier erscheinen nur Bestellungen, die mindestens ein Produkt des Typs enthalten, der im Archiv festgelegt wurde.');
$arrLang['disable'] = array('Deaktivieren', 'Wählen Sie diese Option, um das Abo zu deaktivieren');

/**
 * Legends
 */
$arrLang['personal_legend'] = $GLOBALS['TL_LANG']['tl_member']['personal_legend'];
$arrLang['address_legend'] = $GLOBALS['TL_LANG']['tl_member']['address_legend'];
$arrLang['contact_legend'] = $GLOBALS['TL_LANG']['tl_member']['contact_legend'];
$arrLang['subscription_legend'] = 'Bestellungsdetails';

/**
 * Buttons
 */
$arrLang['new'] = array('Neues Abo', 'Ein neues Abo erstellen');
$arrLang['show'] = array('Abo Details', 'Die Details des Abo ID %s anzeigen');
$arrLang['edit'] = array('Abo bearbeiten', 'Abo ID %s bearbeiten');
$arrLang['editheader'] = array('Abo Einstellungen bearbeiten', 'Einstellungen des Abo ID %s bearbeiten');
$arrLang['copy'] = array('Abo duplizieren', 'Abo ID %s duplizieren');
$arrLang['toggle'] = array('Abo deaktivieren', 'Abo ID %s deaktivieren');
$arrLang['delete'] = array('Abo löschen', 'Abo ID %s löschen');
$arrLang['export'] = array('Exportieren', 'Alle Aboen exportieren');
$arrLang['import'] = array('Importieren', 'Alle Aboen importieren');

/**
 * Reference
 */
$arrLang['confirm'] = '%s neue Abonnenten wurden importiert.';
$arrLang['invalid'] = '%s ungültige Einträge wurden übersprungen.';
$arrLang['subscribed'] = 'registriert am %s';
$arrLang['manually'] = 'manuell hinzugefügt';

/**
 * Misc
 */
$arrLang['salutationMale'] = 'Herr';
$arrLang['salutationFemale'] = 'Frau';