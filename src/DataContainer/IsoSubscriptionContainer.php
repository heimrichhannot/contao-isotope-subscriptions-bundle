<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeSubscriptionsBundle\DataContainer;

use Contao\BackendUser;
use Contao\Controller;
use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\Database;
use Contao\DataContainer;
use Contao\Image;
use Contao\Input;
use Contao\RequestToken;
use Contao\Session;
use Contao\StringUtil;
use Contao\System;
use Contao\Versions;
use HeimrichHannot\UtilsBundle\Database\DatabaseUtil;
use HeimrichHannot\UtilsBundle\Dca\DcaUtil;
use HeimrichHannot\UtilsBundle\Model\ModelUtil;

class IsoSubscriptionContainer
{
    /**
     * @var ModelUtil
     */
    protected ModelUtil    $modelUtil;
    protected DcaUtil      $dcaUtil;
    protected DatabaseUtil $databaseUtil;

    public function __construct(ModelUtil $modelUtil, DcaUtil $dcaUtil, DatabaseUtil $databaseUtil)
    {
        $this->modelUtil = $modelUtil;
        $this->dcaUtil = $dcaUtil;
        $this->databaseUtil = $databaseUtil;
    }

    /**
     * @Callback(table="tl_iso_subscription", target="list.sorting.child_record")
     */
    public function listSubscriptions($row)
    {
        $text = trim($row['firstname'].' '.$row['lastname']);

        if ($row['gender'] && '' == trim($row['firstname'])) {
            $text = $GLOBALS['TL_LANG']['tl_iso_subscription']['salutation'.ucfirst($row['gender'])].' '.$text;
        }

        if ($row['company']) {
            $text = $row['company'].(trim($text) ? ' ('.$text.')' : '');
        }

        return $text;
    }

    /**
     * @Callback(table="tl_iso_subscription", target="config.onsubmit")
     */
    public function setDateAdded($dc)
    {
        $this->dcaUtil->setDateAdded($dc);
    }

    /**
     * @Callback(table="tl_iso_subscription", target="config.oncopy")
     */
    public function setDateAddedOnCopy($insertId, $dc)
    {
        $this->dcaUtil->setDateAddedOnCopy($insertId, $dc);
    }

    /**
     * @Callback(table="tl_iso_subscription", target="list.operations.toggle.button")
     */
    public function toggleIcon($row, $href, $label, $title, $icon, $attributes)
    {
        $user = BackendUser::getInstance();

        if (\strlen(Input::get('tid'))) {
            $this->toggleVisibility(Input::get('tid'), (1 == Input::get('state')));
            Controller::redirect(Controller::getReferer());
        }

        // Check permissions AFTER checking the tid, so hacking attempts are logged
        if (!$user->isAdmin && !$user->hasAccess('tl_iso_subscription::disable', 'alexf')) {
            return '';
        }

        $href .= '&amp;tid='.$row['id'].'&amp;state='.($row['disable'] ? 1 : '');

        if ($row['disable']) {
            $icon = 'invisible.gif';
        }

        return '<a href="'.Controller::addToUrl($href).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ';
    }

    public function checkPermission()
    {
        $user = BackendUser::getInstance();
        $sessionInstance = Session::getInstance();
        $database = Database::getInstance();

        if ($user->isAdmin) {
            return;
        }

        // Set the root IDs
        if (!\is_array($user->subscriptions) || empty($user->subscriptions)) {
            $root = [0];
        } else {
            $root = $user->subscriptions;
        }

        $id = \strlen(Input::get('id')) ? Input::get('id') : CURRENT_ID;

        // Check current action
        switch (Input::get('act')) {
            case 'paste':
                // Allow
                break;

            case 'create':
                if (!\strlen(Input::get('pid')) || !\in_array(Input::get('pid'), $root)) {
                    Controller::log('Not enough permissions to create subscription items in subscription archive ID "'.Input::get('pid').'"', 'tl_iso_subscription checkPermission', TL_ERROR);
                    Controller::redirect('contao/main.php?act=error');
                }

                break;

            case 'cut':
            case 'copy':
                if (!\in_array(Input::get('pid'), $root)) {
                    Controller::log('Not enough permissions to '.Input::get('act').' subscription item ID "'.$id.'" to subscription archive ID "'.Input::get('pid').'"', 'tl_iso_subscription checkPermission', TL_ERROR);
                    Controller::redirect('contao/main.php?act=error');
                }
            // no break STATEMENT HERE

            case 'edit':
            case 'show':
            case 'delete':
            case 'toggle':
            case 'feature':
                $objArchive = $database->prepare('SELECT pid FROM tl_iso_subscription WHERE id=?')->limit(1)->execute($id);

                if ($objArchive->numRows < 1) {
                    Controller::log('Invalid subscription item ID "'.$id.'"', 'tl_iso_subscription checkPermission', TL_ERROR);
                    Controller::redirect('contao/main.php?act=error');
                }

                if (!\in_array($objArchive->pid, $root)) {
                    Controller::log('Not enough permissions to '.Input::get('act').' subscription item ID "'.$id.'" of subscription archive ID "'.$objArchive->pid.'"', 'tl_iso_subscription checkPermission', TL_ERROR);
                    Controller::redirect('contao/main.php?act=error');
                }

                break;

            case 'select':
            case 'editAll':
            case 'deleteAll':
            case 'overrideAll':
            case 'cutAll':
            case 'copyAll':
                if (!\in_array($id, $root)) {
                    Controller::log('Not enough permissions to access subscription archive ID "'.$id.'"', 'tl_iso_subscription checkPermission', TL_ERROR);
                    Controller::redirect('contao/main.php?act=error');
                }

                $objArchive = $database->prepare('SELECT id FROM tl_iso_subscription WHERE pid=?')->execute($id);

                if ($objArchive->numRows < 1) {
                    Controller::log('Invalid subscription archive ID "'.$id.'"', 'tl_iso_subscription checkPermission', TL_ERROR);
                    Controller::redirect('contao/main.php?act=error');
                }

                $session = $sessionInstance->getData();
                $session['CURRENT']['IDS'] = array_intersect($session['CURRENT']['IDS'], $objArchive->fetchEach('id'));
                $sessionInstance->setData($session);

                break;

            default:
                if (\strlen(Input::get('act'))) {
                    Controller::log('Invalid command "'.Input::get('act').'"', 'tl_iso_subscription checkPermission', TL_ERROR);
                    Controller::redirect('contao/main.php?act=error');
                } elseif (!\in_array($id, $root)) {
                    Controller::log('Not enough permissions to access subscription archive ID '.$id, 'tl_iso_subscription checkPermission', TL_ERROR);
                    Controller::redirect('contao/main.php?act=error');
                }

                break;
        }
    }

    public function toggleVisibility($id, $visible)
    {
        $user = BackendUser::getInstance();
        $database = Database::getInstance();

        // Check permissions to edit
        Input::setGet('id', $id);
        Input::setGet('act', 'toggle');
        $this->checkPermission();

        // Check permissions to disable
        if (!$user->isAdmin && !$user->hasAccess('tl_iso_subscription::disable', 'alexf')) {
            Controller::log('Not enough permissions to disable/enable subscription item ID "'.$id.'"', 'tl_iso_subscription toggleVisibility', TL_ERROR);
            Controller::redirect('contao/main.php?act=error');
        }

        $versions = new Versions('tl_iso_subscription', $id);
        $versions->initialize();

        // Trigger the save_callback
        if (\is_array($GLOBALS['TL_DCA']['tl_iso_subscription']['fields']['disable']['save_callback'])) {
            foreach ($GLOBALS['TL_DCA']['tl_iso_subscription']['fields']['disable']['save_callback'] as $callback) {
                $callbackObj = System::importStatic($callback[0]);
                $visible = $callbackObj->{$callback[1]}($visible, $this);
            }
        }

        // Update the database
        $database->prepare('UPDATE tl_iso_subscription SET tstamp='.time().", disable='".($visible ? '' : 1)."' WHERE id=?")->execute($id);

        $versions->create();
        Controller::log('A new version of record "tl_iso_subscription.id='.$id.'" has been created', 'tl_iso_subscription toggleVisibility()', TL_GENERAL);
    }

    /**
     * The order needs to contain at least one item of the product type defined in the archive.
     *
     * @Callback(table="tl_iso_subscription", target="fields.order_id.options")
     */
    public function getOrders(DataContainer $dc)
    {
        $options = [];

        if (null === ($subscription = $this->modelUtil->findModelInstanceByPk('tl_iso_subscription', $dc->id))) {
            return [];
        }

        if (null === ($products = $this->modelUtil->findModelInstancesBy('tl_iso_product', [
                'tl_iso_product.type=?',
            ], [$subscription->getRelated('pid')->productType]))) {
            return [];
        }

        if (null === ($items = $this->modelUtil->findModelInstancesBy('tl_iso_product_collection_item', [
                'tl_iso_product_collection_item.product_id IN ('.implode(',', $products->fetchEach('id')).')',
            ], []))) {
            return [];
        }

        if (null === ($orders = $this->modelUtil->findModelInstancesBy('tl_iso_product_collection', [
                'tl_iso_product_collection.id IN ('.implode(',', $items->fetchEach('pid')).')',
            ], []))) {
            return [];
        }

        while ($orders->next()) {
            if (!$orders->document_number) {
                continue;
            }

            $options[$orders->id] = $GLOBALS['TL_LANG']['MSC']['order'].' '.$orders->document_number;
        }

        // inverse asort
        arsort($options);

        return $options;
    }

    /**
     * @Callback(table="tl_iso_subscription", target="fields.order_id.wizard")
     */
    public function editOrder(DataContainer $dc)
    {
        return ($dc->value < 1) ? ''
            : ' <a href="contao/main.php?do=iso_orders&act=edit&id='.$dc->value.'&rt='.RequestToken::get().'" title="'.sprintf(StringUtil::specialchars($GLOBALS['TL_LANG']['tl_content']['editalias'][1]), $dc->value).'" style="padding-left:3px">'.Image::getHtml('alias.gif', $GLOBALS['TL_LANG']['tl_content']['editalias'][0], 'style="vertical-align:top"').'</a>';
    }
}
