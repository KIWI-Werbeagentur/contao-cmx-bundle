<?php

namespace Kiwi\Contao\CmxBundle\Twig;

use Contao\System;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class BackendExtension extends AbstractExtension
{
    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('clipboard', [$this, 'getClipboard']),
            new TwigFunction('copiedArticle', [$this, 'getCopiedArticleId']),
            new TwigFunction('csrf', [$this, 'getCSRF']),
            new TwigFunction('isPreview', [$this, 'isPreview']),
        ];
    }

    public function getClipboard()
    {
        return $this->requestStack->getSession()->get('CLIPBOARD');
    }

    public function getCopiedArticleId()
    {
        $arrClipboard = $this->requestStack->getSession()->get('CLIPBOARD');

        return ($arrClipboard['tl_article']['mode'] ?? false) == 'copy' ? $arrClipboard['tl_article']['id'] : false;
    }

    /**
     * @deprecated Deprecated since version 1.x, to be removed in version 2.x;
     * *           use contao.request_token instead.
     */
    public function getCSRF(): string
    {
        return System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue();
    }

    /**
     * @deprecated Deprecated since version 1.x, to be removed in version 2.x;
     * *           use contao.is_preview_mode instead.
     */
    public function isPreview()
    {
        return System::getContainer()->get('request_stack')->getCurrentRequest()->attributes->get('_preview');
    }
}