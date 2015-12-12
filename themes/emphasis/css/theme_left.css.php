<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * navigation css file from theme Emphasis
 *
 * @version $Id$
 * @package phpMyAdmin-theme
 * @subpackage Emphasis
 */

// unplanned execution path
if (!defined('PMA_MINIMUM_COMMON')) {
    exit();
}
?>
/******************************************************************************/
/* general tags */
html {
    font-size:		<?php echo (null !== $GLOBALS['PMA_Config']->get('fontsize') ? $GLOBALS['PMA_Config']->get('fontsize') : $_COOKIE['pma_fontsize']); ?>;
}

input,
select,
textarea {
    font-size:		1em;
    border-radius:	0.3em;
}

body {
<?php if (! empty($GLOBALS['cfg']['FontFamily'])) { ?>
    font-family:	<?php echo $GLOBALS['cfg']['FontFamily']; ?>;
<?php } ?>
    background:		<?php echo $GLOBALS['cfg']['NaviBackground']; ?> url('<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>background.png') 0% 0% repeat-x;
    color:		<?php echo $GLOBALS['cfg']['NaviColor']; ?>;
    margin:		0;
    padding:		0.2em 0.2em 0.2em 0.2em;
}

a img {
    border:		0;
}

a:link,
a:visited,
a:active {
    text-decoration:	none;
    color:		#0000FF;
}
a:hover {
    text-decoration:	none;
}

ul {
    margin:		0;
}

form {
    margin:		0;
    padding:		0;
    display:		inline;
}

select#select_server,
select#lightm_db {
    width:		100%;
}

/* buttons in some browsers (eg. Konqueror) are block elements, this breaks design */
button {
    display:		inline;
}

/******************************************************************************/
/* classes */

/* leave some space between icons and text */
.icon {
    vertical-align:	middle;
    margin-right:	0.3em;
    margin-left:	0.3em;
}

.navi_dbName {
    font-weight:	bold;
    color:		<?php echo $GLOBALS['cfg']['NaviDatabaseNameColor']; ?>;
    font-size:		11pt;
    margin-left:	10px;
}

/******************************************************************************/
/* specific elements */

div#pmalogo {
    padding:		.3em;
}

div#pmalogo,
div#leftframelinks,
div#databaseList {
    text-align:		center;
    margin-bottom:	0.5em;
    padding-bottom:	0.5em;
}

ul#databaseList {
    margin-bottom:	0.5em;
    margin-left;	5px;
    padding-bottom:	0.5em;
    padding-left:	1.5em;
}

ul#databaseList li {
    list-style-image:	url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>b_db.png);
}


ul#databaseList a {
    display:		block;
}

div#databaseList form select,
div#navidbpageselector form select {
    background-color:	<?php echo $GLOBALS['cfg']['NaviBackground']; ?>;
    border:		1px solid #909090;
    color:		<?php echo $GLOBALS['cfg']['NaviColor']; ?>;
    margin-top:		5px;
    padding-left:	5px;
    font-size:		0.9em;
}

div#navidbpageselector a,
ul#databaseList a {
    color:		#909090;
}

div#navidbpageselector a:hover,
ul#databaseList a:hover {
    background:		<?php echo $GLOBALS['cfg']['NaviPointerBackground']; ?>;
    color:		<?php echo $GLOBALS['cfg']['NaviColor']; ?>;
}

ul#databaseList a:hover {
    color:		<?php echo $GLOBALS['cfg']['NaviPointerColor']; ?>;
}

ul#databaseList ul {
}

ul#databaseList li.selected a {
    background:		<?php echo $GLOBALS['cfg']['BrowseMarkerBackground']; ?>;
    color:		<?php echo $GLOBALS['cfg']['BrowseMarkerColor']; ?>;
}

div#leftframelinks .icon {
    padding:		0;
    margin:		0;
}

div#leftframelinks a img.icon {
    margin:		0;
    padding:		0.2em;
    border-width:	0px;
}

div#leftframelinks a:hover img {
    background:		<?php echo $GLOBALS['cfg']['NaviPointerBackground']; ?>;
    color:		<?php echo $GLOBALS['cfg']['NaviPointerColor']; ?>;
}

/* serverlist */
#body_leftFrame #list_server {
    list-style-image:	url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_host.png);
    list-style-position: inside;
    list-style-type:	none;
    margin:		0;
    padding:		0;
}

#body_leftFrame #list_server li {
    margin:		0;
    padding:		0;
    font-size:		80%;
}

div#left_tableList ul {
    list-style-type:	none;
    list-style-position: outside;
    margin:		0;
    padding:		0;
    font-size:		80%;
}

div#left_tableList ul ul {
    font-size:          100%;
}

div#left_tableList a {
    color:		#909090;
    text-decoration:	none;
}

div#left_tableList a:hover {
    color:		<?php echo $GLOBALS['cfg']['NaviColor']; ?>;
}

div#left_tableList li {
    margin:		0;
    padding:		0;
    white-space:	nowrap;
}

<?php if ($GLOBALS['cfg']['BrowseMarkerColor']) { ?>
/* marked items */
div#left_tableList > ul li.marked > a,
div#left_tableList > ul li.marked {
    background:		<?php echo $GLOBALS['cfg']['BrowseMarkerBackground']; ?>;
    color:		<?php echo $GLOBALS['cfg']['BrowseMarkerColor']; ?>;
}
<?php } ?>

<?php if ($GLOBALS['cfg']['LeftPointerEnable']) { ?>
div#left_tableList > ul li:hover > a,
div#left_tableList > ul li:hover {
    background:		<?php echo $GLOBALS['cfg']['NaviPointerBackground']; ?>;
    color:		<?php echo $GLOBALS['cfg']['NaviPointerColor']; ?>;
}
<?php } ?>

div#left_tableList img {
    padding:		0;
    vertical-align:	middle;
}

div#left_tableList ul ul {
    margin-left:	5px;
    padding-left:	0.1em;
    border-left:	1px solid #909090;
    padding-bottom:	1px;
    border-bottom:	1px solid #909090;
    font-size:		100%;
}

/* for the servers list in navi panel */
#serverinfo .item {
    white-space:	nowrap;
    color:		<?php echo $GLOBALS['cfg']['NaviColor']; ?>;
}
#serverinfo a:hover {
    background:		<?php echo $GLOBALS['cfg']['NaviPointerBackground']; ?>;
    color:		<?php echo $GLOBALS['cfg']['NaviPointerColor']; ?>;
}

#NavFilter {
    display:		none;
}

#clear_fast_filter {
    background:		white;
    color:		black;
    cursor:		pointer;
    padding:		0;
    margin:		3px 5px 0 -23px;
    position:		relative;
    float:		right;
    font-weight:	bold;
}

#clear_fast_filter:hover {
    color:		#903050;
}

#fast_filter {
    width:		100%;
    padding:		2px 0px;
    margin:		0;
    border:		0;
}

#newtable li a{
}
