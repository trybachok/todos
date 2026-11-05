<?php

declare(strict_types=1);

namespace App\Notes\Tests\Integration;

use App\Notes\Domain\Note;
use App\Notes\Domain\NoteInput;
use App\Notes\Domain\NoteRepository;
use App\Notes\Domain\Page;
use App\Notes\Infrastructure\BitrixNoteRepository;
use App\Notes\Infrastructure\NoteTable;
use Bitrix\Main\DB\Connection;
use Bitrix\Main\Diag\SqlTrackerQuery;
use Closure;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Each repository operation stays within its query budget and every query it runs is served by the primary key.
 */
final class NoteQueryPlanTest extends TestCase
{
    private static Connection $connection;
    private static NoteRepository $repository;

    public static function setUpBeforeClass(): void
    {
        $_SERVER['DOCUMENT_ROOT'] = '/var/www/html';
        require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

        self::$connection = NoteTable::getEntity()->getConnection();
        self::$repository = new BitrixNoteRepository();
    }

    /**
     * @return iterable<string, array{Closure(NoteRepository, int): mixed, int}>
     */
    public static function operations(): iterable
    {
        yield 'list' => [static fn(NoteRepository $notes): mixed => $notes->list(new Page(20, 0)), 1];
        yield 'view' => [static fn(NoteRepository $notes, int $id): mixed => $notes->find($id), 1];
        yield 'create' => [static fn(NoteRepository $notes): mixed => $notes->create(new NoteInput('План', '')), 1];
        yield 'edit' => [static fn(NoteRepository $notes, int $id): mixed => $notes->update($id, new NoteInput('План', 'итог')), 2];
        yield 'delete' => [static fn(NoteRepository $notes, int $id): mixed => $notes->delete($id), 1];
    }

    /**
     * @param Closure(NoteRepository, int): mixed $operation
     */
    #[DataProvider('operations')]
    public function testOperationIsWithinBudgetAndUsesPrimaryKey(Closure $operation, int $budget): void
    {
        $note = self::$repository->create(new NoteInput('Черновик', ''));
        // Every HTTP request starts with an empty cache of table structures.
        self::$connection->clearCaches();

        $questions = $this->questions();
        [$result, $queries] = self::track(static fn(): mixed => $operation(self::$repository, $note->id));
        // The server counts every statement, including the ones Bitrix runs past the tracker,
        // and the second SHOW STATUS itself.
        $executed = $this->questions() - $questions - 1;

        self::assertSame($budget, $executed, implode("\n", $queries));
        foreach ($queries as $sql) {
            $this->assertServedByPrimaryKey($sql);
        }

        self::$repository->delete($note->id);
        if ($result instanceof Note && $result->id !== $note->id) {
            self::$repository->delete($result->id);
        }
    }

    /**
     * Runs the operation under SqlTracker and returns its result and SQL.
     *
     * @param Closure(): mixed $operation
     * @return array{mixed, array<string>}
     */
    private static function track(Closure $operation): array
    {
        // With the tracker on, Bitrix reads result properties of the `true` that mysqli returns for INSERT, UPDATE and DELETE.
        $previous = set_error_handler(
            static function (int $type, string $message, string $file, int $line) use (&$previous): bool {
                if (str_ends_with($file, '/db/mysqliresult.php') && str_ends_with($message, ' on true')) {
                    return true;
                }

                return is_callable($previous) && (bool) $previous($type, $message, $file, $line);
            },
        );
        $tracker = self::$connection->startTracker(true);
        try {
            $result = $operation();
        } finally {
            self::$connection->stopTracker();
            restore_error_handler();
        }

        return [$result, array_map(static fn(SqlTrackerQuery $query): string => $query->getSql(), $tracker->getQueries())];
    }

    private function questions(): int
    {
        /** @var array{Value: string} $status */
        $status = self::$connection->query("SHOW SESSION STATUS LIKE 'Questions'")->fetch();

        return (int) $status['Value'];
    }

    private function assertServedByPrimaryKey(string $sql): void
    {
        // An INSERT has no access path to check.
        if (stripos(ltrim($sql), 'INSERT') === 0) {
            return;
        }

        /** @var list<array{key: ?string, type: ?string, Extra: ?string}> $plans */
        $plans = self::$connection->query('EXPLAIN ' . $sql)->fetchAll();
        foreach ($plans as $plan) {
            self::assertSame('PRIMARY', $plan['key'], $sql);
            self::assertNotSame('ALL', $plan['type'], $sql);
            self::assertStringNotContainsString('filesort', (string) $plan['Extra'], $sql);
        }
    }
}
