<?php

declare(strict_types=1);

namespace App\Notes\Domain;

final readonly class ValidationErrors
{
    /**
     * @param array<string, string> $errors A message for every invalid field.
     */
    public function __construct(
        public array $errors,
    ) {}
}
