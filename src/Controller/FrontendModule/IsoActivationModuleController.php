<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeSubscriptionsBundle\Module;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\CoreBundle\ServiceAnnotation\FrontendModule;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\Template;
use HeimrichHannot\UtilsBundle\Model\ModelUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @FrontendModule(category="miscellaneous")
 */
class IsoActivationModuleController extends AbstractFrontendModuleController
{
    const TYPE = 'iso_activation';

    /**
     * @var ModelUtil
     */
    protected ModelUtil $modelUtil;

    public function __construct(ModelUtil $modelUtil)
    {
        $this->modelUtil = $modelUtil;
    }

    protected function getResponse(Template $template, ModuleModel $module, Request $request): ?Response
    {
        if (!($token = $request->get('token'))) {
            return new Response('');
        }

        $subscription = $this->modelUtil->findOneModelInstanceBy('tl_iso_subscription', ['tl_iso_subscription.activation=?'], [$token]);

        if (null !== $subscription) {
            if (!$subscription->disable) {
                $subscription->activation = '';
                $subscription->save();
                $template->warning = $GLOBALS['TL_LANG']['MSC']['iso_subscriptionAlreadyActivated'];
            } else {
                $template->success = $GLOBALS['TL_LANG']['MSC']['iso_subscriptionActivatedSuccessfully'];
                $subscription->activation = $subscription->disable = '';
                $subscription->save();

                // redirect
                /** @var PageModel $jumpTo */
                $jumpTo = $this->modelUtil->findModelInstanceByPk('tl_page', $module->jumpTo);

                if (null !== $jumpTo) {
                    throw new RedirectResponseException($jumpTo->getFrontendUrl());
                }
            }
        } else {
            $template->error = $GLOBALS['TL_LANG']['MSC']['iso_subscriptionTokenNotFound'];
        }

        return $template->getResponse();
    }
}
