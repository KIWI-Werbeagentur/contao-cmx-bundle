<?php

namespace Kiwi\Contao\CmxBundle\Twig;

use Contao\FilesModel;
use Contao\System;
use Symfony\Component\Mime\MimeTypes;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class FrontendExtension extends AbstractExtension
{
    public function __construct(protected MimeTypes $mimeTypes)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('file', [$this, 'getFile']),
            new TwigFunction('inline', [$this, 'getContents'])
        ];
    }

    /**
     * The file's data, or null if there is no such file (e.g. deleted in the file manager).
     */
    public function getFile($uuid): ?array
    {
        return FilesModel::findByPk($uuid)?->row();
    }

    public function getContents($uuid)
    {
        $objFile = FilesModel::findByPk($uuid);
        if($objFile && $this->mimeTypes->guessMimeType($objFile->path) == "image/svg+xml" && $objFile->extension == 'svg') {
            return file_get_contents($objFile->path);
        }
        return "";
    }
}