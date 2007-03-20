<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Output buffer functions for phpMyAdmin
 *
 * Copyright 2001 Jeremy Brand <jeremy@nirvani.net>
 * http://www.jeremybrand.com/Jeremy/Brand/Jeremy_Brand.html
 *
 * Check for all the needed functions for output buffering
 * Make some wrappers for the top and bottoms of our files.
 *
 * @version $Id$
 */

/**
 * This function be used eventually to support more modes.  It is needed
 * because both header and footer functions must know what each other is
 * doing.
 *
 * @uses    $GLOBALS['cfg']['OBGzip']
 * @uses    function_exists()
 * @uses    ini_get()
 * @uses    ob_get_level()
 * @uses    header()
 * @staticvar integer remember last calculated value
 * @return  integer  the output buffer mode
 */
function PMA_outBufferModeGet()
{
    static $mode = null;

    if (null !== $mode) {
        return $mode;
    }

    $mode = 0;

    if ($GLOBALS['cfg']['OBGzip'] && function_exists('ob_start')) {
        if (ini_get('output_handler') == 'ob_gzhandler') {
            // If a user sets the output_handler in php.ini to ob_gzhandler, then
            // any right frame file in phpMyAdmin will not be handled properly by
            // the browser. My fix was to check the ini file within the
            // PMA_outBufferModeGet() function.
            //
            // (Patch by Garth Gillespie, modified by Marc Delisle)
            $mode = 0;
        } elseif (function_exists('ob_get_level') && ob_get_level() > 0) {
            // If output buffering is enabled in php.ini it's not possible to
            // add the ob_gzhandler without a warning message from php 4.3.0.
            // Being better safe than sorry, check for any existing output handler
            // instead of just checking the 'output_buffering' setting.
            $mode = 0;
        } else {
            $mode = 1;
        }
    }

    // Zero (0) is no mode or in other words output buffering is OFF.
    // Follow 2^0, 2^1, 2^2, 2^3 type values for the modes.
    // Usefull if we ever decide to combine modes.  Then a bitmask field of
    // the sum of all modes will be the natural choice.

    header('X-ob_mode: ' . $mode);

    return $mode;
} // end of the 'PMA_outBufferModeGet()' function


/**
 * This function will need to run at the top of all pages if output
 * output buffering is turned on.  It also needs to be passed $mode from
 * the PMA_outBufferModeGet() function or it will be useless.
 *
 * @uses    PMA_outBufferModeGet()
 * @uses    ob_start()
 * @param   integer  $mode  DEPRECATED
 * @return  boolean  whether output buffering is enabled or not
 */
function PMA_outBufferPre($mode = null)
{
    switch(PMA_outBufferModeGet())
    {
        case 1:
            ob_start('ob_gzhandler');
            $retval = true;
            break;

        case 0:
        default:
            $retval = false;
            break;
    } // end switch

    return $retval;
} // end of the 'PMA_outBufferPre()' function


/**
 * This function will need to run at the bottom of all pages if output
 * buffering is turned on.  It also needs to be passed $mode from the
 * PMA_outBufferModeGet() function or it will be useless.
 *
 * @uses    PMA_outBufferModeGet()
 * @uses    ob_flush()
 * @uses    flush()
 * @param   integer  $mode  DEPRECATED
 * @return  boolean  whether data has been send from the buffer or not
 */
function PMA_outBufferPost($mode = null)
{
    switch(PMA_outBufferModeGet())
    {
        case 1:
            # This output buffer doesn't need a footer.
            ob_flush();
            $retval = true;
            break;

        case 0:
        default:
            flush();
            $retval = false;
            break;
    } // end switch

    return $retval;
} // end of the 'PMA_outBufferPost()' function

?>
