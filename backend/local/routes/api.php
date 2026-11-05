<?php

declare(strict_types=1);

use App\Notes\Http\NoteController;
use App\Notes\Http\Problem;
use Bitrix\Main\HttpResponse;
use Bitrix\Main\Routing\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    // Unsigned INT of app_notes_note.ID, at most 10 digits.
    $id = '[1-9][0-9]{0,9}';

    $routes->get('/api/notes', [NoteController::class, 'list']);
    $routes->post('/api/notes', [NoteController::class, 'create']);
    $routes->get('/api/notes/{id}', [NoteController::class, 'view'])->where('id', $id);
    $routes->put('/api/notes/{id}', [NoteController::class, 'update'])->where('id', $id);
    $routes->delete('/api/notes/{id}', [NoteController::class, 'delete'])->where('id', $id);

    // Without it an unknown address falls through to the public part of the site.
    $routes->any('/api/{path}', static fn(): HttpResponse => Problem::notFound('Метод API не найден'))->where('path', '.*');
};
