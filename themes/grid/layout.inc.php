<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * for nicer (human readable) css formating add "$cfg['NiceCss'] = true;" in your config.inc.php
 * 2007-05-11: layout choosable in config.inc.php :
 * you can switch to an alternate Color/Font set simply by putting the line
 * $GLOBALS['cfg']['customGrid'] = 'tan'; or 'dark' ... (see bottom)
 * into your pma/config.inc.php or if you like the settings of the original theme:
 * $GLOBALS['cfg']['customGrid'] = 'originalColors';
 * you can add any number of different settings in the themes/grid/layout.inc.php,
 * take the 'originalColors' section as an example.
 * to use "Grid" on startup use this in your config.inc.php:
 *  if (is_dir($GLOBALS['cfg']['ThemePath'] . '/grid-x.yy')) {
 *       $GLOBALS['cfg']['ThemeDefault'] = 'grid';
 *    }
 * some bugfixes like textarea height (thx Mario Rohkrämer) ...
 *
 * 2007-02-11: grid2.9.d error.ico cursor for non Opera browsers only,
 * vertical line IE only, for pma 2.8 td{color:#000} (or whatever) is necessary!
 *
 * 2007-02-08 bug 1653769 fixed: BrowsePointerEnable, BrowseMarkerEnable
 *
 * for detailed layout configuration please refer to the css files
 *
 * comments, suggestions, bugreports are welcome:
 * http://sourceforge.net/support/tracker.php?aid=1656956
 *
 * CSS doesn't like empty values, except for Font.. ..Link.. ..Marke...
 *(a forced frame reload may be needed after changes, esp. for some Opera versions)
 *
 * @version $Id$
 * @package phpMyAdmin-theme
 * @subpackage Grid
 */

if(!defined('PMA_VERSION')) {
    die('unplanned execution path');
}

// ~~~~~~~~~~~~~ NAVIGATION frame (called "Left frame" in older versions) ~~~~~~~~~~

// navi/left frame width for index.php :
$GLOBALS['cfg']['NaviWidth'] =
empty($_COOKIE['pma_navi_width']) ? 200 : $_COOKIE['pma_navi_width'];
// 200 px: default value if cookie not found

// foreground (text) color for the navi frame
$GLOBALS['cfg']['NaviColor']   = '#000';

// Navi BackGround-Color
$GLOBALS['cfg']['NaviBGC']     = '#d6d6d6';
$GLOBALS['cfg']['Navi2ndBGC']  = '#e6e6e6';
// pmalogo background
$GLOBALS['cfg']['NaviLogoBGC'] = $GLOBALS['cfg']['NaviBGC'];

// link color
$GLOBALS['cfg']['NaviLinkColor'] = '#00a';

// foreground (text) color of the navi pointer
$GLOBALS['cfg']['NaviPointerColor'] = '#000';
// background of the navi pointer
$GLOBALS['cfg']['NaviPointerBGC']   = '#fff';

// marked item(s) IMPORTANT ! (orig: "Marked" was "Marker")

// if "LeftFrameLight" as in config.default.php :
$GLOBALS['cfg']['NaviLightMarkedBGC']   = '#ffa';
// color of the marked (visually marks selected) item
$GLOBALS['cfg']['NaviLightMarkedColor'] = '#000';

// if ! "LeftFrameLight" :
$GLOBALS['cfg']['NaviMarkedBGC']        = '#999';
$GLOBALS['cfg']['NaviMarkedColor']      = '#ff6';

// BGcolor after clicking on a link:
$GLOBALS['cfg']['NaviActiveBGC'] = '#cfc'; // '#9df';

// "zoom" factor for list items
$GLOBALS['cfg']['NaviFontPercentage'] = '90%';

// work in progress "trunk"
$GLOBALS['cfg']['NaviDblBGC'] = '#ddd';
// BGcolor ul ul:
$GLOBALS['cfg']['NaviTblBGC'] = '#eee'; //#dfdfdc'; // '#9df';

// text color of the selected database name (when showing the table list)
// $GLOBALS['cfg']['NaviDbNameColor'] = '#000';//c60
$GLOBALS['cfg']['NaviDbNameBGC']   = $GLOBALS['cfg']['NaviLightMarkedBGC'];

//$GLOBALS['cfg']['NaviFocusBGC']    = '#fd9';
//$GLOBALS['cfg']['NaviDatabaseNameColor'] = '' ; // not used

// ~~~~~~~~~~~~~~~~~           MAIN frame           ~~~~~~~~~~~~~~~~~~~~~~~~
$GLOBALS['cfg']['MainGroup'] = '#f6f6f6'; // new for pma 3
$GLOBALS['cfg']['MainGroupHeader'] = '#e6e6e6';

// foreground (text) color for the main frame
$GLOBALS['cfg']['MainColor'] = '#000';

// BackgroundColor for the main frame, different solution than in ´original´!
$GLOBALS['cfg']['MainBGC']   = '#dadada'; //e7e7e7 same as scrollbar color;

//at least 1 bit difference from $GLOBALS['cfg']['MainBGC'] to show the grid!(?)
$GLOBALS['cfg']['MainGridColor']      = '#dadadb';

// link color
$GLOBALS['cfg']['MainLinkColor']      = '#00d';
$GLOBALS['cfg']['MainLinkHoverColor'] = '#000';

// link BGcolor
// $GLOBALS['cfg']['MainLinkBGC'] = $GLOBALS['cfg']['MainBGC'];
$GLOBALS['cfg']['MainLinkHoverBGC']   = '#fff';
$GLOBALS['cfg']['MainActiveBGC']      = '#cfc';

// foreground (text) color of the pointer in browse mode
$GLOBALS['cfg']['BrowsePointerColor'] = '#000';

// background of the pointer in browse mode
$GLOBALS['cfg']['BrowsePointerBGC']   = '#dfd';#f3f3f3'; // '#cfc';

// foreground (text) color of the marker (visually marks row by clicking on it) in browse mode
$GLOBALS['cfg']['BrowseMarkerColor']  = '#000';

// background of the marker (visually marks row by clicking on it) in browse mode
$GLOBALS['cfg']['BrowseMarkerBGC']    = '#ffb'; // '#fc9';

// tab decorations
$GLOBALS['cfg']['TabBGC']             = '#f0f0ff';//??

$GLOBALS['cfg']['TabHoverColor']      = '#55f';
$GLOBALS['cfg']['TabHoverBGC']        = '#fff'; //dfd

$GLOBALS['cfg']['TabActiveColor']     = '#000';
$GLOBALS['cfg']['TabActiveBGC']       = '#ffb'; //afa

$GLOBALS['cfg']['TabBorderColor']     = '#bbb';
$GLOBALS['cfg']['TabUnderlineColor']  = '#888';

$GLOBALS['cfg']['SuccessBorderColor'] = '#0d0';

// top (former legend)
// both IE 6&7 expand the BGC too high
if ('IE' == PMA_USR_BROWSER_AGENT) {
    $GLOBALS['cfg']['BorderColor']  = '#000';
    $GLOBALS['cfg']['FieldsetBGC']  =  $GLOBALS['cfg']['MainBGC'];
    $GLOBALS['cfg']['LegendBorder'] =  false;
    $GLOBALS['cfg']['LegendColor']  = '#000';
    $GLOBALS['cfg']['LegendBGC']    = '#f3f3f3';
} else {
    $GLOBALS['cfg']['BorderColor']  = '#bbb';
    $GLOBALS['cfg']['FieldsetBGC']  = '#f3f3f3';
    $GLOBALS['cfg']['LegendBorder'] =  true;
    $GLOBALS['cfg']['LegendColor']  = '#000';
    $GLOBALS['cfg']['LegendBGC']    = '#f6f6f6';
}
$GLOBALS['cfg']['FieldsetFooterBGC'] = '#e7e7e7';

$GLOBALS['cfg']['BacktickBGC']  = '#eea';

// better readability in popup -> SQL history
$GLOBALS['cfg']['queryWindowContainerBGC'] = '#e7e7e7';

// NOTE: notice and warning colors are defined in theme_right.css.php

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
if (version_compare(PMA_VERSION, '2.9', 'lt')) {
    $GLOBALS['cfg']['LeftWidth'] = $GLOBALS['cfg']['NaviWidth'];
    $GLOBALS['cfg']['FontSize']  = '82% /*2.8*/';
}

/**
 * tables
 */

// border strenght ( e.g. .05em(min!)|1px|3pt| 0 but NOT 1)
$GLOBALS['cfg']['Border'] = '1px';

// table header,footer and "OK box" color
$GLOBALS['cfg']['ThBGC'] = '#c8c8c8';

// table header and footer background
$GLOBALS['cfg']['ThColor'] = '#000';

// table data row background
$GLOBALS['cfg']['BgOne'] = '#f7f7f7';// >=f8 = white on some displays

// table data row background, alternate
$GLOBALS['cfg']['BgTwo'] = '#fff';

// table outer border color
$GLOBALS['cfg']['TblBorderColor'] = 'blue'; // no effect?

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
$GLOBALS['cfg']['SQP']['fmtColor'] = array(
    'comment'        => '#808000',
    'comment_mysql'  => '',
    'comment_ansi'   => '',
    'comment_c'      => '',
    'digit'          => '',
    'digit_hex'      => 'teal',
    'digit_integer'  => 'teal',
    'digit_float'    => 'aqua',
    'punct'          => 'fuchsia',
    'alpha'          => '',
    'alpha_columnType'  => '#F90',
    'alpha_columnAttrib'=> '#00f',
    'alpha_reservedWord'=> '#909',
    'alpha_functionName'=> '#f00',
    'alpha_identifier'  => '#000',
    'alpha_charset'     => '#6495ed',
    'alpha_variable'    => '#800000',
    'quote'              => '#008000',
    'quote_double'      => '',
    'quote_single'      => '',
    'quote_backtick'    => '#0a0'
);

// ~~~~~~~ alternate COLOR/FONT SETS choosable in config.inc.php ~~~~~~~~~~~
// (default settings from above are simply overridden)
if (!empty($GLOBALS['cfg']['customGrid']))
{
  if ('old' == $GLOBALS['cfg']['customGrid'])
  {
    $GLOBALS['cfg']['NaviBGC']        = '#efefef';
    $GLOBALS['cfg']['MainBGC']   = '#ddd';
    $GLOBALS['cfg']['BrowseMarkerBGC']= '#ee9';
    $GLOBALS['cfg']['TblBorderColor']        = '#ddddde';
    $GLOBALS['cfg']['LegendColor']            = '#090';
    $GLOBALS['cfg']['LegendBGC']       = '#fff';
  }
  elseif ('originalColors' == $GLOBALS['cfg']['customGrid'])
  //not fully tested yet !
  {
    $GLOBALS['cfg']['NaviBGC']           = '#D0DCE0';
    $GLOBALS['cfg']['NaviLogoBGC'] = $GLOBALS['cfg']['NaviBGC'];
    $GLOBALS['cfg']['NaviPointerColor']         = '#000';
    $GLOBALS['cfg']['NaviPointerBGC']    = '#99C';
    $GLOBALS['cfg']['NaviLightMarkedColor']     = '#000';
    $GLOBALS['cfg']['NaviLightMarkedBGC']= '#fc9';
    $GLOBALS['cfg']['MainColor']                = '#000';
    $GLOBALS['cfg']['MainBGC']      = '#F5F5F5';//'MainBGC'
    $GLOBALS['cfg']['BrowsePointerColor']       = '#000';
    $GLOBALS['cfg']['BrowsePointerBGC']  = '#CFC';
    $GLOBALS['cfg']['BrowseMarkerColor']        = '#000';
    $GLOBALS['cfg']['BrowseMarkerBGC']   = '#FC9';
    $GLOBALS['cfg']['FontFamily']           = 'sans-serif';
    $GLOBALS['cfg']['FontFamilyFixed']      = 'monospace';
    $GLOBALS['cfg']['MainGridColor']        = '#F5F5F6';
    $GLOBALS['cfg']['ThBGC']         = '#D3DCE3';
    $GLOBALS['cfg']['ThColor']              = '#000';
    $GLOBALS['cfg']['BgOne']                = '#E5E5E5';
    $GLOBALS['cfg']['BgTwo']                = '#D5D5D5';
    $GLOBALS['cfg']['QueryWindowWidth']     = 600;
    $GLOBALS['cfg']['QueryWindowHeight']    = 400;
    $GLOBALS['cfg']['LegendBorder']           = 0;
    $GLOBALS['cfg']['LegendColor']            = '#000';
//    $GLOBALS['cfg']['LegendBGC']       = '#fff';//??
  }
  elseif ('tan' == $GLOBALS['cfg']['customGrid'])
  {
    $GLOBALS['cfg']['FontFamily']     = 'verdana,sans-serif';
    $GLOBALS['cfg']['NaviBGC'] = '#edb';//dfc7a0
    $GLOBALS['cfg']['Navi2ndBGC']     = '#fff';
    $GLOBALS['cfg']['NaviDblBGC']     = '#fff6ee';
        //white looks ugly, '#e7e7e7'like IE6 scrollbar Am.:gray Br.:grey
    $GLOBALS['cfg']['NaviLogoBGC'] = $GLOBALS['cfg']['NaviBGC'];
    $GLOBALS['cfg']['NaviLinkColor']       = '#00a';
    $GLOBALS['cfg']['NaviPointerColor']      = '#00f';
    $GLOBALS['cfg']['NaviPointerBGC'] = '#fff';
    $GLOBALS['cfg']['NaviLightMarkedBGC']   = '#ff9';
    $GLOBALS['cfg']['NaviMarkedColor']      = '#000';
    $GLOBALS['cfg']['NaviMarkedBGC']        = '#cb9';
    $GLOBALS['cfg']['NaviFontPercentage'] = '100%';

    $GLOBALS['cfg']['NaviDbNameColor'] = '#faa';
    $GLOBALS['cfg']['NaviDbLinkBGC'] = '#eee'; // ?
    $GLOBALS['cfg']['NaviActiveBGC'] = '#9df'; // 0
    $GLOBALS['cfg']['NaviTblBGC']     = '#fed';

    $GLOBALS['cfg']['MainBGC']  = '#edb'; //~tan
    $GLOBALS['cfg']['MainGridColor']        = '#eeddbc';
    $GLOBALS['cfg']['MainLinkHoverColor']   = '#00c';
    $GLOBALS['cfg']['MainLinkHoverBGC']     = '#fff';
//    $GLOBALS['cfg']['LegendColor']            = '#fff';
//    $GLOBALS['cfg']['LegendBGC']       = '#985';

    $GLOBALS['cfg']['BrowsePointerColor']      = '#000';
    $GLOBALS['cfg']['BrowsePointerBGC'] = '#f4f2f0'; //'#cfc';
    $GLOBALS['cfg']['BrowseMarkerColor']       = '#000';
    $GLOBALS['cfg']['BrowseMarkerBGC']  = '#ffb';

    $GLOBALS['cfg']['ThBGC']    = '#dca'; // ff9 too yellowish
    $GLOBALS['cfg']['ThColor']         = '#000';
    $GLOBALS['cfg']['BgOne']           = '#fcfaf8'; // #fffaf5 a bit too reddish
    $GLOBALS['cfg']['BgTwo']           = '#fff';
  }
  elseif ('dark' == $GLOBALS['cfg']['customGrid'])
  { //like darkblue...
    $GLOBALS['cfg']['LegendBorder'] = true;
    $GLOBALS['cfg']['LegendColor']  = '#b50';
    $GLOBALS['cfg']['LegendBGC']    = '#fff';
    $GLOBALS['cfg']['NaviColor']          = '#eee';
    $GLOBALS['cfg']['NaviBGC']            = '#669';

    $GLOBALS['cfg']['Navi2ndBGC']         = '#88c';
    $GLOBALS['cfg']['NaviDblBGC']         = '#77a';

    $GLOBALS['cfg']['NaviLinkColor']      = '#eef';
    $GLOBALS['cfg']['NaviLightMarkedBGC']   = '#99f';
    $GLOBALS['cfg']['NaviLightMarkedColor'] = '#ee0';
    $GLOBALS['cfg']['NaviMarkedColor']      = '#ff0';
    $GLOBALS['cfg']['NaviMarkedBGC']        = '#558';
    $GLOBALS['cfg']['NaviPointerColor']     = '#000';
    $GLOBALS['cfg']['NaviPointerBGC']       = '#fff';
    $GLOBALS['cfg']['NaviDbLinkBGC']  = '#77a';
    $GLOBALS['cfg']['NaviActiveBGC']  = '#ff5'; // 0
    $GLOBALS['cfg']['NaviTblBGC']     = '#77a';
    $GLOBALS['cfg']['NaviDbNameColor'] = '#000'; #66f';
    $GLOBALS['cfg']['NaviDbNameBGC']   = 0 ; // '#fff'; // effect?
  }
  elseif ('work' == $GLOBALS['cfg']['customGrid']) //funny colors ;)
  {
    $GLOBALS['cfg']['NaviBGC']        = '#d6d6d6';//Server & Database
    $GLOBALS['cfg']['NaviLogoBGC']    = $GLOBALS['cfg']['NaviBGC'];
    $GLOBALS['cfg']['Navi2ndBGC']     = '#e6e6e6';
    $GLOBALS['cfg']['NaviTblBGC']     = '#eee'; //#dfe';
    $GLOBALS['cfg']['NaviDblBGC']     = '#ddd';

    $GLOBALS['cfg']['NaviLightMarkedBGC']   = '#ffa';
    $GLOBALS['cfg']['NaviLightMarkedColor'] = '#000';
    $GLOBALS['cfg']['NaviMarkedBGC']        = '#999';
    $GLOBALS['cfg']['NaviMarkedColor']      = '#ff6';

    $GLOBALS['cfg']['NaviDbNameColor'] = '#bbf';
    $GLOBALS['cfg']['NaviDbNameBGC']   = $GLOBALS['cfg']['NaviLightMarkedBGC']; //'#ddd';
//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    $GLOBALS['cfg']['FieldsetBGC']       = '#f6f6f6';
    $GLOBALS['cfg']['FieldsetFooterBGC'] = '#d6d6d6';

    $GLOBALS['cfg']['BorderColor']  = '#bbb';
    $GLOBALS['cfg']['LegendBorder'] =  1;
    $GLOBALS['cfg']['LegendColor']  = '#000';
    $GLOBALS['cfg']['LegendBGC']    = '#f3f3f3';
    $GLOBALS['cfg']['MainLinkColor'] = '#00d';
    $GLOBALS['cfg']['MainLinkHoverColor'] = '#000';
  }
  elseif ('test' == $GLOBALS['cfg']['customGrid'])
  {
  //070812 navi pager test
    $GLOBALS['cfg']['LegendBorder'] = true;
    $GLOBALS['cfg']['LegendColor']  = '#b50';
    $GLOBALS['cfg']['LegendBGC']    = '#fff';

    $GLOBALS['cfg']['NaviColor']          = '#eee';
    $GLOBALS['cfg']['NaviBGC']            = '#669';

    $GLOBALS['cfg']['Navi2ndBGC']         = '#88c';
    $GLOBALS['cfg']['NaviDblBGC']         = '#77a';

    $GLOBALS['cfg']['NaviLinkColor']      = '#eef';
    $GLOBALS['cfg']['NaviLightMarkedBGC']   = '#99f';
    $GLOBALS['cfg']['NaviLightMarkedColor'] = '#ee0';
    $GLOBALS['cfg']['NaviMarkedColor']      = '#ff0';
    $GLOBALS['cfg']['NaviMarkedBGC']        = '#558';
    $GLOBALS['cfg']['NaviPointerColor']     = '#000';
    $GLOBALS['cfg']['NaviPointerBGC']       = '#fff';
    $GLOBALS['cfg']['NaviDbLinkBGC']  = '#77a';
    $GLOBALS['cfg']['NaviActiveBGC']  = '#99f';
    $GLOBALS['cfg']['NaviTblBGC']     = '#77a';
    $GLOBALS['cfg']['NaviDbNameColor'] = 0; #bbf';
    $GLOBALS['cfg']['NaviDbNameBGC']   = 0;
    $GLOBALS['cfg']['TabBorderColor'] = '#bbb';
    $GLOBALS['cfg']['TabUnderlineColor'] = '#777';
  }
}
?>