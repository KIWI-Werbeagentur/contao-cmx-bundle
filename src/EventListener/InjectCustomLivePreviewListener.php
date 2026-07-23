<?php

declare(strict_types=1);

namespace Kiwi\Contao\CmxBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Symfony\Component\Asset\Packages;

#[AsHook('injectLivePreview')]
class InjectCustomLivePreviewListener
{
    public function __construct(private readonly Packages $packages)
    {
    }

    public function __invoke(string $buffer): string
    {
        $url = $this->packages->getUrl('bundles/kiwicmx/cmx-live-preview.js');
        $jsTag   = '<script src="' . htmlspecialchars($url, \ENT_QUOTES, 'UTF-8') . '" defer></script>';

        // Inject JS into <head>
        $buffer = str_replace('</head>', $jsTag . "\n</head>", $buffer);

        return $buffer;
    }
}