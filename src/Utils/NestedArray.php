<?php

declare(strict_types=1);

namespace PhpMyAdmin\Utils;

use function array_slice;
use function explode;
use function is_array;

/**
 * Reads and changes values in nested arrays given by a path.
 * The path is a string of keys separated by slashes,
 * eg. Servers/1/host refers to $array[Servers][1][host]
 */
final class NestedArray
{
    /**
     * Returns value of an element in $array given by $path.
     *
     * @param string  $path    path in the array
     * @param mixed[] $array   the array
     * @param mixed   $default default value
     *
     * @return mixed[]|mixed|null array element or $default
     *
     * @psalm-pure
     */
    public static function get(string $path, array $array, mixed $default = null): mixed
    {
        $value = $array;
        foreach (explode('/', $path) as $key) {
            if (! is_array($value) || ! isset($value[$key])) {
                return $default;
            }

            $value = $value[$key];
        }

        return $value;
    }

    /**
     * Stores value in an array
     *
     * @param string  $path  path in the array
     * @param mixed[] $array the array
     * @param mixed   $value value to store
     *
     * @return mixed[] the array with the value stored
     *
     * @psalm-pure
     */
    public static function set(string $path, array $array, mixed $value): array
    {
        return self::setKeys($array, explode('/', $path), $value);
    }

    /**
     * Removes value from an array
     *
     * @param string  $path  path in the array
     * @param mixed[] $array the array
     *
     * @return mixed[] the array with the value removed
     *
     * @psalm-pure
     */
    public static function remove(string $path, array $array): array
    {
        return self::removeKeys($array, explode('/', $path));
    }

    /**
     * @param mixed[]                $array
     * @param non-empty-list<string> $keys
     *
     * @return mixed[]
     *
     * @psalm-pure
     */
    private static function setKeys(array $array, array $keys, mixed $value): array
    {
        $key = $keys[0];
        $keys = array_slice($keys, 1);
        if ($keys === []) {
            $array[$key] = $value;

            return $array;
        }

        $child = $array[$key] ?? [];
        $array[$key] = self::setKeys(is_array($child) ? $child : [], $keys, $value);

        return $array;
    }

    /**
     * @param mixed[]                $array
     * @param non-empty-list<string> $keys
     *
     * @return mixed[]
     *
     * @psalm-pure
     */
    private static function removeKeys(array $array, array $keys): array
    {
        $key = $keys[0];
        $keys = array_slice($keys, 1);
        if ($keys === [] || ! isset($array[$key])) {
            unset($array[$key]);

            return $array;
        }

        if (! is_array($array[$key])) {
            return $array;
        }

        $child = self::removeKeys($array[$key], $keys);
        if ($child === []) {
            unset($array[$key]);
        } else {
            $array[$key] = $child;
        }

        return $array;
    }
}
