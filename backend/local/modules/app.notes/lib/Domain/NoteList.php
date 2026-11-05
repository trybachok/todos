<?php

declare(strict_types=1);

namespace App\Notes\Domain;

final readonly class NoteList
{
    /**
     * @param list<NoteSummary> $items
     */
    public function __construct(
        public array $items,
        public bool $hasMore,
    ) {}
}
