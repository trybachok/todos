<?php

declare(strict_types=1);

use App\Notes\Http\NoteController;
use App\Notes\Http\Problem;
use Bitrix\Main\HttpResponse;
use Bitrix\Main\Routing\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes->get('/api/notes', [NoteController::class, 'list']);
    $routes->post('/api/notes', [NoteController::class, 'create']);
    $routes->get('/api/notes/{id}', [NoteController::class, 'view']);
    $routes->put('/api/notes/{id}', [NoteController::class, 'update']);
    $routes->delete('/api/notes/{id}', [NoteController::class, 'delete']);

    // Without it an unknown address falls through to the public part of the site.
    $routes->any('/api/{path}', static fn(): HttpResponse => Problem::notFound('Метод API не найден'))->where('path', '.*');
};
