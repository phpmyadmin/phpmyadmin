<?php /*jw <-- diff from original  20060922 */
// 2do: hover background in Gecko not fitting the img size , a hint somebody?
// unplanned execution path
if (!defined('PMA_MINIMUM_COMMON')) {
	exit();
}
$VER = substr(PMA_VERSION,0,3); /*jw for use with older versions: */
if (('2.7'==$VER)or('2.8'==$VER)) echo "/*$VER*/
html {
	font-size:	", $GLOBALS['cfg']['FontSize'], ';
}
';
?>

/** Navi general v.2.0 for pma2.7+ windkiel 20060917 **/
* { <?php /*jw general reset and corrected later, if needed (f.e. hr) */ ?>
	margin:	0;
	padding:	0
}

body {
<?php if (! empty($GLOBALS['cfg']['FontFamily'])) { ?>
	font-family:	<?php echo $GLOBALS['cfg']['FontFamily']; ?>;
<?php } ?>
	background:	<?php echo $GLOBALS['cfg']['NaviBackground']; ?>;
	color:	<?php echo $GLOBALS['cfg']['NaviColor']; ?>;
	padding:	.2em;
}

hr {
	margin:	5px	0	5px
}

a img {
	border:	0;
}

form {
	display:	inline;
}

select {
	width:	100%;
}

/* buttons in some browsers (eg. Konqueror) are block elements,
   this breaks design */
button {
	display:	inline;
}


<?php /* * * * * * * * * * * * * * * * * */ ?>
/* classes */

/* leave some space between icons and text */
.icon {
	vertical-align:	middle;
	margin:	0	.3em	0	.3em;
}


<?php /* * * * * * * * * * * * * * * * * */ ?>
/* specific elements */

div#pmalogo,
div#leftframelinks {
	text-align:	center;
}
div#pmalogo,
div#leftframelinks,
div#databaseList {
	border-bottom:	1px solid <?php echo $GLOBALS['cfg']['NaviColor']; ?>;
	margin-bottom:	.5em;
	padding-bottom:	.5em;
}
<?php if( ($GLOBALS['cfg']['LeftDisplayServers'])&&($GLOBALS['cfg']['LeftFrameLight']) ) {
	echo '
div#databaseList {
	text-align:	left;
}'; // looks nicer if LeftDisplayServers==true
	}; ?>


div#leftframelinks a img.icon {
	padding:	.2em;
	border:	1px solid <?php echo $GLOBALS['cfg']['NaviColor']; //top links ?>;
}

div#leftframelinks a:hover {
	background:	<?php echo $GLOBALS['cfg']['NaviPointerBackground']; ?>;
	color:	<?php echo $GLOBALS['cfg']['NaviPointerColor']; ?>;
}


/* serverlist */
#body_leftFrame #list_server {
	list-style-image: url(../<?php echo $GLOBALS['cfg']['ThemePath']; ?>/original/img/s_host.png);
	list-style-position: inside;
	list-style-type:	none;
}

#body_leftFrame #list_server li {
	font-size:	<?php echo $GLOBALS['cfg']['NaviFontPercentage']; ?>;
}


/* leftdatabaselist */
div#left_tableList ul {
	list-style-type:	none;
	list-style-position:outside;
	font-size:	<?php echo $GLOBALS['cfg']['NaviFontPercentage']; ?>;
	background:	<?php echo $GLOBALS['cfg']['NaviBackground']; ?>;
}


div#left_tableList ul ul {
	font-size:	100%;
}


/*jw*/
div#left_tableList a {
	text-decoration:	none;
	color:	<?php echo $GLOBALS['cfg']['NaviLinkColor']; ?>;
<?php
// echo 	background:	',$GLOBALS['cfg']['NaviLinkBackground'];
// makes marking impossibel
?>
}

a,
div#left_tableList a {
	padding:	0	2px	0	2px;
}

<?php if ( $GLOBALS['cfg']['LeftPointerEnable'] ) { ?>
a:hover,
div#left_tableList a:hover {
	background:	<?php echo $GLOBALS['cfg']['NaviPointerBackground']; ?>;
	color:	<?php echo $GLOBALS['cfg']['NaviPointerColor']; ?>;
	text-decoration:	underline;
}
<?php } else { ?>
div#left_tableList a:hover {
/*
	background:	<?php echo $GLOBALS['cfg']['NaviBackground']; ?>;
	color:	red <?php #echo $GLOBALS['cfg']['NaviColor']; ?>;
*/
	text-decoration:	underline;
}
<?php } ?>


<?php if ($GLOBALS['cfg']['NaviMarkedColor']) { ?>
/* marked items */
div#left_tableList ul li.marked {
	background:	<?php echo $GLOBALS['cfg']['NaviMarkedBackground']; ?>;
	color:	<?php echo $GLOBALS['cfg']['NaviMarkedColor']; ?>;
}
<?php } ?>

div#left_tableList img {
	vertical-align:	middle;
}

div#left_tableList ul ul {
	padding-left:	1px;
	border-left:	1px solid <?php echo $GLOBALS['cfg']['NaviColor']; // ab__cd ?>;
	padding-bottom:	1px;
	border-bottom:	1px solid <?php echo $GLOBALS['cfg']['NaviColor']; //2do ltr rtl ?>;
}
