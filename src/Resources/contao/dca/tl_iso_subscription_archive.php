<?php

$GLOBALS['TL_DCA']['tl_iso_subscription_archive'] = [

    // Config
    'config'   => [
        'dataContainer'    => 'Table',
        'ctable'           => ['tl_iso_subscription'],
        'switchToEdit'     => true,
        'enableVersioning' => false,
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
            'exclude'   => true,
            'search'    => true,
            'inputType' => 'text',
            'eval'      => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'productType' => [
            'inputType'        => 'select',
            'options_callback' => ['Isotope\Backend\ProductType\Callback', 'getOptions'],
            'eval'             => ['tl_class' => 'w50', 'mandatory' => true],
            'sql'              => "int(10) unsigned NOT NULL default '0'",
        ],
    ],
];
