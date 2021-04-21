<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\IsotopeSubscriptionsBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use HeimrichHannot\IsotopeSubscriptionsBundle\HeimrichHannotIsotopeSubscriptionsBundle;
use Symfony\Component\Config\Loader\LoaderInterface;

class Plugin implements BundlePluginInterface
{
    /**
     * {@inheritdoc}
     */
    public function getBundles(ParserInterface $parser)
    {
        $loadAfter = [
            'isotope',
            ContaoCoreBundle::class,
        ];

        if (class_exists('HeimrichHannot\IsotopeBundle\HeimrichHannotIsotopeBundle')) {
            $loadAfter[] = \HeimrichHannot\IsotopeBundle\HeimrichHannotIsotopeBundle::class;
        }

        return [
            BundleConfig::create(HeimrichHannotIsotopeSubscriptionsBundle::class)->setLoadAfter($loadAfter),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader, array $managerConfig)
    {
        $loader->load('@HeimrichHannotIsotopeSubscriptionsBundle/Resources/config/services.yml');
    }
}
