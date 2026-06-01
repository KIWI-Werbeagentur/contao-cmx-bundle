<?php

namespace Kiwi\Contao\CmxBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Symfony\Component\Asset\Packages;

#[AsHook('initializeSystem')]
class InitializeSystemListener
{
    public function __construct(private readonly Packages $packages)
    {
    }

    public function __invoke(): void
    {
        $GLOBALS['TL_JAVASCRIPT']['cmx'] = $this->packages->getUrl(
            'backend.js',
            'kiwi_cmx',
        );
    }
}