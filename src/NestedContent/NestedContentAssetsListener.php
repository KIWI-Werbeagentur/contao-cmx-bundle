<?php

declare(strict_types=1);

namespace Kiwi\Contao\CmxBundle\NestedContent;

use Contao\CoreBundle\Routing\ScopeMatcher;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Styles und Drag & Drop für die verschachtelten Einträge in der Elementliste (nur im Backend, nur bei tl_content).
 */
class NestedContentAssetsListener
{
    public function __construct(
        private readonly Packages $packages,
        private readonly ScopeMatcher $scopeMatcher,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function __invoke(string $table): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if ('tl_content' !== $table || !$request || !$this->scopeMatcher->isBackendRequest($request)) {
            return;
        }

        $GLOBALS['TL_CSS']['cmx_nested_content'] = trim($this->packages->getUrl('nested-content.css', 'kiwi_cmx'), '/');
        $GLOBALS['TL_JAVASCRIPT']['cmx_nested_content'] = $this->packages->getUrl('nested-content.js', 'kiwi_cmx');
    }
}
