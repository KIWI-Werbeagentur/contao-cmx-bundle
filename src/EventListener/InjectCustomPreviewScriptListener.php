<?php

declare(strict_types=1);

namespace Kiwi\Contao\CmxBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

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
    public function __construct(
        private readonly Packages $packages,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function __invoke(Response $response): Response
    {
        $content = $response->getContent();
        if (false === $content || !str_contains($content, '</body>')) return $response;

        $url = $this->packages->getUrl(
            'livePreviewFe.js',
            'kiwi_cmx',
        );
        $js = sprintf(
            '<script src="%s" data-title-children="%s" data-title-page="%s" defer></script>',
            htmlspecialchars($url, \ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($this->translator->trans('ui.children', [], 'contao_default'), \ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($this->translator->trans('ui.page', [], 'contao_default'), \ENT_QUOTES, 'UTF-8'),
        );

        $response->setContent(str_replace('</body>', $js . '</body>', $content));
        return $response;
    }
}
