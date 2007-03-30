<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * URL/hidden inputs generating.
 *
 * @version $Id$
 */

/**
 * Generates text with hidden inputs.
 *
 * @see     PMA_generate_common_url()
 * @param   string   optional database name
 * @param   string   optional table name
 * @param   int      indenting level
 *
 * @return  string   string with input fields
 *
 * @global  string   the current language
 * @global  string   the current conversion charset
 * @global  string   the current connection collation
 * @global  string   the current server
 * @global  array    the configuration array
 * @global  boolean  whether recoding is allowed or not
 *
 * @access  public
 *
 * @author  nijel
 */
function PMA_generate_common_hidden_inputs($db = '', $table = '', $indent = 0, $skip = array())
{
    if (is_array($db)) {
        $params  =& $db;
        $_indent = empty($table) ? $indent : $table;
        $_skip   = empty($indent) ? $skip : $indent;
        $indent  =& $_indent;
        $skip    =& $_skip;
    } else {
        $params = array();
        if (strlen($db)) {
            $params['db'] = $db;
        }
        if (strlen($table)) {
            $params['table'] = $table;
        }
    }

    if (! empty($GLOBALS['server'])
    &&  $GLOBALS['server'] != $GLOBALS['cfg']['ServerDefault']) {
        $params['server'] = $GLOBALS['server'];
    }
    if (empty($_COOKIE['pma_lang'])
    && ! empty($GLOBALS['lang'])) {
        $params['lang'] = $GLOBALS['lang'];
    }
    if (empty($_COOKIE['pma_charset'])
    && ! empty($GLOBALS['convcharset'])) {
        $params['convcharset'] = $GLOBALS['convcharset'];
    }
    if (empty($_COOKIE['pma_collation_connection'])
    && ! empty($GLOBALS['collation_connection'])) {
        $params['collation_connection'] = $GLOBALS['collation_connection'];
    }

    $params['token'] = $_SESSION[' PMA_token '];

    if (! is_array($skip)) {
        if (isset($params[$skip])) {
            unset($params[$skip]);
        }
    } else {
        foreach ($skip as $skipping) {
            if (isset($params[$skipping])) {
                unset($params[$skipping]);
            }
        }
    }

    $spaces = str_repeat('    ', $indent);

    $return = '';
    foreach ($params as $key => $val) {
        $return .= $spaces . '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($val) . '" />' . "\n";
    }

    return $return;
}

/**
 * Generates text with URL parameters.
 *
 * <code>
 * // note the ?
 * echo 'script.php?' . PMA_generate_common_url('mysql', 'rights');
 * // produces with cookies enabled:
 * // script.php?db=mysql&amp;table=rights
 * // with cookies disabled:
 * // script.php?server=1&amp;lang=en-utf-8&amp;db=mysql&amp;table=rights
 *
 * $params['myparam'] = 'myvalue';
 * $params['db']      = 'mysql';
 * $params['table']   = 'rights';
 * // note the missing ?
 * echo 'script.php' . PMA_generate_common_url($params);
 * // produces with cookies enabled:
 * // script.php?myparam=myvalue&amp;db=mysql&amp;table=rights
 * // with cookies disabled:
 * // script.php?server=1&amp;lang=en-utf-8&amp;myparam=myvalue&amp;db=mysql&amp;table=rights
 *
 * // note the missing ?
 * echo 'script.php' . PMA_generate_common_url();
 * // produces with cookies enabled:
 * // script.php
 * // with cookies disabled:
 * // script.php?server=1&amp;lang=en-utf-8
 * </code>
 *
 * @param   mixed    assoc. array with url params or optional string with database name
 *                   if first param is an array there is also an ? prefixed to the url
 * @param   string   optional table name only if first param is array
 * @param   string   character to use instead of '&amp;' for deviding
 *                   multiple URL parameters from each other
 *
 * @return  string   string with URL parameters
 *
 * @global  string   the current language
 * @global  string   the current conversion charset
 * @global  string   the current connection collation
 * @global  string   the current server
 * @global  array    the configuration array
 * @global  boolean  whether recoding is allowed or not
 *
 * @access  public
 *
 * @author  nijel
 */
function PMA_generate_common_url ($db = '', $table = '', $delim = '&amp;')
{
    if (is_array($db)) {
        $params =& $db;
        $delim  = empty($table) ? $delim : $table;
        $questionmark = '?';
    } else {
        $params = array();
        if (strlen($db)) {
            $params['db'] = $db;
        }
        if (strlen($table)) {
            $params['table'] = $table;
        }
        $questionmark = '';
    }

    // use seperators defined by php, but prefer ';'
    // as recommended by W3C
    $separator = PMA_get_arg_separator();

    // check wether to htmlentity the separator or not
    if ($delim === '&amp;') {
        $delim = htmlentities($separator);
    } else {
        $delim = $separator;
    }

    if (isset($GLOBALS['server'])
      && $GLOBALS['server'] != $GLOBALS['cfg']['ServerDefault']) {
        $params['server'] = $GLOBALS['server'];
    }

    if (empty($_COOKIE['pma_lang'])
      && ! empty($GLOBALS['lang'])) {
        $params['lang'] = $GLOBALS['lang'];
    }
    if (empty($_COOKIE['pma_charset'])
      && ! empty($GLOBALS['convcharset'])) {
        $params['convcharset'] = $GLOBALS['convcharset'];
    }
    if (empty($_COOKIE['pma_collation_connection'])
      && ! empty($GLOBALS['collation_connection'])) {
        $params['collation_connection'] = $GLOBALS['collation_connection'];
    }

    $params['token'] = $_SESSION[' PMA_token '];

    $param_strings = array();
    foreach ($params as $key => $val) {
        /* We ignore arrays as we don't use them! */
        if (!is_array($val)) {
            $param_strings[] = urlencode($key) . '=' . urlencode($val);
        }
    }

    if (empty($param_strings)) {
        return '';
    }

    return $questionmark . implode($delim, $param_strings);
}

/**
 * Returns url separator
 *
 * @return  string   character used for separating url parts
 *
 * @access  public
 *
 * @author  nijel
 */
function PMA_get_arg_separator() {
    // use seperators defined by php, but prefer ';'
    // as recommended by W3C
    $php_arg_separator_input = ini_get('arg_separator.input');
    if (strpos($php_arg_separator_input, ';') !== false) {
        return ';';
    } elseif (strlen($php_arg_separator_input) > 0) {
        return $php_arg_separator_input{0};
    } else {
        return '&';
    }
}

?>
