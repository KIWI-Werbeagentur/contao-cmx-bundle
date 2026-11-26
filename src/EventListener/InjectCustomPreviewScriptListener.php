<?php

declare(strict_types=1);

namespace Kiwi\Contao\CmxBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Adds livePreviewFe.js to the page in the live preview iframe (?_clp=1). It
 * registers the article badge buttons "show content elements" and "show page";
 * their translated titles are passed as data attributes of the script tag.
 *
 * Called by the live preview bundle for front end HTML responses only.
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
