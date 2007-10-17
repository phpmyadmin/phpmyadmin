<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * main css file from theme Grid
 * theme_right.css.php
 *
 * @version $Id$
 * @package phpMyAdmin-theme
 * @subpackage Grid
 */

define('_MainGridVersion', 'main Grid-3 2007-10');

if (!defined('PMA_MINIMUM_COMMON')) {
    die('/* ' . _MainGridVersion . ' unplanned execution path */');
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
    define('_K', ',');         //komma
    define('_T', '');         //tab #8
    define('_D', '');
} else {
    define('_S', ' {' . _NL  . "\t");
    define('_M', ';'  . _NL  . "\t");
    define('_E', ';'  . _NL . '}' . _NL . _NL );
    define('_K', ','  . _NL );
    define('_T', "\t");
    define('_D', "\t\t"); //double tab needed?
}


if (version_compare(PMA_VERSION,'2.9', 'lt')) {
    //needed for pma2.8 only (if E_KOTICE=1 , but no effect) :
    $GLOBALS['cfg']['BgcolorOne'] = '#f7f7f7';
    $GLOBALS['cfg']['BgcolorTwo'] = '#fff';
    echo
    'html', _K,
    'table',
    _S, 'font-size:', _T, $GLOBALS['cfg']['FontSize'],
    _E,

    'td', _K,
    'th',
    _S, 'color:', _D, $GLOBALS['cfg']['MainColor'],
    _E;
}

define('_imgPath',
version_compare(PMA_VERSION,'2.11','lt')
? '../' . $_SESSION['PMA_Theme']->getImgPath()
:         $_SESSION['PMA_Theme']->getImgPath()
);
define('_listImgUrl', 'list-style-image:' . _T . 'url("' . _imgPath ); //.....xxx.png")

// colors of several borders:
define('_red',    '#e00');
define('_silver', '#eee');

echo _NL, '/* ', _MainGridVersion, ' */', _NL,
'body',
_S, 'padding:',    _T, 0,
_M, 'margin:',     _D, '.4em',
_M, 'color:',      _D, $GLOBALS['cfg']['MainColor'],
_M, 'background:', _T, $GLOBALS['cfg']['MainBGC'];
if ('MOZILLA' != PMA_USR_BROWSER_AGENT ) {
    // oldstyle:
    echo ' url("', _imgPath, 'vertical_line.png") repeat-y';
    // http://www.w3.org/TR/CSS21/syndata.html (double) quotes around url ok
    // (just incase someone has spaces in his path)
}

if (!empty($GLOBALS['cfg']['FontFamily'])) {
    echo
    _M, 'font-family:', _T, $GLOBALS['cfg']['FontFamily'];
}
echo _E; //end body

if (!empty($GLOBALS['cfg']['FontFamilyFixed'])) {
    echo
    'textarea', _K,
    'tt', _K,
    'pre',
    _S, 'font-family:', _T, $GLOBALS['cfg']['FontFamilyFixed'],
    _E;
}

echo
'input',
_S, 'padding:',   _T, '0 .2em',
//_M, 'font-size:', _T, '100%',
_M, 'margin-bottom:',    _T, '.1em', //if sql window is narrow
_E,

'h1',
_S, 'font-size:', _T, '140%', // on main only
_M, 'margin:',    _D, '0 .8em 0 .8em',
_E,

'h2',
_S, 'font-size:', _T, '120%',
_E,

'a', //        top/bot  left/right
_S, 'padding:', _T, '0 .2em',
_E,

// Links:
'a',
_S, 'text-decoration:', _T, 'none',
_M, 'color:',           _D, $GLOBALS['cfg']['MainLinkColor'],
_M, 'padding:',         _T, '0 2px 1px 2px', //top l? bot r?

_E,

'a:hover',
_S, 'text-decoration:', _T, 'underline',
_M, 'color:',           _D, $GLOBALS['cfg']['MainLinkHoverColor'],
_M, 'background:',      _T, $GLOBALS['cfg']['MainLinkHoverBGC'],
_E,

'a:active',
_S, 'background:', _T, $GLOBALS['cfg']['MainActiveBGC'],
_E,

'a:focus',
_S, 'background:', _T, $GLOBALS['cfg']['BrowsePointerBGC'],
_E,


'dfn',
_S, 'font-style:', _T, 'normal',
_E,

'dfn:hover',
_S, 'font-style:', _T, 'normal',
_M, 'cursor:',     _T, 'help',
_E,

'th',
_S, 'font-weight:', _T, 'bold',
_M, 'color:',       _D, $GLOBALS['cfg']['ThColor'],
_M, 'background:',  _T, $GLOBALS['cfg']['ThBGC'],
_E,

'a img',
_S, 'border:', _D, 0,
_E,

'hr',
_S, 'color:',      _D, $GLOBALS['cfg']['MainColor'],
_M, 'background:', _T, $GLOBALS['cfg']['MainColor'], //sic!
_M, 'border:',     _D, 0,
_M, 'height:',     _T, '1px',
_E,

'form',
_S, 'padding:', _T, 0,
_M, 'margin:',  _D, '1px',
_M, 'display:', _T, 'inline',
_E,

'textarea',
_S, 'overflow:', _T, 'visible',
_M, 'height:',   _T, ceil($GLOBALS['cfg']['TextareaRows']*1.2), 'em',
//thx Mario Rohkrämer (ligh1l) Gag_H
_E,

'fieldset',
_S, 'margin-top:', _T, '.7em', //for sql window
_M, 'padding:',    _T, '.5em',
_M, 'background:', _T, $GLOBALS['cfg']['FieldsetBGC'],
_M, 'border:',     _D, '1px solid ', $GLOBALS['cfg']['BorderColor'],
_E,

'fieldset fieldset',
_S, 'margin:', _D, '.8em',
_E,

'fieldset legend',
_S, 'padding:',       _T, '.1em .3em';
if ($GLOBALS['cfg']['LegendBorder']) {
    echo
    _M, 'border:', _D, '1px solid ', $GLOBALS['cfg']['BorderColor'],
    _M, 'border-bottom:', _T, 0;
}
echo
_M, 'background:',    _T, $GLOBALS['cfg']['LegendBGC'],
_M, 'color:',         _D, $GLOBALS['cfg']['LegendColor'],
_M, 'margin-top:',    _T, '.3em',
_M, 'font-weight:',   _T, 'bold',
_E,

// buttons in some browsers (eg. Konqueror) are block elements, this breaks design:'

'button',
_S, 'display:', _T, 'inline',
_E,

'table',
_S, 'margin:', _D, '3px 1px 1px 1px',
_M, 'border-collapse:', _T, 'collapse',
_E,

'table caption', _K,
'th', _K,
'td',
_S, 'padding:', _T, '0 .2em 0 .2em';
//margin default
if ($GLOBALS['cfg']['Border']) {
   echo _M, 'border:', _D, $GLOBALS['cfg']['Border'], ' solid ', $GLOBALS['cfg']['MainGridColor'];
}
echo
_E,

'td',
_S, 'vertical-align:', _T, 'top',
_E,

'th',
_S, 'vertical-align:', _T, 'bottom',
_E,

'input', _K,
'select', _K,
'button', _K, //??
'img',
_S, 'vertical-align:', _T, 'middle',
_E,


// classes

'fieldset.tblFooters',
_S, 'border-top:',    _T, 0,
_M, 'margin-top:',    _T, 0,
_M, 'margin-bottom:', _T, '.5em',
_M, 'text-align:',    _T, $right,
_M, 'background:',    _T, $GLOBALS['cfg']['FieldsetFooterBGC'],
_M, 'float:',         _D, 'none',
_M, 'clear:',         _T, 'both',
_E,

'fieldset .formelement',
_S, "margin-$right:", _T, '.5em',
_M, 'white-space:',   _T, 'nowrap', //IE
_E,
// revert for Gecko
'fieldset div[class=formelement]',
_S, 'white-space:', _T, 'normal',
_E,

'button.mult_submit',
_S, 'border:', _D, 0,
//_M, 'border-bottom:', _D, '1px solid blue',
_M, 'margin:',     _D, '0 2px',
_M, 'padding:',    _T, 0,
_M, 'background:', _T, 'transparent',
_E,

'button.mult_submit:hover',
_S, 'background:', _T, $GLOBALS['cfg']['MainLinkHoverBGC'], //not IE6
_M, 'cursor:',_T, 'pointer',
//IE4 _M, 'cursor:',_T, 'hand',
_E,

// odd items 1,3,5,7,...
'table tr.odd th', _K,
'.odd',
_S, 'background:', _T, $GLOBALS['cfg']['BgOne'],
_E,

// even items 2,4,6,8,...
'table tr.even th', _K,
'.even',
_S, 'background:', _T, $GLOBALS['cfg']['BgTwo'],
_E,

// odd table rows 1,3,5,7,...
'table tr.odd th', _K,
'table tr.odd', _K,
'table tr.even th', _K,
'table tr.even',
_S, 'text-align:', _T, $left,
_E;

if ($GLOBALS['cfg']['BrowseMarkerEnable']) {
// marked table rows
    echo
    'table tr.marked th', _K,
    'table tr.marked',
    _S, 'background:', _T, $GLOBALS['cfg']['BrowseMarkerBGC'],
    _M, 'color:',      _D, $GLOBALS['cfg']['BrowseMarkerColor'],
_E;
}

if ($GLOBALS['cfg']['BrowsePointerEnable']) {
// hovered items
    echo
    '.odd:hover', _K,
    '.even:hover', _K,
    '.hover',
    _S, 'background:', _T, $GLOBALS['cfg']['BrowsePointerBGC'],
    _M, 'color:',      _D, $GLOBALS['cfg']['BrowsePointerColor'],
    _E,

// hovered table rows
    'table tr.odd:hover th', _K,
    'table tr.even:hover th', _K,
    'table tr.hover th',
    _S, 'background:', _T, $GLOBALS['cfg']['BrowsePointerBGC'],
    _M, 'color:',      _D, $GLOBALS['cfg']['BrowsePointerColor'],
    _E;
} // endif BrowsePointerEnabled

echo
// marks table rows/cells if the db field is in a where condition
'tr.condition th', _K,
'tr.condition td', _K,
'td.condition', _K,
'th.condition',
_S, 'border:', _D, '1px solid ', _T,             $GLOBALS['cfg']['BrowseMarkerBGC'],
_E,

'table .value',
_S, 'text-align:', _T, $right,
_M, 'white-space:', _T, 'normal',
_E,

// IE(?) doesn't handle 'pre' right:
'table [class=value]',
_S, 'white-space:', _T, 'normal',
_E;

if (! empty($GLOBALS['cfg']['FontFamilyFixed'])) {
    echo
    '.value',
    _S, 'font-family:', _T, $GLOBALS['cfg']['FontFamilyFixed'],
    _E;
}

echo
'.value .attention',
_S, 'color:',       _D, _red,
_M, 'font-weight:', _T, 'bold',
_E,

'.value .allfine',
_S, 'color:',       _D, 'green',
_E,

'img.lightbulb',
_S, 'cursor:', _T, 'pointer',
_E,

'.pdflayout',
_S, 'overflow:', _T, 'hidden',
_M, 'clip:', _T, 'inherit',
_M, 'background:', _T, '#fff',
_M, 'display:', _T, 'none',
_M, 'border:', _D, '1px solid #000',
_M, 'position:', _T, 'relative',
_E,

'.pdflayout_table',
_S, 'background:', _T, '#D3DCE3',
_M, 'color:', _D, '#000',
_M, 'overflow:', _T, 'hidden',
_M, 'clip:', _T, 'inherit',
_M, 'z-index:', _T, '2',
_M, 'display:', _T, 'inline',
_M, 'visibility:', _T, 'inherit',
_M, 'cursor:', _T, 'move',
_M, 'position:', _T, 'absolute',
_M, 'font-size:', _T, '80%',
_M, 'border:', _D, '1px dashed #000',
_E,

// MySQL Parser:
'.syntax',
_S, 'font-size:', _T, '90%',
_E,

'.syntax_comment',
_S, 'padding-left:', _T, '4pt',
_M, 'padding-right:', _T, '4pt',
_E,

'.syntax_alpha_columnType', _K,
'.syntax_alpha_columnAttrib', _K,
'.syntax_alpha_functionName', _K,
'.syntax_alpha_reservedWord',
_S, 'text-transform:', _T, 'uppercase',
_E,

'.syntax_alpha_reservedWord',
_S, 'font-weight:', _T, 'bold',
_E,

'.syntax_quote',
_S, 'white-space:', _T, 'pre',
_E,

/**
'.syntax_quote_backtick',
_S, 'background:', _T, $GLOBALS['cfg']['BacktickBGC'],
_M, 'padding-left:', _T, '2px',
_M, 'padding-right:', _T, '2px',
_E,
**/

//leave some space between icons and text
'.icon',
_S, 'vertical-align:', _T, 'middle',
_M, 'margin-right:', _T, '.3em',
_M, 'margin-left:', _T, '.3em',
_E,

'td .icon',
_S, 'margin:', _D, 0,
_E,

'.selectallarrow',
_S, "margin-$right:", _T, '.3em',
_M, "margin-$left:",  _T, '.6em',
_E,


// message boxes: warning, error, confirmation
'.warning',
_S, 'color:',      _D, '#c00',
_M, 'background:', _T, '#ffc',
_E,

'.error',
_S, 'background:', _T, '#ffc',
_M, 'color:',      _D, '#f00',
_E;

echo version_compare(PMA_VERSION,'3', 'lt') //r10741
?
'.notice' .
_S . 'color:'      . _D . '#000' .
_M . 'background:' . _T . '#ffd' .
_E .

'h1.notice'  . _K .
'div.notice' .
_S . 'margin:' . _D . '5px 0' .
_M . 'border:' . _D . '1px solid #FFD700'
:
'.notice h1'   . _K .
'.success h1'  . _K .
'.warning h1'  . _K .
'div.error h1' .
_S . 'border-bottom:' . _T . '2px solid' . //??
_M . 'font-weight:'   . _T . 'bold' .
_M . 'text-align:'    . _T . $left .
_M . 'margin:'        . _D . '0 0 2px 0' .
_E .

'div.success' . _K .
'div.notice'  . _K .
'div.warning' . _K .
'div.error'   .
_S . 'margin:' . _D . '2px 0 0 0' .
_M . 'border:' . _D . '1px solid';
if ($GLOBALS['cfg']['ErrorIconic']) {
   if (version_compare(PMA_VERSION,'3', 'lt')) {
      echo _M, 'background-image:', _T, 'url("', _imgPath, 's_notice.png")';
   }
   echo _M, 'background-repeat:', _T, 'no-repeat';
    if ($GLOBALS['text_dir'] === 'ltr') {
        echo
        _M, 'background-position:', _T, '1em 50%',
        _M, 'padding:', _T, '4px 1em 4px 3.6em';
    } else {
        echo
        _M, 'background-position:', _T, '99% 50%',
        _M, 'padding:', _T, '4px 5% 4px 1em';
    }
} else {
    echo
    'padding:', _T, '5px';
}

echo
_E;

if (version_compare(PMA_VERSION,'3', 'lt')) { //r10741
    echo
    '.notice h1',
    _S, 'border-bottom:', _T, '1px solid #FFD700',
    _M, 'font-weight:',   _T, 'bold',
    _M, 'text-align:',    _T, $left,
    _M, 'margin:',        _D, '0 0 .2em 0',
    _E,

    'p.warning', _K,
    'h1.warning', _K,
    'div.warning',

    _S, 'margin:', _T, '3px 0 0 0',
    _M, 'border:', _T, '1px solid #c00';

    if ($GLOBALS['cfg']['ErrorIconic']) {
        echo
        _M, 'background-image:',  _T, 'url("', _imgPath, 's_warn.png")',
        _M, 'background-repeat:', _T, 'no-repeat';
        if ($GLOBALS['text_dir'] === 'ltr') {
            echo
            _M, 'background-position:', _T, '1em 50%',
            _M, 'padding:', _T, '4px 1em 4px 3.6em';
        } else {
            echo
            _M, 'background-position:', _T, '99% 50%',
            _M, 'padding:', _T, '4px 5% 4px 1em';
        }
    } else {
        echo _M, 'padding:', _T, '4px';
    }

    echo
    _E,

    '.warning h1',
    _S, 'border-bottom:', _T, '1px solid #c00',
    _M, 'font-weight:',   _T, 'bold',
    _M, 'text-align:',    _T, $left,
    _M, 'margin:',        _T, '0 0 2px 0',
    _E,

    '.error',
    _S, 'background:', _T, '#ffc',
    _M, 'color:', _D, '#f00',
    _E,

    'h1.error', _K,
    'div.error',
    _S, 'margin:', _D, '5px 0',
    _M, 'border:', _D, '1px solid #f00';

    if ($GLOBALS['cfg']['ErrorIconic']) {
        echo
        _M, 'background-image:', _T, 'url("', _imgPath, 's_error.png")',
        _M, 'background-repeat:', _T, 'no-repeat';
        if ($GLOBALS['text_dir'] === 'ltr') {
           echo
            _M, 'background-position:', _T, '1em 50%',
            _M, 'padding:', _T, '5px 1em 5px 3.6em';
        } else {
            echo
            _M, 'background-position:', _T, '99% 50%',
            _M, 'padding:', _T, '5px 5% 5px 1em';
       }
    } else {
        echo
            _M, 'padding:', _T, '.5em';
    }

    echo
    _E,

    'div.error h1',
    _S, 'border-bottom:', _T, '1px solid #f00',
    _M, 'font-weight:', _T, 'bold',
    _M, 'text-align:', _T, $left,
    _M, 'margin:', _D, '0 0 2px 0',
    _E;

} else {

    echo
    '.success',
    _S, 'color:',      _D, '#000',
    _M, 'background:', _T, '#f0fff0',
    _E,

    'h1.success', _K,
    'div.success',
    _S, 'border-color:', _T, '#0f0';
    if ($GLOBALS['cfg']['ErrorIconic']) {
        echo _M, 'background-image:', _T, 'url("', _imgPath, 's_success.png")';
    }
    echo
    _E,

    '.success h1',
    _S, 'border-color:', _T, '#0d0',
    _E,

    '.notice',
    _S, 'color:',      _D, '#000',
    _M, 'background:', _T, '#ffd',
    _E,

    'h1.notice', _K,
    'div.notice',
    _S, 'border-color:', _T, '#FFD700';
    if ($GLOBALS['cfg']['ErrorIconic']) {
        echo _M, 'background-image:', _T, 'url("', _imgPath, 's_notice.png")';
    }
    echo
    _E,

    '.notice h1',
    _S, 'border-color:', _T, '#FFD700',
    _E,

    'p.warning', _K,
    'h1.warning', _K,
    'div.warning',
    _S, 'border-color:', _T, '#ffd700';
    if ($GLOBALS['cfg']['ErrorIconic']) {
        echo _M, 'background-image:',  _T, 'url("', _imgPath, 's_warn.png")';
    }
    echo
    _E,

    '.warning h1',
    _S, 'border-color:', _T, '#c00',
    _E,

    'h1.error', _K,
    'div.error',
    _S, 'border-color:', _T, '#f00';
    if ($GLOBALS['cfg']['ErrorIconic']) {
        echo _M, 'background-image:', _T, 'url("', _imgPath, 's_error.png")';
    }
    echo
    _E,

    'div.error h1',
    _S, 'border-color:', _T, '#f00',
    _E;
}//r10741

echo
'.confirmation',
_S, 'background:', _T, '#ffc',
_E,

'fieldset.confirmation',
_S, 'border:', _D, '1px solid #f00',
_E,

'fieldset.confirmation legend',
_S, 'border-left:', _T, '1px solid #f00',
_M, 'border-right:', _T, '1px solid #f00',
_M, 'font-weight:', _T, 'bold';
if ($GLOBALS['cfg']['ErrorIconic']) {
    echo
    _M, 'background-image:',  _T, 'url("', _imgPath, 's_really.png")',
    _M, 'background-repeat:', _T, 'no-repeat';
    if ($GLOBALS['text_dir'] === 'ltr') {
        echo
            _M, 'background-position:', _T, '.5em 50%',
            _M, 'padding:', _T, '.2em .2em .2em 2.5em';
    } else {
        echo
            _M, 'background-position:', _T, '97% 50%',
            _M, 'padding:', _T, '.2em 2.5em .2em .2em';
    }
}

echo
 _E, // end messageboxes

'.tblcomment',
_S, 'font-size:', _T, '90%',
_M, 'font-weight:', _T, 'normal',
_M, 'color:', _D, '#009',
_E,

'.tblHeaders',
_S, 'font-weight:', _T, 'bold',
_M, 'color:', _D, $GLOBALS['cfg']['ThColor'],
_M, 'background:', _T,  $GLOBALS['cfg']['ThBGC'],
_E,

'.tblFooters',
_S, 'font-weight:', _T, 'normal',
_M, 'color:', _D, $GLOBALS['cfg']['ThColor'],
_M, 'background:', _T, $GLOBALS['cfg']['ThBGC'],
_E,

'.tblHeaders a:link', _K,
'.tblHeaders a:active', _K,
'.tblHeaders a:visited', _K,
'.tblFooters a:link', _K,
'.tblFooters a:active', _K,
'.tblFooters a:visited',
_S, 'color:', _D, '#00f',
_E,

'.tblHeaders a:hover', _K,
'.tblFooters a:hover',
_S, 'color:', _D, '#f00',
_E,

'.noPrivileges',
_S, 'color:', _D, '#f00',
_M, 'font-weight:', _T, 'bold',
_E,

'.disabled', _K,
'.disabled a:link', _K,
'.disabled a:active', _K,
'.disabled a:visited',
_S, 'color:', _D, '#666',
_E,

'.disabled a:hover',
_S, 'color:', _D, '#666',
_M, 'text-decoration:', _T, 'none',
_E,

'tr.disabled td', _K,
'td.disabled',
_S, 'background:', _T, '#ccc',
_E,

'body.loginform h1', _K,
'body.loginform a.logo',
_S, 'display:', _T, 'block',
_M, 'text-align:', _T, 'center',
_E,

'body.loginform',
_S, 'text-align:', _T, 'center',
_E,

'body.loginform div.container',
_S, 'text-align:', _T, $left,
_M, 'width:', _D, '30em',
_M, 'margin:', _D, '0 auto',
_E,

'form.login label',
_S, 'width:', _D, '10em',
_M, 'font-weight:', _T, 'bolder',
_E,

// specific elements
'ul#topmenu',
_S, 'font-weight:', _T, 'bold',
_M, 'list-style-type:', _T, 'none',
_M, 'margin:', _D, 0,
_M, 'padding:', _T, 0,
_E,

'ul#topmenu li',
_S, 'list-style-type:', _T, 'none',
_M, 'margin:', _D, 0,
_M, 'padding:', _T, 0,
_M, 'vertical-align:', _T, 'middle',
_E,

'ul#topmenu li.active',
_S, 'border-bottom:', _T, '2px solid ', $GLOBALS['cfg']['MainBGC'],
_E,


'#topmenu img',
_S, 'vertical-align:', _T, 'middle',
_M, "margin-$right:", _T, '1px',
_E,

'.tab', _K,
'.tabcaution', _K,
'.tabactive',
_S, 'display:', _T, 'block',
_M, 'margin:',  _T, '.2em .2em 0 .2em',
_M, 'padding:', _T, '.2em .2em 0 .2em',
_M, 'white-space:', _T, 'nowrap',
_E,

'span.tab',
_S, 'color:', _D, '#666',
_E,

'span.tabcaution',
_S, 'color:', _D, '#f66',
_E,

'a.tabcaution',
_S, 'color:', _D, '#f00',
_E,

'a.tabcaution:hover',
_S, 'color:', _D, '#fff',
_M, 'background:', _T, '#f00',
_E;

if ($GLOBALS['cfg']['LightTabs']) {
    echo
    'a.tabactive',
    _S, 'color:', _D, $GLOBALS['cfg']['MainColor'];
} else {
    echo
    '#topmenu',
    _S, 'margin-top:', _T, '.5em',
    _M, 'padding:',    _T, '.1em .3em',
    _E,

    'ul#topmenu li',
    _S, 'border-bottom:', _T, '2px solid ', $GLOBALS['cfg']['TabUnderlineColor'],
    _E,

    '.tab', _K,
    '.tabcaution', _K,
    '.tabactive',
    _S, 'background:', _T, $GLOBALS['cfg']['TabBGC'];
    if ('MOZILLA' == PMA_USR_BROWSER_AGENT) {
         // FF,SeaMonkey..
        echo
        _M, '-moz-border-radius-topleft:',  _T, '.5em',
        _M, '-moz-border-radius-topright:', _T, '.5em';
    } else {
        echo
        _M, 'border-top:',   _D, '1px solid ', _T, $GLOBALS['cfg']['TabBorderColor'],
        _M, 'border-left:',  _D, '1px solid ', _T, $GLOBALS['cfg']['TabBorderColor'],
        _M, 'border-right:', _D, '1px solid ', _T, $GLOBALS['cfg']['TabBorderColor'];
    }
    // MSIE 6: http:', _T, '//blogs.msdn.com/ie/archive/2005/06/23/431980.aspx
    echo
    _E,

    'a.tab:hover', _K,
    'a.tabcaution:hover', _K,
    '.tabactive', _K,
    '.tabactive:hover',
    _S, 'margin:',          _D, ('MOZILLA' == PMA_USR_BROWSER_AGENT) ? 0 : '0 1px' ,
    _M, 'padding:',         _T, '.2em .4em',
    _M, 'text-decoration:', _T, 'none',
    _E,

    'a.tab:hover',
    _S, 'background:', _T, $GLOBALS['cfg']['TabHoverBGC'],
    _M, 'color:',      _D, $GLOBALS['cfg']['TabHoverColor'],
    _E,

    'a.tabactive',
    _S, 'background:', _T, $GLOBALS['cfg']['TabActiveBGC'],
    _M, 'color:',      _D, $GLOBALS['cfg']['TabActiveColor'],
    _E;

    if ('OPERA' != PMA_USR_BROWSER_AGENT ) {
        echo
        'span.tab', _K,
        'a.warning', _K,
        'span.tabcaution',
        _S, 'cursor:', _T, 'url("', _imgPath, 'error.ico"), auto',
        _E;
    }
} // end topmenu

echo
'table.calendar',
_S, 'width:',      _D, '100%',
_E,

'table.calendar td',
_S, 'text-align:', _T, 'center',
_E,

'table.calendar td a',
_S, 'display:',    _T, 'block',
_E,

'table.calendar td a:hover',
_S, 'background:', _T, '#cfc',
_E,

'table.calendar th',
_S, 'background:', _T, '#D3DCE3',
_E,

'table.calendar td.selected',
_S, 'background:', _T, '#fc9',
_E,

'img.calendar',
_S, 'border:',     _D, 'none',
_E,

'form.clock',
_S, 'text-align:', _T, 'center',
_E,

'div#tablestatistics',
_S, 'border-bottom:',  _T, '1px solid #699',
_M, 'margin-bottom:',  _T, '.5em',
_M, 'padding-bottom:', _T, '.5em',
_E,

'div#tablestatistics table',
_S, 'margin-bottom:',  _T, '.5em',
_M, "margin-$right:",  _T, '.5em',
_E,

'div#tablestatistics table caption',
_S, "margin-$right:",  _T, '.5em',
_E,
//END server privileges

'#tableuserrights td,#tablespecificuserrights td,#tabledatabases td',
_S, 'vertical-align:', _T, 'middle',
_E,

// Heading
'#serverinfo',
_S, 'font-weight:',   _T, 'bold',
_M, 'margin-bottom:', _T, '.5em',
_E,

'#serverinfo .item',
_S, 'white-space:', _T, 'nowrap',
_E,

'#span_table_comment',
_S, 'font-weight:', _T, 'normal',
_M, 'font-style:',  _T, 'italic',
_M, 'white-space:', _T, 'nowrap',
_E,

'#serverinfo img',
_S, 'margin:',      _D, '0 1px 0 .2em',
_E,

'#textSQLDUMP',
_S, 'width:',       _D, '95%',
_M, 'height:',      _T, '95%',
_M, 'font-family:', _T, '"Courier New", Courier, mono',
_M, 'font-size:',   _T, '110%',
_E,

'#TooltipContainer',
_S, 'position:',    _T, 'absolute',
_M, 'z-index:',     _T, '99',
_M, 'width:',       _D, '20em',
_M, 'height:',      _T, 'auto',
_M, 'overflow:',    _T, 'visible',
_M, 'visibility:',  _T, 'hidden',
_M, 'background:',  _T, '#ffc',
_M, 'color:',       _D, '#060',
_M, 'border:',      _D, '1px solid #000',
_M, 'padding:',     _T, '.5em',
_E,

// user privileges
'#fieldset_add_user_login div.item',
_S, 'border-bottom:',  _T, '1px solid ', _silver,
_M, 'padding-bottom:', _T, '.3em',
_M, 'margin-bottom:',  _T, '.3em',
_E,

'#fieldset_add_user_login label',
_S, 'display:',        _T, 'block',
_M, 'width:',          _D, '10em',
_M, 'max-width:',      _T, '100%',
_M, 'text-align:',     _T, $right,
_M, "padding-$right:", _T, '.5em',
_E,

'#fieldset_add_user_login span.options #select_pred_username', _K,
'#fieldset_add_user_login span.options #select_pred_hostname', _K,
'#fieldset_add_user_login span.options #select_pred_password',
_S, 'width:',     _D, '100%',
_M, 'max-width:', _T, '100%',
_E,

'#fieldset_add_user_login span.options',
_S, 'display:',           _T, 'block',
_M, 'width:',             _D, '12em',
_M, 'max-width:',         _T, '100%',
_M, "padding-$right:",    _T, '.5em',
_E,

'#fieldset_add_user_login input',
_S, 'width:',             _D, '12em',
_M, 'clear:',             _T, $right,
_M, 'max-width:',         _T, '100%',
_E,

'#fieldset_add_user_login span.options input',
_S, 'width:',             _D, 'auto',
_E,

'#fieldset_user_priv div.item',
_S, 'width:',             _D, '9em',
_M, 'max-width:',         _T, '100%',
_E,

'#fieldset_user_priv div.item div.item',
_S, 'float:',        _D, 'none',
_E,

'#fieldset_user_priv div.item label',
_S, 'white-space:',  _T, 'nowrap',
_E,

'#fieldset_user_priv div.item select',
_S, 'width:',        _D, '100%',
_E,

// END user privileges


// serverstatus
'div#serverstatus table caption a.top',
_S, 'float:', _D, $right,
_E,

'#serverstatussection', _K,
'.clearfloat',
_S, 'clear:',            _T, 'both',
_E,

'div#serverstatussection table',
_S, 'width:', _D, '100%',
_M, 'margin-bottom:',    _T, '1em',
_E,

'div#serverstatussection table .name',
_S, 'width:',            _D, '18em',
_E,

'div#serverstatussection table .value',
_S, 'width:',            _D, '6em',
_E,

'div#serverstatus table tbody td.descr a', _K,
'div#serverstatus table .tblFooters a',
_S, 'white-space:',     _T, 'nowrap',
_E,

'div#serverstatus div#statuslinks a:before', _K,
'div#serverstatus div#sectionlinks a:before', _K,
'div#serverstatus table tbody td.descr a:before', _K,
'div#serverstatus table .tblFooters a:before',
_S, 'content:',         _T , "'['",
_E,

'div#serverstatus div#statuslinks a:after', _K,
'div#serverstatus div#sectionlinks a:after', _K,
'div#serverstatus table tbody td.descr a:after', _K,
'div#serverstatus table .tblFooters a:after',
_S, 'content:',         _T, "']'",
_E,
// end serverstatus

// querywindow  -image:', _T, 'none',
//??-color:', _T

'body#bodyquerywindow',
_S, 'margin:',         _D, 0,
_M, 'padding:',        _T, 0,
_M, 'background:',     _T, $GLOBALS['cfg']['MainBGC'],
_E,

'div#querywindowcontainer',
_S, 'margin:',  _D, 0,
_M, 'padding:', _T, 0,
_M, 'width:',   _D, '100%',
_E,

'div#querywindowcontainer fieldset',
_S, 'margin-top:', _T, 0,
_E,

//END querywindow

// querybox
'div#sqlquerycontainer',
_S, 'width:', _D, '69%',
// 'height:', _T, '15em',_M,
_E,

'div#tablefieldscontainer',
_S, 'float:', _D, $right,
_M, 'width:', _D, '29%',
_E,

'div#tablefieldscontainer select',
_S, 'width:', _D, '100%',
_E,

// height:', _T, '12em',
'textarea#sqlquery',
_S, 'width:', _D, '100%',
_E,

// height:', _T, '100%',
'div#queryboxcontainer div#bookmarkoptions',
_S, 'margin-top:', _T, '.5em',
_E,
// end querybox

// main page
'#maincontainer',
_S, 'background-image:',    _T, 'url("', _imgPath, 'logo_right.png")',
_M, 'background-position:', _T, $right, ' bottom',
_M, 'background-repeat:',   _T, 'no-repeat',
//_M, 'border-bottom:',       _T, '1px solid ', _silver,
_E,

'#mysqlmaininformation', _K,
'#pmamaininformation',
_S, 'width:',         _D, '49%',
_E,

'#maincontainer ul',
_S, 'list-style-image:', _T, 'url("', _imgPath, 'item_', $GLOBALS['text_dir'], '.png")',
_M, 'vertical-align:',   _T, 'middle',
_E,

'#maincontainer li',
_S, 'margin-bottom:',    _T, '.3em',
_M, 'padding:',          _T, '0 .3em 0 .3em',
_E;
// END main page

if ($GLOBALS['cfg']['MainPageIconic']) {
    // iconic view for ul items
    echo
    'li#li_create_database',
    _S, _listImgUrl,'b_newdb.png")',
    _E,

    'li#li_select_lang',
    _S, _listImgUrl,'s_lang.png")',
    _E,

    'li#li_select_mysql_collation', _K,
    'li#li_select_mysql_charset',
    _S, _listImgUrl,'s_asci.png")',
    _E,

    'li#li_select_theme',
    _S, _listImgUrl,'s_theme.png")',
    _E,

    'li#li_server_info',_K,
    'li#li_server_version',
    _S, _listImgUrl,'s_host.png")',
    _E,

    'li#li_mysql_status',
    _S, _listImgUrl,'s_status.png")',
    _E,

    'li#li_mysql_variables',
    _S, _listImgUrl,'s_vars.png")',
    _E,

    'li#li_mysql_processes',
    _S, _listImgUrl,'s_process.png")',
    _E,

    'li#li_mysql_collations',
    _S, _listImgUrl,'s_asci.png")',
    _E,

    'li#li_mysql_engines',
    _S, _listImgUrl,'b_engine.png")',
    _E,

    'li#li_mysql_binlogs',
    _S, _listImgUrl,'s_tbl.png")',
    _E,

    'li#li_mysql_databases',
    _S, _listImgUrl,'s_db.png")',
    _E,

    'li#li_export',
    _S, _listImgUrl,'b_export.png")',
    _E,

    'li#li_import',
    _S, _listImgUrl,'b_import.png")',
    _E,

    'li#li_change_password',
    _S, _listImgUrl,'s_passwd.png")',
    _E,

    'li#li_log_out',
    _S, _listImgUrl,'s_loggoff.png")',
    _E,

    'li#li_pma_docs', _K,
    'li#li_pma_wiki',
    _S, _listImgUrl,'b_docs.png")',
    _E,

    'li#li_phpinfo',
    _S, _listImgUrl,'php_sym.png")',
    _E,

    'li#li_pma_homepage',
    _S, _listImgUrl,'b_home.png")',
    _E,

    'li#li_mysql_privilegs',
    _S, _listImgUrl,'s_rights.png")',
    _E,

    'li#li_switch_dbstats',
    _S, _listImgUrl,'b_dbstatistics.png")',
    _E,

    'li#li_flush_privileges',
    _S, _listImgUrl,'s_reload.png")',
    _E,

    'li#li_user_info',
    _S, _listImgUrl, 's_rights.png")',
    _E;
} //END iconic view for ul items

echo
'#body_browse_foreigners',
_S, 'background:', _T, $GLOBALS['cfg']['MainBGC'], //2do??
_M, 'margin:',     _D, '.5em .5em 0 .5em',
_E,

'#bodyquerywindow',
_S, 'background:', _T, $GLOBALS['cfg']['MainBGC'], //2do??
_E,

'#bodythemes',
_S, 'width:',      _D, '50em',
_M, 'margin:',     _D, 'auto',
_M, 'text-align:', _T, 'center',
_E,

'#bodythemes img',
_S, 'border:', _D, '1px solid #000',
_E,

'#bodythemes a:hover img',
_S, 'border:', _D, '1px solid ', _red,
_E,

'#selflink',
_S, 'clear:',           _T, 'both',  //No floating elements allowed on either side
_M, 'margin:',          _T, '.2em',
_M, 'padding:',         _T, '.2em',
_M, 'background:',      _T, $GLOBALS['cfg']['FieldsetFooterBGC'],
_M, 'text-align:',      _T, $right,
_E,

'#div_table_options',
_S, 'clear:',           _T, 'both',
_E,

'#div_table_options', _K,
'#div_table_order',   _K,
'#div_table_rename',  _K,
'#div_table_copy',
_S, 'min-width:', _T, '48%',
_E,

'#div_table_options', _K,
'#div_table_order', _K,
'#div_table_rename', _K,
'#div_table_copy', _K,
'fieldset .formelement', _K,
'form.login label', _K,
'ul#topmenu li', _K,
'div#tablestatistics table', _K,
'#fieldset_add_user_login label', _K,
'#fieldset_add_user_login span.options', _K,
'#fieldset_user_priv div.item', _K,
'#fieldset_user_global_rights fieldset', _K,
'div#serverstatus div#serverstatusqueriesdetails table', _K,
'div#serverstatus table#serverstatustraffic', _K,
'div#serverstatus table#serverstatusconnections', _K,
'div#sqlquerycontainer', _K,
'#mysqlmaininformation', _K,
'#pmamaininformation', _K,
'#fieldset_select_fields', _K,
'#div_table_options', _K,
'#table_innodb_bufferpool_usage', _K, //table_innodb_bufferpool_activity', _K,
'#div_mysql_charset_collations table', _K,
'#qbe_div_table_list', _K,
'#qbe_div_sql_query', _K,
'label.desc',
_S, 'float:', _D, $left,
_E,

'label.desc',
_S, 'width:', _D, '30em',
_E;
?>
