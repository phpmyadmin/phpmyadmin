<?php
/* $Id$ */


if (!defined('__OB_LIB__')) {
    define('__OB_LIB__', 1);

  # Output buffer functions for phpMyAdmin

  # Copyright 2001 Jeremy Brand <jeremy@nirvani.net>
  # http://www.jeremybrand.com/Jeremy/Brand/Jeremy_Brand.html

  # Check for all the needed functions for output buffering
  # Make some wrappers for the top and bottoms of our files.

  function out_buffer_mode_get()
  # This will be used eventually to support more modes.  It is
  # needed because both header and footer functions must know
  # what each other is doing.
  {
    if (@function_exists('ob_start'))
      $mode = 1;
    else
      $mode = 0;

    /**
     * If a user sets the output_handler in php.ini to ob_gzhandler, then
     * any right frame file in phpMyAdmin will not be handled properly by the
     * browser. My fix was to check the ini file within the
     * out_buffer_mode_get() function.
     *
     * (Patch by Garth Gillespie, modified by Marc Delisle)
     */
    if (@function_exists('ini_get')) {
        if (@ini_get('output_handler') == 'ob_gzhandler') {
            $mode = 0;
        }
    } else {
        if (@get_cfg_var('output_handler') == 'ob_gzhandler') {
            $mode = 0;
        }
    }
    // End patch

    # Zero (0) is no mode or in other words output buffering is OFF.

    # Follow 2^0, 2^1, 2^2, 2^3 type values for the modes.
    # Usefull if we ever decide to combine modes.  Then a bitmask
    # field of the sum of all modes will be the natural choice.

    header("X-ob_mode: $mode");
  
    return $mode;
  }

  # This function will need to run at the top of all pages if 
  # output buffering is turned on.  It also needs to be passed $mode
  # from the out_buffer_mode_get() function or it will be useless.
  function out_buffer_pre($mode)
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
    }
    return $retval;
  }
  
  # This function will need to run at the bottom of all pages if
  # output buffering is turned on.  It also needs to be passed $mode
  # from the out_buffer_mode_get() function or it will be useless.
  function out_buffer_post($mode)
  {
    switch($mode)
    {
      case 1:
        # This output buffer doesn't need a footer.
        $retval = TRUE;
        break;

      case 0:
        $retval = FALSE;

      // loic1: php3 fix
      default:
        $retval = FALSE;
    }
    return $retval;
  }

} // $__OB_LIB__

?>
