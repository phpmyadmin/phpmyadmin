<?php
// 2do: hover background in Gecko not fitting the img size , has anybody a hint?
// illegal execution path
if (!defined('PMA_MINIMUM_COMMON')) exit();

$parts = explode('.', PMA_VERSION );// f.e. 2.11.0-dev
$last  = explode('-', $parts[2]);   // 2.8.2.4«last part ignored
$iPmaVersion = 10000 * $parts[0] + 100 * $parts[1] + $last[0];// max 99 ?
// new  pma 2.11 style image paths:
if($iPmaVersion < 21100) {
// old style (causing problems with some MS products) :
	$GridImgPath = '../' . $_SESSION['PMA_Theme']->getImgPath();
} else {
	$GridImgPath = $_SESSION['PMA_Theme']->getImgPath();
}

if($iPmaVersion < 20900) {
	echo 'html,
table{font-size:', $GLOBALS['cfg']['FontSize'],'}
';
}
/** general **/
echo '/*theme "Grid-2.9d" Navi 20070211 windkiel pma 2.8..2.1x*/
*{margin:0;padding:0}';//1st: general reset and corrected later, if needed (f.e. hr)
echo '
body{background:', $GLOBALS['cfg']['NaviBackground'],
	';color:', $GLOBALS['cfg']['NaviColor'],
	';padding:.2em';
if (!empty($GLOBALS['cfg']['FontFamily'])) {
	echo ';font-family:', $GLOBALS['cfg']['FontFamily'];
}
echo '}
hr{margin:5px 0 5px}
a img{border:0}
form{display:inline}
select{width:100%}
button{display:inline}'; // buttons in some browsers (eg. Konqueror) are block elements,this breaks design

/* * * * classes * * * * * * * * * * * * * */
/*
specific elements div#pmalogo{background:', $GLOBALS['cfg']['NaviLinkBackground'], '}
div#pmalogo,
*/

/* leave some space between icons and text: */ echo '
.icon{vertical-align:middle;margin:0 .1em 0 .1em}
div#leftframelinks{padding-top:5px;text-align:center}
div#leftframelinks a img.icon{padding:.1em;border:0}
div#leftframelinks,
div#databaseList{margin-bottom:.5em;padding-bottom:.5em;border-bottom:1px solid ', $GLOBALS['cfg']['NaviColor'], '}
div#pmalogo{text-align:center;';
if($iPmaVersion > 20900) {
	echo 'background:', $GLOBALS['cfg']['NaviLinkBackground'];
} else {
	echo 'border-bottom:1px solid ', $GLOBALS['cfg']['NaviColor'];
}
echo "}\n";
if( ($GLOBALS['cfg']['LeftDisplayServers'])&&($GLOBALS['cfg']['LeftFrameLight']) ){
	echo 'div#databaseList{text-align:left}
'; // looks nicer if LeftDisplayServers==true
};

if($GLOBALS['cfg']['LeftPointerEnable']){
	echo 'div#leftframelinks a:hover{background:', $GLOBALS['cfg']['NaviPointerBackground'],
';color:', $GLOBALS['cfg']['NaviPointerColor'], '}
';
}
/*1px solid echo $GLOBALS['cfg']['NaviColor']; top links */

/* serverlist */ ?>
#body_leftFrame #list_server{list-style-image: url("<?php
 echo $GridImgPath; ?>s_host.png");list-style-position: inside;list-style-type:none}
#body_leftFrame #list_server li{font-size:<?php echo $GLOBALS['cfg']['NaviFontPercentage']; ?>}
<?php /* leftdatabaselist */ ?>
div#left_tableList ul{list-style-type:none;list-style-position:outside;font-size:<?php
 echo $GLOBALS['cfg']['NaviFontPercentage']; ?>;background:<?php echo $GLOBALS['cfg']['NaviBackground']; ?>}
div#left_tableList ul ul{font-size:100%}
div#left_tableList a{text-decoration:none;color:<?php echo $GLOBALS['cfg']['NaviLinkColor']; ?>}
<?php
// echo 	background:	',$GLOBALS['cfg']['NaviLinkBackground'];
// makes marking impossibel
?>
a,
div#left_tableList a{padding:0 2px 0 2px}
<?php if ( $GLOBALS['cfg']['LeftPointerEnable'] ){ ?>
a:hover,
div#left_tableList a:hover{background:<?php echo $GLOBALS['cfg']['NaviPointerBackground']; ?>;color:<?php
 echo $GLOBALS['cfg']['NaviPointerColor']; ?>;text-decoration:underline}
<?php } else{ ?>
div#left_tableList a:hover{text-decoration:underline}
<?php } ?>
<?php
if( ($GLOBALS['cfg']['LeftMarkerEnable'] ) && ($GLOBALS['cfg']['NaviMarkedColor']) ){
	echo 'div#left_tableList ul li.marked{background:', $GLOBALS['cfg']['NaviMarkedBackground'], ';color:', $GLOBALS['cfg']['NaviMarkedColor'], '}
';
}/* end marked items */
?>
div#left_tableList img{vertical-align:middle}
div#left_tableList ul ul{padding-left:1px;border-left:1px solid <?php
 echo $GLOBALS['cfg']['NaviColor']; /* ab__cd */ ?>;padding-bottom:1px;border-bottom:1px solid <?php
 echo $GLOBALS['cfg']['NaviColor']; /*2do ltr rtl*/ ?>}
