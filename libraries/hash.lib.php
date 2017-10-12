<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * The hash extension polyfills.
 *
 * @package PhpMyAdmin
 */

if (! defined('PHPMYADMIN')) {
    exit;
}

/* Compatibility with PHP < 5.6 */
if (! function_exists('hash_equals')) {
    /**
     * Timing attack safe string comparison
     *
     * @param string $a first string
     * @param string $b second string
     *
     * @return boolean whether they are equal
     */
    function hash_equals($a, $b) {
        $ret = strlen($a) ^ strlen($b);
        $ret |= array_sum(unpack("C*", $a ^ $b));
        return ! $ret;
    }
}
