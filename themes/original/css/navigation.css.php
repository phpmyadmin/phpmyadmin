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
    background: <?php echo $GLOBALS['cfg']['NaviBackground']; ?>;
    color: <?php echo $GLOBALS['cfg']['NaviColor']; ?>;
    width: <?php echo $GLOBALS['cfg']['NaviWidth']; ?>px;
    overflow: hidden;
    position: fixed;
    top: 0;
    <?php echo $left; ?>: 0;
    height: 100%;
    border-<?php echo $right; ?>: 1px solid gray;
    z-index: 800;
}

#pma_navigation_content {
    width: 100%;
    height: 100%;
    position: absolute;
    top: 0;
    <?php echo $left; ?>: 0;
    z-index: 0;
    padding-bottom: 1em;
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
/* specific elements */

#pma_navigation div.pageselector {
    text-align: center;
    margin: 0 0 0;
    margin-<?php echo $left; ?>: 0.75em;
    border-<?php echo $left; ?>: 1px solid #666;
}

#pma_navigation div#pmalogo {
    <?php //better echo $GLOBALS['cfg']['logoBGC']; ?>
    background-color: <?php echo $GLOBALS['cfg']['NaviBackground']; ?>;
    padding: .3em;
}

#pma_navigation div#recentTableList,
#pma_navigation div#FavoriteTableList {
    text-align: center;
    margin-bottom: 0.5em;
}

#pma_navigation #recentTable,
#pma_navigation #FavoriteTable {
    width: 200px;
}

#pma_navigation #pmalogo,
#pma_navigation #serverChoice,
#pma_navigation #navipanellinks,
#pma_navigation #recentTableList,
#pma_navigation #FavoriteTableList,
#pma_navigation #databaseList,
#pma_navigation div.pageselector.dbselector {
    text-align:         center;
    margin-bottom:      0.3em;
    padding-bottom:     0.3em;
    border: 0;
}

#pma_navigation #recentTableList select,
#pma_navigation #FavoriteTableList select,
#pma_navigation #serverChoice select
 {
    width: 80%;
}

#pma_navigation #recentTableList,
#pma_navigation #FavoriteTableList {
    margin-bottom: 0;
    padding-bottom: 0;
}

#pma_navigation_content > img.throbber {
    display: block;
    margin: 0 auto;
}

/* Navigation tree*/
#pma_navigation_tree {
    margin: 0;
    margin-<?php echo $left; ?>: 1em;
    color: #444;
    height: 74%;
    position: relative;
}

#pma_navigation_select_database {
    text-align: left;
    padding: 0 0 0;
    border: 0;
    margin: 0;
}

#pma_navigation_db_select {
    margin-top: 0.5em;
    margin-<?php echo $left; ?>: 0.75em;
}
#pma_navigation_db_select select {
    background: url("./themes/pmahomme/img/select_bg.png") repeat scroll 0 0;
    -webkit-border-radius: 2px;
    border-radius: 2px;
    border: 1px solid #bbb;
    border-top: 1px solid #bbb;
    color: #333;
    padding: 4px 6px;
    margin: 0 0 0;
    width: 92%;
    font-size: 1.11em;
}

#pma_navigation_tree_content {
    width: 100%;
    overflow: hidden;
    overflow-y: auto;
    position: absolute;
    height: 100%;
}
#pma_navigation_tree_content a.hover_show_full {
    position: relative;
    z-index: 100;
}
#pma_navigation_tree a {
    color: <?php echo $GLOBALS['cfg']['NaviColor']; ?>;
}
#pma_navigation_tree a:hover {
    text-decoration: underline;
}
#pma_navigation_tree li.activePointer {
    color: <?php echo $GLOBALS['cfg']['NaviPointerColor']; ?>;
    background-color: <?php echo $GLOBALS['cfg']['NaviPointerBackground']; ?>;
}
#pma_navigation_tree li.selected {
    color: <?php echo $GLOBALS['cfg']['NaviPointerColor']; ?>;
    background-color: <?php echo $GLOBALS['cfg']['NaviPointerBackground']; ?>;
}
#pma_navigation_tree li .dbItemControls {
    padding-left: 4px;
}
#pma_navigation_tree li .navItemControls {
    display: none;
    padding-left: 4px;
}
#pma_navigation_tree li.activePointer .navItemControls {
    display: inline;
    opacity: 0.5;
}
#pma_navigation_tree li.activePointer .navItemControls:hover {
    display: inline;
    opacity: 1.0;
}
#pma_navigation_tree ul {
    clear: both;
    padding: 0;
    list-style-type: none;
    margin: 0;
}
#pma_navigation_tree ul ul {
    position: relative;
}
#pma_navigation_tree li {
    white-space: nowrap;
    clear: both;
    min-height: 16px;
}
#pma_navigation_tree img {
    margin: 0;
}
#pma_navigation_tree i {
    display: block;
}
#pma_navigation_tree div.block {
    position: relative;
    width:1.5em;
    height:1.5em;
    min-width: 16px;
    min-height: 16px;
    float: <?php echo $left; ?>;
}
#pma_navigation_tree div.block.double {
    width: 3em;
}
#pma_navigation_tree div.block i,
#pma_navigation_tree div.block b {
    width: 1.5em;
    height: 1.7em;
    min-width: 16px;
    min-height: 8px;
    position: absolute;
    bottom: 0.7em;
    <?php echo $left; ?>: 0.75em;
    z-index: 0;
}
#pma_navigation_tree div.block i {
    border-<?php echo $left; ?>: 1px solid #666;
    border-bottom: 1px solid #666;
    position: relative;
    z-index: 0;
}
#pma_navigation_tree div.block i.first { /* Removes top segment */
    border-<?php echo $left; ?>: 0;
}
/* Bottom segment for the tree element connections */
#pma_navigation_tree div.block b {
    display: block;
    height: 0.75em;
    bottom: 0;
    <?php echo $left; ?>: 0.75em;
    border-<?php echo $left; ?>: 1px solid #666;
}
#pma_navigation_tree div.block a,
#pma_navigation_tree div.block u {
    position: absolute;
    <?php echo $left; ?>: 50%;
    top: 50%;
    z-index: 10;
}#pma_navigation_tree div.block a + a {
    <?php echo $left; ?>: 100%;
}
#pma_navigation_tree div.block.double a,
#pma_navigation_tree div.block.double u {
    <?php echo $left; ?>: 25%;
}
#pma_navigation_tree div.block.double a + a {
    <?php echo $left; ?>: 70%;
}
#pma_navigation_tree div.block img {
    position: relative;
    top: -0.6em;
    <?php echo $left; ?>: 0;
    margin-<?php echo $left; ?>: -5px;
}
#pma_navigation_tree li.last > ul {
    background: none;
}
#pma_navigation_tree li > a, #pma_navigation_tree li > i {
    line-height: 1.5em;
    height: 1.5em;
    padding-<?php echo $left; ?>: 0.3em;
}
#pma_navigation_tree .list_container {
    border-<?php echo $left; ?>: 1px solid #666;
    margin-<?php echo $left; ?>: 0.75em;
    padding-<?php echo $left; ?>: 0.75em;
}
#pma_navigation_tree .last > .list_container {
    border-<?php echo $left; ?>: 0 solid #666;
}

/* Fast filter */
li.fast_filter {
    padding-<?php echo $left; ?>: 0.75em;
    margin-<?php echo $left; ?>: 0.75em;
    padding-<?php echo $right; ?>: 35px;
    border-<?php echo $left; ?>: 1px solid #666;
}
li.fast_filter input {
    padding-<?php echo $right; ?>: 1.7em;
    width: 100%;
}
li.fast_filter span {
    position: relative;
    <?php echo $right; ?>: 1.5em;
    padding: 0.2em;
    cursor: pointer;
    font-weight: bold;
    color: #800;
}
/* IE10+ has its own reset X */
html.ie li.fast_filter span {
    display: none;
}
html.ie.ie9 li.fast_filter span,
html.ie.ie8 li.fast_filter span {
    display: auto;
}
html.ie li.fast_filter input {
    padding-<?php echo $right; ?>: .2em;
}
html.ie.ie9 li.fast_filter input,
html.ie.ie8 li.fast_filter input {
    padding-<?php echo $right; ?>: 1.7em;
}
li.fast_filter.db_fast_filter {
    border: 0;
}

/* Resize handler */
#pma_navigation_resizer {
    width: 3px;
    height: 100%;
    background-color: #aaa;
    cursor: col-resize;
    position: fixed;
    top: 0;
    <?php echo $left; ?>: 240px;
    z-index: 801;
}
#pma_navigation_collapser {
    width: 20px;
    height: 22px;
    line-height: 22px;
    background: #eee;
    color: #555;
    font-weight: bold;
    position: fixed;
    top: 0;
    <?php echo $left; ?>: <?php echo $GLOBALS['cfg']['NaviWidth']; ?>px;
    text-align: center;
    cursor: pointer;
    z-index: 800;
    text-shadow: 0 1px 0 #fff;
    filter: dropshadow(color=#fff, offx=0, offy=1);
    border: 1px solid #888;
}

#navigation_controls_outer {
    min-height: 21px !important;
}

#navigation_controls_outer.activePointer {
    background-color: transparent !important;
}

#navigation_controls {
    float: right;
    padding-right: 23px;
}

/* Quick warp links */
.pma_quick_warp {
    margin-top: 5px;
    margin-<?php echo $left; ?>: 2px;
    position: relative;
}
.pma_quick_warp .drop_list {
    float: <?php echo $left; ?>;
    margin-<?php echo $left; ?>: 3px;
    padding: 2px 0;
}
.pma_quick_warp .drop_button{
    padding: 0 .3em;
    border: 1px solid #ddd;
    background: #f2f2f2;
    cursor: pointer;
}
.pma_quick_warp .drop_list:hover .drop_button {
    background: #fff;
}
.pma_quick_warp .drop_list ul {
    position: absolute;
    margin: 0;
    padding: 0;
    overflow: hidden;
    overflow-y: auto;
    list-style: none;
    background: #fff;
    border: 1px solid #ddd;
    border-top-<?php echo $right; ?>-radius: 0;
    border-bottom-<?php echo $right; ?>-radius: 0;
    top: 100%;
    <?php echo $left; ?>: 3px;
    <?php echo $right; ?>: 0;
    display: none;
    z-index: 802;
}
.pma_quick_warp .drop_list:hover ul {
    display: block;
}
.pma_quick_warp .drop_list li {
    white-space: nowrap;
}
.pma_quick_warp .drop_list li img {
    vertical-align: sub;
}
.pma_quick_warp .drop_list li:hover {
    background: #f2f2f2;
}
.pma_quick_warp .drop_list a {
    display: block;
    padding: .1em .3em;
}
.pma_quick_warp .drop_list a.favorite_table_anchor {
    clear: left;
    float: left;
    padding: .1em .3em 0;
}
