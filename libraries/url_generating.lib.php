<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * URL/hidden inputs generating.
 *
 * @package PhpMyAdmin
 */

/**
 * Generates text with hidden inputs.
 *
 * @param string $db     optional database name
 *                       (can also be an array of parameters)
 * @param string $table  optional table name
 * @param int    $indent indenting level
 * @param string $skip   do not generate a hidden field for this parameter
 *                       (can be an array of strings)
 *
 * @see PMA_generate_common_url()
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
        && $GLOBALS['server'] != $GLOBALS['cfg']['ServerDefault']
    ) {
        $params['server'] = $GLOBALS['server'];
    }
    if (empty($_COOKIE['pma_lang']) && ! empty($GLOBALS['lang'])) {
        $params['lang'] = $GLOBALS['lang'];
    }
    if (empty($_COOKIE['pma_collation_connection'])
        && ! empty($GLOBALS['collation_connection'])
    ) {
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

    return PMA_getHiddenFields($params);
}

/**
 * create hidden form fields from array with name => value
 *
 * <code>
 * $values = array(
 *     'aaa' => aaa,
 *     'bbb' => array(
 *          'bbb_0',
 *          'bbb_1',
 *     ),
 *     'ccc' => array(
 *          'a' => 'ccc_a',
 *          'b' => 'ccc_b',
 *     ),
 * );
 * echo PMA_getHiddenFields($values);
 *
 * // produces:
 * <input type="hidden" name="aaa" Value="aaa" />
 * <input type="hidden" name="bbb[0]" Value="bbb_0" />
 * <input type="hidden" name="bbb[1]" Value="bbb_1" />
 * <input type="hidden" name="ccc[a]" Value="ccc_a" />
 * <input type="hidden" name="ccc[b]" Value="ccc_b" />
 * </code>
 *
 * @param array  $values hidden values
 * @param string $pre    prefix
 *
 * @return string form fields of type hidden
 */
function PMA_getHiddenFields($values, $pre = '')
{
    $fields = '';

    foreach ($values as $name => $value) {
        if (! empty($pre)) {
            $name = $pre. '[' . $name . ']';
        }

        if (is_array($value)) {
            $fields .= PMA_getHiddenFields($value, $name);
        } else {
            // do not generate an ending "\n" because
            // PMA_generate_common_hidden_inputs() is sometimes called
            // from a JS document.write()
            $fields .= '<input type="hidden" name="' . htmlspecialchars($name)
                . '" value="' . htmlspecialchars($value) . '" />';
        }
    }

    return $fields;
}

/**
 * Generates text with URL parameters.
 *
 * <code>
 * // OLD derepecated style
 * // note the ?
 * echo 'script.php?' . PMA_generate_common_url('mysql', 'rights');
 * // produces with cookies enabled:
 * // script.php?db=mysql&amp;table=rights
 * // with cookies disabled:
 * // script.php?server=1&amp;lang=en&amp;db=mysql&amp;table=rights
 *
 * // NEW style
 * $params['myparam'] = 'myvalue';
 * $params['db']      = 'mysql';
 * $params['table']   = 'rights';
 * // note the missing ?
 * echo 'script.php' . PMA_generate_common_url($params);
 * // produces with cookies enabled:
 * // script.php?myparam=myvalue&amp;db=mysql&amp;table=rights
 * // with cookies disabled:
 * // script.php?server=1&amp;lang=en&amp;myparam=myvalue&amp;db=mysql&amp;table=rights
 *
 * // note the missing ?
 * echo 'script.php' . PMA_generate_common_url();
 * // produces with cookies enabled:
 * // script.php
 * // with cookies disabled:
 * // script.php?server=1&amp;lang=en
 * </code>
 *
 * @param mixed  assoc. array with url params or optional string with database name
 *               if first param is an array there is also an ? prefixed to the url
 *
 * @param string - if first param is array: 'html' to use htmlspecialchars()
 *               on the resulting URL (for a normal URL displayed in HTML)
 *               or something else to avoid using htmlspecialchars() (for
 *               a URL sent via a header); if not set,'html' is assumed
 *               - if first param is not array:  optional table name
 *
 * @param string - if first param is array: optional character to
 *               use instead of '?'
 *               - if first param is not array: optional character to use
 *               instead of '&amp;' for dividing URL parameters
 *
 * @return  string   string with URL parameters
 * @access  public
 */
function PMA_generate_common_url()
{
    $args = func_get_args();

    if (isset($args[0]) && is_array($args[0])) {
        // new style
        $params = $args[0];

        if (isset($args[1])) {
            $encode = $args[1];
        } else {
            $encode = 'html';
        }

        if (isset($args[2])) {
            $questionmark = $args[2];
        } else {
            $questionmark = '?';
        }
    } else {
        // old style

        if (PMA_isValid($args[0])) {
            $params['db'] = $args[0];
        }

        if (PMA_isValid($args[1])) {
            $params['table'] = $args[1];
        }

        if (isset($args[2]) && $args[2] !== '&amp;') {
            $encode = 'text';
        } else {
            $encode = 'html';
        }

        $questionmark = '';
    }

    $separator = PMA_get_arg_separator();

    if (isset($GLOBALS['server'])
        && $GLOBALS['server'] != $GLOBALS['cfg']['ServerDefault']
        // avoid overwriting when creating navi panel links to servers
        && ! isset($params['server'])
    ) {
        $params['server'] = $GLOBALS['server'];
    }

    if (empty($_COOKIE['pma_lang']) && ! empty($GLOBALS['lang'])) {
        $params['lang'] = $GLOBALS['lang'];
    }
    if (empty($_COOKIE['pma_collation_connection'])
        && ! empty($GLOBALS['collation_connection'])
    ) {
        $params['collation_connection'] = $GLOBALS['collation_connection'];
    }

    if (isset($_SESSION[' PMA_token '])) {
        $params['token'] = $_SESSION[' PMA_token '];
    }

    if (empty($params)) {
        return '';
    }

    $query = $questionmark . http_build_query($params, null, $separator);

    if ($encode === 'html') {
        $query = htmlspecialchars($query);
    }

    return $query;
}

/**
 * Returns url separator
 *
 * extracted from arg_separator.input as set in php.ini
 * we do not use arg_separator.output to avoid problems with &amp; and &
 *
 * @param string $encode whether to encode separator or not,
 * currently 'none' or 'html'
 *
 * @return  string  character used for separating url parts usally ; or &
 * @access  public
 */
function PMA_get_arg_separator($encode = 'none')
{
    static $separator = null;

    if (null === $separator) {
        // use seperators defined by php, but prefer ';'
        // as recommended by W3C
        // (see http://www.w3.org/TR/1999/REC-html401-19991224/appendix/notes.html#h-B.2.2)
        $php_arg_separator_input = ini_get('arg_separator.input');
        if (strpos($php_arg_separator_input, ';') !== false) {
            $separator = ';';
        } elseif (strlen($php_arg_separator_input) > 0) {
            $separator = $php_arg_separator_input{0};
        } else {
            $separator = '&';
        }
    }

    switch ($encode) {
    case 'html':
        return htmlentities($separator);
        break;
    case 'text' :
    case 'none' :
    default :
        return $separator;
    }
}

?>
