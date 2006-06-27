<?php
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2004 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 3.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/3_0.txt.                                  |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Aidan Lister <aidan@php.net>                                |
// +----------------------------------------------------------------------+
//
// $Id: var_export.php,v 1.15 2005/12/05 14:24:27 aidan Exp $


/**
 * Replace var_export()
 *
 * @category    PHP
 * @package     PHP_Compat
 * @link        http://php.net/function.var_export
 * @author      Aidan Lister <aidan@php.net>
 * @version     $Revision: 1.15 $
 * @since       PHP 4.2.0
 * @require     PHP 4.0.0 (user_error)
 */
if (!function_exists('var_export')) {
    function var_export($var, $return = false, $level = 0)
    {
        // Init
        $indent      = '  ';
        $doublearrow = ' => ';
        $lineend     = ",\n";
        $stringdelim = '\'';
        $newline     = "\n";
        $find        = array(null, '\\', '\'');
        $replace     = array('NULL', '\\\\', '\\\'');
        $out         = '';
        
        // Indent
        $level++;
        for ($i = 1, $previndent = ''; $i < $level; $i++) {
            $previndent .= $indent;
        }

        // Handle each type
        switch (gettype($var)) {
            // Array
            case 'array':
                $out = 'array (' . $newline;
                foreach ($var as $key => $value) {
                    // Key
                    if (is_string($key)) {
                        // Make key safe
                        for ($i = 0, $c = count($find); $i < $c; $i++) {
                            $var = str_replace($find[$i], $replace[$i], $var);
                        }
                        $key = $stringdelim . $key . $stringdelim;
                    }
                    
                    // Value
                    if (is_array($value)) {
                        $export = var_export($value, true, $level);
                        $value = $newline . $previndent . $indent . $export;
                    } else {
                        $value = var_export($value, true, $level);
                    }

                    // Piece line together
                    $out .= $previndent . $indent . $key . $doublearrow . $value . $lineend;
                }

                // End string
                $out .= $previndent . ')';
                break;

            // String
            case 'string':
                // Make the string safe
                for ($i = 0, $c = count($find); $i < $c; $i++) {
                    $var = str_replace($find[$i], $replace[$i], $var);
                }
                $out = $stringdelim . $var . $stringdelim;
                break;

            // Number
            case 'integer':
            case 'double':
                $out = (string) $var;
                break;
            
            // Boolean
            case 'boolean':
                $out = $var ? 'true' : 'false';
                break;

            // NULLs
            case 'NULL':
            case 'resource':
                $out = 'NULL';
                break;

            // Objects
            case 'object':
                // Start the object export
                $out = $newline . $previndent . 'class ' . get_class($var) . ' {' . $newline;

                // Export the object vars
                foreach (get_object_vars($var) as $key => $val) {
                    $out .= $previndent . '  var $' . $key . ' = ';
                    if (is_array($val)) {
                        $export = var_export($val, true, $level);
                        $out .= $newline . $previndent . $indent .  $export  . ';' . $newline;
                    } else {
                        $out .= var_export($val, true, $level) . ';' . $newline;
                    }
                }
                $out .= $previndent . '}';
                break;
        }

        // Method of output
        if ($return === true) {
            return $out;
        } else {
            echo $out;
        }
    }
}

?>
