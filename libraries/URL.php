<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Static methods for URL/hidden inputs generating
 *
 * @package PhpMyAdmin
 */
namespace PMA\libraries;

/**
 * Static methods for URL/hidden inputs generating
 *
 * @package PhpMyAdmin
 */
class URL
{
    /**
     * Generates text with hidden inputs.
     *
     * @param string|array $db     optional database name
     *                             (can also be an array of parameters)
     * @param string       $table  optional table name
     * @param int          $indent indenting level
     * @param string|array $skip   do not generate a hidden field for this parameter
     *                             (can be an array of strings)
     *
     * @see URL::getCommon()
     *
     * @return string   string with input fields
     *
     * @access  public
     */
    public static function getHiddenInputs($db = '', $table = '',
        $indent = 0, $skip = array()
    ) {
        if (is_array($db)) {
            $params  =& $db;
            $_indent = empty($table) ? $indent : $table;
            $_skip   = empty($indent) ? $skip : $indent;
            $indent  =& $_indent;
            $skip    =& $_skip;
        } else {
            $params = array();
            if (strlen($db) > 0) {
                $params['db'] = $db;
            }
            if (strlen($table) > 0) {
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

        return URL::getHiddenFields($params);
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
     * echo URL::getHiddenFields($values);
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
    public static function getHiddenFields($values, $pre = '')
    {
        $fields = '';

        /* Always include token in plain forms */
        if ($pre === '') {
            $values['token'] = $_SESSION[' PMA_token '];
        }

        foreach ($values as $name => $value) {
            if (! empty($pre)) {
                $name = $pre . '[' . $name . ']';
            }

            if (is_array($value)) {
                $fields .= URL::getHiddenFields($value, $name);
            } else {
                // do not generate an ending "\n" because
                // URL::getHiddenInputs() is sometimes called
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
     * $params['myparam'] = 'myvalue';
     * $params['db']      = 'mysql';
     * $params['table']   = 'rights';
     * // note the missing ?
     * echo 'script.php' . URL::getCommon($params);
     * // produces with cookies enabled:
     * // script.php?myparam=myvalue&amp;db=mysql&amp;table=rights
     * // with cookies disabled:
     * // script.php?server=1&amp;lang=en&amp;myparam=myvalue&amp;db=mysql
     * // &amp;table=rights
     *
     * // note the missing ?
     * echo 'script.php' . URL::getCommon();
     * // produces with cookies enabled:
     * // script.php
     * // with cookies disabled:
     * // script.php?server=1&amp;lang=en
     * </code>
     *
     * @param mixed  $params  optional, Contains an associative array with url params
     * @param string $divider optional character to use instead of '?'
     *
     * @return string   string with URL parameters
     * @access  public
     */
    public static function getCommon($params = array(), $divider = '?')
    {
        return htmlspecialchars(
            URL::getCommonRaw($params, $divider)
        );
    }

    /**
     * Generates text with URL parameters.
     *
     * <code>
     * $params['myparam'] = 'myvalue';
     * $params['db']      = 'mysql';
     * $params['table']   = 'rights';
     * // note the missing ?
     * echo 'script.php' . URL::getCommon($params);
     * // produces with cookies enabled:
     * // script.php?myparam=myvalue&amp;db=mysql&amp;table=rights
     * // with cookies disabled:
     * // script.php?server=1&amp;lang=en&amp;myparam=myvalue&amp;db=mysql
     * // &amp;table=rights
     *
     * // note the missing ?
     * echo 'script.php' . URL::getCommon();
     * // produces with cookies enabled:
     * // script.php
     * // with cookies disabled:
     * // script.php?server=1&amp;lang=en
     * </code>
     *
     * @param mixed  $params  optional, Contains an associative array with url params
     * @param string $divider optional character to use instead of '?'
     *
     * @return string   string with URL parameters
     * @access  public
     */
    public static function getCommonRaw($params = array(), $divider = '?')
    {
        $separator = URL::getArgSeparator();

        // avoid overwriting when creating navi panel links to servers
        if (isset($GLOBALS['server'])
            && $GLOBALS['server'] != $GLOBALS['cfg']['ServerDefault']
            && ! isset($params['server'])
            && ! defined('PMA_SETUP')
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

        $query = http_build_query($params, null, $separator);

        if ($divider != '?' || strlen($query) > 0) {
            return $divider . $query;
        }

        return '';
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
     * @return string  character used for separating url parts usually ; or &
     * @access  public
     */
    public static function getArgSeparator($encode = 'none')
    {
        static $separator = null;
        static $html_separator = null;

        if (null === $separator) {
            // use separators defined by php, but prefer ';'
            // as recommended by W3C
            // (see https://www.w3.org/TR/1999/REC-html401-19991224/appendix
            // /notes.html#h-B.2.2)
            $arg_separator = ini_get('arg_separator.input');
            if (mb_strpos($arg_separator, ';') !== false) {
                $separator = ';';
            } elseif (strlen($arg_separator) > 0) {
                $separator = $arg_separator{0};
            } else {
                $separator = '&';
            }
            $html_separator = htmlentities($separator);
        }

        switch ($encode) {
        case 'html':
            return $html_separator;
        case 'text' :
        case 'none' :
        default :
            return $separator;
        }
    }
}



