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
use Contao\Model;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\Template;
use Contao\Widget;
use HeimrichHannot\IsotopeSubscriptionsBundle\Manager\SubscriptionManager;
use HeimrichHannot\UtilsBundle\Util\Utils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @FrontendModule(IsoCancellationModuleController::TYPE,category="isotope_subscriptions")
 */
class IsoCancellationModuleController extends AbstractFrontendModuleController
{
    const TYPE = 'iso_cancellation';

    protected SubscriptionManager $subscriptionManager;
    protected Utils               $utils;
    protected bool                $blnDoNotSubmit = false;

    protected static function getFieldDefaults(): array
    {
        return [
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
    }

    public function __construct(
        SubscriptionManager $subscriptionManager,
        Utils               $utils
    ) {
        $this->subscriptionManager = $subscriptionManager;
        $this->utils = $utils;
    }

    protected function getResponse(Template $template, ModuleModel $module, Request $request): ?Response
    {
        if ($token = $request->get('token')) {
            return $this->cancel($token, $template, $module, $request);
        }

        return $this->renderCancelForm($template, $module, $request);
    }

    protected function cancel(string $token, Template $template, ModuleModel $module, Request $request): ?Response
    {
        $subscription = $this->utils->model()->findOneModelInstanceBy(
            'tl_iso_subscription',
            ['tl_iso_subscription.activation=?'],
            [$token]
        );

        if (null === $subscription) {
            $template->error = $GLOBALS['TL_LANG']['MSC']['iso_subscriptionTokenNotFound'];
            return $template->getResponse();
        }

        $data = $subscription->row();

        $subscription->delete();

        $this->subscriptionManager->addPrivacyProtocolEntry((int) $module->iso_secondPrivacyEntryConfig, $module, $data);

        // success message
        $template->success = \sprintf($GLOBALS['TL_LANG']['MSC']['iso_subscriptionCancelledSuccessfully'], $subscription->email);

        /** @var PageModel $jumpTo */
        $jumpTo = $this->utils->model()->findModelInstanceByPk('tl_page', $module->jumpTo);

        if (null !== $jumpTo) {
            throw new RedirectResponseException('/'.$jumpTo->getFrontendUrl());
        }

        return $template->getResponse();
    }

    protected function renderCancelForm(Template $template, ModuleModel $module, Request $request): Response
    {
        $fields = self::getFieldDefaults();

        $widgets = [];

        foreach ($fields as $name => $data)
        {
            $inputType = $data['inputType'] ?? null;
            $class = $GLOBALS['TL_FFL'][$inputType] ?? null;

            if (!$class) {
                continue;
            }

            $widgets[] = new $class(Widget::getAttributesFromDca($data, $name));
        }

        $respond = function () use (&$template, &$widgets) {
            // parse (validated) widgets
            $template->fields = implode('', array_map(function ($objWidget) {
                return $objWidget->parse();
            }, $widgets));

            return $template->getResponse();
        };

        if ('iso_cancellation' !== $request->get('FORM_SUBMIT'))
        {
            return $respond();
        }

        // validate
        foreach ($widgets as $widget)
        {
            $widget->validate();

            if ($widget->hasErrors()) {
                $this->blnDoNotSubmit = true;
            }
        }

        if ($this->blnDoNotSubmit)
        {
            return $respond();
        }

        $email = $request->get('email');
        $archive = $module->iso_cancellationArchive;

        $subscription = $this->utils->model()->findModelInstancesBy(
            'tl_iso_subscription',
            ['tl_iso_subscription.email=?', 'tl_iso_subscription.pid=?'],
            [$email, $archive]
        );

        if ($subscription === null)
        {
            $template->error = sprintf($GLOBALS['TL_LANG']['MSC']['iso_subscriptionDoesNotExist'], $email);
            return $respond();
        }

        if ($module->iso_addActivation)
        {
            $tokens = [
                'form_email' => $subscription->email,
            ];

            $token = md5(uniqid(mt_rand(), true));

            $subscription->activation = $token;
            $subscription->save();

            $notification = $this->utils->model()->findModelInstanceByPk('tl_nc_notification', $module->iso_activationNotification);

            if ($notification === null) {
                return $respond();
            }

            /** @var class-string<PageModel> $pageModel */
            $pageModel = Model::getClassFromTable('tl_page');

            if ($module->iso_activationJumpTo
                && null !== ($objPageRedirect = $pageModel::findPublishedById($module->iso_activationJumpTo)))
            {
                $tokens['link'] = $this->utils->url()
                    ->addQueryStringParameterToUrl('token='.$token, $objPageRedirect->getAbsoluteUrl());
            }

            $notification->send($tokens, $GLOBALS['TL_LANGUAGE']);

            // privacy
            $this->subscriptionManager->addPrivacyProtocolEntry((int) $module->iso_privacyEntryConfig, $module, $subscription->row());

            // redirect
            /** @var PageModel $jumpTo */
            $jumpTo = $this->utils->model()->findModelInstanceByPk('tl_page', $module->iso_activationLinkSentJumpTo);

            if (null !== $jumpTo) {
                throw new RedirectResponseException('/'.$jumpTo->getFrontendUrl());
            }

            return $respond();
        }

        $data = $subscription->row();

        // no activation -> delete immediately
        $subscription->delete();

        // success message
        $template->success = sprintf($GLOBALS['TL_LANG']['MSC']['iso_subscriptionCancelledSuccessfully'], $email);

        // privacy
        $this->subscriptionManager->addPrivacyProtocolEntry((int) $module->iso_privacyEntryConfig, $module, $data);

        // redirect
        /** @var PageModel $jumpTo */
        $jumpTo = $this->utils->model()->findModelInstanceByPk('tl_page', $module->jumpTo);

        if (null !== $jumpTo) {
            throw new RedirectResponseException('/'.$jumpTo->getFrontendUrl());
        }

        return $respond();
    }
}
