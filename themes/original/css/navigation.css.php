<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Navigation styles for the original theme
 *
 * @package    PhpMyAdmin-theme
 * @subpackage Original
 */

// unplanned execution path
if (! defined('PMA_MINIMUM_COMMON') && ! defined('TESTSUITE')) {
    exit();
}
?>

/******************************************************************************/
/* Navigation */

#pma_navigation {
    background:         <?php echo $GLOBALS['cfg']['NaviBackground']; ?>;
    color:              <?php echo $GLOBALS['cfg']['NaviColor']; ?>;
    width: 240px;
    overflow: hidden;
    position: fixed;
    top: 0;
    <?php echo $left; ?>: 0;
    height: 100%;
    border-<?php echo $right; ?>: 1px solid gray;
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

/******************************************************************************/
/* specific elements */

#pma_navigation div#pmalogo {
    <?php //better echo $GLOBALS['cfg']['logoBGC']; ?>
    background-color: <?php echo $GLOBALS['cfg']['NaviBackground']; ?>;
    padding:.3em;
}

#pma_navigation div#recentTableList {
    text-align: center;
    margin-bottom: 0.5em;
}

#pma_navigation div#recentTableList select {
    width: 100%;
}

#pma_navigation div#pmalogo,
#pma_navigation div#leftframelinks,
#pma_navigation div#databaseList {
    text-align:         center;
    margin-bottom:      0.5em;
    padding-bottom:     0.5em;
}

#pma_navigation ul#databaseList {
    margin-bottom:      0.5em;
    padding-bottom:     0.5em;
    padding-<?php echo $left; ?>:     1.5em;
    font-style: italic;
}

#pma_navigation ul#databaseList a {
    display: block;
    font-style: normal;
}

#pma_navigation div.pageselector a,
#pma_navigation ul#databaseList a {
    background:         <?php echo $GLOBALS['cfg']['NaviBackground']; ?>;
    color:              <?php echo $GLOBALS['cfg']['NaviColor']; ?>;
}

#pma_navigation ul#databaseList ul {
    padding-left: 1em;
    padding-right: 0;
}

#pma_navigation ul#databaseList a:hover {
    background:         <?php echo $GLOBALS['cfg']['NaviPointerBackground']; ?>;
    color:              <?php echo $GLOBALS['cfg']['NaviPointerColor']; ?>;
}

#pma_navigation ul#databaseList li.selected a {
    background: <?php echo $GLOBALS['cfg']['BrowseMarkerBackground']; ?>;
    color: <?php echo $GLOBALS['cfg']['BrowseMarkerColor']; ?>;
}

#pma_navigation div#leftframelinks .icon {
    padding:            0;
    margin:             0;
}

#pma_navigation div#leftframelinks a img.icon {
    margin:             2px;
    border:             0.1em solid <?php echo $GLOBALS['cfg']['NaviColor']; ?>;
    padding:            0;
}

#pma_navigation div#leftframelinks a:hover img {
    background-color:         <?php echo $GLOBALS['cfg']['NaviPointerBackground']; ?>;
    color:              <?php echo $GLOBALS['cfg']['NaviPointerColor']; ?>;
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
    font-size:          80%;
}

#pma_navigation div#left_tableList ul {
    list-style-type:    none;
    list-style-position: outside;
    margin:             0;
    padding:            0;
    font-size:          80%;
    background:         <?php echo $GLOBALS['cfg']['NaviBackground']; ?>;
}

#pma_navigation div#left_tableList ul ul {
    font-size:          100%;
}

#pma_navigation div#left_tableList a {
    background:         <?php echo $GLOBALS['cfg']['NaviBackground']; ?>;
    color:              <?php echo $GLOBALS['cfg']['NaviColor']; ?>;
    text-decoration:    none;
}

#pma_navigation div#left_tableList a:hover {
    background:         <?php echo $GLOBALS['cfg']['NaviBackground']; ?>;
    color:              <?php echo $GLOBALS['cfg']['NaviColor']; ?>;
    text-decoration:    underline;
}

#pma_navigation div#left_tableList li {
    margin:             0;
    padding:            0;
    white-space:        nowrap;
}

<?php if ($GLOBALS['cfg']['BrowseMarkerColor']) { ?>
/* marked items */
#pma_navigation div#left_tableList > ul li.marked > a,
#pma_navigation div#left_tableList > ul li.marked {
    background: <?php echo $GLOBALS['cfg']['BrowseMarkerBackground']; ?>;
    color: <?php echo $GLOBALS['cfg']['BrowseMarkerColor']; ?>;
}
<?php } ?>

<?php if ($GLOBALS['cfg']['LeftPointerEnable']) { ?>
#pma_navigation div#left_tableList > ul li:hover > a,
#pma_navigation div#left_tableList > ul li:hover {
    background:         <?php echo $GLOBALS['cfg']['NaviPointerBackground']; ?>;
    color:              <?php echo $GLOBALS['cfg']['NaviPointerColor']; ?>;
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
    white-space:        nowrap;
    color:              <?php echo $GLOBALS['cfg']['NaviColor']; ?>;
}
#pma_navigation #serverinfo a:hover {
    background:         <?php echo $GLOBALS['cfg']['NaviPointerBackground']; ?>;
    color:              <?php echo $GLOBALS['cfg']['NaviPointerColor']; ?>;
}

#pma_navigation #NavFilter {
    display: none;
}

#pma_navigation #clear_fast_filter,
#pma_navigation #clear_fast_db_filter {
    color: black;
    cursor: pointer;
    padding: 0;
    margin: 3px 5px 0 -23px;
    float: right;
}

#pma_navigation #fast_filter,
#pma_navigation #fast_db_filter {
    width: 90%;
    padding: 2px 0;
    margin: 0;
    border: 0;
}

#pma_navigation #fast_filter.gray,
#pma_navigation #fast_db_fiter.gray {
    color: gray;
}
