<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Javascript escaping functions.
 *
 * @package PhpMyAdmin
 *
 */

/**
 * Format a string so it can be a string inside JavaScript code inside an
 * eventhandler (onclick, onchange, on..., ).
 * This function is used to displays a javascript confirmation box for
 * "DROP/DELETE/ALTER" queries.
 *
 * @param string  $a_string       the string to format
 * @param boolean $add_backquotes whether to add backquotes to the string or not
 *
 * @return string   the formatted string
 *
 * @access  public
 */
function PMA_jsFormat($a_string = '', $add_backquotes = true)
{
    $a_string = htmlspecialchars($a_string);
    $a_string = PMA_escapeJsString($a_string);
    // Needed for inline javascript to prevent some browsers
    // treating it as a anchor
    $a_string = str_replace('#', '\\#', $a_string);

    return $add_backquotes
        ? PMA\libraries\Util::backquote($a_string)
        : $a_string;
} // end of the 'PMA_jsFormat()' function

/**
 * escapes a string to be inserted as string a JavaScript block
 * enclosed by <![CDATA[ ... ]]>
 * this requires only to escape ' with \' and end of script block
 *
 * We also remove NUL byte as some browsers (namely MSIE) ignore it and
 * inserting it anywhere inside </script would allow to bypass this check.
 *
 * @param string $string the string to be escaped
 *
 * @return string  the escaped string
 */
function PMA_escapeJsString($string)
{
    return preg_replace(
        '@</script@i', '</\' + \'script',
        strtr(
            $string,
            array(
                "\000" => '',
                '\\' => '\\\\',
                '\'' => '\\\'',
                '"' => '\"',
                "\n" => '\n',
                "\r" => '\r'
            )
        )
    );
}

/**
 * Formats a value for javascript code.
 *
 * @param string $value String to be formatted.
 *
 * @return string formatted value.
 */
function PMA_formatJsVal($value)
{
    if (is_bool($value)) {
        if ($value) {
            return 'true';
        }

        return 'false';
    }

    if (is_int($value)) {
        return (int)$value;
    }

    return '"' . PMA_escapeJsString($value) . '"';
}

/**
 * Formats an javascript assignment with proper escaping of a value
 * and support for assigning array of strings.
 *
 * @param string $key    Name of value to set
 * @param mixed  $value  Value to set, can be either string or array of strings
 * @param bool   $escape Whether to escape value or keep it as it is
 *                       (for inclusion of js code)
 *
 * @return string Javascript code.
 */
function PMA_getJsValue($key, $value, $escape = true)
{
    $result = $key . ' = ';
    if (!$escape) {
        $result .= $value;
    } elseif (is_array($value)) {
        $result .= '[';
        foreach ($value as $val) {
            $result .= PMA_formatJsVal($val) . ",";
        }
        $result .= "];\n";
    } else {
        $result .= PMA_formatJsVal($value) . ";\n";
    }
    return $result;
}

/**
 * Prints an javascript assignment with proper escaping of a value
 * and support for assigning array of strings.
 *
 * @param string $key   Name of value to set
 * @param mixed  $value Value to set, can be either string or array of strings
 *
 * @return void
 */
function PMA_printJsValue($key, $value)
{
    echo PMA_getJsValue($key, $value);
}

/**
 * Formats javascript assignment for form validation api
 * with proper escaping of a value.
 *
 * @param string  $key   Name of value to set
 * @param string  $value Value to set
 * @param boolean $addOn Check if $.validator.format is required or not
 * @param boolean $comma Check if comma is required
 *
 * @return string Javascript code.
 */
function PMA_getJsValueForFormValidation($key, $value, $addOn, $comma)
{
    $result = $key . ': ';
    if ($addOn) {
        $result .= '$.validator.format(';
    }
    $result .= PMA_formatJsVal($value);
    if ($addOn) {
        $result .= ')';
    }
    if ($comma) {
        $result .= ', ';
    }
    return $result;
}

/**
 * Prints javascript assignment for form validation api
 * with proper escaping of a value.
 *
 * @param string  $key   Name of value to set
 * @param string  $value Value to set
 * @param boolean $addOn Check if $.validator.format is required or not
 * @param boolean $comma Check if comma is required
 *
 * @return void
 */
function PMA_printJsValueForFormValidation($key, $value, $addOn=false, $comma=true)
{
    echo PMA_getJsValueForFormValidation($key, $value, $addOn, $comma);
}
