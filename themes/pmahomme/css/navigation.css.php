<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Navigation styles for the pmahomme theme
 *
 * @package    PhpMyAdmin-theme
 * @subpackage PMAHomme
 */

// unplanned execution path
if (! defined('PMA_MINIMUM_COMMON') && ! defined('TESTSUITE')) {
    exit();
}
?>

/******************************************************************************/
/* Navigation */

body#body_leftFrame {
    background: url(./themes/pmahomme/img/left_nav_bg.png) repeat-y right 0% #f3f3f3;
    border-right: 1px solid #aaa;
    color: <?php echo $GLOBALS['cfg']['NaviColor']; ?>;
    margin: 0;
    padding: 0;
}

#body_leftFrame ul {
    margin: 0;
}

#body_leftFrame form {
    margin: 0;
    padding: 0;
    display: inline;
}

#body_leftFrame select#select_server,
#body_leftFrame select#lightm_db {
    width: 100%;
}

/******************************************************************************/
/* classes */

#body_leftFrame .navi_dbName {
    font-weight: bold;
    color: <?php echo $GLOBALS['cfg']['NaviDatabaseNameColor']; ?>;
}

/******************************************************************************/
/* specific elements */

#body_leftFrame div#pmalogo {
    <?php //better echo $GLOBALS['cfg']['logoBGC']; ?>
}

#body_leftFrame div#recentTableList {
    text-align: center;
    margin: 20px 10px 0px 10px;
}

#body_leftFrame div#recentTableList select {
    width: 100%;
}

#body_leftFrame div#pmalogo,
#body_leftFrame div#leftframelinks,
#body_leftFrame div#databaseList {
    text-align: center;
    margin: 5px 10px 0px 10px;
}

#body_leftFrame ul#databaseList {
    margin: .8em 0px;
    padding-bottom: .5em;
    padding-<?php echo $left; ?>: .3em;
    font-style: italic;
}

#body_leftFrame ul#databaseList span {
    padding: 5px;
}

#body_leftFrame ul#databaseList a {
    color: #333;
    background: url(./themes/pmahomme/img/database.png) no-repeat 0 5px transparent;
    display: block;
    text-indent: 0;
    padding: 5px 5px 5px 25px;
    font-style: normal;
}

#body_leftFrame div#navidbpageselector {
    margin: .1em;
    text-align: center;
}

#body_leftFrame div#navidbpageselector a,
#body_leftFrame div#navidbpageselector select{
    color: #333;
    margin: .2em;
}

#body_leftFrame ul#databaseList ul {
    margin: 0;
    padding: 0;
}

#body_leftFrame ul#databaseList li {
    list-style: none;
    text-indent: 20px;
    margin: 0;
    padding: 0;
}

#body_leftFrame ul#databaseList a:hover {
    background-color: #e4e4e4;
}

#body_leftFrame ul#databaseList li.selected a {
    background: <?php echo $GLOBALS['cfg']['BrowseMarkerBackground']; ?>;
    color: <?php echo $GLOBALS['cfg']['BrowseMarkerColor']; ?>;
}

#body_leftFrame div#leftframelinks .icon {
    padding: 0;
    margin: 0;
}

#body_leftFrame div#reloadlink a img,
#body_leftFrame div#leftframelinks a img.icon {
    margin: .3em;
    margin-top: .7em;
    border: 0;
}

#body_leftFrame div#leftframelinks a:hover img {

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

#body_leftFrame div#left_tableList {margin:10px 10px 0 10px;}
#body_leftFrame div#left_tableList ul {
    list-style-type: none;
    list-style-position: outside;
    margin: 0;
    padding: 0;
}

#body_leftFrame div#left_tableList ul ul {
    font-size: 100%;
}

#body_leftFrame div#left_tableList a {
    color: <?php echo $GLOBALS['cfg']['NaviColor']; ?>;
    text-decoration: none;
}

#body_leftFrame div#left_tableList a:hover {
    background: <?php echo $GLOBALS['cfg']['NaviBackground']; ?>;
    color: <?php echo $GLOBALS['cfg']['NaviColor']; ?>;
    text-decoration: underline;
}

#body_leftFrame div#left_tableList li {
    margin: 0;
    padding: 2px 0;
    white-space: nowrap;
}

#body_leftFrame #newtable {
    margin-top: 15px !important;
}

#body_leftFrame #newtable a {
    display: block;
    padding: 1px;
    <?php echo $_SESSION['PMA_Theme']->getCssGradient('ffffff', 'cccccc'); ?>
    border: 1px solid #aaa;
    -moz-border-radius: 20px;
    -webkit-border-radius: 20px;
    border-radius: 20px;
}

#body_leftFrame #newtable li:hover {
    background: transparent !important;
}

#body_leftFrame #newtable a:hover {
    <?php echo $_SESSION['PMA_Theme']->getCssGradient('cccccc', 'dddddd'); ?>
}

#body_leftFrame #newtable li a:hover {
    text-decoration: none;
}


<?php if ($GLOBALS['cfg']['BrowseMarkerColor']) { ?>
/* marked items */
#body_leftFrame div#left_tableList > ul li.marked > a,
#body_leftFrame div#left_tableList > ul li.marked {
    background: #e4e4e4;
    color: <?php echo $GLOBALS['cfg']['BrowseMarkerColor']; ?>;
}
<?php } ?>

<?php if ($GLOBALS['cfg']['LeftPointerEnable']) { ?>
#body_leftFrame div#left_tableList > ul li:hover > a,
#body_leftFrame div#left_tableList > ul li:hover {
    background: #e4e4e4;
    color: <?php echo $GLOBALS['cfg']['NaviPointerColor']; ?>;
}
<?php } ?>

#body_leftFrame div#left_tableList img {
    padding: 0;
    vertical-align: middle;
}

#body_leftFrame div#left_tableList ul ul {
    margin-<?php echo $left; ?>: 0;
    padding-<?php echo $left; ?>: .1em;
    border-<?php echo $left; ?>: .1em solid <?php echo $GLOBALS['cfg']['NaviColor']; ?>;
    padding-bottom: .1em;
    border-bottom: .1em solid <?php echo $GLOBALS['cfg']['NaviColor']; ?>;
}

/* for the servers list in navi panel */
#body_leftFrame #serverinfo .item {
    white-space: nowrap;
    color: <?php echo $GLOBALS['cfg']['NaviColor']; ?>;
}
#body_leftFrame #serverinfo a:hover {
    background: <?php echo $GLOBALS['cfg']['NaviPointerBackground']; ?>;
    color: <?php echo $GLOBALS['cfg']['NaviPointerColor']; ?>;
}
#body_leftFrame #reloadlink {
    clear: both;
    float: <?php echo $right; ?>;
    display: block;
    padding: 1em;
}

#body_leftFrame #NavFilter {
    display: none;
}

#body_leftFrame #clear_fast_filter,
#body_leftFrame #clear_fast_db_filter {
    color: black;
    cursor: pointer;
    padding: 0;
    margin: 0;
}

#body_leftFrame #fast_filter {
    width: 85%;
    padding: .1em;
    margin-right: 0;
    margin-left: 0;
}

#body_leftFrame #fast_db_filter {
    width: 85%;
    padding: .1em;
    margin-right: 0;
    margin-left: 10px;
}

#body_leftFrame #fast_filter.gray,
#body_leftFrame #fast_db_filter.gray {
    color: gray;
}
