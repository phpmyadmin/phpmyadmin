<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * navigation css file from theme Original
 *
 * @package PhpMyAdmin-theme
 * @subpackage pmahomme
 */

// unplanned execution path
if (!defined('PMA_MINIMUM_COMMON')) {
    exit();
}
?>
/******************************************************************************/
/* general tags */
html {
    font-size: <?php echo (null !== $GLOBALS['PMA_Config']->get('fontsize') ? $GLOBALS['PMA_Config']->get('fontsize') : $_COOKIE['pma_fontsize']); ?>;
}

input, select, textarea {
    font-size: 1em;
}

body {
<?php if (! empty($GLOBALS['cfg']['FontFamily'])) { ?>
    font-family:        <?php echo $GLOBALS['cfg']['FontFamily']; ?>;
<?php } ?>
    background:         url(./themes/pmahomme/img/left_nav_bg.png) repeat-y right 0% #f3f3f3;
    border-right:       1px solid #aaa;
    color:              <?php echo $GLOBALS['cfg']['NaviColor']; ?>;
    margin:             0;
    padding:            0;
}

a img {
    border: 0;
}

a:link,
a:visited,
a:active {
    text-decoration:    none;
    color:              #0000FF;
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

/* buttons in some browsers (eg. Konqueror) are block elements,
   this breaks design */
button {
    display:            inline;
}

/******************************************************************************/
/* classes */

.navi_dbName {
    font-weight:    bold;
    color:          <?php echo $GLOBALS['cfg']['NaviDatabaseNameColor']; ?>;
}

/******************************************************************************/
/* specific elements */

div#pmalogo {
    <?php //better echo $GLOBALS['cfg']['logoBGC']; ?>
}

div#recentTableList {
    text-align: center;
    margin: 20px 10px 0px 10px;
}

div#recentTableList select {
    width: 100%;
}

div#pmalogo,
div#leftframelinks,
div#databaseList {
    text-align:         center;
    margin:             5px 10px 0px 10px;
}

ul#databaseList {
    margin: 0.8em 0px;
    padding-bottom:     0.5em;
    padding-<?php echo $left; ?>:     0.3em;
    font-style: italic;
}

ul#databaseList span {
    padding: 5px;
}

ul#databaseList a {
    color: #333;
    background: url(./themes/pmahomme/img/database.png) no-repeat 0 5px transparent;
    display: block;
    text-indent: 0;
    padding: 5px 5px 5px 25px;
    font-style: normal;
}

div#navidbpageselector {
    margin: 0.1em;
    text-align: center;
}

div#navidbpageselector a,
div#navidbpageselector select{
    color: #333;
    margin: 0.2em;
}

ul#databaseList ul {
    margin: 0;
    padding: 0;
}

ul#databaseList li {
    list-style: none;
    text-indent: 20px;
    margin: 0;
    padding: 0;
}

ul#databaseList a:hover {
    background-color: #e4e4e4;
}

ul#databaseList li.selected a {
    background: <?php echo $GLOBALS['cfg']['BrowseMarkerBackground']; ?>;
    color: <?php echo $GLOBALS['cfg']['BrowseMarkerColor']; ?>;
}

div#leftframelinks .icon {
    padding:            0;
    margin:             0;
}

div#reloadlink a img,
div#leftframelinks a img.icon {
    margin:        0.3em;
    margin-top:    0.7em;
    border:        0;
}

div#leftframelinks a:hover img {

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
}

div#left_tableList {margin:10px 10px 0 10px;}
div#left_tableList ul {
    list-style-type:    none;
    list-style-position: outside;
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
    background:         <?php echo $GLOBALS['cfg']['NaviBackground']; ?>;
    color:              <?php echo $GLOBALS['cfg']['NaviColor']; ?>;
    text-decoration:    underline;
}

div#left_tableList li {
    margin:             0;
    padding:            2px 0;
    white-space:        nowrap;
}

#newtable {
    margin-top: 15px !important;
}

#newtable a {
    display: block;
    padding: 1px;
    <?php echo $_SESSION['PMA_Theme']->getCssGradient('ffffff', 'cccccc'); ?>
    border: 1px solid #aaa;
    -moz-border-radius: 20px;
    -webkit-border-radius: 20px;
    border-radius: 20px;
}

#newtable li:hover {
    background: transparent !important;
}

#newtable a:hover {
    <?php echo $_SESSION['PMA_Theme']->getCssGradient('cccccc', 'dddddd'); ?>
}

#newtable li a:hover {
    text-decoration: none;
}

select{
    -moz-border-radius:2px 2px 2px 2px;
    -moz-box-shadow:0 1px 2px #DDDDDD;
    border:1px solid #aaa;
    color:#333333;
    padding:3px;
    background:url(./themes/pmahomme/img/input_bg.gif);
}

<?php if ($GLOBALS['cfg']['BrowseMarkerColor']) { ?>
/* marked items */
div#left_tableList > ul li.marked > a,
div#left_tableList > ul li.marked {
    background: #e4e4e4;
    color: <?php echo $GLOBALS['cfg']['BrowseMarkerColor']; ?>;
}
<?php } ?>

<?php if ($GLOBALS['cfg']['LeftPointerEnable']) { ?>
div#left_tableList > ul li:hover > a,
div#left_tableList > ul li:hover {
    background:         #e4e4e4;
    color:              <?php echo $GLOBALS['cfg']['NaviPointerColor']; ?>;
}
<?php } ?>

div#left_tableList img {
    padding:            0;
    vertical-align:     middle;
}

div#left_tableList ul ul {
    margin-<?php echo $left; ?>:        0;
    padding-<?php echo $left; ?>:       0.1em;
    border-<?php echo $left; ?>:        0.1em solid <?php echo $GLOBALS['cfg']['NaviColor']; ?>;
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
#reloadlink {
    clear: both;
    float: <?php echo $right; ?>;
    display: block;
    padding: 1em;
}

#NavFilter {
    display: none;
}

#clear_fast_filter {
    background: white;
    color: black;
    cursor: pointer;
    padding: 0;
    margin: 0;
    position: relative;
    right: 3ex;
}

#fast_filter {
    width: 85%;
    padding: 0.1em;
}

#fast_filter.gray {
    color: gray;
}