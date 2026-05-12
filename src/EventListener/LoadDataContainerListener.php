<?php

declare(strict_types=1);

namespace Kiwi\Contao\CmxBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsHook('loadDataContainer')]
class LoadDataContainerListener
{
    public function __construct(
        private readonly Packages $packages,
        private readonly ScopeMatcher $scopeMatcher,
        private readonly RequestStack $requestStack,
        private readonly string $environment,
    ) {
    }

    public function __invoke(string $strTable): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if (
            $this->scopeMatcher->isBackendRequest($request ?? Request::create(''))
            ||
            $this->environment === 'dev'
            ||
            $request?->attributes->get('_preview') === true
        ) {
            $GLOBALS['TL_CSS']['ui.css'] = trim($this->packages->getUrl(
                'ui.css',
                'kiwi_cmx',
            ), '/');

            $GLOBALS['TL_CSS']['backend.css'] = trim($this->packages->getUrl(
                'backend.css',
                'kiwi_cmx',
            ), '/');
        }
    }
}
