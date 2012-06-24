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

body#body_leftFrame {
    background:         <?php echo $GLOBALS['cfg']['NaviBackground']; ?>;
    color:              <?php echo $GLOBALS['cfg']['NaviColor']; ?>;
    margin:             0;
    padding:            0.2em;
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

/******************************************************************************/
/* specific elements */

#body_leftFrame div#pmalogo {
    <?php //better echo $GLOBALS['cfg']['logoBGC']; ?>
    background-color: <?php echo $GLOBALS['cfg']['NaviBackground']; ?>;
    padding:.3em;
}

#body_leftFrame div#recentTableList {
    text-align: center;
    margin-bottom: 0.5em;
}

#body_leftFrame div#recentTableList select {
    width: 100%;
}

#body_leftFrame div#pmalogo,
#body_leftFrame div#leftframelinks,
#body_leftFrame div#databaseList {
    text-align:         center;
    margin-bottom:      0.5em;
    padding-bottom:     0.5em;
}

#body_leftFrame ul#databaseList {
    margin-bottom:      0.5em;
    padding-bottom:     0.5em;
    padding-<?php echo $left; ?>:     1.5em;
    font-style: italic;
}

#body_leftFrame ul#databaseList a {
    display: block;
    font-style: normal;
}

#body_leftFrame div#navidbpageselector a,
#body_leftFrame ul#databaseList a {
    background:         <?php echo $GLOBALS['cfg']['NaviBackground']; ?>;
    color:              <?php echo $GLOBALS['cfg']['NaviColor']; ?>;
}

#body_leftFrame ul#databaseList ul {
    padding-left: 1em;
    padding-right: 0;
}

#body_leftFrame ul#databaseList a:hover {
    background:         <?php echo $GLOBALS['cfg']['NaviPointerBackground']; ?>;
    color:              <?php echo $GLOBALS['cfg']['NaviPointerColor']; ?>;
}

#body_leftFrame ul#databaseList li.selected a {
    background: <?php echo $GLOBALS['cfg']['BrowseMarkerBackground']; ?>;
    color: <?php echo $GLOBALS['cfg']['BrowseMarkerColor']; ?>;
}

#body_leftFrame div#leftframelinks .icon {
    padding:            0;
    margin:             0;
}

#body_leftFrame div#leftframelinks a img.icon {
    margin:             2px;
    border:             0.1em solid <?php echo $GLOBALS['cfg']['NaviColor']; ?>;
    padding:            0;
}

#body_leftFrame div#leftframelinks a:hover img {
    background-color:         <?php echo $GLOBALS['cfg']['NaviPointerBackground']; ?>;
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

#body_leftFrame div#left_tableList ul {
    list-style-type:    none;
    list-style-position: outside;
    margin:             0;
    padding:            0;
    font-size:          80%;
    background:         <?php echo $GLOBALS['cfg']['NaviBackground']; ?>;
}

#body_leftFrame div#left_tableList ul ul {
    font-size:          100%;
}

#body_leftFrame div#left_tableList a {
    background:         <?php echo $GLOBALS['cfg']['NaviBackground']; ?>;
    color:              <?php echo $GLOBALS['cfg']['NaviColor']; ?>;
    text-decoration:    none;
}

#body_leftFrame div#left_tableList a:hover {
    background:         <?php echo $GLOBALS['cfg']['NaviBackground']; ?>;
    color:              <?php echo $GLOBALS['cfg']['NaviColor']; ?>;
    text-decoration:    underline;
}

#body_leftFrame div#left_tableList li {
    margin:             0;
    padding:            0;
    white-space:        nowrap;
}

<?php if ($GLOBALS['cfg']['BrowseMarkerColor']) { ?>
/* marked items */
#body_leftFrame div#left_tableList > ul li.marked > a,
#body_leftFrame div#left_tableList > ul li.marked {
    background: <?php echo $GLOBALS['cfg']['BrowseMarkerBackground']; ?>;
    color: <?php echo $GLOBALS['cfg']['BrowseMarkerColor']; ?>;
}
<?php } ?>

<?php if ($GLOBALS['cfg']['LeftPointerEnable']) { ?>
#body_leftFrame div#left_tableList > ul li:hover > a,
#body_leftFrame div#left_tableList > ul li:hover {
    background:         <?php echo $GLOBALS['cfg']['NaviPointerBackground']; ?>;
    color:              <?php echo $GLOBALS['cfg']['NaviPointerColor']; ?>;
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
    white-space:        nowrap;
    color:              <?php echo $GLOBALS['cfg']['NaviColor']; ?>;
}
#body_leftFrame #serverinfo a:hover {
    background:         <?php echo $GLOBALS['cfg']['NaviPointerBackground']; ?>;
    color:              <?php echo $GLOBALS['cfg']['NaviPointerColor']; ?>;
}

#body_leftFrame #NavFilter {
    display: none;
}

#body_leftFrame #clear_fast_filter,
#body_leftFrame #clear_fast_db_filter {
    color: black;
    cursor: pointer;
    padding: 0;
    margin: 3px 5px 0 -23px;
    float: right;
}

#body_leftFrame #fast_filter,
#body_leftFrame #fast_db_filter {
    width: 90%;
    padding: 2px 0;
    margin: 0;
    border: 0;
}

#body_leftFrame #fast_filter.gray,
#body_leftFrame #fast_db_fiter.gray {
    color: gray;
}
