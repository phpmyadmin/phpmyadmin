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
}
