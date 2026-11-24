<?php

namespace Kiwi\Contao\CmxBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\DataContainer\DataContainerOperationsBuilder;
use Contao\CoreBundle\DataContainer\DcaUrlAnalyzer;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Config\ConfigPluginInterface;
use Kiwi\Contao\CmxBundle\KiwiCmxBundle;
use Symfony\Component\Config\Loader\LoaderInterface;

class Plugin implements BundlePluginInterface, ConfigPluginInterface
{
    /**
     * {@inheritdoc}
     */
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(KiwiCmxBundle::class)
                ->setLoadAfter([
                    ContaoCoreBundle::class
                ]),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader, array $managerConfig): void
    {
        $loader->load(__DIR__ . '/../../config/services.yaml');

        // verschachtelte Kindelemente in der Elementliste: dekoriert Core-Services, die es erst ab Contao 5.7 gibt
        if (class_exists(DataContainerOperationsBuilder::class) && class_exists(DcaUrlAnalyzer::class)) {
            $loader->load(__DIR__ . '/../../config/nested_content.yaml');
        }
    }
}
