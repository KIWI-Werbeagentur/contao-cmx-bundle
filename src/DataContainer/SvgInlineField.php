<?php

declare(strict_types=1);

namespace Kiwi\Contao\CmxBundle\DataContainer;

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;

/**
 * tl_content.svgInline sits in the shared addImage subpalette, but only templates that render
 * the text element's text_media block read it. The field is removed for all other element types
 * and for legacy (.html5) custom templates.
 *
 * Projects add their own types (e.g. elements whose template extends the text template) with
 * $GLOBALS['TL_DCA']['tl_content']['fields']['svgInline']['showForTypes'][] = 'myType';
 */
#[AsCallback(table: 'tl_content', target: 'config.onload')]
class SvgInlineField
{
    public function __invoke(DataContainer $dc): void
    {
        $types = $GLOBALS['TL_DCA']['tl_content']['fields']['svgInline']['showForTypes'] ?? [];
        $record = $dc->id ? $dc->getCurrentRecord() : null;

        if (null !== $record && \in_array($record['type'], $types, true) && !$this->isLegacyTemplate($record['customTpl'])) {
            return;
        }

        PaletteManipulator::create()
            ->removeField('svgInline')
            ->applyToSubpalette('addImage', 'tl_content')
        ;
    }

    /**
     * Same rule as AbstractFragmentController::isLegacyTemplate(): Twig template names contain a slash.
     */
    private function isLegacyTemplate(string $customTpl): bool
    {
        return '' !== $customTpl && !str_contains($customTpl, '/');
    }
}
