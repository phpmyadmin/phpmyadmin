<?php
/** 
 * derived from original layout cvs60811
 * modifications by windkiel 20060819/0917 marked with "*jw"
 * works with pma >= 2.7
 * no '' allowed, except for Font.. ..Link.. ..Marke...
 * reuse original pics if img folder lacking
 * for detailed layout configuration please refer to the css files
 */

/**
 * navi frame
 */
// navi frame width
$GLOBALS['cfg']['NaviWidth']                = 200;
//backword compatibility for index.php <pma2.9
#$cfg['LeftWidth'] = $GLOBALS['cfg']['NaviWidth'];
$GLOBALS['cfg']['LeftWidth'] = $GLOBALS['cfg']['NaviWidth'];

// foreground (text) color for the navi frame
$GLOBALS['cfg']['NaviColor']                = '#000';

// background for the navi frame
$GLOBALS['cfg']['NaviBackground']           = '#D3DCE3';//'#eee';

// link color
$GLOBALS['cfg']['NaviLinkColor']        = '#00a';/*jw*/

// link background-color
$GLOBALS['cfg']['NaviLinkBackground']       = 'white';/*jw*/

// foreground (text) color of the pointer in navi frame
$GLOBALS['cfg']['NaviPointerColor']         = '#00f';
// background of the pointer in navi frame

$GLOBALS['cfg']['NaviPointerBackground']    = 'white';

// color of the marked (visually marks selected) item
$GLOBALS['cfg']['NaviMarkedColor'] = $GLOBALS['cfg']['NaviColor'];/*jw*/

// background of the marked item
$GLOBALS['cfg']['NaviMarkedBackground']     = '#fc9';

// "zoom" factor for list items
$GLOBALS['cfg']['NaviFontPercentage']       = '90%';/*jw*/

/**
 * main frame
 */
// foreground (text) color for the main frame
$GLOBALS['cfg']['MainColor']                = '#000';

// background for the main frame
$GLOBALS['cfg']['MainBackground']           = '#ccc';

if ( PMA_USR_BROWSER_AGENT != 'MOZILLA' ) { 	/*jw index.php: NO frameborder="0"*/
$GLOBALS['cfg']['MainBackground'] .=
' url(../' . $_SESSION['PMA_Theme']->getImgPath() . 'vertical_line.png) repeat-y';
}
// link color
$GLOBALS['cfg']['MainLinkColor']       = '#00d';/*jw*/

// link BGcolor
$GLOBALS['cfg']['MainLinkBackground']       = '#fff';/*jw*/

// foreground (text) color of the pointer in browse mode
$GLOBALS['cfg']['BrowsePointerColor']       = '#000';

// background of the pointer in browse mode
$GLOBALS['cfg']['BrowsePointerBackground']  = '#cfc';

// foreground (text) color of the marker (visually marks row by clicking on it) in browse mode
$GLOBALS['cfg']['BrowseMarkerColor']        = '#000';

// background of the marker (visually marks row by clicking on it) in browse mode
$GLOBALS['cfg']['BrowseMarkerBackground']   = '#fc9';

/**
 * fonts
 */

/**
 * the font family as a valid css font family value,
 * if not set the browser default will be used
 * (depending on browser, DTD and system settings)
 */
$GLOBALS['cfg']['FontFamily']           = 'Arial, sans-serif';
/**
 * fixed width font family, used in textarea
 */
$GLOBALS['cfg']['FontFamilyFixed']      = 'monospace';

/**
 * font size as a valid css font size value,
 * if not set the browser default will be used
 * (depending on browser, DTD and system settings)
 * pma >2.8 uses font size stored in cookie
 */

// for pma <= 2.8 :
$GLOBALS['cfg']['FontSize']             = '80%';

/**
 * tables
 */
/*jw*/
// border strenght ONLY FF(Gecko, e.g. .05em(min!)|1px|3pt| 0 but NOT 1)
$GLOBALS['cfg']['Border'] = '1px';
//at least 1 bit difference from $GLOBALS['cfg']['MainBackground'] to show the grid!?
$GLOBALS['cfg']['MainGridColor'] = '#cccccd';

// table header and footer color
$GLOBALS['cfg']['ThBackground']         = '#D3DCE3';
// table header and footer background
$GLOBALS['cfg']['ThColor']              = '#000';
// table data row background
$GLOBALS['cfg']['BgOne']                = '#f7f7f7';#E5E5E5';
// table data row background, alternate
$GLOBALS['cfg']['BgTwo']                = 'white';//'#D5D5D5';//'#ffff99';

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