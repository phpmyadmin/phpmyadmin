<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


/**
 * Output buffer functions for phpMyAdmin
 *
 * Copyright 2001 Jeremy Brand <jeremy@nirvani.net>
 * http://www.jeremybrand.com/Jeremy/Brand/Jeremy_Brand.html
 *
 * Check for all the needed functions for output buffering
 * Make some wrappers for the top and bottoms of our files.
 */

/**
 * This function be used eventually to support more modes.  It is needed
 * because both header and footer functions must know what each other is
 * doing.
 *
 * @return  integer  the output buffer mode
 */
function PMA_outBufferModeGet()
{
    if (@function_exists('ob_start')) {
        $mode = 1;
    } else {
        $mode = 0;
    }

    // If a user sets the output_handler in php.ini to ob_gzhandler, then
    // any right frame file in phpMyAdmin will not be handled properly by
    // the browser. My fix was to check the ini file within the
    // PMA_outBufferModeGet() function.
    //
    // (Patch by Garth Gillespie, modified by Marc Delisle)
    if (@ini_get('output_handler') == 'ob_gzhandler') {
        $mode = 0;
    }
    if (@get_cfg_var('output_handler') == 'ob_gzhandler') {
        $mode = 0;
    }
    // End patch

    // If output buffering is enabled in php.ini it's not possible to
    // add the ob_gzhandler without a warning message from php 4.3.0.
    // Being better safe than sorry, check for any existing output handler
    // instead of just checking the 'output_buffering' setting.
    //
    if (@function_exists('ob_get_level')) {
        if (ob_get_level() > 0) {
            $mode = 0;
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
 * @param   integer  the output buffer mode
 *
 * @return  boolean  whether output buffering is enabled or not
 */
function PMA_outBufferPre($mode)
{
    switch($mode)
    {
        case 1:
            ob_start('ob_gzhandler');
            $retval = TRUE;
            break;

        case 0:
            $retval = FALSE;
            break;

        // loic1: php3 fix
        default:
            $retval = FALSE;
            break;
    } // end switch

    return $retval;
} // end of the 'PMA_outBufferPre()' function


/**
 * This function will need to run at the bottom of all pages if output
 * buffering is turned on.  It also needs to be passed $mode from the
 * PMA_outBufferModeGet() function or it will be useless.
 *
 * @param   integer  the output buffer mode
 *
 * @return  boolean  whether data has been send from the buffer or not
 */
function PMA_outBufferPost($mode)
{
    switch($mode)
    {
        case 1:
            # This output buffer doesn't need a footer.
            $retval = TRUE;
            break;

        case 0:
            $retval = FALSE;
            break;

        // loic1: php3 fix
        default:
            $retval = FALSE;
            break;
    } // end switch

    return $retval;
} // end of the 'PMA_outBufferPost()' function

?>
