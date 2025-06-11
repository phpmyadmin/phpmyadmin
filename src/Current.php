<?php

declare(strict_types=1);

namespace PhpMyAdmin;

final class Current
{
    /** @psalm-var int<0, max> */
    public static int $server = 0;
    public static string $database = '';
    public static string $table = '';
    public static string $sqlQuery = '';
    public static Message|null $message = null;
    public static string $lang = 'en';
    /** @var array<string>|string|null */
    public static array|string|null $whereClause = null;
    public static string|null $displayQuery = null;
    public static string|null $dispQuery = null;
    public static string|null $charset = null;
    public static string|null $completeQuery = null;
    public static Message|string|null $displayMessage = null;
    public static int $numTables = 0;
    public static string $messageToShow = '';
}
