<?php

declare(strict_types=1);

namespace App\Notes\Http;

use App\Notes\Domain\NoteList;
use App\Notes\Domain\NoteSummary;

/**
 * @phpstan-type SummaryJson array{id: int, title: string, createdAt: string, updatedAt: string}
 */
final class NotePresenter
{
    /**
     * @return array{items: list<SummaryJson>, hasMore: bool}
     */
    public static function list(NoteList $list): array
    {
        return [
            'items' => array_map(self::summary(...), $list->items),
            'hasMore' => $list->hasMore,
        ];
    }

    /**
     * @return SummaryJson
     */
    private static function summary(NoteSummary $note): array
    {
        return [
            'id' => $note->id,
            'title' => $note->title,
            'createdAt' => $note->createdAt->format(DATE_ATOM),
            'updatedAt' => $note->updatedAt->format(DATE_ATOM),
        ];
    }
}
