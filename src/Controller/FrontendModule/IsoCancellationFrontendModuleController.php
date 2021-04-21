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
use Contao\StringUtil;
use Contao\Template;
use Contao\Widget;
use HeimrichHannot\UtilsBundle\Model\ModelUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @FrontendModule(category="isotope_subscriptions")
 */
class IsoCancellationFrontendModuleController extends AbstractFrontendModuleController
{
    const TYPE = 'iso_cancellation';

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
                // cancel subscription
                $email = $request->get('email');
                $archives = StringUtil::deserialize($module->iso_cancellationArchives, true);
                $noSuccess = false;

                foreach ($archives as $archive) {
                    $subscription = $this->modelUtil->findModelInstancesBy('tl_iso_subscription', [
                        'tl_iso_subscription.email=?', 'tl_iso_subscription.pid=?',
                    ], [$email, $archive]);

                    if (null === ($subscription)) {
                        if (1 == \count($archives)) {
                            $template->error = sprintf($GLOBALS['TL_LANG']['MSC']['iso_subscriptionDoesNotExist'], $email, $framework->getAdapter(SubscriptionArchiveModel::class)->findByPk($archive)->title);
                            $noSuccess = true;
                        }

                        break;
                    }

                    $subscription->delete();
                }

                if (!$noSuccess) {
                    // success message
                    if (\count($archives) > 1) {
                        $template->success = $GLOBALS['TL_LANG']['MSC']['iso_subscriptionsCancelledSuccessfully'];
                    } else {
                        $template->success = sprintf($GLOBALS['TL_LANG']['MSC']['iso_subscriptionCancelledSuccessfully'], $email, $framework->getAdapter(SubscriptionArchiveModel::class)->findByPk($archives[0])->title);
                    }

                    // redirect
                    /** @var PageModel $jumpTo */
                    $jumpTo = $this->modelUtil->findModelInstanceByPk('tl_page', $module->jumpTo);

                    if (null !== $jumpTo) {
                        throw new RedirectResponseException($jumpTo->getFrontendUrl());
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
