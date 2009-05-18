<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Specialized String Functions for phpMyAdmin
 *
 * Copyright 2002 Robin Johnson <robbat2@users.sourceforge.net>
 * http://www.orbis-terrarum.net/?l=people.robbat2
 *
 * Defines a set of function callbacks that have a pure C version available if
 * the "ctype" extension is available, but otherwise have PHP versions to use
 * (that are slower).
 *
 * The SQL Parser code relies heavily on these functions.
 *
 * @version $Id$
 * @package phpMyAdmin-String-MB
 */

/**
 * Returns length of string depending on current charset.
 *
 * @uses    mb_strlen()
 * @param   string   string to count
 * @return  int      string length
 * @access  public
 * @author  nijel
 * @todo rename to PM_STR_len()
 */
function PMA_strlen($string)
{
    return mb_strlen($string);
}

/**
 * Returns substring from string, works depending on current charset.
 *
 * @uses    mb_substr()
 * @param   string   string to count
 * @param   int      start of substring
 * @param   int      length of substring
 * @return  int      substring
 * @access  public
 * @author  nijel
 * @todo rename to PM_STR_sub()
 */
function PMA_substr($string, $start, $length = 2147483647)
{
    return mb_substr($string, $start, $length);
}

/**
 * returns postion of $needle in $haystack or false if not found
 *
 * @uses    mb_strpos()
 * @param   string  $needle
 * @param   string  $haystack
 * @return  integer position of $needle in $haystack or false
 */
function PMA_STR_pos($haystack, $needle, $offset = 0)
{
    return mb_strpos($haystack, $needle, $offset);
}

/**
 * returns right most postion of $needle in $haystack or false if not found
 *
 * @uses    mb_strrpos()
 * @param   string  $needle
 * @param   string  $haystack
 * @return  integer position of $needle in $haystack or false
 */
function PMA_STR_rPos($haystack, $needle, $offset = 0)
{
    return mb_strrpos($haystack, $needle, $offset);
}

?>
