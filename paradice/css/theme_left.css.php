<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * navigation css file from theme Paradice
 *
 * @package phpMyAdmin-theme
 * @subpackage Paradice
 */

// unplanned execution path
if (!defined('PMA_MINIMUM_COMMON')) {
    exit();
}
?>
/******************************************************************************/
/* general tags */

body {
<?php if (! empty($GLOBALS['cfg']['FontFamily'])) { ?>
    font-family:        <?php echo $GLOBALS['cfg']['FontFamily']; ?>;
<?php } ?>
	font-size:			0.8em;
    background:         <?php echo $GLOBALS['cfg']['NaviBackground']; ?>;
    color:              <?php echo $GLOBALS['cfg']['NaviColor']; ?>;
    margin:             0;
    padding:            0.2em 0.2em 0.2em 0.2em;
	background-image: 	url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>leftBgnd.png);
	background-position:left top;
	background-repeat: 	repeat-y;
}

a img {
    border: 0;
}

ul {
	margin:0;
}	

form {
    margin:             0;
    padding:            0;
    display:            inline;
}

select#select_server,
select#lightm_db {
    width:              100%;
}

select {
    background-color:   #ffffff;
    color:              #000000;
    width:              100%;
}

/* buttons in some browsers (eg. Konqueror) are block elements,
   this breaks design */
button {
    display:            inline;
}


/******************************************************************************/
/* classes */

/* leave some space between icons and text */
.icon {
    vertical-align:     middle;
    margin-right:       0.3em;
    margin-left:        0.3em;
}

.navi_dbName {
    font-weight:    bold;
    color:          <?php echo $GLOBALS['cfg']['NaviDatabaseNameColor']; ?>;
}

/******************************************************************************/
/* specific elements */

div#pmalogo,
div#leftframelinks,
div#databaseList {
    text-align:         center;
    border-bottom:      0.05em solid <?php echo $GLOBALS['cfg']['NaviColor']; ?>;
    margin-bottom:      0.5em;
    padding-bottom:     0.5em;
}

ul#databaseList {
    border-bottom:      0.05em solid <?php echo $GLOBALS['cfg']['NaviColor']; ?>;
    margin-bottom:      0.5em;
    padding-bottom:     0.5em;
    padding-left: 		1.5em;
    list-style-type:    none;
    list-style-position:outside;
    list-style-image: 	url(<?php echo $GLOBALS['cfg']['ThemePath']; ?>/paradice/img/dbitem_ltr.png);
}

ul#databaseList ul {
    list-style-image: 	url(<?php echo $GLOBALS['cfg']['ThemePath']; ?>/paradice/img/dbitem_ltr2.png);
}

ul#databaseList lia {
    padding-bottom:     0.5em;
}

ul#databaseList a {
    display: block;
}

div#navidbpageselector a,
ul#databaseList a {  
    color:              <?php echo $GLOBALS['cfg']['NaviColor']; ?>;
}

ul#databaseList a:hover {
    background:         <?php echo $GLOBALS['cfg']['NaviPointerBackground']; ?>;
    color:              <?php echo $GLOBALS['cfg']['NaviPointerColor']; ?>;
}

ul#databaseList li.selected a {
    background: <?php echo $GLOBALS['cfg']['BrowseMarkerBackground']; ?>;
    color: <?php echo $GLOBALS['cfg']['BrowseMarkerColor']; ?>;
}

div#leftframelinks .icon {
    padding:            0;
    margin:             0;
}

div#leftframelinks a img.icon {
    margin:             0;
    padding:            0.2em;
    border:             0;
}

div#leftframelinks a:hover {
    background:         <?php echo $GLOBALS['cfg']['NaviPointerBackground']; ?>;
    color:              <?php echo $GLOBALS['cfg']['NaviPointerColor']; ?>;
}

/* serverlist */
#body_leftFrame #list_server {
    list-style-image: url(<?php echo $GLOBALS['cfg']['ThemePath']; ?>/original/img/s_host.png);
    list-style-position: inside;
    list-style-type: none;
    margin: 0;
    padding: 0;
}

#body_leftFrame #list_server li {
    margin: 0;
    padding: 0;
    font-size:          80%;
}

/* leftdatabaselist */
div#left_tableList {
	background-color:	none;	
}
div#left_tableList ul  {
    list-style-type:    none;
    list-style-position: outside;
    list-style-image: url(<?php echo $GLOBALS['cfg']['ThemePath']; ?>/paradice/img/bd_sbrowse.png);
    margin:             0;
    padding:            0;
}

div#left_tableList ul ul {
    font-size:          100%;
}

div#left_tableList a {
    color:              <?php echo $GLOBALS['cfg']['NaviColor']; ?>;
    text-decoration:    none;
}

div#left_tableList a:hover {
    color:              <?php echo $GLOBALS['cfg']['NaviColor']; ?>;
    text-decoration:    underline;
}

div#left_tableList li {
    margin:             0;
    padding:            0;
    white-space:        nowrap;
}

<?php if ($GLOBALS['cfg']['BrowseMarkerColor']) { ?>
/* marked items */
div#left_tableList > ul li.marked > a,
div#left_tableList > ul li.marked {
    background: <?php echo $GLOBALS['cfg']['NaviPointerBackground']; ?>;
    color: <?php echo $GLOBALS['cfg']['BrowseMarkerColor']; ?>;
}
<?php } ?>

<?php if ($GLOBALS['cfg']['LeftPointerEnable']) { ?>
div#left_tableList > ul li:hover > a,
div#left_tableList > ul li:hover {
    background:         <?php echo $GLOBALS['cfg']['NaviPointerBackground']; ?>;
    color:              <?php echo $GLOBALS['cfg']['NaviPointerColor']; ?>;
}
<?php } ?>

div#left_tableList img {
    padding:            0;
    vertical-align:     middle;
}

div#left_tableList ul ul {
    margin-left:        0;
    padding-left:       0.1em;
    border-left:        0.1em solid <?php echo $GLOBALS['cfg']['NaviColor']; ?>;
    padding-bottom:     0.1em;
    border-bottom:      0.1em solid <?php echo $GLOBALS['cfg']['NaviColor']; ?>;
}
/* for the servers list in navi panel */
#serverinfo .item {
    white-space:        nowrap;
    color:              <?php echo $GLOBALS['cfg']['NaviColor']; ?>;
}
#serverinfo a:hover {
    background:         <?php echo $GLOBALS['cfg']['NaviPointerBackground']; ?>;
    color:              <?php echo $GLOBALS['cfg']['NaviPointerColor']; ?>;
}
