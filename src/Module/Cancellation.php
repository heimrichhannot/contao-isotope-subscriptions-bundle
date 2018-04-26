<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeSubscriptionsBundle\Module;

use Contao\Controller;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Contao\Widget;
use Isotope\Model\Subscription;
use Isotope\Model\SubscriptionArchive;
use Isotope\Module\Module;

/**
 * Class Cancellation.
 *
 * Copyright (c) 2015 Heimrich & Hannot GmbH
 *
 * @author  Dennis Patzer <d.patzer@heimrich-hannot.de>
 * @license http://www.gnu.org/licences/lgpl-3.0.html LGPL
 */
class Cancellation extends Module
{
    protected $strTemplate = 'mod_iso_cancellation';
    protected $strFormId = 'iso_cancellation';
    protected $blnDoNotSubmit;

    public function generate()
    {
        if (System::getContainer()->get('huh.utils.container')->isBackend()) {
            $objTemplate = new \BackendTemplate('be_wildcard');

            $objTemplate->wildcard = '### ISOTOPE ECOMMERCE: CANCELLATION ###';

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
        $request = System::getContainer()->get('huh.request');
        $framework = System::getContainer()->get('contao.framework');
        $this->Template->formId = $this->strFormId;
        $arrFieldDcas = [
            'email' => [
                'label' => &$GLOBALS['TL_LANG']['tl_module']['email'],
                'inputType' => 'text',
                'eval' => ['rgxp' => 'email', 'mandatory' => true],
            ],
            'submit' => [
                'inputType' => 'submit',
                'label' => &$GLOBALS['TL_LANG']['MSC']['cancel'],
            ],
        ];

        $arrWidgets = [];
        foreach ($arrFieldDcas as $strName => $arrData) {
            if ($strClass = $GLOBALS['TL_FFL'][$arrData['inputType']]) {
                $arrWidgets[] = new $strClass($framework->getAdapter(Widget::class)->getAttributesFromDca($arrData, $strName));
            }
        }

        if ($request->getPost('FORM_SUBMIT') == $this->strFormId) {
            // validate
            foreach ($arrWidgets as $objWidget) {
                $objWidget->validate();

                if ($objWidget->hasErrors()) {
                    $this->blnDoNotSubmit = true;
                }
            }

            if (!$this->blnDoNotSubmit) {
                // cancel subscription
                $strEmail = $request->getPost('email');
                $arrArchives = StringUtil::deserialize($this->iso_cancellationArchives, true);
                $blnNoSuccess = false;

                foreach ($arrArchives as $intArchive) {
                    if (null === ($objSubscription = $framework->getAdapter(Subscription::class)->findBy(['email=?', 'pid=?'], [$strEmail, $intArchive]))) {
                        if (1 == count($arrArchives)) {
                            $this->Template->error = sprintf($GLOBALS['TL_LANG']['MSC']['iso_subscriptionDoesNotExist'], $strEmail, $framework->getAdapter(SubscriptionArchive::class)->findByPk($intArchive)->title);
                            $blnNoSuccess = true;
                        }

                        break;
                    }

                    $objSubscription->delete();
                }

                if (!$blnNoSuccess) {
                    // success message
                    if (count($arrArchives) > 1) {
                        $this->Template->success = $GLOBALS['TL_LANG']['MSC']['iso_subscriptionsCancelledSuccessfully'];
                    } else {
                        $this->Template->success = sprintf($GLOBALS['TL_LANG']['MSC']['iso_subscriptionCancelledSuccessfully'], $strEmail, $framework->getAdapter(SubscriptionArchive::class)->findByPk($arrArchives[0])->title);
                    }

                    // redirect
                    if ($this->jumpTo && null !== ($objPageRedirect = $framework->getAdapter(PageModel::class)->findPublishedById($this->jumpTo))) {
                        Controller::redirect($objPageRedirect->getFrontendUrl());
                    }
                }
            }
        }

        // parse (validated) widgets
        $this->Template->fields = implode('', array_map(function ($objWidget) {
            return $objWidget->parse();
        }, $arrWidgets));
    }
}
