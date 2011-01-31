<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Common config manipulation functions
 *
 * @package phpMyAdmin
 */

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
    $message = isset($GLOBALS["strConfig$lang_key"]) ? $GLOBALS["strConfig$lang_key"] : $lang_key;
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
 * Returns translated field name/description or comment
 *
 * @param string $canonical_path
 * @param string $type  'name', 'desc' or 'cmt'
 * @param mixed  $default
 * @return string
 */
function PMA_lang_name($canonical_path, $type = 'name', $default = 'key')
{
    $lang_key = str_replace(
    	array('Servers/1/', '/'),
    	array('Servers/', '_'),
    	$canonical_path) . '_' . $type;
    return isset($GLOBALS["strConfig$lang_key"])
        ? ($type == 'desc' ? PMA_lang($lang_key) : $GLOBALS["strConfig$lang_key"])
        : ($default == 'key' ? $lang_key : $default);
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

    if (!preg_match('#^https?://#', $link)) {
        $link = str_replace('&amp;', $separator, $link);
    } else {
        $link = PMA_linkURL($link);
    }

    return '<a href="' . $link . '">' . $text . '</a>';
}
?>
