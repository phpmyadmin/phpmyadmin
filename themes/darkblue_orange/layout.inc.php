<?php
/**
 * configures general layout
 * for detailed layout configuration please refer to the css files
 */

/**
 * navi frame
 */
$GLOBALS['cfg']['LeftWidth']            = 180;          // navi frame width
/* colors */
$GLOBALS['cfg']['LeftColor']            = '#ffffff';    // foreground (text) color for the navi frame
$GLOBALS['cfg']['LeftBgColor']          = '#666699';    // background color for the navi frame
$GLOBALS['cfg']['LeftPointerColor']     = '#9999CC';    // color of the pointer in navi frame

/**
 * main frame
 */
$GLOBALS['cfg']['RightColor']           = '#000000';    // foreground (text) color for the Main frame
$GLOBALS['cfg']['RightBgColor']         = '#FFFFFF';    // background color for the Main frame

/**
 * path to a background image for the Main frame
 * (leave blank for no background image)
 */
$GLOBALS['cfg']['RightBgImage']         = '';

/**
 * fonts
 */
/**
 * the font family as a valid css font family value,
 * if not set the browser default will be used
 * (depending on browser, DTD and system settings)
 */
$GLOBALS['cfg']['FontFamily']          = 'Verdana, Arial, Helvetica, sans-serif';
/**
 * fixed width font family, used in textarea
 */
$GLOBALS['cfg']['FontFamilyFixed']     = '';
/**
 * font size as a valid css font size value,
 * if not set the browser default will be used
 * (depending on browser, DTD and system settings)
 */
$GLOBALS['cfg']['FontSize']            = '10px';

/**
 * tables
 */
$GLOBALS['cfg']['Border']              = 0;            // border width on tables
$GLOBALS['cfg']['ThBgcolor']           = '#ff9900';    // table header row colour
$GLOBALS['cfg']['BgcolorOne']          = '#E5E5E5';    // table data row colour
$GLOBALS['cfg']['BgcolorTwo']          = '#D5D5D5';    // table data row colour, alternate
$GLOBALS['cfg']['BrowsePointerColor']  = '#CCFFCC';    // color of the pointer in browse mode
$GLOBALS['cfg']['BrowseMarkerColor']   = '#FFCC99';    // color of the marker (visually marks row
                                            // by clicking on it) in browse mode
/**
 * query window
 */
$GLOBALS['cfg']['QueryWindowWidth']    = 600;          // Width of Query window
$GLOBALS['cfg']['QueryWindowHeight']   = 400;          // Height of Query window

/**
 * SQL Parser Settings
 */
$GLOBALS['cfg']['SQP']['fmtColor']     = array(        // Syntax colouring data
    'comment'            => '#808000',
    'comment_mysql'      => '',
    'comment_ansi'       => '',
    'comment_c'          => '',
    'digit'              => '',
    'digit_hex'          => 'teal',
    'digit_integer'      => 'teal',
    'digit_float'        => 'aqua',
    'punct'              => 'fuchsia',
    'alpha'              => '',
    'alpha_columnType'   => '#FF9900',
    'alpha_columnAttrib' => '#0000FF',
    'alpha_reservedWord' => '#990099',
    'alpha_functionName' => '#FF0000',
    'alpha_identifier'   => 'black',
    'alpha_charset'      => '#6495ed',
    'alpha_variable'     => '#800000',
    'quote'              => '#008000',
    'quote_double'       => '',
    'quote_single'       => '',
    'quote_backtick'     => ''
);
?>
