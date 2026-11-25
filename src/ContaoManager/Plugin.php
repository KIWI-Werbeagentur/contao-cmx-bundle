<?php

namespace Kiwi\Contao\CmxBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
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

        // verschachtelte Kindelemente in der Elementliste: dekoriert Core-Services mit den Konstruktoren von
        // Contao 5.7. Die Klassen selbst gibt es schon seit 5.5, dort aber mit anderen Argumenten, deshalb
        // nach der installierten Version prüfen
        if (preg_match('/^(\d+\.\d+)/', ContaoCoreBundle::getVersion(), $version) && version_compare($version[1], '5.7', '>=')) {
            $loader->load(__DIR__ . '/../../config/nested_content.yaml');
        }
    }
}
