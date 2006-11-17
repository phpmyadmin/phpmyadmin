<?php
/**
 * derived from theme "original" pma 2.9, HEAD
 * modifications by windkiel 2006-08-19 ~ 10-31 marked with "*jw"
 * works with pma 2.8 (still experimental)
 * no '' allowed, except for Font.. ..Link.. ..Marke...
 * for detailed layout configuration please refer to the css files
 * comments, suggestions, bugreports are welcome:
 * http://sourceforge.net/users/windkiel/
 */

/**
 * navi frame
 */
// navi frame width for index.php :
$GLOBALS['cfg']['NaviWidth'] = 200; //only for >= 2.9
$GLOBALS['cfg']['LeftWidth'] = 200; //backword compatibility 2.8

// foreground (text) color for the navi frame
$GLOBALS['cfg']['NaviColor']                = '#000';

// background for the navi frame
$GLOBALS['cfg']['NaviBackground']           = '#D3DCE3';#dee

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
$GLOBALS['cfg']['MainBackground']           = '#e7e7e7';

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
$GLOBALS['cfg']['FontFamily']           = 'sans-serif'; //was:Arial
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

// for pma 2.8 :
$GLOBALS['cfg']['FontSize']             = '90%';

/**
 * tables
 */
/*jw*/
// border strenght ( e.g. .05em(min!)|1px|3pt| 0 but NOT 1)
$GLOBALS['cfg']['Border'] = '1px';

//at least 1 bit difference from $GLOBALS['cfg']['MainBackground'] to show the grid!
$GLOBALS['cfg']['MainGridColor'] = '#e7e7e8';
// table header and footer color
$GLOBALS['cfg']['ThBackground'] = $GLOBALS['cfg']['NaviBackground'];#def';//'#D3DCE3';
// table header and footer background
$GLOBALS['cfg']['ThColor']              = '#000';
// table data row background
$GLOBALS['cfg']['BgOne']                = '#f7f7f7';
// table data row background, alternate
$GLOBALS['cfg']['BgTwo']                = '#fff';
// table outer border color
//$GLOBALS['cfg']['TblBorderColor'] = 'blue';//60928 test

//needed for pma2.8 only (if E_NOTICE=1 , but no effect) :
$GLOBALS['cfg']['BgcolorOne']='#f7f7f7';
$GLOBALS['cfg']['BgcolorTwo']='#fff';

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
    'alpha_columnType'   => '#F90',
    'alpha_columnAttrib' => 'blue',
    'alpha_reservedWord' => '#909',
    'alpha_functionName' => 'red',
    'alpha_identifier'   => 'black',
    'alpha_charset'      => '#6495ed',
    'alpha_variable'     => '#800000',
    'quote'              => '#008000',
    'quote_double'       => '',
    'quote_single'       => '',
    'quote_backtick'     => ''
);
?>