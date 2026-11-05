<?php

declare(strict_types=1);

namespace App\Notes\Domain;

/**
 * Up to $limit notes with ids below $before, newest first.
 */
final readonly class Page
{
    public function __construct(
        public int $limit,
        public int $before,
    ) {}
}
