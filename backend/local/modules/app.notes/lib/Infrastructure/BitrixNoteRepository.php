<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure;

use App\Notes\Domain\NoteList;
use App\Notes\Domain\NoteRepository;
use App\Notes\Domain\NoteSummary;
use App\Notes\Domain\Page;
use Bitrix\Main\Type\DateTime;
use DateTimeImmutable;

/**
 * @phpstan-type SummaryRow array{ID: int|string, TITLE: string, CREATED_AT: DateTime, UPDATED_AT: DateTime}
 */
final class BitrixNoteRepository implements NoteRepository
{
    private const array SUMMARY_FIELDS = ['ID', 'TITLE', 'CREATED_AT', 'UPDATED_AT'];

    public function list(Page $page): NoteList
    {
        // One row more than the page tells whether there is a next page without COUNT(*).
        /** @var list<SummaryRow> $rows */
        $rows = NoteTable::getList([
            'select' => self::SUMMARY_FIELDS,
            'order' => ['ID' => 'DESC'],
            'limit' => $page->limit + 1,
            'offset' => $page->offset,
        ])->fetchAll();

        return new NoteList(
            array_map(self::summary(...), array_slice($rows, 0, $page->limit)),
            count($rows) > $page->limit,
        );
    }

    /**
     * @param SummaryRow $row
     */
    private static function summary(array $row): NoteSummary
    {
        return new NoteSummary(
            (int) $row['ID'],
            $row['TITLE'],
            self::immutable($row['CREATED_AT']),
            self::immutable($row['UPDATED_AT']),
        );
    }

    private static function immutable(DateTime $dateTime): DateTimeImmutable
    {
        return (new DateTimeImmutable())->setTimestamp($dateTime->getTimestamp());
    }
}
