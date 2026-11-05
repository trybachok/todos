<?php

declare(strict_types=1);

namespace App\Notes\Http;

use Bitrix\Main\HttpResponse;
use Bitrix\Main\Web\Json;

final class JsonResponse
{
    /**
     * Engine\Response\Json is not used: it appends its own Content-Type on send.
     *
     * @param array<mixed> $data
     */
    public static function create(array $data, int $status = 200, string $mediaType = 'application/json'): HttpResponse
    {
        return (new HttpResponse())
            ->setStatus((string) $status)
            ->addHeader('Content-Type', "{$mediaType}; charset=UTF-8")
            ->setContent(Json::encode($data));
    }
}
