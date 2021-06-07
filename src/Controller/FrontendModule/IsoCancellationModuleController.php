<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
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
use Contao\Widget;
use HeimrichHannot\IsotopeSubscriptionsBundle\Manager\SubscriptionManager;
use HeimrichHannot\UtilsBundle\Model\ModelUtil;
use HeimrichHannot\UtilsBundle\Url\UrlUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @FrontendModule(IsoCancellationModuleController::TYPE,category="isotope_subscriptions")
 */
class IsoCancellationModuleController extends AbstractFrontendModuleController
{
    const TYPE = 'iso_cancellation';

    /**
     * @var ModelUtil
     */
    protected ModelUtil           $modelUtil;
    protected UrlUtil             $urlUtil;
    protected SubscriptionManager $subscriptionManager;

    public function __construct(ModelUtil $modelUtil, UrlUtil $urlUtil, SubscriptionManager $subscriptionManager)
    {
        $this->modelUtil = $modelUtil;
        $this->urlUtil = $urlUtil;
        $this->subscriptionManager = $subscriptionManager;
    }

    protected function getResponse(Template $template, ModuleModel $module, Request $request): ?Response
    {
        if ($token = $request->get('token')) {
            return $this->cancel($token, $template, $module, $request);
        }

        return $this->renderForm($template, $module, $request);
    }

    protected function cancel(string $token, Template $template, ModuleModel $module, Request $request): ?Response
    {
        $subscription = $this->modelUtil->findOneModelInstanceBy('tl_iso_subscription', ['tl_iso_subscription.activation=?'], [$token]);

        if (null !== $subscription) {
            $data = $subscription->row();

            $subscription->delete();

            $this->subscriptionManager->addPrivacyProtocolEntry($module->iso_secondPrivacyEntryConfig, $module, $data);

            // success message
            $template->success = sprintf($GLOBALS['TL_LANG']['MSC']['iso_subscriptionCancelledSuccessfully'], $subscription->email);

            /** @var PageModel $jumpTo */
            $jumpTo = $this->modelUtil->findModelInstanceByPk('tl_page', $module->jumpTo);

            if (null !== $jumpTo) {
                throw new RedirectResponseException('/'.$jumpTo->getFrontendUrl());
            }
        } else {
            $template->error = $GLOBALS['TL_LANG']['MSC']['iso_subscriptionTokenNotFound'];
        }

        return $template->getResponse();
    }

    protected function renderForm(Template $template, ModuleModel $module, Request $request): ?Response
    {
        $fields = [
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

        $widgets = [];

        foreach ($fields as $name => $data) {
            if (!isset($GLOBALS['TL_FFL'][$data['inputType']]) || !($class = $GLOBALS['TL_FFL'][$data['inputType']])) {
                continue;
            }

            $widgets[] = new $class(Widget::getAttributesFromDca($data, $name));
        }

        if ('iso_cancellation' === $request->get('FORM_SUBMIT')) {
            // validate
            foreach ($widgets as $widget) {
                $widget->validate();

                if ($widget->hasErrors()) {
                    $this->blnDoNotSubmit = true;
                }
            }

            if (!$this->blnDoNotSubmit) {
                $email = $request->get('email');
                $archive = $module->iso_cancellationArchive;

                $subscription = $this->modelUtil->findModelInstancesBy('tl_iso_subscription', [
                    'tl_iso_subscription.email=?', 'tl_iso_subscription.pid=?',
                ], [$email, $archive]);

                if (null === ($subscription)) {
                    $template->error = sprintf($GLOBALS['TL_LANG']['MSC']['iso_subscriptionDoesNotExist'], $email);
                } else {
                    if ($module->iso_addActivation) {
                        $tokens = [
                            'form_email' => $subscription->email,
                        ];

                        $token = md5(uniqid(mt_rand(), true));

                        $subscription->activation = $token;
                        $subscription->save();

                        if (null !== ($notification = $this->modelUtil->findModelInstanceByPk('tl_nc_notification', $module->iso_activationNotification))) {
                            if ($module->iso_activationJumpTo
                                && null !== ($objPageRedirect = $this->modelUtil->callModelMethod('tl_page', 'findPublishedById', $module->iso_activationJumpTo))) {
                                $tokens['link'] = $this->urlUtil->addQueryString('token='.$token, $objPageRedirect->getAbsoluteUrl());
                            }

                            $notification->send($tokens, $GLOBALS['TL_LANGUAGE']);

                            // privacy
                            $this->subscriptionManager->addPrivacyProtocolEntry($module->iso_privacyEntryConfig, $module, $subscription->row());

                            // redirect
                            /** @var PageModel $jumpTo */
                            $jumpTo = $this->modelUtil->findModelInstanceByPk('tl_page', $module->iso_activationLinkSentJumpTo);

                            if (null !== $jumpTo) {
                                throw new RedirectResponseException('/'.$jumpTo->getFrontendUrl());
                            }
                        }
                    } else {
                        $data = $subscription->row();

                        // no activation -> delete immediately
                        $subscription->delete();

                        // success message
                        $template->success = sprintf($GLOBALS['TL_LANG']['MSC']['iso_subscriptionCancelledSuccessfully'], $email);

                        // privacy
                        $this->subscriptionManager->addPrivacyProtocolEntry($module->iso_privacyEntryConfig, $module, $data);

                        // redirect
                        /** @var PageModel $jumpTo */
                        $jumpTo = $this->modelUtil->findModelInstanceByPk('tl_page', $module->jumpTo);

                        if (null !== $jumpTo) {
                            throw new RedirectResponseException('/'.$jumpTo->getFrontendUrl());
                        }
                    }
                }
            }
        }

        // parse (validated) widgets
        $template->fields = implode('', array_map(function ($objWidget) {
            return $objWidget->parse();
        }, $widgets));

        return $template->getResponse();
    }
}
