<?php
/**
 * derived from theme "original" pma 2.9
 * 20070211: error.ico cursor for non Opera browsers, vertical line IE only, pma 2.8 td{color:black} necessary!
 * 20070208 bug 1653769 fixed: BrowsePointerEnable, BrowseMarkerEnable
 * by windkiel (started 2006-08-19)
 * works with pma 2.8+
 * for detailed layout configuration please refer to the css files
 * comments, suggestions, bugreports are welcome:
 * http://sourceforge.net/users/windkiel/
 * no '' allowed, except for Font.. ..Link.. ..Marke...(frame reload may be needed after changes)
 */

/**
 * Navi frame (called "Left frame" in older versions)
 */

// for this theme almost all settings (except "SPQ") can be done in config.inc.php :
// like "$cfg['LeftMarkerEnable'] = false;"

// Whether to activate the Navi marker. Similar settings see libraries/config.default.php
if(!isset($GLOBALS['cfg']['LeftMarkerEnable']))
	$GLOBALS['cfg']['LeftMarkerEnable'] = $GLOBALS['cfg']['LeftPointerEnable'];

// Navi (left) frame width for index.php :
if(!isset($GLOBALS['cfg']['NaviWidth'])) $GLOBALS['cfg']['NaviWidth'] = 180; 

// backward compatibility :
if('2.8' == substr(PMA_VERSION,0,3)) $GLOBALS['cfg']['LeftWidth'] = $GLOBALS['cfg']['NaviWidth']; 

// foreground (text) color for the navi frame
if(!isset($GLOBALS['cfg']['NaviColor'])) $GLOBALS['cfg']['NaviColor'] = '#000';

// background for the navi frame
if(!isset($GLOBALS['cfg']['NaviBackground'])) $GLOBALS['cfg']['NaviBackground'] = '#D3DCE6';#dee

// link color
if(!isset($GLOBALS['cfg']['NaviLinkColor'])) $GLOBALS['cfg']['NaviLinkColor'] = '#00a';

// link background-color
if(!isset($GLOBALS['cfg']['NaviLinkBackground'])) $GLOBALS['cfg']['NaviLinkBackground'] = 'white';

// foreground (text) color of the pointer in navi frame
if(!isset($GLOBALS['cfg']['NaviPointerColor'])) $GLOBALS['cfg']['NaviPointerColor']	= '#00f';
// background of the pointer in navi frame

if(!isset($GLOBALS['cfg']['NaviPointerBackground'])) $GLOBALS['cfg']['NaviPointerBackground'] = 'white';

// color of the marked (visually marks selected) item
if(!isset($GLOBALS['cfg']['NaviMarkedColor'])) $GLOBALS['cfg']['NaviMarkedColor'] = $GLOBALS['cfg']['NaviColor'];

// background of the marked item
if( $GLOBALS['cfg']['LeftMarkerEnable'] ) {
	$GLOBALS['cfg']['NaviMarkedBackground'] = '#fc9';
} else {
	$GLOBALS['cfg']['NaviMarkedBackground'] = $GLOBALS['cfg']['NaviBackground'];
}

// "zoom" factor for list items
if(!isset($GLOBALS['cfg']['NaviFontPercentage'])) $GLOBALS['cfg']['NaviFontPercentage'] = '90%';

/**
 * main frame
 */
// foreground (text) color for the main frame
if(!isset($GLOBALS['cfg']['MainColor'])) $GLOBALS['cfg']['MainColor'] = '#000';

// BackgroundColor for the main frame, other solution than in original!
if(!isset($GLOBALS['cfg']['MainBackgroundColor'])) $GLOBALS['cfg']['MainBackgroundColor'] = '#d0d0d0';

// link color
if(!isset($GLOBALS['cfg']['MainLinkColor'])) $GLOBALS['cfg']['MainLinkColor'] = '#00d';

// link BGcolor
if(!isset($GLOBALS['cfg']['MainLinkBackground'])) $GLOBALS['cfg']['MainLinkBackground'] = '#fff';

// foreground (text) color of the pointer in browse mode
if(!isset($GLOBALS['cfg']['BrowsePointerColor'])) $GLOBALS['cfg']['BrowsePointerColor'] = '#000';

// background of the pointer in browse mode
if(!isset($GLOBALS['cfg']['BrowsePointerBackground'])) $GLOBALS['cfg']['BrowsePointerBackground'] = '#cfc';

// foreground (text) color of the marker (visually marks row by clicking on it) in browse mode
if(!isset($GLOBALS['cfg']['BrowseMarkerColor'])) $GLOBALS['cfg']['BrowseMarkerColor'] = '#000';

// background of the marker (visually marks row by clicking on it) in browse mode
if(!isset($GLOBALS['cfg']['BrowseMarkerBackground'])) $GLOBALS['cfg']['BrowseMarkerBackground'] = '#fc9';

/**
 * fonts
 */

/**
 * the font family as a valid css font family value,
 * if not set the browser default will be used
 * (depending on browser, DTD and system settings)
 */
if(!isset($GLOBALS['cfg']['FontFamily'])) $GLOBALS['cfg']['FontFamily'] = 'sans-serif'; //was:Arial
/**
 * fixed width font family, used in textarea
 */
if(!isset($GLOBALS['cfg']['FontFamilyFixed'])) $GLOBALS['cfg']['FontFamilyFixed'] = 'monospace';

/**
 * font size as a valid css font size value,
 * if not set the browser default will be used
 * (depending on browser, DTD and system settings)
 * pma >2.8 uses font size stored in cookie
 */

// for pma <2.9 :
if(!isset($GLOBALS['cfg']['FontSize'])) $GLOBALS['cfg']['FontSize'] = '90%';

/**
 * tables
 */

// border strenght ( e.g. .05em(min!)|1px|3pt| 0 but NOT 1)
if(!isset($GLOBALS['cfg']['Border'])) $GLOBALS['cfg']['Border'] = '1px';

//at least 1 bit difference from $GLOBALS['cfg']['MainBackground'] to show the grid!
if(!isset($GLOBALS['cfg']['MainGridColor'])) $GLOBALS['cfg']['MainGridColor'] = '#d0d0d1';

// table header and footer color
if(!isset($GLOBALS['cfg']['ThBackground'])) $GLOBALS['cfg']['ThBackground'] = $GLOBALS['cfg']['NaviBackground'];#dee

// table header and footer background
if(!isset($GLOBALS['cfg']['ThColor'])) $GLOBALS['cfg']['ThColor'] = '#000';

// table data row background
if(!isset($GLOBALS['cfg']['BgOne'])) $GLOBALS['cfg']['BgOne'] = '#f8f8fa';

// table data row background, alternate
if(!isset($GLOBALS['cfg']['BgTwo'])) $GLOBALS['cfg']['BgTwo'] = '#fff';

// table outer border color
//if(!isset($GLOBALS['cfg']['TblBorderColor'])) $GLOBALS['cfg']['TblBorderColor'] = 'blue';

//needed for pma2.8 only (if E_NOTICE=1 , but no effect) :
if(!isset($GLOBALS['cfg']['BgcolorOne'])) $GLOBALS['cfg']['BgcolorOne'] = '#f7f7f7';
if(!isset($GLOBALS['cfg']['BgcolorTwo'])) $GLOBALS['cfg']['BgcolorTwo'] = '#fff';

/**
 * query window
 */
// Width of Query window
if(!isset($GLOBALS['cfg']['QueryWindowWidth'])) $GLOBALS['cfg']['QueryWindowWidth'] = 600;

// Height of Query window
if(!isset($GLOBALS['cfg']['QueryWindowHeight'])) $GLOBALS['cfg']['QueryWindowHeight'] = 300;

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