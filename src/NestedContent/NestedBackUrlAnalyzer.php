<?php

declare(strict_types=1);

namespace Kiwi\Contao\CmxBundle\NestedContent;

use Contao\CoreBundle\DataContainer\DcaUrlAnalyzer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * „Zurück“ (und die Weiterleitung nach Speichern und schließen, Löschen, …) bei Kindelementen:
 * Contao springt dorthin, was in der Spur (Trail) vor dem Element steht – bei einem Kindelement die
 * Ansicht seiner Gruppe. Wurde das Element aus einer Liste geöffnet, in der es verschachtelt angezeigt
 * wird, trägt der Link nested=<Tiefe> (siehe NestedContentViewListener): so viele Gruppen-Ansichten
 * werden für den Rücksprung übersprungen, man landet wieder in dieser Liste. Die Brotkrumen (mit
 * Labels) bleiben unverändert.
 *
 * Echter Decorator: alle Aufrufe gehen an den inneren Service, damit weitere Decorators anderer Bundles
 * erhalten bleiben. Die Klasse muss trotzdem von DcaUrlAnalyzer erben (Typ der Core-Services); die
 * Elternklasse wird mit dem Zustand des inneren Services initialisiert, für Methoden, die es hier noch
 * nicht gibt. Läuft als äußerster Decorator (decoration_priority in nested_content.yaml), damit
 * Decorators, die den Zustand des Core-Services per Reflection übernehmen, weiterhin genau diesen bekommen.
 */
class NestedBackUrlAnalyzer extends DcaUrlAnalyzer
{
    public const QUERY_PARAMETER = 'nested';

    public function __construct(
        private readonly DcaUrlAnalyzer $inner,
        private readonly RequestStack $requestStack,
    ) {
        $args = [];

        // über die deklarierende Klasse lesen, damit es auch geht, wenn der innere Service selbst ein Decorator ist
        foreach ((new \ReflectionClass(DcaUrlAnalyzer::class))->getConstructor()?->getParameters() ?? [] as $parameter) {
            $args[] = (new \ReflectionProperty(DcaUrlAnalyzer::class, $parameter->getName()))->getValue($inner);
        }

        parent::__construct(...$args);
    }

    public function getCurrentTableId(Request|string|null $request = null): array
    {
        return $this->inner->getCurrentTableId($request);
    }

    public function getEditUrl(string $table, int $id): string|null
    {
        return $this->inner->getEditUrl($table, $id);
    }

    public function getViewUrl(string $table, int $id): string|null
    {
        return $this->inner->getViewUrl($table, $id);
    }

    public function getTrail(Request|string|null $request = null, int $limit = PHP_INT_MAX, bool $withTreeTrail = false, bool $loadLabels = true): array
    {
        $depth = (int) $this->requestStack->getCurrentRequest()?->query->get(self::QUERY_PARAMETER);

        // nur für den Rücksprung (getReferer lädt keine Labels) und nur bei Aufruf aus einer verschachtelten Liste
        if (null !== $request || $loadLabels || $depth < 1) {
            return $this->inner->getTrail($request, $limit, $withTreeTrail, $loadLabels);
        }

        $trail = $this->inner->getTrail($request, PHP_INT_MAX, $withTreeTrail, $loadLabels);
        $current = array_pop($trail);

        // die letzten <Tiefe> Einträge vor dem Element sind die Ansichten der Gruppen dazwischen
        array_splice($trail, max(0, \count($trail) - $depth));

        if (null !== $current) {
            $trail[] = $current;
        }

        return PHP_INT_MAX === $limit ? $trail : \array_slice($trail, -$limit);
    }
}
