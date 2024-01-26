<?php

declare(strict_types=1);

namespace PhpMyAdmin\Import;

final class ImportSettings
{
    public static bool $charsetConversion = false;
    public static string $charsetOfFile = '';
    public static int $readMultiply = 0;
    public static int $readLimit = 0;
    public static bool $goSql = false;
    public static bool $sqlQueryDisabled = false;
    public static int $maxSqlLength = 0;
    public static int $skipQueries = 0;
    public static int $executedQueries = 0;
    /** @var array{sql:string, error:string}[] */
    public static array $failedQueries = [];
    public static bool $timeoutPassed = false;
    public static string $importNotice = '';
    public static int $offset = 0;
    public static bool $finished = false;
    public static bool $runQuery = false;
    public static int $maximumTime = 0;
    public static int $timestamp = 0;
    public static string $message = '';
    public static string $importFile = '';
    public static string $importType = '';
    public static string $importFileName = '';
    public static string $localImportFile = '';
}
