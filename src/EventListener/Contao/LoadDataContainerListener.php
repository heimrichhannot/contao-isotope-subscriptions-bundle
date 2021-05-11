<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeSubscriptionsBundle\EventListener\Contao;

use Contao\CoreBundle\ServiceAnnotation\Hook;
use HeimrichHannot\IsotopeSubscriptionsBundle\Manager\SubscriptionManager;

/**
 * @Hook("loadDataContainer")
 */
class LoadDataContainerListener
{
    protected SubscriptionManager $subscriptionManager;

    public function __construct(SubscriptionManager $subscriptionManager)
    {
        $this->subscriptionManager = $subscriptionManager;
    }

    public function __invoke(string $table): void
    {
        if ('tl_iso_subscription' !== $table) {
            return;
        }

        // if not set, all fields are used
        $this->subscriptionManager->importIsotopeAddressFields();
    }
}
