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
use Contao\Image;
use Contao\Input;
use Contao\Session;
use HeimrichHannot\UtilsBundle\Database\DatabaseUtil;
use HeimrichHannot\UtilsBundle\Dca\DcaUtil;
use HeimrichHannot\UtilsBundle\Model\ModelUtil;

class IsoSubscriptionArchiveContainer
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
     * @Callback(table="tl_iso_subscription_archive", target="config.onload")
     */
    public function checkPermission()
    {
        $objUser = BackendUser::getInstance();
        $objSession = Session::getInstance();
        $objDatabase = Database::getInstance();

        if ($objUser->isAdmin) {
            return;
        }

        // Set root IDs
        if (!\is_array($objUser->subscriptions) || empty($objUser->subscriptions)) {
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
                if (!\in_array(Input::get('id'), $root)) {
                    $arrNew = $objSession->get('new_records');

                    if (\is_array($arrNew['tl_iso_subscription_archive']) && \in_array(Input::get('id'), $arrNew['tl_iso_subscription_archive'])) {
                        // Add permissions on user level
                        if ('custom' == $objUser->inherit || !$objUser->groups[0]) {
                            $objUser = $objDatabase->prepare('SELECT subscriptions, subscriptionp FROM tl_user WHERE id=?')->limit(1)->execute($objUser->id);

                            $arrsubscriptionp = deserialize($objUser->subscriptionp);

                            if (\is_array($arrsubscriptionp) && \in_array('create', $arrsubscriptionp)) {
                                $arrsubscriptions = deserialize($objUser->subscriptions);
                                $arrsubscriptions[] = Input::get('id');

                                $objDatabase->prepare('UPDATE tl_user SET subscriptions=? WHERE id=?')->execute(serialize($arrsubscriptions), $objUser->id);
                            }
                        } // Add permissions on group level
                        elseif ($objUser->groups[0] > 0) {
                            $objGroup = $objDatabase->prepare('SELECT subscriptions, subscriptionp FROM tl_user_group WHERE id=?')->limit(1)->execute($objUser->groups[0]);

                            $arrsubscriptionp = deserialize($objGroup->subscriptionp);

                            if (\is_array($arrsubscriptionp) && \in_array('create', $arrsubscriptionp)) {
                                $arrsubscriptions = deserialize($objGroup->subscriptions);
                                $arrsubscriptions[] = Input::get('id');

                                $objDatabase->prepare('UPDATE tl_user_group SET subscriptions=? WHERE id=?')->execute(serialize($arrsubscriptions), $objUser->groups[0]);
                            }
                        }

                        // Add new element to the user object
                        $root[] = Input::get('id');
                        $objUser->subscriptions = $root;
                    }
                }
            // no break;

            case 'copy':
            case 'delete':
            case 'show':
                if (!\in_array(Input::get('id'), $root) || ('delete' == Input::get('act') && !$objUser->hasAccess('delete', 'subscriptionp'))) {
                    Controller::log('Not enough permissions to '.Input::get('act').' subscriptions archive ID "'.Input::get('id').'"', 'tl_iso_subscription_archive checkPermission', TL_ERROR);
                    Controller::redirect('contao/main.php?act=error');
                }

                break;

            case 'editAll':
            case 'deleteAll':
            case 'overrideAll':
                $session = $objSession->getData();

                if ('deleteAll' == Input::get('act') && !$objUser->hasAccess('delete', 'subscriptionp')) {
                    $session['CURRENT']['IDS'] = [];
                } else {
                    $session['CURRENT']['IDS'] = array_intersect($session['CURRENT']['IDS'], $root);
                }
                $objSession->setData($session);

                break;

            default:
                if (\strlen(Input::get('act'))) {
                    Controller::log('Not enough permissions to '.Input::get('act').' subscription archives', 'tl_iso_subscription_archive checkPermission', TL_ERROR);
                    Controller::redirect('contao/main.php?act=error');
                }

                break;
        }
    }

    /**
     * @Callback(table="tl_iso_subscription_archive", target="list.operations.editheader")
     */
    public function editHeader($row, $href, $label, $title, $icon, $attributes)
    {
        $user = BackendUser::getInstance();

        return ($user->isAdmin || \count(preg_grep('/^tl_iso_subscription_archive::/', $user->alexf)) > 0) ? '<a href="'.Controller::addToUrl($href.'&amp;id='.$row['id']).'" title="'.specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ' : Image::getHtml(preg_replace('/\.gif$/i', '_.gif', $icon)).' ';
    }

    public function copyArchive($row, $href, $label, $title, $icon, $attributes)
    {
        $user = BackendUser::getInstance();

        return ($user->isAdmin || $user->hasAccess('create', 'subscriptionp')) ? '<a href="'.Controller::addToUrl($href.'&amp;id='.$row['id']).'" title="'.specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ' : Image::getHtml(preg_replace('/\.gif$/i', '_.gif', $icon)).' ';
    }

    public function deleteArchive($row, $href, $label, $title, $icon, $attributes)
    {
        $user = BackendUser::getInstance();

        return ($user->isAdmin || $user->hasAccess('delete', 'subscriptionp')) ? '<a href="'.Controller::addToUrl($href.'&amp;id='.$row['id']).'" title="'.specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ' : Image::getHtml(preg_replace('/\.gif$/i', '_.gif', $icon)).' ';
    }
}
