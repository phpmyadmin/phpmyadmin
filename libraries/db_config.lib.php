<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Database based configuration system
 * Robin Johnson <robbat2@users.sourceforge.net>
 * May 19, 2002
 */

if (!defined('PMA_DB_CONFIG_LIB_INCLUDED')) {
    define('PMA_DB_CONFIG_LIB_INCLUDED', 1);

    /**
     * Converts attributes of an object to xml code
     *
     * Original obj2xml() function by <jgettys@gnuvox.com>
     * as found on http://www.php.net/manual/en/function.get-defined-vars.php
     * Fixed and improved by Robin Johnson <robbat2@users.sourceforge.net>
     *
     * @param   object  the source
     * @param   string  identication
     *
     * @access  public
     */
    function obj2xml($v, $indent = '') {
        $attr = '';
        while (list($key, $val) = each($v)) {
            if (is_string($key) && ($key == '__attr')) {
                continue;
            }

            // Check for __attr
            if (is_object($val->__attr)) {
                while (list($key2, $val2) = each($val->__attr)) {
                    $attr .= " $key2=\"$val2\"";
                }
            } else {
                $attr     = '';
            }

            // Preserve data type information
            $attr .= ' type="' . gettype($val) . '"';

            if (is_array($val) || is_object($val)) {
                echo "$indent<$key$attr>\n";
                obj2xml($val, $indent . '  ');
                echo "$indent</$key>\n";
            } else {
                if (is_string($val) && ($val == '')) {
                    echo "$indent<$key$attr />\n";
                } else {
                    echo "$indent<$key$attr>$val</$key>\n";
                }
            }
        } // end while
    } // end of the "obj2xml()" function


    $cfg['DBConfig']['AllowUserOverride'] = array(
        'Servers/*/bookmarkdb',
        'Servers/*/bookmarktable',
        'Servers/*/relation',
        'Servers/*/pdf_table_position',
        'ShowSQL',
        'Confirm',
        'LeftFrameLight',
        'ShowTooltip',
        'ShowBlob',
        'NavigationBarIconic',
        'ShowAll',
        'MaxRows',
        'Order',
        'ProtectBinary',
        'ShowFunctionFields',
        'LeftWidth',
        'LeftBgColor',
        'LeftPointerColor',
        'RightBgColor',
        'Border',
        'ThBgcolor',
        'BgcolorOne',
        'BgcolorTwo',
        'BrowsePointerColor',
        'BrowseMarkerColor',
        'TextareaCols',
        'TextareaRows',
        'LimitChars',
        'ModifyDeleteAtLeft',
        'ModifyDeleteAtRight',
        'DefaultDisplay',
        'RepeatCells'
    );

} // $__PMA_DB_CONFIG_LIB__
