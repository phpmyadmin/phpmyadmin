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
}
