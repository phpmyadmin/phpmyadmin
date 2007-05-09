<?php 
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * navigation css file from theme
 *
 * @version $Id$
 * @package phpMyAdmin-theme
 * @subpackage Grid
 */

if (!defined('PMA_MINIMUM_COMMON')) exit(); // illegal execution path

$GridImgPath = version_compare(PMA_VERSION,'2.11','lt') ? '../' : ''; 
$GridImgPath .= $_SESSION['PMA_Theme']->getImgPath();

if(version_compare(PMA_VERSION,'2.9','lt')) {
	echo 'html,table{font-size:', $GLOBALS['cfg']['FontSize'], '}';
}

// Whether to activate the Navi marker (bug pma < 2.11)
if (empty($GLOBALS['cfg']['LeftMarkerEnable'])) {
	$GLOBALS['cfg']['LeftMarkerEnable'] = $GLOBALS['cfg']['LeftPointerEnable'];
}

if (!$GLOBALS['cfg']['LeftMarkerEnable'] ) { // init in config.default
	$GLOBALS['cfg']['NaviMarkedBackground'] = $GLOBALS['cfg']['NaviBackground'];
}

/** (left) navigation.php **/
echo '
*{margin:0;padding:0}'; //general reset and defined later if needed (f.e. hr)

//           top left
echo '
body{margin:.2em .3em;background:', $GLOBALS['cfg']['NaviBackground'],
	';color:', $GLOBALS['cfg']['NaviColor'];
//	';padding:.2em';

if (!empty($GLOBALS['cfg']['FontFamily'])) {
	echo ';font-family:', $GLOBALS['cfg']['FontFamily'];
}
echo '}
hr{margin:.5em 0 .3em;color:', $GLOBALS['cfg']['NaviColor'],
	';background:', $GLOBALS['cfg']['NaviColor'], ';border:0;height:1px}
a img{border:0}
form{display:inline}
select{width:100%}
button{display:inline}'; // buttons in some browsers (eg. Konqueror) are block elements,this breaks design

/* * * * classes * * * * * * * * * * * * * */
// specific elements div#pmalogo{background:', $GLOBALS['cfg']['NaviLinkBackground'], '} div#pmalogo,

/* leave some space between icons and text:
 * ??? div#databaseList a{text-decoration:underline}
*/
echo '
.icon{vertical-align:middle;margin:0 .1em 0 .1em}
div#leftframelinks{text-align:center}
div#leftframelinks a img.icon{padding:.2em;border:0}
div#leftframelinks,
div#databaseList{margin-bottom:.3em;padding-bottom:.3em;border-bottom:1px solid ', $GLOBALS['cfg']['NaviColor'], '}
div#pmalogo{text-align:center;';//padding:.7em;
echo ($iPmaVersion > 20900) ? 'background:' . $GLOBALS['cfg']['NaviBackground'] :
	'border-bottom:1px solid ' . $GLOBALS['cfg']['NaviColor'];
echo '}
';

if( $GLOBALS['cfg']['LeftDisplayServers'] && $GLOBALS['cfg']['LeftFrameLight'] ){
	echo 'div#databaseList{text-align:left}
'; // looks nicer if LeftDisplayServers==true - 2do: r2l char sets
};

/***** serverlist *****/
echo '
#body_leftFrame #list_server{list-style-image:url("', $GridImgPath,'s_host.png");',
	'list-style-position: inside;list-style-type:none}
#body_leftFrame #list_server li{font-size:', $GLOBALS['cfg']['NaviFontPercentage'], '}
div#left_tableList ul{list-style-type:none;line-height:110%;list-style-position:outside;font-size:',
	$GLOBALS['cfg']['NaviFontPercentage'], ';background:', $GLOBALS['cfg']['NaviBackground'], '}
div#left_tableList ul ul{font-size:100%}
div#left_tableList a{text-decoration:none;color:', $GLOBALS['cfg']['NaviLinkColor'], '}
a,
div#left_tableList a{padding:.2em}
';

echo ( $GLOBALS['cfg']['LeftPointerEnable'] )
? 'a:hover,
div#left_tableList a:hover{background:' . $GLOBALS['cfg']['NaviPointerBackground'] .
	';color:' . $GLOBALS['cfg']['NaviPointerColor'] .
	';text-decoration:underline}'
:	'div#left_tableList a:hover{text-decoration:underline}';

if ( $GLOBALS['cfg']['LeftMarkerEnable'] && $GLOBALS['cfg']['NaviMarkedColor'] ) {
	echo '
div#left_tableList ul li.marked{background:', $GLOBALS['cfg']['NaviMarkedBackground'],
	';color:', $GLOBALS['cfg']['NaviMarkedColor'], '}';
}

echo '
div#left_tableList img{vertical-align:middle}
div#left_tableList ul ul{padding:.2em;border-left:1px solid ', $GLOBALS['cfg']['NaviColor'], // __ table sep.
	';border-bottom:1px solid ', $GLOBALS['cfg']['NaviColor'], '}';
?>
