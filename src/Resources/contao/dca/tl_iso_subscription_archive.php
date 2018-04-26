<?php

$GLOBALS['TL_DCA']['tl_iso_subscription_archive'] = [

    // Config
    'config'   => [
        'dataContainer'    => 'Table',
        'ctable'           => ['tl_iso_subscription'],
        'switchToEdit'     => true,
        'enableVersioning' => false,
        'onload_callback'  => [
            ['tl_iso_subscription_archive', 'checkPermission'],
        ],
        'sql'              => [
            'keys' => [
                'id' => 'primary',
            ],
        ],
    ],

    // List
    'list'     => [
        'sorting'           => [
            'mode'        => 1,
            'fields'      => ['title'],
            'flag'        => 1,
            'panelLayout' => 'search,limit',
        ],
        'label'             => [
            'fields' => ['title'],
            'format' => '%s',
        ],
        'global_operations' => [
            'all' => [
                'label'      => &$GLOBALS['TL_LANG']['MSC']['all'],
                'href'       => 'act=select',
                'class'      => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset();"',
            ],
        ],
        'operations'        => [
            'edit'       => [
                'label' => &$GLOBALS['TL_LANG']['tl_iso_subscription_archive']['edit'],
                'href'  => 'table=tl_iso_subscription',
                'icon'  => 'edit.gif',
            ],
            'editheader' => [
                'label'           => &$GLOBALS['TL_LANG']['tl_iso_subscription_archive']['editheader'],
                'href'            => 'act=edit',
                'icon'            => 'header.gif',
                'button_callback' => ['tl_iso_subscription_archive', 'editHeader'],
                'attributes'      => 'class="edit-header"',
            ],
            'copy'       => [
                'label' => &$GLOBALS['TL_LANG']['tl_iso_subscription_archive']['copy'],
                'href'  => 'act=copy',
                'icon'  => 'copy.gif',
            ],
            'delete'     => [
                'label'      => &$GLOBALS['TL_LANG']['tl_iso_subscription_archive']['delete'],
                'href'       => 'act=delete',
                'icon'       => 'delete.gif',
                'attributes' => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"',
            ],
            'show'       => [
                'label' => &$GLOBALS['TL_LANG']['tl_iso_subscription_archive']['show'],
                'href'  => 'act=show',
                'icon'  => 'show.gif',
            ],
            'export'     => [
                'label' => &$GLOBALS['TL_LANG']['tl_iso_subscription_archive']['export'],
                'href'  => 'table=tl_iso_subscription&key=export',
                'icon'  => 'export.gif',
            ],
        ],
    ],

    // Palettes
    'palettes' => [
        'default' => 'label;title,productType',
    ],

    // Fields
    'fields'   => [
        'id'          => [
            'sql' => "int(10) unsigned NOT NULL auto_increment",
        ],
        'tstamp'      => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'title'       => [
            'label'     => &$GLOBALS['TL_LANG']['tl_iso_subscription_archive']['title'],
            'exclude'   => true,
            'search'    => true,
            'inputType' => 'text',
            'eval'      => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'productType' => [
            'label'     => &$GLOBALS['TL_LANG']['tl_iso_subscription_archive']['productType'],
            'inputType' => 'select',
            'options'   => \Isotope\Backend\ProductType\Callback::getOptions(),
            'eval'      => ['tl_class' => 'w50', 'mandatory' => true],
            'sql'       => "int(10) unsigned NOT NULL default '0'",
        ],
    ],
];

class tl_iso_subscription_archive extends \Backend
{

    public function checkPermission()
    {
        $objUser     = \BackendUser::getInstance();
        $objSession  = \Session::getInstance();
        $objDatabase = \Database::getInstance();

        if ($objUser->isAdmin) {
            return;
        }

        // Set root IDs
        if (!is_array($objUser->subscriptions) || empty($objUser->subscriptions)) {
            $root = [0];
        } else {
            $root = $objUser->subscriptions;
        }

        $GLOBALS['TL_DCA']['tl_iso_subscription_archive']['list']['sorting']['root'] = $root;

        // Check permissions to add archives
        if (!$objUser->hasAccess('create', 'subscriptionp')) {
            $GLOBALS['TL_DCA']['tl_iso_subscription_archive']['config']['closed'] = true;
        }

        // Check current action
        switch (Input::get('act')) {
            case 'create':
            case 'select':
                // Allow
                break;

            case 'edit':
                // Dynamically add the record to the user profile
                if (!in_array(Input::get('id'), $root)) {
                    $arrNew = $objSession->get('new_records');

                    if (is_array($arrNew['tl_iso_subscription_archive']) && in_array(Input::get('id'), $arrNew['tl_iso_subscription_archive'])) {
                        // Add permissions on user level
                        if ($objUser->inherit == 'custom' || !$objUser->groups[0]) {
                            $objUser = $objDatabase->prepare("SELECT subscriptions, subscriptionp FROM tl_user WHERE id=?")->limit(1)->execute($objUser->id);

                            $arrsubscriptionp = deserialize($objUser->subscriptionp);

                            if (is_array($arrsubscriptionp) && in_array('create', $arrsubscriptionp)) {
                                $arrsubscriptions   = deserialize($objUser->subscriptions);
                                $arrsubscriptions[] = Input::get('id');

                                $objDatabase->prepare("UPDATE tl_user SET subscriptions=? WHERE id=?")->execute(serialize($arrsubscriptions), $objUser->id);
                            }
                        } // Add permissions on group level
                        elseif ($objUser->groups[0] > 0) {
                            $objGroup = $objDatabase->prepare("SELECT subscriptions, subscriptionp FROM tl_user_group WHERE id=?")->limit(1)->execute($objUser->groups[0]);

                            $arrsubscriptionp = deserialize($objGroup->subscriptionp);

                            if (is_array($arrsubscriptionp) && in_array('create', $arrsubscriptionp)) {
                                $arrsubscriptions   = deserialize($objGroup->subscriptions);
                                $arrsubscriptions[] = Input::get('id');

                                $objDatabase->prepare("UPDATE tl_user_group SET subscriptions=? WHERE id=?")->execute(serialize($arrsubscriptions), $objUser->groups[0]);
                            }
                        }

                        // Add new element to the user object
                        $root[]                 = Input::get('id');
                        $objUser->subscriptions = $root;
                    }
                }
            // No break;

            case 'copy':
            case 'delete':
            case 'show':
                if (!in_array(Input::get('id'), $root) || (Input::get('act') == 'delete' && !$objUser->hasAccess('delete', 'subscriptionp'))) {
                    \Controller::log('Not enough permissions to ' . Input::get('act') . ' subscriptions archive ID "' . Input::get('id') . '"', 'tl_iso_subscription_archive checkPermission', TL_ERROR);
                    \Controller::redirect('contao/main.php?act=error');
                }
                break;

            case 'editAll':
            case 'deleteAll':
            case 'overrideAll':
                $session = $objSession->getData();
                if (Input::get('act') == 'deleteAll' && !$objUser->hasAccess('delete', 'subscriptionp')) {
                    $session['CURRENT']['IDS'] = [];
                } else {
                    $session['CURRENT']['IDS'] = array_intersect($session['CURRENT']['IDS'], $root);
                }
                $objSession->setData($session);
                break;

            default:
                if (strlen(Input::get('act'))) {
                    \Controller::log('Not enough permissions to ' . Input::get('act') . ' subscription archives', 'tl_iso_subscription_archive checkPermission', TL_ERROR);
                    \Controller::redirect('contao/main.php?act=error');
                }
                break;
        }
    }

    public function editHeader($row, $href, $label, $title, $icon, $attributes)
    {
        $objUser = \BackendUser::getInstance();

        return ($objUser->isAdmin || count(preg_grep('/^tl_iso_subscription_archive::/', $objUser->alexf)) > 0) ? '<a href="' . $this->addToUrl($href . '&amp;id=' . $row['id']) . '" title="' . specialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label) . '</a> ' : Image::getHtml(preg_replace('/\.gif$/i', '_.gif', $icon)) . ' ';
    }

    public function copyArchive($row, $href, $label, $title, $icon, $attributes)
    {
        $objUser = \BackendUser::getInstance();

        return ($objUser->isAdmin || $objUser->hasAccess('create', 'subscriptionp')) ? '<a href="' . $this->addToUrl($href . '&amp;id=' . $row['id']) . '" title="' . specialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label) . '</a> ' : Image::getHtml(preg_replace('/\.gif$/i', '_.gif', $icon)) . ' ';
    }

    public function deleteArchive($row, $href, $label, $title, $icon, $attributes)
    {
        $objUser = \BackendUser::getInstance();

        return ($objUser->isAdmin || $objUser->hasAccess('delete', 'subscriptionp')) ? '<a href="' . $this->addToUrl($href . '&amp;id=' . $row['id']) . '" title="' . specialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label) . '</a> ' : Image::getHtml(preg_replace('/\.gif$/i', '_.gif', $icon)) . ' ';
    }
}

