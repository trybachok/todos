<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure;

use Bitrix\Main\Diag\ExceptionHandlerFormatter;
use Bitrix\Main\Diag\ExceptionHandlerLog;

/**
 * Sends Bitrix exceptions to the PHP error log, which php-fpm forwards to the container output.
 * FileExceptionHandlerLog cannot write there: PHP fails to open /proc/self/fd/2 when it is a pipe.
 */
final class ErrorLog extends ExceptionHandlerLog
{
    /**
     * @param array<mixed> $options
     */
    public function initialize(array $options): void {}

    /**
     * @param \Throwable $exception
     * @param int $logType
     */
    public function write($exception, $logType): void
    {
        /** @var string $type */
        $type = static::logTypeToString($logType);
        error_log($type . ': ' . ExceptionHandlerFormatter::format($exception));
    }
}
