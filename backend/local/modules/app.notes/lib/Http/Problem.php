<?php

declare(strict_types=1);

namespace App\Notes\Http;

use App\Notes\Domain\ValidationErrors;
use Bitrix\Main\HttpResponse;

/**
 * Error responses in the RFC 9457 format.
 */
final class Problem
{
    public static function notFound(string $detail): HttpResponse
    {
        return self::create(404, 'Not Found', $detail);
    }

    public static function invalid(ValidationErrors $errors): HttpResponse
    {
        // An object even for numeric field names, which json_encode would turn into a list.
        return self::create(422, 'Unprocessable Content', 'Данные не прошли проверку', ['errors' => (object) $errors->errors]);
    }

    public static function internalError(): HttpResponse
    {
        return self::create(500, 'Internal Server Error', 'Не удалось выполнить запрос, попробуйте позже');
    }

    /**
     * @param array<string, mixed> $extensions
     */
    private static function create(int $status, string $title, string $detail, array $extensions = []): HttpResponse
    {
        return JsonResponse::create(
            ['type' => 'about:blank', 'title' => $title, 'status' => $status, 'detail' => $detail] + $extensions,
            $status,
            'application/problem+json',
        );
    }
}
