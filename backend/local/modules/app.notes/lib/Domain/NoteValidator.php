<?php

declare(strict_types=1);

namespace App\Notes\Domain;

/**
 * All validation rules of the notes API.
 */
final class NoteValidator
{
    private const int PAGE_DEFAULT_LIMIT = 20;
    private const int PAGE_MAX_LIMIT = 100;

    /**
     * @param array<mixed> $query Query string parameters.
     */
    public function validatePage(array $query): Page|ValidationErrors
    {
        $limit = self::integer($query['limit'] ?? null, self::PAGE_DEFAULT_LIMIT, 1, self::PAGE_MAX_LIMIT);
        $offset = self::integer($query['offset'] ?? null, 0, 0, PHP_INT_MAX);

        if ($limit !== null && $offset !== null) {
            return new Page($limit, $offset);
        }

        $errors = [];
        if ($limit === null) {
            $errors['limit'] = sprintf('Укажите целое число от 1 до %d', self::PAGE_MAX_LIMIT);
        }
        if ($offset === null) {
            $errors['offset'] = 'Укажите целое неотрицательное число';
        }

        return new ValidationErrors($errors);
    }

    /**
     * Returns the default for a missing value and null for an invalid one.
     */
    private static function integer(mixed $value, int $default, int $min, int $max): ?int
    {
        if ($value === null) {
            return $default;
        }

        $integer = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => $min, 'max_range' => $max]]);

        return is_int($integer) ? $integer : null;
    }
}
