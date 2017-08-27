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

/* Compatibility with PHP < 5.1 or PHP without hash extension */
if (! function_exists('hash_hmac')) {
    /**
     * Generate a keyed hash value using the HMAC method
     *
     * @param string  $algo       Name of selected hashing algorithm
     * @param string  $data       Message to be hashed
     * @param string  $key        Shared secret key used for generating the HMAC variant of the message digest
     * @param boolean $raw_output When set to TRUE, outputs raw binary data. FALSE outputs lowercase hexits
     *
     * @return string
     */
    function hash_hmac($algo, $data, $key, $raw_output = false)
    {
        $algo = strtolower($algo);
        $pack = 'H' . strlen($algo('test'));
        $size = 64;
        $opad = str_repeat(chr(0x5C), $size);
        $ipad = str_repeat(chr(0x36), $size);

        if (strlen($key) > $size) {
            $key = str_pad(pack($pack, $algo($key)), $size, chr(0x00));
        } else {
            $key = str_pad($key, $size, chr(0x00));
        }

        for ($i = 0; $i < strlen($key) - 1; $i++) {
            $opad[$i] = $opad[$i] ^ $key[$i];
            $ipad[$i] = $ipad[$i] ^ $key[$i];
        }

        $output = $algo($opad . pack($pack, $algo($ipad . $data)));

        return ($raw_output) ? pack($pack, $output) : $output;
    }
}
