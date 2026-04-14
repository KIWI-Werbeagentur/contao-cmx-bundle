<?php

namespace Kiwi\Contao\CmxBundle\DataContainer;

use Contao\Controller;
use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\StringUtil;

class PaletteManipulatorExtended extends PaletteManipulator
{
    public static function create(): self
    {
        return new self();
    }

    public function applyToAllPalettes(string $table, array $arrExceptions = []): object
    {
        foreach ($GLOBALS['TL_DCA'][$table]['palettes'] as $strPalette => $varFields) {
            if (!is_string($varFields) || in_array($strPalette, ($arrExceptions ?? []))) continue;

            $this->applyToPalette($strPalette, $table);
        }
        return $this;
    }

    public function applyToPalettes(array $names, string $table): self
    {
        foreach ($names as $name) {
            if(!($GLOBALS['TL_DCA'][$table]['palettes'][$name] ?? false)) continue;
            parent::applyToPalette($name, $table);
        }

        return $this;
    }

    public function getPaletteFields(string $strPalette, string $table){
        $arrPaletteSections = StringUtil::trimsplit(';', $GLOBALS['TL_DCA'][$table]['palettes'][$strPalette] ?? '');

        $arrFields = [];

        foreach ($arrPaletteSections ?? [] as $strPaletteSection){
            $arrFields = array_merge($arrFields, StringUtil::trimsplit(',',$strPaletteSection));
        }

        return $arrFields;
    }

    public function hasField(string $strPalette, string $table, string $strField){
        Controller::loadDataContainer($table);
        return in_array($strField,$this->getPaletteFields($strPalette, $table));
    }
}
