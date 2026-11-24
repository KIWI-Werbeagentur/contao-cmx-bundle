<?php

declare(strict_types=1);

namespace Kiwi\Contao\CmxBundle\NestedContent;

use Contao\CoreBundle\DataContainer\DataContainerOperationsBuilder;
use Contao\CoreBundle\EventListener\DataContainer\ContentElementViewListener;
use Contao\CoreBundle\Fragment\FragmentCompositor;
use Contao\CoreBundle\Fragment\Reference\ContentElementReference;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Contao\DC_Table;
use Contao\Image;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Elementliste im Backend (Ansicht als Raster): Bei Elementen, die Kindelemente haben können, steht
 * statt der Frontend-Vorschau eine Liste der Kindelemente – jedes mit Typ, eigener Bedienleiste
 * (dieselben Operationen wie in der Liste, mit Rechteprüfung) und eigener Vorschau. Verschachtelte
 * Gruppen werden rekursiv genauso dargestellt.
 * Die Reihenfolge lässt sich per Drag & Drop am Griff ändern (assets/scripts/nestedContent.js): gespeichert
 * wird über die Kindelement-Ansicht der Gruppe (ptable=tl_content), damit die Elemente in der Gruppe bleiben.
 */
class NestedContentViewListener extends ContentElementViewListener
{
    private readonly TranslatorInterface $labels;

    /**
     * Verschachtelungstiefe der gerade gerenderten Kindelemente, gezählt ab der angezeigten Liste
     * (1 = Kind einer Gruppe der Liste, 2 = Kind einer Gruppe darin, …)
     */
    private int $depth = 0;

    public function __construct(
        ContaoFramework $framework,
        TranslatorInterface $translator,
        private readonly Connection $connection,
        private readonly FragmentCompositor $compositor,
        private readonly DataContainerOperationsBuilder $operationsBuilder,
        private readonly Security $security,
        private readonly RequestStack $requestStack,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
        parent::__construct($framework, $translator);

        $this->labels = $translator;
    }

    public function generateLabel(array $row, string $label, DC_Table $dc): array|string
    {
        $result = parent::generateLabel($row, $label, $dc);

        // nur in der Raster-Ansicht (Artikel, News, …), nicht in der Theme-Liste
        if (!\is_array($result) || 'tl_theme' === $dc->parentTable || !$this->supportsNesting((string) ($row['type'] ?? ''))) {
            return $result;
        }

        $children = $this->connection->fetchAllAssociative(
            "SELECT * FROM tl_content WHERE ptable='tl_content' AND pid=? ORDER BY sorting",
            [(int) $row['id']],
        );

        if ([] === $children) {
            return $result;
        }

        ++$this->depth;

        try {
            $result[1] = $this->renderChildren($children, (int) $row['id'], $dc);
        } finally {
            --$this->depth;
        }

        return $result;
    }

    private function supportsNesting(string $type): bool
    {
        return '' !== $type && $this->compositor->supportsNesting(ContentElementReference::TAG_NAME.'.'.$type);
    }

    /**
     * @param list<array<string, mixed>> $children
     */
    private function renderChildren(array $children, int $parentId, DC_Table $dc): string
    {
        // Kindelement-Ansicht der Gruppe: dort ist die Gruppe der Elternteil, „an erster Stelle
        // einfügen“ (mode=2) landet also in der Gruppe und nicht im Artikel
        $sortUrl = $this->urlGenerator->generate('contao_backend', [
            'do' => $this->requestStack->getCurrentRequest()?->query->get('do', 'article'),
            'table' => 'tl_content',
            'ptable' => 'tl_content',
            'id' => $parentId,
        ]);

        $html = '<div class="nested-records" data-nested-sortable data-pid="'.$parentId.'" data-sort-url="'.StringUtil::specialchars($sortUrl).'">';

        foreach ($children as $child) {
            [$label, $preview, $state] = (array) $this->generateLabel($child, '', $dc) + ['', '', ''];
            $operations = $this->markActionLinks((string) $this->operationsBuilder->initializeWithButtons('tl_content', $child, $dc));
            $id = (int) $child['id'];
            $canSort = $this->security->isGranted(ContaoCorePermissions::DC_PREFIX.'tl_content', new UpdateAction('tl_content', $child));

            // eigener Griff (nicht .drag-handle, sonst würde die Sortierung des Artikels die Gruppe greifen)
            $handle = $canSort
                ? '<button type="button" class="nested-records__handle" title="'.StringUtil::specialchars($this->labels->trans('DCA.cut.1', [$id], 'contao_default')).'">'.Image::getHtml('drag.svg').'</button>'
                : '';

            $html .= '<div class="tl_content nested-records__record'.($child['invisible'] ?? false ? ' nested-records__record--invisible' : '').'" data-id="'.$id.'">'
                .'<div class="inside hover-div" data-controller="contao--deeplink contao--operations-menu"'
                .' data-action="contextmenu->contao--operations-menu#open:stop"'
                .' data-contao--operations-menu-record-id-value="'.$id.'"'
                .' data-contao--operations-menu-record-table-value="tl_content"'
                .' data-contao--operations-menu-primary-only-value="false">'
                .'<div class="tl_content_right" data-turbo="true">'.$operations.'</div>'
                .'<div class="cte_type '.StringUtil::specialchars((string) $state).($canSort ? ' draggable' : '').'">'.$handle.'<div>'.$label.'</div></div>'
                .('' !== trim((string) $preview) ? '<div class="cte_content" data-contao--limit-height-target="node"><div class="cte_preview" style="contain:paint">'.$preview.'</div></div>' : '')
                .'</div></div>';
        }

        return $html.'</div>';
    }

    /**
     * Aktions-Links (Bearbeiten, Löschen, …) mit nested=<Tiefe> versehen: „Zurück“ und die
     * Weiterleitung nach der Aktion überspringen so viele Gruppen-Ebenen und führen wieder in diese
     * Übersicht (NestedBackUrlAnalyzer).
     */
    private function markActionLinks(string $html): string
    {
        return (string) preg_replace_callback(
            '/href="([^"]*[?&;]act=[^"]*)"/',
            fn (array $m): string => 'href="'.$m[1].'&amp;'.NestedBackUrlAnalyzer::QUERY_PARAMETER.'='.$this->depth.'"',
            $html,
        );
    }
}
