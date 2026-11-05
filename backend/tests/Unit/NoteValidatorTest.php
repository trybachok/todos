<?php

declare(strict_types=1);

namespace App\Notes\Tests\Unit;

use App\Notes\Domain\NoteInput;
use App\Notes\Domain\NoteValidator;
use App\Notes\Domain\Page;
use App\Notes\Domain\ValidationErrors;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class NoteValidatorTest extends TestCase
{
    private NoteValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new NoteValidator();
    }

    /**
     * @return iterable<string, array{array<string, mixed>, NoteInput}>
     */
    public static function validInputs(): iterable
    {
        yield 'title is trimmed' => [
            ['title' => "  Покупки \n", 'content' => 'молоко'],
            new NoteInput('Покупки', 'молоко'),
        ];
        yield 'content may be empty' => [
            ['title' => 'Идея', 'content' => ''],
            new NoteInput('Идея', ''),
        ];
        yield 'lengths are counted in characters, limits included' => [
            ['title' => str_repeat('я', 255), 'content' => str_repeat('ж', 10000)],
            new NoteInput(str_repeat('я', 255), str_repeat('ж', 10000)),
        ];
    }

    /**
     * @param array<string, mixed> $body
     */
    #[DataProvider('validInputs')]
    public function testAcceptsValidNote(array $body, NoteInput $expected): void
    {
        self::assertEquals($expected, $this->validator->validateInput($body));
    }

    /**
     * @return iterable<string, array{array<string, mixed>, list<string>}>
     */
    public static function invalidInputs(): iterable
    {
        yield 'empty body' => [[], ['title', 'content']];
        yield 'blank title' => [['title' => " \t ", 'content' => ''], ['title']];
        yield 'title too long' => [['title' => str_repeat('я', 256), 'content' => ''], ['title']];
        yield 'title is not a string' => [['title' => 42, 'content' => ''], ['title']];
        yield 'title is null' => [['title' => null, 'content' => ''], ['title']];
        yield 'content too long' => [['title' => 'Идея', 'content' => str_repeat('ж', 10001)], ['content']];
        yield 'content is not a string' => [['title' => 'Идея', 'content' => ['text']], ['content']];
        yield 'unknown field' => [['title' => 'Идея', 'content' => '', 'id' => 7], ['id']];
    }

    /**
     * @param array<string, mixed> $body
     * @param list<string> $fields
     */
    #[DataProvider('invalidInputs')]
    public function testRejectsInvalidNote(array $body, array $fields): void
    {
        $result = $this->validator->validateInput($body);

        self::assertInstanceOf(ValidationErrors::class, $result);
        self::assertSame($fields, array_keys($result->errors));
    }

    public function testAcceptsValidId(): void
    {
        self::assertSame(1, $this->validator->validateId('1'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidIds(): iterable
    {
        yield 'zero' => ['0'];
        yield 'not a number' => ['abc'];
        yield 'fraction' => ['1.5'];
        yield 'beyond the integer range' => ['9223372036854775808'];
    }

    #[DataProvider('invalidIds')]
    public function testRejectsInvalidId(string $id): void
    {
        $result = $this->validator->validateId($id);

        self::assertInstanceOf(ValidationErrors::class, $result);
        self::assertSame(['id'], array_keys($result->errors));
    }

    /**
     * @return iterable<string, array{array<string, mixed>, Page}>
     */
    public static function validPages(): iterable
    {
        yield 'defaults' => [[], new Page(20, PHP_INT_MAX)];
        yield 'smallest page' => [['limit' => '1', 'before' => '1'], new Page(1, 1)];
        yield 'largest page' => [['limit' => '100', 'before' => '40'], new Page(100, 40)];
    }

    /**
     * @param array<string, mixed> $query
     */
    #[DataProvider('validPages')]
    public function testAcceptsValidPage(array $query, Page $expected): void
    {
        self::assertEquals($expected, $this->validator->validatePage($query));
    }

    /**
     * @return iterable<string, array{array<string, mixed>, list<string>}>
     */
    public static function invalidPages(): iterable
    {
        yield 'limit is zero' => [['limit' => '0'], ['limit']];
        yield 'limit above maximum' => [['limit' => '101'], ['limit']];
        yield 'limit is not an integer' => [['limit' => '1.5'], ['limit']];
        yield 'limit is an array' => [['limit' => ['1']], ['limit']];
        yield 'before is zero' => [['before' => '0'], ['before']];
        yield 'before is not a number' => [['before' => 'abc'], ['before']];
    }

    /**
     * @param array<string, mixed> $query
     * @param list<string> $fields
     */
    #[DataProvider('invalidPages')]
    public function testRejectsInvalidPage(array $query, array $fields): void
    {
        $result = $this->validator->validatePage($query);

        self::assertInstanceOf(ValidationErrors::class, $result);
        self::assertSame($fields, array_keys($result->errors));
    }
}
