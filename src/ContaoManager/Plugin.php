<?php

namespace Kiwi\Contao\CmxBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Config\ConfigPluginInterface;
use Kiwi\Contao\CmxBundle\KiwiCmxBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use ThinkDigital\ContaoLivePreview\ContaoLivePreviewBundle;

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
                    ContaoCoreBundle::class, ContaoLivePreviewBundle::class
                ]),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader, array $managerConfig): void
    {
        $loader->load(__DIR__ . '/../../config/services.yaml');
    }
}
