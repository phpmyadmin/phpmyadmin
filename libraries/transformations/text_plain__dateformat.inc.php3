<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Plugin function TEMPLATE (Garvin Hicking).
 * -----------------------------------------
 *
 * For instructions, read the libraries/transformations/README file.
 *
 * The string ENTER_FILENAME_HERE shall be substituted with the filename without the '.inc.php3'
 * extension. For further information regarding naming conventions see the README file.
 */

if (!defined('PMA_TRANSFORMATION_TEXT_PLAIN__DATEFORMAT')){
    define('PMA_TRANSFORMATION_TEXT_PLAIN__DATEFORMAT', 1);
    
    function PMA_transformation_text_plain__dateformat($buffer, $options = array()) {
        // possibly use a global transform and feed it with special options:
        // include('./libraries/transformations/global.inc.php3');
        
        // further operations on $buffer using the $options[] array.
        if (!isset($options[0]) || $options[0] == '') {
            $options[0] = 0;
        }
        
        if (!isset($options[1]) || $options[1] == '') {
            $options[1] = $GLOBALS['datefmt'];
        }
        
        $timestamp = -1;
        if (strstr($buffer, ':')) {
            $timestamp = strtotime($buffer);
        } elseif (strlen($buffer) == 14 && eregi('^[0-9]*$', $buffer)) {
            $d = array();
            $d['year']    = substr($buffer, 0, 4);
            $d['month']   = substr($buffer, 4, 2);
            $d['day']     = substr($buffer, 6, 2);
            $d['hour']    = substr($buffer, 8, 2);
            $d['minute']  = substr($buffer, 10, 2);
            $d['second']  = substr($buffer, 12, 2);
            $timestamp    = mktime($d['hour'], $d['minute'], $d['second'], $d['month'], $d['day'], $d['year']);
        }

        if ($timestamp != -1) {
            $timestamp -= $options[0] * 60 * 60;
            $buffer = PMA_localisedDate($timestamp, $options[1]);
        }
        
        return $buffer;
    }
}
