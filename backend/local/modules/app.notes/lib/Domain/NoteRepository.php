<?php

declare(strict_types=1);

namespace App\Notes\Domain;

interface NoteRepository
{
    /**
     * Notes newest first.
     */
    public function list(Page $page): NoteList;
}
