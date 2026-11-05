<?php

declare(strict_types=1);

namespace App\Notes\Domain;

/**
 * Note data that passed validation.
 */
final readonly class NoteInput
{
    public function __construct(
        public string $title,
        public string $content,
    ) {}
}
