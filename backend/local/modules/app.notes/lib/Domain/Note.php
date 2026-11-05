<?php

declare(strict_types=1);

namespace App\Notes\Domain;

use DateTimeImmutable;

final readonly class Note extends NoteSummary
{
    public function __construct(
        int $id,
        string $title,
        public string $content,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ) {
        parent::__construct($id, $title, $createdAt, $updatedAt);
    }
}
