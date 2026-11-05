<?php

declare(strict_types=1);

namespace App\Notes\Domain;

use DateTimeImmutable;

/**
 * A note as shown in the list, without its content.
 */
readonly class NoteSummary
{
    public function __construct(
        public int $id,
        public string $title,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {}
}
