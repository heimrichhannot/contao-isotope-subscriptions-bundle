<?php

$GLOBALS['TL_DCA']['tl_iso_subscription'] = [
    'config'   => [
        'dataContainer'     => 'Table',
        'ptable'            => 'tl_iso_subscription_archive',
        'enableVersioning'  => true,
        'onsubmit_callback' => [
            ['tl_iso_subscription', 'setDateAdded'],
        ],
        'sql'               => [
            'keys' => [
                'id' => 'primary',
            ],
        ],
    ],
    'list'     => [
        'sorting'           => [
            'mode'                  => 4,
            'fields'                => ['dateAdded DESC'],
            'headerFields'          => ['title'],
            'panelLayout'           => 'filter;search,limit',
            'child_record_callback' => ['tl_iso_subscription', 'listSubscriptions'],
        ],
        'global_operations' => [
            'export_xls' => \HeimrichHannot\Exporter\ModuleExporter::getGlobalOperation('export_xls', $GLOBALS['TL_LANG']['MSC']['export_xls'], 'system/modules/exporter/assets/img/icon_export.png'),
            'all'        => [
                'label'      => &$GLOBALS['TL_LANG']['MSC']['all'],
                'href'       => 'act=select',
                'class'      => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset();"',
            ],
        ],
        'operations'        => [
            'edit'   => [
                'label' => &$GLOBALS['TL_LANG']['tl_iso_subscription']['edit'],
                'href'  => 'act=edit',
                'icon'  => 'edit.gif',
            ],
            'copy'   => [
                'label' => &$GLOBALS['TL_LANG']['tl_iso_subscription']['copy'],
                'href'  => 'act=copy',
                'icon'  => 'copy.gif',
            ],
            'delete' => [
                'label'      => &$GLOBALS['TL_LANG']['tl_iso_subscription']['delete'],
                'href'       => 'act=delete',
                'icon'       => 'delete.gif',
                'attributes' => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"',
            ],
            'toggle' => [
                'label'           => &$GLOBALS['TL_LANG']['tl_iso_subscription']['toggle'],
                'icon'            => 'visible.gif',
                'attributes'      => 'onclick="Backend.getScrollOffset();return AjaxRequest.toggleVisibility(this,%s)"',
                'button_callback' => ['tl_iso_subscription', 'toggleIcon'],
            ],
            'show'   => [
                'label' => &$GLOBALS['TL_LANG']['tl_iso_subscription']['show'],
                'href'  => 'act=show',
                'icon'  => 'show.gif',
            ],
        ],
    ],
    'palettes' => [
        'default' => '{subscription_legend},quantity,order_id,disable;',
    ],
    'fields'   => [
        'id'         => [
            'sql' => "int(10) unsigned NOT NULL auto_increment",
        ],
        'pid'        => [
            'foreignKey' => 'tl_iso_subscription_archive.title',
            'sql'        => "int(10) unsigned NOT NULL default '0'",
            'relation'   => ['type' => 'belongsTo', 'load' => 'eager'],
        ],
        'tstamp'     => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'comment'    => [
            'label'     => &$GLOBALS['TL_LANG']['tl_iso_subscription']['comment'],
            'inputType' => 'textarea',
        ],
        'source'     => [
            'label' => &$GLOBALS['TL_LANG']['tl_iso_subscription']['source'],
            'eval'  => ['fieldType' => 'checkbox', 'files' => true, 'filesOnly' => true, 'extensions' => 'csv', 'class' => 'mandatory'],
        ],
        'dateAdded'  => [
            'label'   => &$GLOBALS['TL_LANG']['MSC']['dateAdded'],
            'sorting' => true,
            'flag'    => 6,
            'eval'    => ['rgxp' => 'datim'],
            'sql'     => "int(10) unsigned NOT NULL default '0'",
        ],
        'quantity'   => [
            'label'     => &$GLOBALS['TL_LANG']['tl_iso_subscription']['quantity'],
            'inputType' => 'text',
            'default'   => 1,
            'eval'      => ['rgxp' => 'digit', 'mandatory' => true, 'tl_class' => 'w50'],
            'sql'       => "int(10) unsigned NOT NULL default '1'",
        ],
        'order_id'   => [
            'label'            => &$GLOBALS['TL_LANG']['tl_iso_subscription']['order_id'],
            'inputType'        => 'select',
            'options_callback' => ['tl_iso_subscription', 'getOrders'],
            'eval'             => ['tl_class' => 'w50', 'chosen' => true, 'includeBlankOption' => true],
            'wizard'           => [
                ['tl_iso_subscription', 'editOrder'],
            ],
            'sql'              => "int(10) unsigned NOT NULL default '0'",
        ],
        'disable'    => [
            'label'     => &$GLOBALS['TL_LANG']['tl_iso_subscription']['disable'],
            'exclude'   => true,
            'filter'    => true,
            'inputType' => 'checkbox',
            'eval'      => ['tl_class' => 'w50'],
            'sql'       => "char(1) NOT NULL default ''",
        ],
        'activation' => [
            'sql' => "varchar(255) NOT NULL default ''",
        ],
    ],
];

// if not set, all fields are used
\Contao\System::getContainer()->get('huh.isotope_subscriptions.manager.subscriptions')->importIsotopeAddressFields();

class tl_iso_subscription extends \Backend
{
    public function listSubscriptions($arrRow)
    {
        $strText = trim($arrRow['firstname'] . ' ' . $arrRow['lastname']);

        if ($arrRow['gender'] && trim($arrRow['firstname']) == '') {
            $strText = $GLOBALS['TL_LANG']['tl_iso_subscription']['salutation' . ucfirst($arrRow['gender'])] . ' ' . $strText;
        }

        if ($arrRow['company']) {
            $strText = $arrRow['company'] . (trim($strText) ? ' (' . $strText . ')' : '');
        }

        return $strText;
    }

    public function setDateAdded(\DataContainer $objDc)
    {
        // Return if there is no active record (override all)
        if (!$objDc->activeRecord || $objDc->activeRecord->dateAdded > 0) {
            return;
        }

        // Fallback solution for existing accounts
        if ($objDc->activeRecord->lastLogin > 0) {
            $time = $objDc->activeRecord->lastLogin;
        } else {
            $time = time();
        }

        \Database::getInstance()->prepare("UPDATE tl_iso_subscription SET dateAdded=? WHERE id=?")->execute($time, $objDc->id);
    }

    /**
     * Check permissions to edit table tl_iso_subscription
     */
    public function checkPermission()
    {
        $objUser     = \BackendUser::getInstance();
        $objSession  = \Session::getInstance();
        $objDatabase = \Database::getInstance();

        if ($objUser->isAdmin) {
            return;
        }

        // Set the root IDs
        if (!is_array($objUser->subscriptions) || empty($objUser->subscriptions)) {
            $root = [0];
        } else {
            $root = $objUser->subscriptions;
        }

        $id = strlen(Input::get('id')) ? Input::get('id') : CURRENT_ID;

        // Check current action
        switch (Input::get('act')) {
            case 'paste':
                // Allow
                break;

            case 'create':
                if (!strlen(Input::get('pid')) || !in_array(Input::get('pid'), $root)) {
                    \Controller::log('Not enough permissions to create subscription items in subscription archive ID "' . Input::get('pid') . '"', 'tl_iso_subscription checkPermission', TL_ERROR);
                    \Controller::redirect('contao/main.php?act=error');
                }
                break;

            case 'cut':
            case 'copy':
                if (!in_array(Input::get('pid'), $root)) {
                    \Controller::log('Not enough permissions to ' . Input::get('act') . ' subscription item ID "' . $id . '" to subscription archive ID "' . Input::get('pid') . '"', 'tl_iso_subscription checkPermission', TL_ERROR);
                    \Controller::redirect('contao/main.php?act=error');
                }
            // NO BREAK STATEMENT HERE

            case 'edit':
            case 'show':
            case 'delete':
            case 'toggle':
            case 'feature':
                $objArchive = $objDatabase->prepare("SELECT pid FROM tl_iso_subscription WHERE id=?")->limit(1)->execute($id);

                if ($objArchive->numRows < 1) {
                    \Controller::log('Invalid subscription item ID "' . $id . '"', 'tl_iso_subscription checkPermission', TL_ERROR);
                    \Controller::redirect('contao/main.php?act=error');
                }

                if (!in_array($objArchive->pid, $root)) {
                    \Controller::log('Not enough permissions to ' . Input::get('act') . ' subscription item ID "' . $id . '" of subscription archive ID "' . $objArchive->pid . '"', 'tl_iso_subscription checkPermission', TL_ERROR);
                    \Controller::redirect('contao/main.php?act=error');
                }
                break;

            case 'select':
            case 'editAll':
            case 'deleteAll':
            case 'overrideAll':
            case 'cutAll':
            case 'copyAll':
                if (!in_array($id, $root)) {
                    \Controller::log('Not enough permissions to access subscription archive ID "' . $id . '"', 'tl_iso_subscription checkPermission', TL_ERROR);
                    \Controller::redirect('contao/main.php?act=error');
                }

                $objArchive = $objDatabase->prepare("SELECT id FROM tl_iso_subscription WHERE pid=?")->execute($id);

                if ($objArchive->numRows < 1) {
                    \Controller::log('Invalid subscription archive ID "' . $id . '"', 'tl_iso_subscription checkPermission', TL_ERROR);
                    \Controller::redirect('contao/main.php?act=error');
                }

                $session                   = $objSession->getData();
                $session['CURRENT']['IDS'] = array_intersect($session['CURRENT']['IDS'], $objArchive->fetchEach('id'));
                $objSession->setData($session);
                break;

            default:
                if (strlen(Input::get('act'))) {
                    \Controller::log('Invalid command "' . Input::get('act') . '"', 'tl_iso_subscription checkPermission', TL_ERROR);
                    \Controller::redirect('contao/main.php?act=error');
                } elseif (!in_array($id, $root)) {
                    \Controller::log('Not enough permissions to access subscription archive ID ' . $id, 'tl_iso_subscription checkPermission', TL_ERROR);
                    \Controller::redirect('contao/main.php?act=error');
                }
                break;
        }
    }

    /**
     * Return the "toggle visibility" button
     *
     * @param array
     * @param string
     * @param string
     * @param string
     * @param string
     * @param string
     *
     * @return string
     */
    public function toggleIcon($row, $href, $label, $title, $icon, $attributes)
    {
        $objUser = \BackendUser::getInstance();

        if (strlen(Input::get('tid'))) {
            $this->toggleVisibility(Input::get('tid'), (Input::get('state') == 1));
            \Controller::redirect($this->getReferer());
        }

        // Check permissions AFTER checking the tid, so hacking attempts are logged
        if (!$objUser->isAdmin && !$objUser->hasAccess('tl_iso_subscription::disable', 'alexf')) {
            return '';
        }

        $href .= '&amp;tid=' . $row['id'] . '&amp;state=' . ($row['disable'] ? 1 : '');

        if ($row['disable']) {
            $icon = 'invisible.gif';
        }

        return '<a href="' . $this->addToUrl($href) . '" title="' . specialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label) . '</a> ';
    }


    /**
     * Disable/enable a user group
     *
     * @param integer
     * @param boolean
     */
    public function toggleVisibility($intId, $blnVisible)
    {
        $objUser     = \BackendUser::getInstance();
        $objDatabase = \Database::getInstance();

        // Check permissions to edit
        Input::setGet('id', $intId);
        Input::setGet('act', 'toggle');
        $this->checkPermission();

        // Check permissions to disable
        if (!$objUser->isAdmin && !$objUser->hasAccess('tl_iso_subscription::disable', 'alexf')) {
            \Controller::log('Not enough permissions to disable/enable subscription item ID "' . $intId . '"', 'tl_iso_subscription toggleVisibility', TL_ERROR);
            \Controller::redirect('contao/main.php?act=error');
        }

        $objVersions = new Versions('tl_iso_subscription', $intId);
        $objVersions->initialize();

        // Trigger the save_callback
        if (is_array($GLOBALS['TL_DCA']['tl_iso_subscription']['fields']['disable']['save_callback'])) {
            foreach ($GLOBALS['TL_DCA']['tl_iso_subscription']['fields']['disable']['save_callback'] as $callback) {
                $this->import($callback[0]);
                $blnVisible = $this->$callback[0]->$callback[1]($blnVisible, $this);
            }
        }

        // Update the database
        $objDatabase->prepare("UPDATE tl_iso_subscription SET tstamp=" . time() . ", disable='" . ($blnVisible ? '' : 1) . "' WHERE id=?")->execute($intId);

        $objVersions->create();
        \Controller::log('A new version of record "tl_iso_subscription.id=' . $intId . '" has been created' . $this->getParentEntries('tl_iso_subscription', $intId), 'tl_iso_subscription toggleVisibility()', TL_GENERAL);
    }

    public static function getOrders(\DataContainer $objDc)
    {
        $arrOptions = [];

        if (($objSubscription = \Isotope\Model\Subscription::findByPk($objDc->activeRecord->id)) !== null) {
            if (($objOrders = \Isotope\Model\ProductCollection\Order::findByType('order')) !== null) {
                while ($objOrders->next()) {
                    foreach ($objOrders->current()->getItems() as $objItem) {
                        // the order needs to contain at least one item of the product type defined in the archive
                        if (($objProduct = $objItem->getProduct()) !== null && $objProduct->type == $objSubscription->getRelated('pid')->productType) {
                            $arrOptions[$objOrders->id] = $GLOBALS['TL_LANG']['MSC']['order'] . ' ' . $objOrders->document_number;
                            break;
                        }
                    }
                }
            }

            // inverse asort
            arsort($arrOptions);
        }

        return $arrOptions;
    }

    public function editOrder(\DataContainer $objDc)
    {
        return ($objDc->value < 1) ? ''
            : ' <a href="contao/main.php?do=iso_orders&act=edit&id=' . $objDc->value . '&rt=' . \RequestToken::get() . '" title="' . sprintf(specialchars($GLOBALS['TL_LANG']['tl_content']['editalias'][1]), $objDc->value) . '" style="padding-left:3px">' . \Image::getHtml('alias.gif', $GLOBALS['TL_LANG']['tl_content']['editalias'][0], 'style="vertical-align:top"') . '</a>';
    }

}
