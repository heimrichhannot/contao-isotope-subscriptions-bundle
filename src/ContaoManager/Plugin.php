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
use Contao\ManagerPlugin\Config\ConfigPluginInterface;
use HeimrichHannot\IsotopeSubscriptionsBundle\HeimrichHannotIsotopeSubscriptionsBundle;
use Symfony\Component\Config\Loader\LoaderInterface;

class Plugin implements BundlePluginInterface, ConfigPluginInterface
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

        if (class_exists('HeimrichHannot\IsotopeExtensionBundle\HeimrichHannotIsotopeExtensionBundle')) {
            $loadAfter[] = \HeimrichHannot\IsotopeExtensionBundle\HeimrichHannotIsotopeExtensionBundle::class;
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
