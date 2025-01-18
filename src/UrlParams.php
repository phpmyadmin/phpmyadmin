<?php
/**
 * Static class for URL params
 */

declare(strict_types=1);

namespace PhpMyAdmin;

class UrlParams
{
    /** @var array<string, bool|int|string> $params */
    public static array $params = [];
    public static string $goto = '';
    public static string $back = '';
}
