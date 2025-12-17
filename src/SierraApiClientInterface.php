<?php

declare(strict_types=1);

namespace App;

interface SierraApiClientInterface
{
    /**
     * Hämtar exemplar från Sierra API:et baserat på en eller flera identifierare.
     *
     * @param array<string, string|null> $identifiers En associativ array av identifierare (t.ex. 'bib_id', 'isbn').
     * @return array<array<string, mixed>>|null En array av exemplarinformation, eller null om inga hittades.
     * @throws \RuntimeException Om API-förfrågan misslyckas.
     */
    public function getItemsForIdentifiers(array $identifiers): ?array;
}
