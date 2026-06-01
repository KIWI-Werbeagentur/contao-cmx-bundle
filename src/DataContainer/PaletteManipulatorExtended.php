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

        $arrSelectors = (array) ($GLOBALS['TL_DCA'][$table]['palettes']['__selector__'] ?? []);
        $arrSubpalettes = (array) ($GLOBALS['TL_DCA'][$table]['subpalettes'] ?? []);

        return $this->searchFields($arrPaletteFields, $strField, $arrSelectors, $arrSubpalettes, []);
    }

    /**
     * Recursively searches $arrFields (and any subpalettes reachable via selectors) for $strField.
     *
     * @param string[] $arrFields     Fields to search in this level.
     * @param string   $strField      The field we are looking for.
     * @param string[] $arrSelectors  All selector field names of the table.
     * @param array    $arrSubpalettes The table's subpalettes definition.
     * @param string[] $arrVisited    Subpalette keys already visited (prevents infinite recursion).
     */
    private function searchFields(array $arrFields, string $strField, array $arrSelectors, array $arrSubpalettes, array $arrVisited): bool
    {
        // 1) Direct hit at this level.
        if (in_array($strField, $arrFields, true)) {
            return true;
        }

        // 2) Follow selectors that are present at this level into their subpalettes.
        foreach ($arrSelectors as $strSelector) {
            if (!in_array($strSelector, $arrFields, true)) continue;

            foreach ($arrSubpalettes as $strSubKey => $varSubFields) {
                if ($strSubKey !== $strSelector && !str_starts_with((string) $strSubKey, $strSelector . '_')) continue;
                if (!is_string($varSubFields)) continue;
                if (in_array($strSubKey, $arrVisited, true)) continue;

                $arrSubFields = StringUtil::trimsplit(',', $varSubFields);

                if ($this->searchFields($arrSubFields, $strField, $arrSelectors, $arrSubpalettes, array_merge($arrVisited, [$strSubKey]))) {
                    return true;
                }
            }
        }

        return false;
    }
}
