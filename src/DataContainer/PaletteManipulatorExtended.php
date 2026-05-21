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

    /**
     * Returns whether $strField is reachable from $strPalette - either directly in the main
     * palette string or in a subpalette whose selector is reachable from the main palette.
     *
     * Whether a specific record currently activates a given subpalette (i.e. the runtime value
     * of the selector) is a separate concern handled at call sites that need it.
     */
    public function hasField(string $strPalette, string $table, string $strField): bool
    {
        Controller::loadDataContainer($table);

        $arrPaletteFields = $this->getPaletteFields($strPalette, $table);

        // 1) Direct hit in the main palette.
        if (in_array($strField, $arrPaletteFields, true)) {
            return true;
        }

        // 2) Hit in a subpalette whose selector is reachable from this palette.
        $arrSelectors = (array) ($GLOBALS['TL_DCA'][$table]['palettes']['__selector__'] ?? []);
        $arrSubpalettes = (array) ($GLOBALS['TL_DCA'][$table]['subpalettes'] ?? []);

        foreach ($arrSelectors as $strSelector) {
            if (!in_array($strSelector, $arrPaletteFields, true)) continue;

            foreach ($arrSubpalettes as $strSubKey => $varSubFields) {
                if ($strSubKey !== $strSelector && !str_starts_with((string) $strSubKey, $strSelector . '_')) continue;
                if (!is_string($varSubFields)) continue;

                foreach (StringUtil::trimsplit(',', $varSubFields) as $strSubField) {
                    if ($strSubField === $strField) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
