<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * configures general layout
 * for detailed layout configuration please refer to the css files
 *
 * @version $Id$
 * @package phpMyAdmin-theme
 * @subpackage Aqua_Brushed
 */

/**
 * navi frame
 */
// navi frame width
$GLOBALS['cfg']['NaviWidth']            = 185;

// foreground (text) color for the navi frame
$GLOBALS['cfg']['NaviColor']            = '#000000';

// background for the navi frame
$GLOBALS['cfg']['NaviBackground']       = '#DDDDDD fixed url(' . $_SESSION['PMA_Theme']->getImgPath() . 'RightBg.png)';

// color of the pointer in navi frame
$GLOBALS['cfg']['NaviPointerColor']     = '#9999CC';

/**
 * main frame
 */
// foreground (text) color for the main frame
$GLOBALS['cfg']['MainColor']            = '#000000';

// background for the main frame
$GLOBALS['cfg']['MainBackground']       = '#ffffff fixed url(' . $_SESSION['PMA_Theme']->getImgPath() . 'RightBg.png)';

// color of the pointer in browse mode
$GLOBALS['cfg']['BrowsePointerColor']   = '#BDC5CC';

// color of the marker (visually marks row by clicking on it) in browse mode
$GLOBALS['cfg']['BrowseMarkerColor']    = '#CCCCCC';

/**
 * fonts
 */
/**
 * the font family as a valid css font family value,
 * if not set the browser default will be used
 * (depending on browser, DTD and system settings)
 */
$GLOBALS['cfg']['FontFamily']           = '';
/**
 * fixed width font family, used in textarea
 */
$GLOBALS['cfg']['FontFamilyFixed']      = 'monospace';
/**
 * font size as a valid css font size value,
 * if not set the browser default will be used
 * (depending on browser, DTD and system settings)
 */
$GLOBALS['cfg']['FontSize']             = '8pt';

/**
 * tables
 */
// border
$GLOBALS['cfg']['Border']               = 0;
// table header and footer color
$GLOBALS['cfg']['ThBackground']         = '#B5BDC7 repeat-x top url(' . $_SESSION['PMA_Theme']->getImgPath() . 'tbl_header.png)';
// table header and footer background
$GLOBALS['cfg']['ThColor']              = '#000000';
// table data row background
$GLOBALS['cfg']['BgOne']                = '#EFEFEF';
// table data row background, alternate
$GLOBALS['cfg']['BgTwo']                = '#E5E5E5';

/**
 * query window
 */
// Width of Query window
$GLOBALS['cfg']['QueryWindowWidth']    = 550;
// Height of Query window
$GLOBALS['cfg']['QueryWindowHeight']   = 310;

/**
 * SQL Parser Settings
 * Syntax colouring data
 */
$GLOBALS['cfg']['SQP']['fmtColor']     = array(
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
