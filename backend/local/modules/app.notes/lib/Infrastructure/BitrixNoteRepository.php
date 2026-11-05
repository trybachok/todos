<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure;

use App\Notes\Domain\Note;
use App\Notes\Domain\NoteInput;
use App\Notes\Domain\NoteList;
use App\Notes\Domain\NoteRepository;
use App\Notes\Domain\NoteSummary;
use App\Notes\Domain\Page;
use Bitrix\Main\DB\Connection;
use Bitrix\Main\ORM\Fields\ScalarField;
use Bitrix\Main\Type\DateTime;
use DateTimeImmutable;

/**
 * Reads go through the ORM. Writes are built from the ORM field types instead of DataManager::add/update/delete,
 * which read the table structure from the database (SELECT * ... LIMIT 0) on every request.
 *
 * @phpstan-type SummaryRow array{ID: int|string, TITLE: string, CREATED_AT: DateTime, UPDATED_AT: DateTime}
 * @phpstan-type NoteRow array{ID: int|string, TITLE: string, CONTENT: string, CREATED_AT: DateTime, UPDATED_AT: DateTime}
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

    public function find(int $id): ?Note
    {
        /** @var NoteRow|false $row */
        $row = NoteTable::getByPrimary($id, ['select' => [...self::SUMMARY_FIELDS, 'CONTENT']])->fetch();

        return $row === false ? null : new Note(
            (int) $row['ID'],
            $row['TITLE'],
            $row['CONTENT'],
            self::immutable($row['CREATED_AT']),
            self::immutable($row['UPDATED_AT']),
        );
    }

    public function create(NoteInput $input): Note
    {
        $now = new DateTime();
        $values = self::sqlValues([
            'TITLE' => $input->title,
            'CONTENT' => $input->content,
            'CREATED_AT' => $now,
            'UPDATED_AT' => $now,
        ]);

        $connection = self::connection();
        $connection->queryExecute(sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            self::table(),
            implode(', ', array_keys($values)),
            implode(', ', $values),
        ));

        return new Note((int) $connection->getInsertedId(), $input->title, $input->content, self::immutable($now), self::immutable($now));
    }

    public function update(int $id, NoteInput $input): ?Note
    {
        /** @var array{CREATED_AT: DateTime}|false $row */
        $row = NoteTable::getByPrimary($id, ['select' => ['CREATED_AT']])->fetch();
        if ($row === false) {
            return null;
        }

        $now = new DateTime();
        $values = self::sqlValues([
            'TITLE' => $input->title,
            'CONTENT' => $input->content,
            'UPDATED_AT' => $now,
        ]);
        $assignments = array_map(
            static fn(string $column, string $value): string => "{$column} = {$value}",
            array_keys($values),
            $values,
        );
        self::connection()->queryExecute(sprintf('UPDATE %s SET %s WHERE ID = %d', self::table(), implode(', ', $assignments), $id));

        return new Note($id, $input->title, $input->content, self::immutable($row['CREATED_AT']), self::immutable($now));
    }

    public function delete(int $id): bool
    {
        $connection = self::connection();
        $connection->queryExecute(sprintf('DELETE FROM %s WHERE ID = %d', self::table(), $id));

        return $connection->getAffectedRowsCount() > 0;
    }

    /**
     * Quoted column names mapped to escaped SQL literals.
     *
     * @param array<string, mixed> $row
     * @return array<string, string>
     */
    private static function sqlValues(array $row): array
    {
        $entity = NoteTable::getEntity();
        $helper = self::connection()->getSqlHelper();
        $values = [];
        foreach ($row as $name => $value) {
            /** @var ScalarField $field */
            $field = $entity->getField($name);
            $values[$helper->quote($name)] = (string) $field->convertValueToDb($value);
        }

        return $values;
    }

    private static function table(): string
    {
        return self::connection()->getSqlHelper()->quote(NoteTable::getTableName());
    }

    private static function connection(): Connection
    {
        return NoteTable::getEntity()->getConnection();
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
