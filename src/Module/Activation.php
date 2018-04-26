<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeSubscriptionsBundle\Module;

use Contao\Controller;
use Contao\PageModel;
use Contao\System;
use Isotope\Model\Subscription;
use Isotope\Module\Module;

/**
 * Class Activation.
 *
 * Copyright (c) 2015 Heimrich & Hannot GmbH
 *
 * @author  Dennis Patzer <d.patzer@heimrich-hannot.de>
 * @license http://www.gnu.org/licences/lgpl-3.0.html LGPL
 */
class Activation extends Module
{
    protected $strTemplate = 'mod_iso_activation';
    protected $strFormId = 'iso_activation';
    protected $blnDoNotSubmit;

    public function generate()
    {
        if (System::getContainer()->get('huh.utils.container')->isBackend()) {
            $objTemplate = new \BackendTemplate('be_wildcard');

            $objTemplate->wildcard = '### ISOTOPE ECOMMERCE: ACTIVATION ###';

            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id='.$this->id;

            return $objTemplate->parse();
        }

        return parent::generate();
    }

    protected function compile()
    {
        $container = System::getContainer();
        if (!($strToken = $container->get('huh.request')->getGet('token'))) {
            return;
        }

        $framework = $container->get('contao.framework');
        if (null !== ($objSubscription = $framework->getAdapter(Subscription::class)->findByActivation($strToken))) {
            if (!$objSubscription->disable) {
                $objSubscription->activation = '';
                $objSubscription->save();
                $this->Template->warning = $GLOBALS['TL_LANG']['MSC']['iso_subscriptionAlreadyActivated'];
            } else {
                $this->Template->success = $GLOBALS['TL_LANG']['MSC']['iso_subscriptionActivatedSuccessfully'];
                $objSubscription->activation = $objSubscription->disable = '';
                $objSubscription->save();

                // redirect
                if ($this->jumpTo && null !== ($objPageRedirect = $framework->getAdapter(PageModel::class)->findPublishedById($this->jumpTo))) {
                    Controller::redirect($objPageRedirect->getFrontendUrl());
                }
            }
        } else {
            $this->Template->error = $GLOBALS['TL_LANG']['MSC']['iso_subscriptionTokenNotFound'];
        }
    }
}
