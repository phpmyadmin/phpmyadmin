<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id$
 * @package phpMyAdmin-theme
 * @subpackage Arctic_Ocean
 */

/**
 * for older versions
 */
$cfg['LeftPointerEnable']   = false;
$cfg['LeftWidth']           = 170;          						// left frame width
$cfg['LeftBgColor']         = '#d9e4f4';    						// background color for the left frame
$cfg['RightBgColor']        = '#ffffff';    						// background color for the right frame
$cfg['RightBgImage']        = '';                                   // path to a background image for the right frame
                                            						// (leave blank for no background image)
$cfg['LeftPointerColor']    = '#b4cae9';    						// color of the pointer in left frame
$cfg['Border']              = 0;            						// border width on tables
$cfg['ThBgcolor']           = '#e5e5e5';    						// table header row colour
$cfg['BgcolorOne']          = '#e6f0ff';    						// table data row colour
$cfg['BgcolorTwo']          = '#dbe7f9';    						// table data row colour, alternate
$cfg['BrowsePointerColor']  = '#b4cae9';    						// color of the pointer in browse mode
$cfg['BrowseMarkerColor']   = '#e9c7b4';    						// color of the marker (visually marks row
                                            						// by clicking on it) in browse mode
$cfg['QueryWindowWidth']    = 550;          						// Width of Query window
$cfg['QueryWindowHeight']   = 310;          						// Height of Query window
$cfg['SQP']['fmtColor']     = array(        						// Syntax colouring data
    'comment'            => '#999999',
    'comment_mysql'      => '',
    'comment_ansi'       => '',
    'comment_c'          => '',
    'digit'              => '',
    'digit_hex'          => 'teal',
    'digit_integer'      => 'teal',
    'digit_float'        => 'aqua',
    'punct'              => '#cc0000',
    'alpha'              => '',
    'alpha_columnType'   => '#FF9900',
    'alpha_columnAttrib' => '#0000FF',
    'alpha_reservedWord' => '#cc0000',
    'alpha_functionName' => '#000099',
    'alpha_identifier'   => 'black',
    'alpha_charset'      => '#6495ed',
    'alpha_variable'     => '#800000',
    'quote'              => '#008000',
    'quote_double'       => '#000000',
    'quote_single'       => '#000000',
    'quote_backtick'     => ''
);

/**
 * for current version
 */
// NAVI FRAME
$GLOBALS['cfg']['LeftPointerEnable']        = false;
$GLOBALS['cfg']['NaviWidth']                = 170;       // width
$GLOBALS['cfg']['NaviColor']                = '#000000'; // foreground (text) color
$GLOBALS['cfg']['NaviBackground']           = '#d9e4f4'; // background
$GLOBALS['cfg']['NaviPointerColor']         = '#000000'; // foreground (text) color of the pointer
$GLOBALS['cfg']['NaviPointerBackground']    = '#b4cae9'; // background of the pointer

// MAIN FRAME
$GLOBALS['cfg']['MainColor']                = '#333333'; // foreground (text) color for the main frame
$GLOBALS['cfg']['MainBackground']           = '#ffffff'; // background for the main frame
// for a solid vertical line, uncomment this:
//$GLOBALS['cfg']['MainBackground']           = '#ffffff url(../' . $_SESSION['PMA_Theme']->getImgPath() . 'vertical_line.png) repeat-y';
$GLOBALS['cfg']['BrowsePointerColor']       = '#000000'; // foreground (text) color of the pointer in browse mode
$GLOBALS['cfg']['BrowsePointerBackground']  = '#b4cae9'; // background of the pointer in browse mode
$GLOBALS['cfg']['BrowseMarkerColor']        = '#000000'; // foreground (text) color of the marker (visually marks row by clicking on it) in browse mode
$GLOBALS['cfg']['BrowseMarkerBackground']   = '#e9c7b4'; // background of the marker (visually marks row by clicking on it) in browse mode

// FONTS
// the font family as a valid css font family value,
// if not set the browser default will be used
// (depending on browser, DTD and system settings)
$GLOBALS['cfg']['FontFamily']               = 'Tahoma, Arial, Helvetica, sans-serif';
$GLOBALS['cfg']['FontFamilyFixed']          = '\'Courier New\', Courier, monospace'; // fixed width font family, used in textarea
$GLOBALS['cfg']['FontSize']                 = '11'; // default width of the font
$GLOBALS['cfg']['FontSizePrefix']           = 'px'; // pt (Points) | px (Pixel), default is 'pt'

// TABLES
$GLOBALS['cfg']['Border']                   = 0;         // border
$GLOBALS['cfg']['ThBackground']             = '#e5e5e5'; // table header and footer color
$GLOBALS['cfg']['ThColor']                  = '#000000'; // table header and footer background
$GLOBALS['cfg']['BgOne']                    = '#e6f0ff'; // table data row background
$GLOBALS['cfg']['BgTwo']                    = '#dbe7f9'; // table data row background, alternate

// QUERY WINDOW
$GLOBALS['cfg']['QueryWindowWidth']         = 550;       // width of Query window
$GLOBALS['cfg']['QueryWindowHeight']        = 310;       // height of Query window

// SQL PARSER SETTINGS
// Syntax colouring data
$GLOBALS['cfg']['SQP']['fmtColor']          = array(
    'comment'            => '#808000',
    'comment_mysql'      => '#999999',
    'comment_ansi'       => '#999999',
    'comment_c'          => '#999999',
    'digit'              => '#999999',
    'digit_hex'          => 'teal',
    'digit_integer'      => 'teal',
    'digit_float'        => 'aqua',
    'punct'              => 'fuchsia',
    'alpha'              => '#cc0000',
    'alpha_columnType'   => '#ff9900',
    'alpha_columnAttrib' => '#0000ff',
    'alpha_reservedWord' => '#990099',
    'alpha_functionName' => '#FF0000',
    'alpha_identifier'   => '#000000',
    'alpha_charset'      => '#6495ed',
    'alpha_variable'     => '#800000',
    'quote'              => '#008000',
    'quote_double'       => '',
    'quote_single'       => '',
    'quote_backtick'     => ''
);
?>
