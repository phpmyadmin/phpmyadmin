<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Common config manipulation functions
 *
 * @package PhpMyAdmin
 */

/**
 * Returns sanitized language string, taking into account our special codes
 * for formatting. Takes variable number of arguments.
 * Based on PMA_sanitize from sanitize.lib.php.
 *
 * @param string $lang_key key in $GLOBALS WITHOUT 'strSetup' prefix
 * @param mixed  $args,... arguments for sprintf
 *
 * @return string
 */
function PMA_lang($lang_key, $args = null)
{
    $message = isset($GLOBALS["strConfig$lang_key"])
        ? $GLOBALS["strConfig$lang_key"] : $lang_key;

    $message = PMA_sanitize($message);

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
 * @param string $canonical_path
 * @param string $type  'name', 'desc' or 'cmt'
 * @param mixed  $default
 *
 * @return string
 */
function PMA_lang_name($canonical_path, $type = 'name', $default = 'key')
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
?>
