<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * navigation css file from theme Grid
 *
 * @version $Id$
 * @package phpMyAdmin-theme
 * @subpackage Grid
 */

define('_NaviGridVersion', 'navi Grid-2.11 070902');

if (!defined('PMA_MINIMUM_COMMON')) {
    die( '/* ' . _NaviGridVersion . ' illegal execution path */');
}

if (!isset($GLOBALS['cfg']['LeftMarkerEnable'])) {
    $GLOBALS['cfg']['LeftMarkerEnable'] = $GLOBALS['cfg']['BrowseMarkerEnable'];
}

if ($GLOBALS['cfg']['LeftFrameLight']) {
    $GLOBALS['cfg']['NaviMarkedColor']  = $GLOBALS['cfg']['NaviLightMarkedColor'];
    $GLOBALS['cfg']['NaviMarkedBGC']    = $GLOBALS['cfg']['NaviLightMarkedBGC'];
}

if (!$GLOBALS['cfg']['LeftMarkerEnable']) {
    $GLOBALS['cfg']['NaviMarkedColor']  = $GLOBALS['cfg']['NaviColor'];
    $GLOBALS['cfg']['NaviMarkedBGC']    = $GLOBALS['cfg']['NaviBackground'];
}

if ('IE' == PMA_USR_BROWSER_AGENT) {
    define('_NL', chr(13) . chr(10));
} else {
    define('_NL', chr(10));
}

if (empty($GLOBALS['cfg']['NiceCss'])) {
    define('_S', '{');       //start
    define('_M', ';');       //mid
    define('_E', '}' . _NL); //end
    define('_K', ',');
    define('_T', '');
    define('_D', '');
} else {
    define('_S', ' {' . _NL  . "\t");
    define('_M', ';'  . _NL  . "\t");
    define('_E', ';'  . _NL . '}' . _NL . _NL );
    define('_K', ','  . _NL );
    define('_T', "\t");
    define('_D', "\t\t"); //double tab needed?
}


// css for  navigation.php  (former left.php)

if (version_compare(PMA_VERSION,'2.9','lt') && ! empty($GLOBALS['cfg']['FontSize'])) {
    echo
    'html',
    _S, 'font-size:', _T, $GLOBALS['cfg']['FontSize'],
    _E;
}

echo '/* ', _NaviGridVersion, ' for pma 2.8 ... 2.11+ */', _NL,

//general tags
'*',
_S, 'margin:',  _T, 0,
//no good (select): 'padding:', _T, 0,
_E,

'body', _S;
if (! empty($GLOBALS['cfg']['FontFamily'])) {
    echo 'font-family:', _T,    $GLOBALS['cfg']['FontFamily'],
    _M;
}
echo
    'background:', _T, $GLOBALS['cfg']['NaviBGC'],
_M, 'color:',      _D, $GLOBALS['cfg']['NaviColor'],
_M, 'padding:',    _T, 0,
_E,

'hr',
_S, 'border:',     _D, 0,
_M, 'color:',      _D, $GLOBALS['cfg']['NaviColor'],
_M, 'background:', _T, $GLOBALS['cfg']['NaviColor'],
_M, 'height:',     _D, '1px', //mimic border 1px solid
_M, 'margin-top:', _T, '.5em',
_E,


// Links:
'a',
_S, 'padding:',    _T, '0 2px 1px 2px', //top l? bot r?
_M, 'color:', _D, $GLOBALS['cfg']['NaviLinkColor'],
_E;

if ($GLOBALS['cfg']['LeftPointerEnable']) {
    echo
    'a:hover',
    _S, 'background:', _T, $GLOBALS['cfg']['NaviPointerBGC'],
    _M, 'color:',      _D, $GLOBALS['cfg']['NaviPointerColor'], //doesn'work on dbname
    _E;
}

echo
'a:active',
_S, 'background:', _T, $GLOBALS['cfg']['NaviActiveBGC'],
_E,

'a:focus',
_S, 'text-decoration:', _T, 'none',
_E,


'a img',
_S, 'border:', _D, 0,//avoid thick link border
_E,
// end Links


'form',
_S, 'display:', _T, 'inline',
_E,

'select',
_S, 'margin-top:',  _T, '.2em',
_E;

echo version_compare(PMA_VERSION, '2.11', 'lt') // here only concat!
?
'select'
:
'#navidbpageselector' .
_S . "padding-$left:" . _T . '.2em' .
_M . 'text-align:' .  _T . 'center' .
_E .

'select#select_server' . _K .
'select#lightm_db';

echo
_S, 'width:', _D, '100%',
_E,


// buttons in some browsers (eg. Konqueror) are block elements, this breaks design:
'button',
_S, 'display:', _T, 'inline',
_E,


// classes

'ul#databaseList', _K,
'#databaseList ul',
_S, 'list-style-type:', _T, 'none', // needed for Gecko
//_M, "padding-$left:", _T, '.1em',
_M, 'padding:', _T, 0,
_E;

/**
'ul#databaseList a',
_S, 'display:', _T, 'block', //with a line break before and after the element
_E;
**/

if (!$GLOBALS['cfg']['LeftMarkerEnable']) {
    echo
    'ul#databaseList li.selected a',
    _S, 'background:', _T, $GLOBALS['cfg']['NaviMarkedBGC'],
    _M, 'color:',      _D, $GLOBALS['cfg']['NaviMarkedColor'],
   _E;
}

echo
'#databaseList li',
_S, "padding-$left:", _T, '.4em',
_M, 'background:',    _T, $GLOBALS['cfg']['NaviDblBGC'],
_E,

// 2.11+ : <span class="navi_dbName">
'.navi_dbName',
_S, 'font-weight:',   _T, 'bold';
if (!empty($GLOBALS['cfg']['NaviDbNameColor'])) {
    echo _M, 'color:',       _D, $GLOBALS['cfg']['NaviDbNameColor'];
}
if (!empty($GLOBALS['cfg']['NaviDbNameBGC'])) {
    echo _M, 'background:',  _T, $GLOBALS['cfg']['NaviDbNameBGC'];
}
echo
_M, 'text-decoration:', _T, 'underline',
_E,


// specific elements
'#pmalogo', _K,
'#leftframelinks',
_S, 'text-align:', _T, 'center',
_E,

'#pmalogo',
_S, 'background-color:', _T, $GLOBALS['cfg']['NaviLogoBGC'],
//_S, 'background:', _T, 'transparent', //???
_M, 'padding:',          _T, 0,
_E,

'#leftframelinks', _K,
'#navidbpageselector',
_S, 'padding:',        _T, 0,
_M, 'padding-bottom:', _T, '.3em',
//_M, 'background:',     _T, $GLOBALS['cfg']['Navi2ndBGC'],
_E,

'#leftframelinks',
_S, 'padding-top:', _T, '.3em',
_E;


// serverlist

if ($GLOBALS['cfg']['LeftDisplayServers']) {
    echo
    '#serverinfo',
    _S, 'margin:',  _D, '.2em',
    _E;

    if ($GLOBALS['cfg']['DisplayServersList']) {
        echo
        '#list_server',
        _S, 'list-style-type:',     _T, 'decimal',
        _M, "padding-$left:",       _T, '1.8em', // .2 if "inside"
        _E;
    }
}

echo
'.icon a', _K,
'div#databaseList',
_S, 'padding:', _T, '3px',
_E,

// left_tableList
'#left_tableList',
_S, 'margin:',     _T, '0 .2em',
_M, 'padding:',    _T, '.2em',
_M, 'background:', _T, $GLOBALS['cfg']['Navi2ndBGC'],
_E,

'#left_tableList li',
_S, 'white-space:',    _T, 'nowrap',
_M, 'padding-bottom:', _T, '.1em'; //4 __ spacing
if ('IE' == PMA_USR_BROWSER_AGENT) {
    echo
    _M, 'margin:', _T, 0, //'1px 0 0 0',
    _M, 'border:', _T, '1px solid ', $GLOBALS['cfg']['Navi2ndBGC'], //test
    _M, 'padding:', _T, 0;
} else {
    echo
    _M, 'margin:', _T, '1px 0 0 0';
}
echo
_E;

if ($GLOBALS['cfg']['LeftMarkerEnable']) { // orig:NaviMarkedColor???
// marked items
    if (!$GLOBALS['cfg']['LeftFrameLight']) {
        echo
        '#left_tableList > ul li.marked > a,', _K; //4 overiding Link(Color)
    }
    echo
    '#left_tableList > ul li.marked',
    _S, 'background:',  _T, $GLOBALS['cfg']['NaviMarkedBGC'],
    _M, 'color:',       _D, $GLOBALS['cfg']['NaviMarkedColor'],
    _E;
}

echo
'#imgpmalogo', _K,
'.icon', _K,
'#left_tableList img',
_S, 'vertical-align:', _D, 'middle', //make a:hover covering the whole img
_E,

'#left_tableList ul',
_S, 'list-style-type:', _T, 'none',
_M, 'padding:',         _T, 0 ,
_M, 'background:',      _T, $GLOBALS['cfg']['Navi2ndBGC'], //for marking selected db&table only
_E,

'#left_tableList ul ul',
_S, 'padding:',       _T, 0,
_M, "padding-$left:", _T, '.2em',
_M, "border-$left:",  _T, '1px solid ', $GLOBALS['cfg']['NaviColor'],
_M, 'border-bottom:', _T, '1px solid ', $GLOBALS['cfg']['NaviColor'],
_M, 'background:',    _T, $GLOBALS['cfg']['NaviTblBGC'],
_E;
?>
