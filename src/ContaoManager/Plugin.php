<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeSubscriptionsBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use HeimrichHannot\IsotopeBundle\HeimrichHannotContaoIsotopeBundle;
use HeimrichHannot\IsotopeSubscriptionsBundle\HeimrichHannotContaoIsotopeSubscriptionsBundle;

class Plugin implements BundlePluginInterface
{
    /**
     * {@inheritdoc}
     */
    public function getBundles(ParserInterface $parser)
    {
        return [
            BundleConfig::create(HeimrichHannotContaoIsotopeSubscriptionsBundle::class)->setLoadAfter([
                ContaoCoreBundle::class,
                'isotope',
                HeimrichHannotContaoIsotopeBundle::class,
            ]),
        ];
    }
}
