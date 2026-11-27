<?php

use Contao\CoreBundle\DataContainer\PaletteManipulator;

// Inline SVG: if the image is an SVG, the text template prints its markup instead of an img
// element, e.g. to animate it with CSS. Output: contao/templates/twig/content_element/text.html.twig
// Shown only for the element types in "showForTypes" without a legacy custom template (see Kiwi\Contao\CmxBundle\DataContainer\SvgInlineField).
$GLOBALS['TL_DCA']['tl_content']['fields']['svgInline'] = [
    'inputType' => 'checkbox',
    'showForTypes' => ['text'],
    'eval' => ['tl_class' => 'w50 m12'],
    'sql' => ['type' => 'boolean', 'default' => false],
];

PaletteManipulator::create()
    ->addField('svgInline', 'singleSRC', PaletteManipulator::POSITION_AFTER)
    ->applyToSubpalette('addImage', 'tl_content')
;
