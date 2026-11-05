<?php

declare(strict_types=1);

namespace App\Notes\Domain;

final readonly class Page
{
    public function __construct(
        public int $limit,
        public int $offset,
    ) {}
}
