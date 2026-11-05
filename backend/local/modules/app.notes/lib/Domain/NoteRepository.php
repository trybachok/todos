<?php

declare(strict_types=1);

namespace App\Notes\Domain;

interface NoteRepository
{
    /**
     * Notes newest first.
     */
    public function list(Page $page): NoteList;

    public function find(int $id): ?Note;

    public function create(NoteInput $input): Note;

    /**
     * Returns null when there is no such note.
     */
    public function update(int $id, NoteInput $input): ?Note;

    /**
     * Returns false when there is no such note.
     */
    public function delete(int $id): bool;
}
