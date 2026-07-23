<?php

declare(strict_types=1);

namespace Kiwi\Contao\CmxBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\Response;

/**
 * When the frontend preview iframe loads a page with ?_clp=1, injects a tiny
 * postMessage listener + highlight CSS before </body>. This enables the backend
 * JS to scroll to and briefly outline the currently edited article/element.
 *
 * Only fires for frontend HTML responses — never for backend, JSON, or assets.
 */
#[AsHook('injectPreviewScript')]
class InjectCustomPreviewScriptListener
{
    public function __construct(private readonly Packages $packages)
    {
    }

    public function __invoke(Response $response): Response
    {
        $content = $response->getContent();
        if (false === $content || !str_contains($content, '</body>')) return $response;

        $url = $this->packages->getUrl('bundles/kiwicmx/cmx-live-preview-frontend.js');
        $js   = '<script src="' . htmlspecialchars($url, \ENT_QUOTES, 'UTF-8') . '" defer></script>';

        $response->setContent(str_replace('</body>', $js . '</body>', $content));
        return $response;
    }
}
