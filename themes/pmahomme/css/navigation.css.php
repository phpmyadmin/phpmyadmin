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

#pma_navigation {
    width: 240px;
    overflow: hidden;
    position: fixed;
    top: 0;
    <?php echo $left; ?>: 0;
    height: 100%;
    background: url(./themes/pmahomme/img/left_nav_bg.png) repeat-y right 0% #f3f3f3;
    color: <?php echo $GLOBALS['cfg']['NaviColor']; ?>;
}

#pma_navigation ul {
    margin: 0;
}

#pma_navigation form {
    margin: 0;
    padding: 0;
    display: inline;
}

#pma_navigation select#select_server,
#pma_navigation select#lightm_db {
    width: 100%;
}

/******************************************************************************/
/* classes */

#pma_navigation .navi_dbName {
    font-weight: bold;
    color: <?php echo $GLOBALS['cfg']['NaviDatabaseNameColor']; ?>;
}

/******************************************************************************/
/* specific elements */

#pma_navigation div#pmalogo {
    <?php //better echo $GLOBALS['cfg']['logoBGC']; ?>
}

#pma_navigation div#recentTableList {
    text-align: center;
    margin: 20px 10px 0px 10px;
}

#pma_navigation div#recentTableList select {
    width: 100%;
}

#pma_navigation div#pmalogo,
#pma_navigation div#leftframelinks,
#pma_navigation div#databaseList {
    text-align: center;
    margin: 5px 10px 0px 10px;
}

#pma_navigation ul#databaseList {
    margin: .8em 0px;
    padding-bottom: .5em;
    padding-<?php echo $left; ?>: .3em;
    font-style: italic;
}

#pma_navigation ul#databaseList span {
    padding: 5px;
}

#pma_navigation ul#databaseList a {
    color: #333;
    background: url(./themes/pmahomme/img/database.png) no-repeat 0 5px transparent;
    display: block;
    text-indent: 0;
    padding: 5px 5px 5px 25px;
    font-style: normal;
    border-<?php echo $right; ?>: 1px solid #aaa;
}

#pma_navigation div.pageselector {
    margin: .1em;
    text-align: center;
}

#pma_navigation div.pageselector a,
#pma_navigation div.pageselector select{
    color: #333;
    margin: .2em;
}

#pma_navigation ul#databaseList ul {
    margin: 0;
    padding: 0;
}

#pma_navigation ul#databaseList li {
    list-style: none;
    text-indent: 20px;
    margin: 0;
    padding: 0;
}

#pma_navigation ul#databaseList a:hover {
    background-color: #e4e4e4;
}

#pma_navigation ul#databaseList li.selected a {
    background: <?php echo $GLOBALS['cfg']['BrowseMarkerBackground']; ?>;
    color: <?php echo $GLOBALS['cfg']['BrowseMarkerColor']; ?>;
}

#pma_navigation div#leftframelinks .icon {
    padding: 0;
    margin: 0;
}

#pma_navigation div#reloadlink a img,
#pma_navigation div#leftframelinks a img.icon {
    margin: .3em;
    margin-top: .7em;
    border: 0;
}

#pma_navigation div#leftframelinks a:hover img {

}

/* serverlist */
#pma_navigation #list_server {
    list-style-image: url(<?php echo $GLOBALS['cfg']['ThemePath']; ?>/original/img/s_host.png);
    list-style-position: inside;
    list-style-type: none;
    margin: 0;
    padding: 0;
}

#pma_navigation #list_server li {
    margin: 0;
    padding: 0;
}

#pma_navigation div#left_tableList {margin:10px 10px 0 10px;}
#pma_navigation div#left_tableList ul {
    list-style-type: none;
    list-style-position: outside;
    margin: 0;
    padding: 0;
}

#pma_navigation div#left_tableList ul ul {
    font-size: 100%;
}

#pma_navigation div#left_tableList a {
    color: <?php echo $GLOBALS['cfg']['NaviColor']; ?>;
    text-decoration: none;
}

#pma_navigation div#left_tableList a:hover {
    background: <?php echo $GLOBALS['cfg']['NaviBackground']; ?>;
    color: <?php echo $GLOBALS['cfg']['NaviColor']; ?>;
    text-decoration: underline;
}

#pma_navigation div#left_tableList li {
    margin: 0;
    padding: 2px 0;
    white-space: nowrap;
}

#pma_navigation #newtable {
    margin-top: 15px !important;
}

#pma_navigation #newtable a {
    display: block;
    padding: 1px;
    <?php echo $_SESSION['PMA_Theme']->getCssGradient('ffffff', 'cccccc'); ?>
    border: 1px solid #aaa;
    -moz-border-radius: 20px;
    -webkit-border-radius: 20px;
    border-radius: 20px;
}

#pma_navigation #newtable li:hover {
    background: transparent !important;
}

#pma_navigation #newtable a:hover {
    <?php echo $_SESSION['PMA_Theme']->getCssGradient('cccccc', 'dddddd'); ?>
}

#pma_navigation #newtable li a:hover {
    text-decoration: none;
}


<?php if ($GLOBALS['cfg']['BrowseMarkerColor']) { ?>
/* marked items */
#pma_navigation div#left_tableList > ul li.marked > a,
#pma_navigation div#left_tableList > ul li.marked {
    background: #e4e4e4;
    color: <?php echo $GLOBALS['cfg']['BrowseMarkerColor']; ?>;
}
<?php } ?>

<?php if ($GLOBALS['cfg']['LeftPointerEnable']) { ?>
#pma_navigation div#left_tableList > ul li:hover > a,
#pma_navigation div#left_tableList > ul li:hover {
    background: #e4e4e4;
    color: <?php echo $GLOBALS['cfg']['NaviPointerColor']; ?>;
}
<?php } ?>

#pma_navigation div#left_tableList img {
    padding: 0;
    vertical-align: middle;
}

#pma_navigation div#left_tableList ul ul {
    margin-<?php echo $left; ?>: 0;
    padding-<?php echo $left; ?>: .1em;
    border-<?php echo $left; ?>: .1em solid <?php echo $GLOBALS['cfg']['NaviColor']; ?>;
    padding-bottom: .1em;
    border-bottom: .1em solid <?php echo $GLOBALS['cfg']['NaviColor']; ?>;
}

/* for the servers list in navi panel */
#pma_navigation #serverinfo .item {
    white-space: nowrap;
    color: <?php echo $GLOBALS['cfg']['NaviColor']; ?>;
}
#pma_navigation #serverinfo a:hover {
    background: <?php echo $GLOBALS['cfg']['NaviPointerBackground']; ?>;
    color: <?php echo $GLOBALS['cfg']['NaviPointerColor']; ?>;
}
#pma_navigation #reloadlink {
    clear: both;
    float: <?php echo $right; ?>;
    display: block;
    padding: 1em;
}

#pma_navigation #NavFilter {
    display: none;
}

#pma_navigation #clear_fast_filter,
#pma_navigation #clear_fast_db_filter {
    color: black;
    cursor: pointer;
    padding: 0;
    margin: 0;
}

#pma_navigation #fast_filter {
    width: 85%;
    padding: .1em;
    margin-right: 0;
    margin-left: 0;
}

#pma_navigation #fast_db_filter {
    width: 85%;
    padding: .1em;
    margin-right: 0;
    margin-left: 10px;
}

#pma_navigation #fast_filter.gray,
#pma_navigation #fast_db_filter.gray {
    color: gray;
}
