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
use HeimrichHannot\UtilsBundle\Util\Utils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @FrontendModule(IsoActivationModuleController::TYPE,category="isotope_subscriptions")
 */
class IsoActivationModuleController extends AbstractFrontendModuleController
{
    const TYPE = 'iso_activation';

    protected SubscriptionManager $subscriptionManager;
    protected Utils               $utils;

    public function __construct(SubscriptionManager $subscriptionManager, Utils $utils)
    {
        $this->subscriptionManager = $subscriptionManager;
        $this->utils = $utils;
    }

    protected function getResponse(Template $template, ModuleModel $module, Request $request): ?Response
    {
        $token = $request->get('token');
        if (!$token)
        {
            $template->error = $GLOBALS['TL_LANG']['MSC']['iso_subscriptionTokenNotFound'];
            return $template->getResponse();
        }

        $subscription = $this->utils->model()
            ->findOneModelInstanceBy('tl_iso_subscription', ['tl_iso_subscription.activation=?'], [$token]);

        if ($subscription === null)
        {
            $template->error = $GLOBALS['TL_LANG']['MSC']['iso_subscriptionTokenNotFound'];
            return $template->getResponse();
        }

        if (!$subscription->disable)
        {
            $subscription->activation = '';
            $subscription->save();
            $template->warning = $GLOBALS['TL_LANG']['MSC']['iso_subscriptionAlreadyActivated'];
            return $template->getResponse();
        }

        $template->success = $GLOBALS['TL_LANG']['MSC']['iso_subscriptionActivatedSuccessfully'];
        $subscription->activation = $subscription->disable = '';
        $subscription->save();

        $this->subscriptionManager->addPrivacyProtocolEntry(
            (int) $module->iso_privacyEntryConfig,
            $module,
            $subscription->row()
        );

        // redirect
        /** @var PageModel $jumpTo */
        $jumpTo = $this->utils->model()->findModelInstanceByPk('tl_page', $module->jumpTo);

        if (null !== $jumpTo)
        {
            throw new RedirectResponseException('/' . $jumpTo->getFrontendUrl());
        }

        return $template->getResponse();
    }
}