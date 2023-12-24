<?php

declare(strict_types=1);

namespace PhpMyAdmin\Utils;

use PhpMyAdmin\Config;
use PhpMyAdmin\Current;

final class SessionCache
{
    private static function key(): string
    {
        $key = 'server_' . Current::$server;

        $config = Config::getInstance();
        if (isset($config->selectedServer['user'])) {
            return $key . '_' . $config->selectedServer['user'];
        }

        return $key;
    }

    public static function has(string $name): bool
    {
        return isset($_SESSION['cache'][self::key()][$name]);
    }

    public static function get(string $name, callable|null $defaultValueCallback = null): mixed
    {
        if (self::has($name)) {
            return $_SESSION['cache'][self::key()][$name];
        }

        if ($defaultValueCallback !== null) {
            $value = $defaultValueCallback();
            self::set($name, $value);

            return $value;
        }

        return null;
    }

    public static function set(string $name, mixed $value): void
    {
        $_SESSION['cache'][self::key()][$name] = $value;
    }

    public static function remove(string $name): void
    {
        unset($_SESSION['cache'][self::key()][$name]);
    }
}
