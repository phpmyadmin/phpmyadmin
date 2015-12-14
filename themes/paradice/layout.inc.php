<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * configures general layout
 * for detailed layout configuration please refer to the css files
 *
 * @version $Id: layout.inc.php 12 2008-05-28 20:51:41Z andyscherzinger $
 * @package phpMyAdmin-theme
 * @subpackage Paradice
 */

/**
 * navi frame
 */
// navi frame width
$GLOBALS['cfg']['NaviWidth']                = 164;

// foreground (text) color for the navi frame
$GLOBALS['cfg']['NaviColor']                = '#FFFFFF';

// background for the navi frame
$GLOBALS['cfg']['NaviBackground']           = '#3674CF';

// foreground (text) color of the pointer in navi frame
$GLOBALS['cfg']['NaviPointerColor']         = '#000000';
// background of the pointer in navi frame
$GLOBALS['cfg']['NaviPointerBackground']    = '#5796EF';
// text color of the selected database name (when showing the table list)
$GLOBALS['cfg']['NaviDatabaseNameColor']    = '#FFFFFF';

/**
 * main frame
 */
// foreground (text) color for the main frame
$GLOBALS['cfg']['MainColor']                = '#142F56';

// background for the main frame
$GLOBALS['cfg']['MainBackground']           = '#FFFFFF';
//$GLOBALS['cfg']['MainBackground']       = '#F5F5F5 url(' . $_SESSION['PMA_Theme']->getImgPath() . 'vertical_line.png) repeat-y';

// foreground (text) color of the pointer in browse mode
$GLOBALS['cfg']['BrowsePointerColor']       = '#FFFFFF';

// background of the pointer in browse mode
$GLOBALS['cfg']['BrowsePointerBackground']  = '#5287D6';

// foreground (text) color of the marker (visually marks row by clicking on it) in browse mode
$GLOBALS['cfg']['BrowseMarkerColor']        = '#000000';

// background of the marker (visually marks row by clicking on it) in browse mode
$GLOBALS['cfg']['BrowseMarkerBackground']   = '#FBAE36';

/**
 * fonts
 */
/**
 * the font family as a valid css font family value,
 * if not set the browser default will be used
 * (depending on browser, DTD and system settings)
 */
$GLOBALS['cfg']['FontFamily']           = 'Verdana, Arial, Helvetica, sans-serif';
/**
 * fixed width font family, used in textarea
 */
$GLOBALS['cfg']['FontFamilyFixed']      = '"Courier New", Courier, monospace';

/**
 * tables
 */
// border
$GLOBALS['cfg']['Border']               = 0;
// table header and footer color
$GLOBALS['cfg']['ThBackground']         = '#79A2DF';
// table header and footer background
$GLOBALS['cfg']['ThColor']              = '#000000';
// table data row background
$GLOBALS['cfg']['BgOne']                = '#EEEEEE';
// table data row background, alternate
$GLOBALS['cfg']['BgTwo']                = '#E5E5E5';

/**
 * query window
 */
// Width of Query window
$GLOBALS['cfg']['QueryWindowWidth']     = 600;
// Height of Query window
$GLOBALS['cfg']['QueryWindowHeight']    = 400;

/**
 * SQL Parser Settings
 * Syntax colouring data
 */
$GLOBALS['cfg']['SQP']['fmtColor']      = array(
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
