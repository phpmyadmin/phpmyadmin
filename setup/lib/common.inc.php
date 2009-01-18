<?php
/**
 * Loads libraries/common.inc.php and preforms some additional actions
 *
 * @package    phpMyAdmin-setup
 * @author     Piotr Przybylski <piotrprz@gmail.com>
 * @license    http://www.gnu.org/licenses/gpl.html GNU GPL 2.0
 * @version    $Id$
 */

/**
 * Do not include full common.
 * @ignore
 */
define('PMA_MINIMUM_COMMON', TRUE);
define('PMA_SETUP', TRUE);
chdir('..');

require_once './libraries/common.inc.php';
require_once './libraries/url_generating.lib.php';
require_once './setup/lib/ConfigFile.class.php';

// use default error handler
restore_error_handler();

// Save current language in a cookie, required since we use PMA_MINIMUM_COMMON
PMA_setCookie('pma_lang', $GLOBALS['lang']);

if (!isset($_SESSION['ConfigFile'])) {
    $_SESSION['ConfigFile'] = array();
}

// allows for redirection even after sending some data
ob_start();

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
function array_read($path, $array, $default = null)
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
function array_write($path, &$array, $value)
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
function array_remove($path, &$array)
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
        $search = array_keys($replace_pairs);
        $replace = array_values($replace_pairs);
    }
    if (!isset($GLOBALS["strSetup$lang_key"])) {
        return $lang_key;
    }
    $message = str_replace($search, $replace, $GLOBALS["strSetup$lang_key"]);
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
?>
