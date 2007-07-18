<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Javascript escaping functions.
 *
 * @author Michal Čihař <michal@cihar.com>
 * @package phpMyAdmin
 *
 * @version $Id$
 */

/**
 * Format a string so it can be a string inside JavaScript code inside an
 * eventhandler (onclick, onchange, on..., ).
 * This function is used to displays a javascript confirmation box for
 * "DROP/DELETE/ALTER" queries.
 *
 * @uses    PMA_escapeJsString()
 * @uses    PMA_backquote()
 * @uses    is_string()
 * @uses    htmlspecialchars()
 * @uses    str_replace()
 * @param   string   $a_string          the string to format
 * @param   boolean  $add_backquotes    whether to add backquotes to the string or not
 *
 * @return  string   the formatted string
 *
 * @access  public
 */
function PMA_jsFormat($a_string = '', $add_backquotes = true)
{
    if (is_string($a_string)) {
        $a_string = htmlspecialchars($a_string);
        $a_string = PMA_escapeJsString($a_string);
        /**
         * @todo what is this good for?
         */
        $a_string = str_replace('#', '\\#', $a_string);
    }

    return (($add_backquotes) ? PMA_backquote($a_string) : $a_string);
} // end of the 'PMA_jsFormat()' function

/**
 * escapes a string to be inserted as string a JavaScript block
 * enclosed by <![CDATA[ ... ]]>
 * this requires only to escape ' with \' and end of script block
 *
 * @uses    strtr()
 * @uses    preg_replace()
 * @param   string  $string the string to be escaped
 * @return  string  the escaped string
 */
function PMA_escapeJsString($string)
{
    return preg_replace('@</script@i', '</\' + \'script',
                        strtr($string, array(
                                '\\' => '\\\\',
                                '\'' => '\\\'',
                                "\n" => '\n',
                                "\r" => '\r')));
}

?>
