<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * navigation css file from theme Grid
 *
 * @version $Id$
 * @package phpMyAdmin-theme
 * @subpackage Grid
 */

define('_NaviGridVersion', 'Grid 08080815 NAVI (pma 2.8+)');

// css for navigation.php (former left.php)
if (!defined('PMA_MINIMUM_COMMON')) {
    die('/* ' . _NaviGridVersion . ' illegal execution path */');
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

if ('IE' == PMA_USR_BROWSER_AGENT && !empty($GLOBALS['cfg']['NiceCss'])) {
    define('_NL', chr(13) . chr(10));
} else {
    define('_NL', chr(10));
}

if (empty($GLOBALS['cfg']['NiceCss'])) {
    define('_S', '{');       //start
    define('_M', ';');       //mid
    define('_E', '}' . _NL); //end
    define('_K', ',');
    define('_1', '');
    define('_2', '');
    define('_3', '');
} else {
    define('_S', ' {' . _NL  . "\t");
    define('_M', ';'  . _NL  . "\t");
    define('_E', ';'  . _NL . '}' . _NL . _NL );
    define('_K', ','  . _NL );
    define('_1', "\t");
    define('_2', "\t\t");
    define('_3', "\t\t\t");
}

echo '/* ', _NaviGridVersion, ' */', _NL,
// general tags
'*',
_S, 'color:',       _3, '#000', //FF option
_M, 'margin:',      _3,  0,
_M, 'padding:',     _2,  0,
_M, 'line-height:', _2,  1.25,  // "line-spacing"
_E;

if (version_compare(PMA_VERSION, '3.0', 'ge')) {
    echo
    'html',
    _S, 'font-size:',_2;
    if (null !== $GLOBALS['PMA_Config']->get('fontsize')) {
        echo $GLOBALS['PMA_Config']->get('fontsize');
        } elseif (!empty($_COOKIE['pma_fontsize'])) {
            echo $_COOKIE['pma_fontsize'];
            } else echo '82%';
    echo
    _E,

    'input',  _K,
    'select', _K,
    'textarea',
    _S, 'font-size:', _2, '1em',
    _E;

}

if (version_compare(PMA_VERSION, '2.9', 'lt') && !empty($GLOBALS['cfg']['FontSize'])) {
    echo
    'html',
    _S, 'font-size:', _2, $GLOBALS['cfg']['FontSize'],
    _E;

}

echo
'body', _S;
if (!empty($GLOBALS['cfg']['FontFamily'])) {
    echo 'font-family:', _2,    $GLOBALS['cfg']['FontFamily'],
    _M;
}

echo
    'background:',  _2, $GLOBALS['cfg']['NaviBGC'],
_M, 'color:',       _3, $GLOBALS['cfg']['NaviColor'],
_E,

'hr',
_S, 'border:',     _3, 0,
_M, 'color:',      _3, $GLOBALS['cfg']['NaviColor'],
_M, 'background:', _2, $GLOBALS['cfg']['NaviColor'],
_M, 'height:',     _3, '1px', //mimic border 1px solid
_M, 'margin-top:', _2, '.5em',
_E,


// Links:
'a',
_S, 'text-decoration:', _1, 'none',
_M, 'padding:',    _2, '0 2px 1px 2px', //top l? bot r?
_M, 'color:', _3, $GLOBALS['cfg']['NaviLinkColor'],
_E,

'a:hover',
    _S, 'text-decoration:', _1, 'underline';
if ($GLOBALS['cfg']['LeftPointerEnable']) {
    echo
    _M, 'background:',      _2, $GLOBALS['cfg']['NaviPointerBGC'],
    _M, 'color:',           _3, $GLOBALS['cfg']['NaviPointerColor']; //doesn'work on dbname
}
echo
_E,

'a:active',
_S, 'background:', _2, $GLOBALS['cfg']['NaviActiveBGC'],
_E,

'a:focus',
_S, 'text-decoration:', _1, 'none',
_E,

'a img',
_S, 'border:', _3, 0, //avoid thick link border
_E,
// end Links

'form',
_S, 'display:', _2, 'inline',
_E,

'select',
_S, 'margin-top:',   _2, '2px',
_E;

echo version_compare(PMA_VERSION, '2.11', 'lt')
?
'select'
:
'#navidbpageselector' . // here only concat!
_S . 'padding-', $left, ':' . _2 . '2px' .
_M . 'text-align:'          . _2 . 'center' .
_E .

'select#select_server' . _K .
'select#lightm_db';

echo
_S, 'width:', _3, '100%',
_E,

'option', // for db paging
_S, 'padding-', $left, ':',  _2, '5px',
_E,

// buttons in some browsers (e.g., Konqueror) are block elements, this breaks design:
'button',
_S, 'display:', _2, 'inline',
_E,


// classes

'ul#databaseList', _K,
'#databaseList ul',
_S, 'list-style-type:', _1, 'none', // Gecko
_E;

if (!$GLOBALS['cfg']['LeftMarkerEnable']) {
    echo
    'ul#databaseList li.selected a',
    _S, 'background:', _2, $GLOBALS['cfg']['NaviMarkedBGC'],
    _M, 'color:',      _3, $GLOBALS['cfg']['NaviMarkedColor'],
    _E;
}

echo
'#databaseList li',
_S, 'padding-', $left, ':', _2, '4px',
_M, 'background:',          _2, $GLOBALS['cfg']['NaviDblBGC'],
_E,

// 2.11+ : <span class="navi_dbName">
'.navi_dbName',
_S, 'font-weight:', _2, 'bold';
if (!empty($GLOBALS['cfg']['NaviDbNameColor'])) {
    echo _M, 'color:', _3, $GLOBALS['cfg']['NaviDbNameColor'];
}
if (!empty($GLOBALS['cfg']['NaviDbNameBGC'])) {
    echo _M, 'background:', _2, $GLOBALS['cfg']['NaviDbNameBGC'];
}
echo
_E,

'.navi_dbName:hover',
_S, 'text-decoration:', _1, 'underline',
_E,


// specific elements
'#pmalogo', _K,
'#leftframelinks',
_S, 'text-align:', _2, 'center',
_E,

'#pmalogo',
_S, 'background-color:', _1, $GLOBALS['cfg']['NaviLogoBGC'],
_E,

'#leftframelinks', _K,
'#navidbpageselector',
_S, 'padding-bottom:', _2, '3px',
_E,

'#leftframelinks',
_S, 'padding-top:', _2, '3px',
_E;


// serverlist

if ($GLOBALS['cfg']['LeftDisplayServers']) {
    echo
    '#serverinfo',
    _S, 'margin:',  _3, '2px',
    _E;

    if ($GLOBALS['cfg']['DisplayServersList']) {
        echo
        '#list_server',
        _S, 'list-style-type:',     _1, 'decimal',
        _M, 'padding-', $left, ':', _2, '1.8em', // .2 if "inside"
        _E;
    }
}

echo
'.icon a', _K,
'div#databaseList',
_S, 'padding:', _2, '3px',
_E,

// left_tableList
'#left_tableList',
_S, 'margin:',      _3, '0 2px',
_M, 'padding:',     _2, '0 2px',
_M, 'background:',  _2, $GLOBALS['cfg']['Navi2ndBGC'],
_E,

'#left_tableList li',
_S, 'padding-bottom:', _2, '1px', //for "__" spacing
_M, 'white-space:',    _2, 'nowrap';

if ('IE' != PMA_USR_BROWSER_AGENT) {
    echo
    _M, 'margin:',      _3, '1px 0 0 0';
}
echo
_E;

if ($GLOBALS['cfg']['LeftMarkerEnable']) { // orig:NaviMarkedColor???
// marked items
    if (!$GLOBALS['cfg']['LeftFrameLight']) {
        echo
        '#left_tableList > ul li.marked > a', _K; //4 overiding Link(Color)
    }
    echo
    '#left_tableList > ul li.marked',
    _S, 'background:',  _2, $GLOBALS['cfg']['NaviMarkedBGC'],
    _M, 'color:',       _3, $GLOBALS['cfg']['NaviMarkedColor'],
    _E;
}

echo
'#imgpmalogo', _K,
'.icon', _K,
'#left_tableList img',
_S, 'vertical-align:',  _2, 'middle', //make a:hover covering the whole img
_E,

'#left_tableList ul',
_S, 'list-style-type:', _1, 'none',
_M, 'background:',      _2, $GLOBALS['cfg']['Navi2ndBGC'], //for marking selected db&table only
_E,

'#left_tableList ul ul',
_S, 'padding-', $left, ':', _2, '2px',
_M, 'border-',  $left, ':', _2, '1px solid ', $GLOBALS['cfg']['NaviColor'],
_M, 'border-bottom:',       _2, '1px solid ', $GLOBALS['cfg']['NaviColor'],
_M, 'background:',          _2, $GLOBALS['cfg']['NaviTblBGC'],
_E;

