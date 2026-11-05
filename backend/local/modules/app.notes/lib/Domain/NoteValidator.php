<?php

declare(strict_types=1);

namespace App\Notes\Domain;

/**
 * All validation rules of the notes API.
 */
final class NoteValidator
{
    private const int TITLE_MAX_LENGTH = 255;
    private const int CONTENT_MAX_LENGTH = 10000;
    private const int PAGE_DEFAULT_LIMIT = 20;
    private const int PAGE_MAX_LIMIT = 100;
    private const array NOTE_FIELDS = ['title' => true, 'content' => true];

    /**
     * @param array<mixed> $body Decoded JSON object.
     */
    public function validateInput(array $body): NoteInput|ValidationErrors
    {
        $errors = [];

        $title = $body['title'] ?? null;
        $title = is_string($title) ? trim($title) : null;
        if ($title === null || $title === '') {
            $errors['title'] = 'Введите заголовок';
        } elseif (mb_strlen($title) > self::TITLE_MAX_LENGTH) {
            $errors['title'] = sprintf('Заголовок длиннее %d символов', self::TITLE_MAX_LENGTH);
        }

        $content = $body['content'] ?? null;
        if (!is_string($content)) {
            $errors['content'] = 'Текст заметки должен быть строкой';
        } elseif (mb_strlen($content) > self::CONTENT_MAX_LENGTH) {
            $errors['content'] = sprintf('Текст длиннее %d символов', self::CONTENT_MAX_LENGTH);
        }

        foreach (array_keys(array_diff_key($body, self::NOTE_FIELDS)) as $field) {
            $errors[(string) $field] = 'Неизвестное поле';
        }

        if ($errors === [] && $title !== null && is_string($content)) {
            return new NoteInput($title, $content);
        }

        return new ValidationErrors($errors);
    }

    public function validateId(string $id): int|ValidationErrors
    {
        return self::integer($id, 1, PHP_INT_MAX) ?? new ValidationErrors(['id' => 'Укажите целое положительное число']);
    }

    /**
     * @param array<mixed> $query Query string parameters.
     */
    public function validatePage(array $query): Page|ValidationErrors
    {
        $limit = self::integer($query['limit'] ?? self::PAGE_DEFAULT_LIMIT, 1, self::PAGE_MAX_LIMIT);
        // Without a cursor the page starts with the newest note.
        $before = self::integer($query['before'] ?? PHP_INT_MAX, 1, PHP_INT_MAX);

        if ($limit !== null && $before !== null) {
            return new Page($limit, $before);
        }

        $errors = [];
        if ($limit === null) {
            $errors['limit'] = sprintf('Укажите целое число от 1 до %d', self::PAGE_MAX_LIMIT);
        }
        if ($before === null) {
            $errors['before'] = 'Укажите целое положительное число';
        }

        return new ValidationErrors($errors);
    }

    /**
     * Null for anything but an integer within the range.
     */
    private static function integer(mixed $value, int $min, int $max): ?int
    {
        $integer = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => $min, 'max_range' => $max]]);

        return is_int($integer) ? $integer : null;
    }
}
