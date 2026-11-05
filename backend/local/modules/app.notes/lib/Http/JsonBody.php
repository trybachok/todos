<?php

declare(strict_types=1);

namespace App\Notes\Http;

use Bitrix\Main\HttpResponse;
use JsonException;
use stdClass;

final class JsonBody
{
    /**
     * Decodes a request body that must be a JSON object; otherwise returns the error response.
     *
     * @return array<mixed>|HttpResponse
     */
    public static function parse(string $contentType, string $body): array|HttpResponse
    {
        $mediaType = strtolower(trim(explode(';', $contentType, 2)[0]));
        if ($mediaType !== 'application/json') {
            return Problem::unsupportedMediaType();
        }

        try {
            $data = json_decode($body, false, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return Problem::badRequest();
        }

        return $data instanceof stdClass ? get_object_vars($data) : Problem::badRequest();
    }
}
