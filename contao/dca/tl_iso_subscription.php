<?php

$GLOBALS['TL_DCA']['tl_iso_subscription'] = [
    'config'   => [
        'dataContainer'    => 'Table',
        'ptable'           => 'tl_iso_subscription_archive',
        'enableVersioning' => true,
        'sql'              => [
            'keys' => [
                'id' => 'primary',
            ],
        ],
    ],
    'list'     => [
        'sorting'           => [
            'mode'         => 4,
            'fields'       => ['dateAdded DESC'],
            'headerFields' => ['title'],
            'panelLayout'  => 'filter;search,limit',
        ],
        'global_operations' => [
            'export_xls' => \Contao\System::getContainer()->get('huh.exporter.action.backendexport')->getGlobalOperation('export_xls', $GLOBALS['TL_LANG']['MSC']['export_xls']),
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
                'label'      => &$GLOBALS['TL_LANG']['tl_iso_subscription']['toggle'],
                'icon'       => 'visible.gif',
                'attributes' => 'onclick="Backend.getScrollOffset();return AjaxRequest.toggleVisibility(this,%s)"',
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
            'inputType' => 'textarea',
        ],
        'source'     => [
            'eval' => ['fieldType' => 'checkbox', 'files' => true, 'filesOnly' => true, 'extensions' => 'csv', 'class' => 'mandatory'],
        ],
        'dateAdded'  => [
            'sorting' => true,
            'flag'    => 6,
            'eval'    => ['rgxp' => 'datim'],
            'sql'     => "int(10) unsigned NOT NULL default '0'",
        ],
        'quantity'   => [
            'inputType' => 'text',
            'default'   => 1,
            'eval'      => ['rgxp' => 'digit', 'mandatory' => true, 'tl_class' => 'w50'],
            'sql'       => "int(10) unsigned NOT NULL default '1'",
        ],
        'order_id'   => [
            'inputType' => 'select',
            'eval'      => ['tl_class' => 'w50', 'chosen' => true, 'includeBlankOption' => true],
            'sql'       => "int(10) unsigned NOT NULL default '0'",
        ],
        'disable'    => [
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
