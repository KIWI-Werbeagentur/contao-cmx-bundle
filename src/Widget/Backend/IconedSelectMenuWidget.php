<?php

namespace Kiwi\Contao\CmxBundle\Widget\Backend;

use Contao\SelectMenu;
use Contao\System;
use Symfony\Component\DomCrawler\Crawler;

class IconedSelectMenuWidget extends SelectMenu
{

    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'be_widget';

    public function generate()
    {

        // Prepare icon array
        $arrIcons=[];
        $arrData = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField];
        if (\is_array($arrData['icon_callback'] ?? null))
        {
            $arrCallback = $arrData['icon_callback'];
            $arrIcons = System::importStatic($arrCallback[0])->{$arrCallback[1]}($this);
        }
        elseif (\is_callable($arrData['icon_callback'] ?? null))
        {
            $arrIcons = $arrData['icon_callback']($this);
        }

        // Keep core "chosen" enabled. On Contao 5.7 this yields the native
        // Choices.js component, on Contao 5.3 the legacy MooTools "chosen"
        // select. The JS detects which one is present and feeds the icons to it.
        $this->chosen = true;

        // Get HTML string
        $strBuffer = parent::generate();

        // Load HTML string as DOM document
        $crawler = new Crawler($strBuffer);

        // The <select> exists in every Contao version (unlike the
        // .tl_select_wrapper, which only Contao 5.7+ emits).
        $select = $crawler->filter('select');
        if ($select->count() <= 0) {
            return $strBuffer;
        }

        // Marker class on the <select>. Both Choices.js and the legacy chosen
        // copy the select's class list onto their container, so the existing
        // .cmx--iconedSelect styles keep applying on both versions.
        $selectNode = $select->getNode(0);
        $selectNode->setAttribute('class', trim($selectNode->getAttribute('class') . ' cmx--iconedSelect'));

        // Contao 5.7+: stop the native choices controller from also
        // initialising, since our JS drives Choices.js itself. No-op on 5.3.
        foreach ($crawler->filter('[data-controller*="choices"]') as $node) {
            $node->removeAttribute('data-controller');
        }

        // add data-icon attribute to each option
        $crawler->filter('option')->each(function (Crawler $optionCrawler) use ($arrIcons) {
            $option = $optionCrawler->getNode(0);
            if ($arrIcons[$option->getAttribute('value')] ?? false) {
                $option->setAttribute('data-icon', $arrIcons[$option->getAttribute('value')]);
            }
        });

        // Prepare HTML output
        $strBuffer = $crawler->filter('body')->html();

        return $strBuffer;
    }
}
