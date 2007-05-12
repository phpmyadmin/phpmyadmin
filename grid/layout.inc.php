<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * 2007-05-11: grid-2.10  layout choosable in config.inc.php see bottom
 * you can switch to an alternate Color/Font set simply by putting the line
 * $cfg['customGrid'] = 'tan';
 * into your pma/config.inc.php or if you like the settings of the original theme:
 * $cfg['customGrid'] = 'originalColors';
 * you can add any number of different settings in the themes/grid/layout.inc.php,
 * take the 'test' section as an example.
 * some bugfixes like textarea height (thx Mario Rohkrämer) ...
 *
 * by windkiel (started 2006-08-19) derived from theme "original"
 * 2007-02-11: grid2.9.d error.ico cursor for non Opera browsers,
 * vertical line IE only, for pma 2.8 td{color:black} is necessary!
 * 2007-02-08 bug 1653769 fixed: BrowsePointerEnable, BrowseMarkerEnable
 *
 * for detailed layout configuration please refer to the css files
 * comments, suggestions, bugreports are welcome:
 * https://sourceforge.net/tracker/index.php?func=detail&aid=1656956&group_id=23067&atid=689412
 *
 * CSS doesn't like empty values, except for Font.. ..Link.. ..Marke...
 *(a forced frame reload may be needed after changes, esp. for some Opera versions)
 *
 * @version $Id$
 * @package phpMyAdmin-theme
 * @subpackage Grid
 */


/**
 * ~~~~~~~~~~~~~ NAVIGATION frame (called "Left frame" in older versions) ~~~~~~~~~~
 */

// navi/left frame width for index.php :
$GLOBALS['cfg']['NaviWidth'] = 200;

// foreground (text) color for the navi frame
$GLOBALS['cfg']['NaviColor'] = '#000';

// background
$GLOBALS['cfg']['NaviBackground'] = '#e7e7e7';

// link color
$GLOBALS['cfg']['NaviLinkColor'] = '#00a';

// link background-color
$GLOBALS['cfg']['NaviLinkBackground'] = 'white';

// foreground (text) color of the navi pointer
$GLOBALS['cfg']['NaviPointerColor']    = '#00f';

// background of the navi pointer
$GLOBALS['cfg']['NaviPointerBackground'] = 'white';

// color of the marked (visually marks selected) item
$GLOBALS['cfg']['NaviMarkedColor'] = $GLOBALS['cfg']['NaviColor'];

// background of the marked item
$GLOBALS['cfg']['NaviMarkedBackground'] = '#fafaaa';

// "zoom" factor for list items
$GLOBALS['cfg']['NaviFontPercentage'] = '90%';

/**
 * ~~~~~~~~~~~~~~~~~           MAIN frame           ~~~~~~~~~~~~~~~~~~~~~~~~
 */
// BackgroundColor for the main frame, different solution than in ´original´!
$GLOBALS['cfg']['MainBackgroundColor']	= '#efefef';

//at least 1 bit difference from $GLOBALS['cfg']['MainBackground'] to show the grid!(?)
$GLOBALS['cfg']['MainGridColor']		= '#efefee';

// foreground (text) color for the main frame
$GLOBALS['cfg']['MainColor'] = '#000';

// link color
$GLOBALS['cfg']['MainLinkColor'] = '#00d';

// link BGcolor
$GLOBALS['cfg']['MainLinkBackground'] = '#fff';

// foreground (text) color of the pointer in browse mode
$GLOBALS['cfg']['BrowsePointerColor'] = '#000';

// background of the pointer in browse mode
$GLOBALS['cfg']['BrowsePointerBackground'] = '#cfc';

// foreground (text) color of the marker (visually marks row by clicking on it) in browse mode
$GLOBALS['cfg']['BrowseMarkerColor'] = '#000';

// background of the marker (visually marks row by clicking on it) in browse mode
$GLOBALS['cfg']['BrowseMarkerBackground'] = '#ffb'; #fc9';

/**
 * fonts
 */

/**
 * the font family as a valid css font family value,
 * if not set the browser default will be used
 * (depending on browser, DTD and system settings)
 */
$GLOBALS['cfg']['FontFamily'] = 'sans-serif'; 

/**
 * fixed width font family, used in textarea
 */
$GLOBALS['cfg']['FontFamilyFixed'] = 'monospace';

/**
 * font size as a valid css font size value,
 * if not set the browser default will be used
 * (depending on browser, DTD and system settings)
 * pma >2.8 uses font size stored in cookie
 */
// backward compatibility :
if(version_compare(PMA_VERSION, '2.9', 'lt')) {
    $GLOBALS['cfg']['LeftWidth'] = $GLOBALS['cfg']['NaviWidth'];
    $GLOBALS['cfg']['FontSize']  = '90%';
}

/**
 * tables
 */

// border strenght ( e.g. .05em(min!)|1px|3pt| 0 but NOT 1)
$GLOBALS['cfg']['Border'] = '.1em';

// table header,footer and "OK box" color
$GLOBALS['cfg']['ThBackground'] = '#ddd';

// table header and footer background
$GLOBALS['cfg']['ThColor'] = '#000';

// table data row background
$GLOBALS['cfg']['BgOne'] = '#f5f5f5';

// table data row background, alternate
$GLOBALS['cfg']['BgTwo'] = '#fff';

// table outer border color
$GLOBALS['cfg']['TblBorderColor'] = 'blue';

/**
 * query window
 */
// Width of Query window
$GLOBALS['cfg']['QueryWindowWidth'] = 600;

// Height of Query window
$GLOBALS['cfg']['QueryWindowHeight'] = 300;

/**
 * SQL Parser Settings
 * Syntax colouring data
 */
$GLOBALS['cfg']['SQP']['fmtColor']      = array(
    'comment'        => '#808000',
    'comment_mysql'      => '',
    'comment_ansi'       => '',
    'comment_c'      => '',
    'digit'          => '',
    'digit_hex'      => 'teal',
    'digit_integer'      => 'teal',
    'digit_float'    => 'aqua',
    'punct'          => 'fuchsia',
    'alpha'          => '',
    'alpha_columnType'   => '#F90',
    'alpha_columnAttrib' => 'blue',
    'alpha_reservedWord' => '#909',
    'alpha_functionName' => 'red',
    'alpha_identifier'   => 'black',
    'alpha_charset'      => '#6495ed',
    'alpha_variable'     => '#800000',
    'quote'          => '#008000',
    'quote_double'       => '',
    'quote_single'       => '',
    'quote_backtick'     => ''
);

// Settings from .../phpmyadmin/config.inc.php
// See libraries/config.default.php for similar settings

// ~~~~~~~ alternate COLOR/FONT SETS choosable in config.inc.php ~~~~~~~~~~~
// (default settings from above are simply overridden)
if (isset($GLOBALS['cfg']['customGrid']))
{
  if('old' == $GLOBALS['cfg']['customGrid'])
  {
    $GLOBALS['cfg']['NaviBackground']        = '#efefef';
    $GLOBALS['cfg']['MainBackgroundColor']   = '#ddd';
    $GLOBALS['cfg']['BrowseMarkerBackground']= '#ee9';
    $GLOBALS['cfg']['TblBorderColor']        = '#ddddde';
  }
  elseif('originalColors' == $GLOBALS['cfg']['customGrid'])
  {
    $GLOBALS['cfg']['NaviBackground']           = '#D0DCE0';
    $GLOBALS['cfg']['NaviPointerColor']         = '#000';
    $GLOBALS['cfg']['NaviPointerBackground']    = '#99C';
    $GLOBALS['cfg']['NaviMarkedColor']     = '#000';
    $GLOBALS['cfg']['NaviMarkedBackground']= '#99c';
    $GLOBALS['cfg']['MainColor']                = '#000';
    $GLOBALS['cfg']['MainBackgroundColor']      = '#F5F5F5';//'MainBackground'
    $GLOBALS['cfg']['BrowsePointerColor']       = '#000';
    $GLOBALS['cfg']['BrowsePointerBackground']  = '#CFC';
    $GLOBALS['cfg']['BrowseMarkerColor']        = '#000';
    $GLOBALS['cfg']['BrowseMarkerBackground']   = '#FC9';
    $GLOBALS['cfg']['FontFamily']           = 'sans-serif';
    $GLOBALS['cfg']['FontFamilyFixed']      = 'monospace';
    $GLOBALS['cfg']['Border']               = 0;
    $GLOBALS['cfg']['ThBackground']         = '#D3DCE3';
    $GLOBALS['cfg']['ThColor']              = '#000';
    $GLOBALS['cfg']['BgOne']                = '#E5E5E5';
    $GLOBALS['cfg']['BgTwo']                = '#D5D5D5';
    $GLOBALS['cfg']['QueryWindowWidth']     = 600;
    $GLOBALS['cfg']['QueryWindowHeight']    = 400;
  }
  elseif('tan' == $GLOBALS['cfg']['customGrid'])
  {
    $GLOBALS['cfg']['FontFamily']          = 'verdana,sans-serif';
    $GLOBALS['cfg']['NaviBackground']      = '#dfc7a0';//white looks ugly, '#e7e7e7'like IE6 scrollbar Am.:gray Br.:grey
    $GLOBALS['cfg']['NaviLinkColor']       = '#00a';
    $GLOBALS['cfg']['NaviLinkBackground']  = '#fff';
    $GLOBALS['cfg']['NaviPointerColor']      = '#00f';
    $GLOBALS['cfg']['NaviPointerBackground'] = '#fff'; //'#d9f5ff'; ~cyan/tuerkis
    $GLOBALS['cfg']['NaviMarkedColor']     = '#000';
    $GLOBALS['cfg']['NaviMarkedBackground']= '#ff9';
    $GLOBALS['cfg']['NaviFontPercentage']  = '90%';

    $GLOBALS['cfg']['MainBackgroundColor'] = '#edb'; //~tan
    $GLOBALS['cfg']['MainGridColor']       = '#eeddbc';
    $GLOBALS['cfg']['MainLinkColor']       = '#00c';
    $GLOBALS['cfg']['MainLinkBackground']  = '#fff';

    $GLOBALS['cfg']['BrowsePointerColor']      = '#000';
    $GLOBALS['cfg']['BrowsePointerBackground'] = '#cfc';
    $GLOBALS['cfg']['BrowseMarkerColor']       = '#000';
    $GLOBALS['cfg']['BrowseMarkerBackground']  = '#ffb';
    $GLOBALS['cfg']['ThBackground']    = '#dca'; // ff9 too yellowish
    $GLOBALS['cfg']['ThColor']         = '#000';
    $GLOBALS['cfg']['BgOne']           = '#fffcfa';#fffaf5 a bit too reddish
    $GLOBALS['cfg']['BgTwo']           = '#fff';
  }
}
?>