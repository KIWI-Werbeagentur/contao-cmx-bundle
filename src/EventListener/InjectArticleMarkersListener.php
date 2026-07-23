<?php

namespace Kiwi\Contao\CmxBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Injects data-contao-table="tl_article" and data-contao-id="{N}" into the
 * article wrapper when the page is loaded inside the live-preview iframe (?_clp=1).
 *
 * This makes the partial DOM-swap (clp:refresh) work in any Contao theme without
 * requiring theme-level changes. Themes that already emit these attributes (e.g.
 * Design+) are skipped via the str_contains guard.
 *
 * The parseFrontendTemplate hook fires in Module::generate() after rendering,
 * including Twig-rendered articles in Contao 5.
 */
#[AsHook('parseFrontendTemplate')]
class InjectArticleMarkersListener
{
    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    public function __invoke(string $buffer, string $template): string
    {
        if (!str_starts_with($template, 'mod_article')) {
            return $buffer;
        }

        $request = $this->requestStack->getCurrentRequest();
        if (!$request?->query->getBoolean('_clp')) {
            return $buffer;
        }

        return preg_replace(
            '/(<[a-z][a-z0-9]*\b)/i',
            '$1 data-cmx="live-preview"',
            $buffer,
            1,
        ) ?? $buffer;
    }
}
