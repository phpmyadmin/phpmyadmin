<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Common config manipulation functions
 *
 * @package    phpMyAdmin
 */

/**
 * Returns value of an element in $array given by $path.
 * $path is a string describing position of an element in an associative array,
 * eg. Servers/1/host refers to $array[Servers][1][host]
 *
 * @param  string   $path
 * @param  array    $array
 * @param  mixed    $default
 * @return mixed    array element or $default
 */
function PMA_array_read($path, $array, $default = null)
{
    $keys = explode('/', $path);
    $value =& $array;
    foreach ($keys as $key) {
        if (!isset($value[$key])) {
            return $default;
        }
        $value =& $value[$key];
    }
    return $value;
}

/**
 * Stores value in an array
 *
 * @param  string   $path
 * @param  array    &$array
 * @param  mixed    $value
 */
function PMA_array_write($path, &$array, $value)
{
    $keys = explode('/', $path);
    $last_key = array_pop($keys);
    $a =& $array;
    foreach ($keys as $key) {
        if (!isset($a[$key])) {
            $a[$key] = array();
        }
        $a =& $a[$key];
    }
    $a[$last_key] = $value;
}

/**
 * Removes value from an array
 *
 * @param  string   $path
 * @param  array    &$array
 * @param  mixed    $value
 */
function PMA_array_remove($path, &$array)
{
    $keys = explode('/', $path);
    $keys_last = array_pop($keys);
    $path = array();
    $depth = 0;

    $path[0] =& $array;
    $found = true;
    // go as deep as required or possible
    foreach ($keys as $key) {
        if (!isset($path[$depth][$key])) {
            $found = false;
            break;
        }
        $depth++;
        $path[$depth] =& $path[$depth-1][$key];
    }
    // if element found, remove it
    if ($found) {
        unset($path[$depth][$keys_last]);
        $depth--;
    }

    // remove empty nested arrays
    for (; $depth >= 0; $depth--) {
        if (!isset($path[$depth+1]) || count($path[$depth+1]) == 0) {
            unset($path[$depth][$keys[$depth]]);
        } else {
            break;
        }
    }
}

/**
 * Returns sanitized language string, taking into account our special codes
 * for formatting. Takes variable number of arguments.
 * Based on PMA_sanitize from sanitize.lib.php.
 *
 * @param  string  $lang_key key in $GLOBALS WITHOUT 'strSetup' prefix
 * @param  mixed   $args     arguments for sprintf
 * @return string
 */
function PMA_lang($lang_key)
{
    static $search, $replace;

    // some quick cache'ing
    if ($search === null) {
        $replace_pairs = array(
            '<'         => '&lt;',
            '>'         => '&gt;',
            '[em]'      => '<em>',
            '[/em]'     => '</em>',
            '[strong]'  => '<strong>',
            '[/strong]' => '</strong>',
            '[code]'    => '<code>',
            '[/code]'   => '</code>',
            '[kbd]'     => '<kbd>',
            '[/kbd]'    => '</kbd>',
            '[br]'      => '<br />',
            '[sup]'     => '<sup>',
            '[/sup]'    => '</sup>');
        if (defined('PMA_SETUP')) {
            $replace_pairs['[a@Documentation.html'] = '[a@../Documentation.html';
        }
        $search = array_keys($replace_pairs);
        $replace = array_values($replace_pairs);
    }
    $message = isset($GLOBALS["strSetup$lang_key"]) ? $GLOBALS["strSetup$lang_key"] : $lang_key;
    $message = str_replace($search, $replace, $message);
    // replace [a@"$1"]$2[/a] with <a href="$1">$2</a>
    $message = preg_replace('#\[a@("?)([^\]]+)\1\]([^\[]+)\[/a\]#e',
        "PMA_lang_link_replace('$2', '$3')", $message);

    if (func_num_args() == 1) {
        return $message;
    } else {
        $args = func_get_args();
        array_shift($args);
        return vsprintf($message, $args);
    }
}

/**
 * Returns translated field name
 *
 * @param string $canonical_path
 * @return string
 */
function PMA_lang_name($canonical_path)
{
    $lang_key = str_replace(
    	array('Servers/1/', '/'),
    	array('Servers/', '_'),
    	$canonical_path) . '_name';
    return isset($GLOBALS["strSetup$lang_key"])
        ? $GLOBALS["strSetup$lang_key"]
        : $lang_key;
}

/**
 * Returns translated field description
 *
 * @param string $canonical_path
 * @return string
 */
function PMA_lang_desc($canonical_path)
{
    $lang_key = str_replace(
    	array('Servers/1/', '/'),
    	array('Servers/', '_'),
    	$canonical_path) . '_desc';
    return isset($GLOBALS["strSetup$lang_key"])
        ? PMA_lang($lang_key)
        : '';
}

/**
 * Wraps link in &lt;a&gt; tags and replaces argument separator in internal links
 * to the one returned by PMA_get_arg_separator()
 *
 * @param string $link
 * @param string $text
 * @return string
 */
function PMA_lang_link_replace($link, $text)
{
    static $separator;

    if (!isset($separator)) {
        $separator = PMA_get_arg_separator('html');
    }

    if (!preg_match('#^http://#', $link)) {
        $link = str_replace('&amp;', $separator, $link);
    }

    return '<a href="' . $link . '">' . $text . '</a>';
}

/**
 * Reads user preferences field names
 *
 * @param array|null $forms
 * @return array
 */
function PMA_read_userprefs_fieldnames(array $forms = null)
{
    static $names;

    // return cached results
    if ($names !== null) {
        return $names;
    }
    if (is_null($forms)) {
        $forms = array();
        include 'libraries/config/user_preferences.forms.php';
    }
    $names = array();
    foreach ($forms as $formset) {
        foreach ($formset as $form) {
            foreach ($form as $k => $v) {
                $names[] = is_int($k) ? $v : $k;
            }
        }
    }
    return $names;
}
?>