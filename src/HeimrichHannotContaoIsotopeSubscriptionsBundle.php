<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeSubscriptionsBundle;

use HeimrichHannot\IsotopeSubscriptionsBundle\DependencyInjection\IsotopeSubscriptionsExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class HeimrichHannotContaoIsotopeSubscriptionsBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        return new IsotopeSubscriptionsExtension();
    }
}
