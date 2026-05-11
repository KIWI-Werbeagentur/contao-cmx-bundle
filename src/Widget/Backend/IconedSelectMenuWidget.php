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
        $GLOBALS['TL_JAVASCRIPT']['iconedSelect.js'] = $this->asset(
            'iconedSelect.js',
            'kiwi_cmx',
        );

        $GLOBALS['TL_CSS']['iconedSelect.css'] = trim($this->asset(
            'iconedSelect.css',
            'kiwi_cmx',
        ), '/');

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

        // Force chosen select
        $this->chosen = true;

        // Get HTML string
        $strBuffer = parent::generate();

        // Load HTML string as DOM document
        $crawler = new Crawler($strBuffer);

        $wrapper = $crawler->filter('.tl_select_wrapper');
        if ($wrapper->count() <= 0) {
            return $strBuffer;
        }

        // replace 'data-controller' attribute with class
        $wrapperNode = $wrapper->getNode(0);
        $wrapperNode->removeAttribute('data-controller');
        $wrapperNode->setAttribute('class', $wrapperNode->getAttribute('class') . ' cmx--iconedSelect');

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
