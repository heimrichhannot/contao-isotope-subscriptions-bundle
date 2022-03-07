<?php

/*
 * Copyright (c) 2022 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeSubscriptionsBundle\Controller\FrontendModule;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\CoreBundle\ServiceAnnotation\FrontendModule;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\Template;
use HeimrichHannot\IsotopeSubscriptionsBundle\Manager\SubscriptionManager;
use HeimrichHannot\UtilsBundle\Model\ModelUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @FrontendModule(IsoActivationModuleController::TYPE,category="isotope_subscriptions")
 */
class IsoActivationModuleController extends AbstractFrontendModuleController
{
    const TYPE = 'iso_activation';

    protected ModelUtil           $modelUtil;
    protected SubscriptionManager $subscriptionManager;

    public function __construct(ModelUtil $modelUtil, SubscriptionManager $subscriptionManager)
    {
        $this->modelUtil = $modelUtil;
        $this->subscriptionManager = $subscriptionManager;
    }

    protected function getResponse(Template $template, ModuleModel $module, Request $request): ?Response
    {
        if (!($token = $request->get('token'))) {
            $template->error = $GLOBALS['TL_LANG']['MSC']['iso_subscriptionTokenNotFound'];

            return $template->getResponse();
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

                $this->subscriptionManager->addPrivacyProtocolEntry((int) $module->iso_privacyEntryConfig, $module, $subscription->row());

                // redirect
                /** @var PageModel $jumpTo */
                $jumpTo = $this->modelUtil->findModelInstanceByPk('tl_page', $module->jumpTo);

                if (null !== $jumpTo) {
                    throw new RedirectResponseException('/'.$jumpTo->getFrontendUrl());
                }
            }
        } else {
            $template->error = $GLOBALS['TL_LANG']['MSC']['iso_subscriptionTokenNotFound'];
        }

        return $template->getResponse();
    }
}
