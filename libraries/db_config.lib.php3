<?php
/* $Id$ */

/**
 * Database based configuration system
 * Robin Johnson <robbat2@users.sourceforge.net>
 * May 19, 2002
 */

if (!defined('PMA_DB_CONFIG_LIB_INCLUDED')) {
    define('PMA_DB_CONFIG_LIB_INCLUDED', 1);

    /**
     * Original obj2xml() function by <jgettys@gnuvox.com>
     * as found on http://www.php.net/manual/en/function.get-defined-vars.php
     * Fixed and improved by Robin Johnson <robbat2@users.sourceforge.net>
     */
    function obj2xml($v, $indent='') {
        while (list($key, $val) = each($v)) {
            if (is_string($key) && ($key == '__attr'))
                continue;
            // Check for __attr
            if (is_object($val->__attr)) {
                while (list($key2, $val2) = each($val->__attr)) {
                    $attr .= " $key2=\"$val2\"";
                }
            } else {
                $attr = '';
            }

            //preserve data type information
            $attr .= " type=\"".gettype($val)."\"";

            if (is_array($val) || is_object($val)) {
                print("$indent<$key$attr>\n");
                obj2xml($val, $indent.'  ');
                print("$indent</$key>\n");
            } else {
                if (is_string($val) && ($val == "")) {
                    print("$indent<$key$attr />\n");
                } else {
                    print("$indent<$key$attr>$val</$key>\n");
                }
            }
        }
    }


$cfg['DBConfig']['AllowUserOverride'] =
array(
"Servers/*/bookmarkdb",
"Servers/*/bookmarktable",
"Servers/*/relation",
"Servers/*/pdf_table_position",
"ShowSQL",
"Confirm",
"LeftFrameLight",
"ShowTooltip",
"ShowBlob",
"NavigationBarIconic",
"ShowAll",
"MaxRows",
"Order",
"ProtectBinary",
"ShowFunctionFields",
"LeftWidth",
"LeftBgColor",
"LeftPointerColor",
"RightBgColor",
"Border",
"ThBgcolor",
"BgcolorOne",
"BgcolorTwo",
"BrowsePointerColor",
"BrowseMarkerColor",
"TextareaCols",
"TextareaRows",
"LimitChars",
"ModifyDeleteAtLeft",
"ModifyDeleteAtRight",
"DefaultDisplay",
"RepeatCells"
)


} // $__PMA_DB_CONFIG_LIB__
