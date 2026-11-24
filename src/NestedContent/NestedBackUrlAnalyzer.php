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
 */
class NestedBackUrlAnalyzer extends DcaUrlAnalyzer
{
    public const QUERY_PARAMETER = 'nested';

    private RequestStack|null $requestStack = null;

    public function setRequestStack(RequestStack $requestStack): void
    {
        $this->requestStack = $requestStack;
    }

    public function getTrail(Request|string|null $request = null, int $limit = PHP_INT_MAX, bool $withTreeTrail = false, bool $loadLabels = true): array
    {
        $depth = (int) $this->requestStack?->getCurrentRequest()?->query->get(self::QUERY_PARAMETER);

        // nur für den Rücksprung (getReferer lädt keine Labels) und nur bei Aufruf aus einer verschachtelten Liste
        if (null !== $request || $loadLabels || $depth < 1) {
            return parent::getTrail($request, $limit, $withTreeTrail, $loadLabels);
        }

        $trail = parent::getTrail($request, PHP_INT_MAX, $withTreeTrail, $loadLabels);
        $current = array_pop($trail);

        // die letzten <Tiefe> Einträge vor dem Element sind die Ansichten der Gruppen dazwischen
        array_splice($trail, max(0, \count($trail) - $depth));

        if (null !== $current) {
            $trail[] = $current;
        }

        return PHP_INT_MAX === $limit ? $trail : \array_slice($trail, -$limit);
    }
}
