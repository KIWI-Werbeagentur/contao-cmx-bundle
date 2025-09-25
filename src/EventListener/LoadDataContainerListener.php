<?php

namespace Kiwi\Contao\CmxBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\System;
use Symfony\Component\HttpFoundation\Request;

#[AsHook('loadDataContainer')]
class LoadDataContainerListener
{
    public function __invoke(string $strTable): void
    {
        $container = System::getContainer();
        if ($container === null) {
            return;
        }

        $request = $container->get('request_stack')->getCurrentRequest();

        if ($container->get('contao.routing.scope_matcher')->isBackendRequest($request ?? Request::create('')) || (($_ENV['APP_ENV'] ?? '') === 'dev') || ($request?->attributes?->get('_preview') ?? false)) {
            $GLOBALS['TL_CSS'][] = "bundles/kiwicmx/main.css";
        }
    }
}
