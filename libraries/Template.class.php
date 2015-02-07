<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Main interface for database interactions
 *
 * @package PhpMyAdmin-Template
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Helper class for building HTML
 *
 * @package PhpMyAdmin-Template
 */
class PMA_Template
{
    /**
     * A helper method for building a HTML element string
     *
     * @param string $name
     * @param array $attrs
     * @param array $children
     * @return string Generated HTML string
     */
    public static function element($name, $attrs = array(), $children = '')
    {
        $result = "<$name";
        foreach ($attrs as $key => $value) {
            if (!$value) {
                continue;
            }
            $result += " $key";
            if (is_string($value)) {
                $result .= '="'.htmlspecialchars($value).'"';
            }
        }
        $result .= ">$children</$name>";
        return $result;
    }

    /**
     * A helper method that invokes the callback when given value is true
     *
     * @param mixed $condition
     * @param callable $callback
     * @return string Generated HTML string
     */
    public static function when($condition, $callback)
    {
        if ($condition) {
            return call_user_func($callback);
        }
        return '';
    }

    /**
     * Helper method that invokes the callback with parameters for each key
     * value pair in the given array
     *
     * @param array $array
     * @param callable $callback
     * @return string Generated HTML string
     */
    public static function each($array, $callback)
    {
        $result = '';
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                call_user_func($callback, $value, $key);
            }
        }
        return $result;
    }
}