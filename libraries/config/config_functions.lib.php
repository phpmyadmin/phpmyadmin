<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Common config manipulation functions
 *
 * @package PhpMyAdmin
 */
use PMA\libraries\Sanitize;

/**
 * Returns sanitized language string, taking into account our special codes
 * for formatting. Takes variable number of arguments.
 * Based on Sanitize::sanitize from sanitize.lib.php.
 *
 * @param string $lang_key key in $GLOBALS WITHOUT 'strSetup' prefix
 *
 * @return string
 */
function PMA_lang($lang_key)
{
    $message = isset($GLOBALS["strConfig$lang_key"])
        ? $GLOBALS["strConfig$lang_key"] : $lang_key;

    $message = Sanitize::sanitize($message);

    if (func_num_args() == 1) {
        return $message;
    } else {
        $args = func_get_args();
        array_shift($args);
        return vsprintf($message, $args);
    }
}

/**
 * Returns translated field name/description or comment
 *
 * @param string $canonical_path path to handle
 * @param string $type           'name', 'desc' or 'cmt'
 * @param mixed  $default        default value
 *
 * @return string
 */
function PMA_langName($canonical_path, $type = 'name', $default = 'key')
{
    $lang_key = str_replace(
        array('Servers/1/', '/'),
        array('Servers/', '_'),
        $canonical_path
    ) . '_' . $type;
    return isset($GLOBALS["strConfig$lang_key"])
        ? ($type == 'desc' ? PMA_lang($lang_key) : $GLOBALS["strConfig$lang_key"])
        : ($default == 'key' ? $lang_key : $default);
}
