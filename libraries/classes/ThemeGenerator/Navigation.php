<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Creates Navigation.css.php file
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin\ThemeGenerator;

/**
 * Function to create Navigation.css.php in phpMyAdmin
 *
 * @package PhpMyAdmin
 */
class Navigation
{

    /**
     * Creates navigation.css.php
     *
     * @param string $name name of new theme
     *
     * @return null
     */
    public function createNavigationFile($name)
    {
        $file = fopen("themes/" . $name . "/scss/_navigation.scss", "w");

        $txt = '#pma_navigation {
                    width: $navi-width;
                    position: fixed;
                    top: 0;
                    left: 0;
                    height: 100vh;';
        $txt .= '    background: linear-gradient(to right, $navi-background , $main-background );';
        $txt .= '    color: $navi-color;';
        $txt .= '    z-index: 800;';
        $txt .= '}';

        $txt .= '#pma_navigation_header {
                overflow: hidden;
                }';

        $txt .= '#pma_navigation_content {
                    width: 100%;
                    height: 100%;
                    position: absolute;
                    top: 0;
                    left: 0;
                    z-index: 0;
                }';

        $txt .= '#pma_navigation ul {
                margin: 0;
                }';

        $txt .= '#pma_navigation form {
                margin: 0;
                padding: 0;
                display: inline;
            }';

        $txt .='#pma_navigation select#select_server,
                #pma_navigation select#lightm_db {
                    width: 100%;
                }
                #pma_navigation div.pageselector {
                text-align: center;
                margin: 0;
                margin-left: 0.75em;
                border-left: 1px solid #666;
            }';

        $txt .= '#pma_navigation #pmalogo,
        #pma_navigation #serverChoice,
        #pma_navigation #navipanellinks,
        #pma_navigation #recentTableList,
        #pma_navigation #favoriteTableList,
        #pma_navigation #databaseList,
        #pma_navigation div.pageselector.dbselector {
            text-align: center;
            padding: 5px 10px 0;
            border: 0;
        }';
        $txt .= '#pma_navigation #recentTable,';
        $txt .= '#pma_navigation #favoriteTable {';
        $txt .= '    width: 200px;';
        $txt .= '}';

        $txt .= '#pma_navigation #favoriteTableList select,';
        $txt .= '#pma_navigation #serverChoice select';
        $txt .= ' {';
        $txt .= '    width: 80%;';
        $txt .= '}';

        $txt .= '#pma_navigation_content > img.throbber {';
        $txt .= '    display: none;';
        $txt .= '    margin: .3em auto 0;';
        $txt .= '}';

        $txt .= '#pma_navigation_tree {';
        $txt .= '    margin: 0;';
        $txt .= '    margin-left: 5px;';
        $txt .= '    overflow: hidden;';
        $txt .= '    color: #444;';
        $txt .= '    height: 74%;';
        $txt .= '    position: relative;';
        $txt .= '}';
        $txt .= '#pma_navigation_select_database {';
        $txt .= '    text-align: left;';
        $txt .= '    padding: 0 0 0;';
        $txt .= '    border: 0;';
        $txt .= '    margin: 0;';
        $txt .= '}';
        $txt .= '#pma_navigation_db_select {';
        $txt .= '    margin-top: 0.5em;';
        $txt .= '    margin-left: 0.75em;';
        $txt .= '}';
        $txt .= '#pma_navigation_db_select select {';
        $txt .= '    background: url("../../pmahomme/img/select_bg.png") repeat scroll 0 0;';
        $txt .= '    -webkit-border-radius: 2px;';
        $txt .= '    border-radius: 2px;';
        $txt .= '    border: 1px solid #bbb;';
        $txt .= '    border-top: 1px solid #bbb;';
        $txt .= '    color: #333;';
        $txt .= '    padding: 4px 6px;';
        $txt .= '    margin: 0 0 0;';
        $txt .= '    width: 92%;';
        $txt .= '    font-size: 1.11em;';
        $txt .= '}';
        $txt .= '#pma_navigation_tree_content {';
        $txt .= '    width: 100%;';
        $txt .= '    overflow: hidden;';
        $txt .= '    overflow-y: auto;';
        $txt .= '    position: absolute;';
        $txt .= '    height: 100%;';
        $txt .= '}';
        $txt .= '#pma_navigation_tree_content a.hover_show_full {';
        $txt .= '    position: relative;';
        $txt .= '    z-index: 100;';
        $txt .= '    vertical-align: sub;';
        $txt .= '}';
        $txt .= '#pma_navigation_tree a {';
        $txt .= '    color: $navi-color;';
        $txt .= '#pma_navigation_tree a:hover {';
        $txt .= '    text-decoration: underline;';
        $txt .= '}';
        $txt .= '#pma_navigation_tree li.activePointer {';
        $txt .= '    color: $navi-pointer-color;';
        $txt .= '    background-color: $navi-pointer-background;';
        $txt .= '}';

        $txt .= '#pma_navigation_tree li.selected {';
        $txt .= '    color: $navi-pointer-color;';
        $txt .= '    background-color: $navi-pointer-background;';
        $txt .= '}';

        $txt .='#pma_navigation_tree li .dbItemControls {
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
        #pma_navigation_tree li,
        #pma_navigation_tree li.fast_filter {
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
            width: 1.5em;
            height: 1.5em;
            min-width: 16px;
            min-height: 16px;
            float: left;
        }';

        $txt .= '#pma_navigation_tree div.block.double {';
        $txt .= '    width: 2.5em;';
        $txt .= '}';
        $txt .= '#pma_navigation_tree div.block i,';
        $txt .= '#pma_navigation_tree div.block b {';
        $txt .= '    width: 1.5em;';
        $txt .= '    height: 1.7em;';
        $txt .= '    min-width: 16px;';
        $txt .= '    min-height: 8px;';
        $txt .= '    position: absolute;';
        $txt .= '    bottom: 0.7em;';
        $txt .= '    left: 0.75em;';
        $txt .= '    z-index: 0;';
        $txt .= '}';
        $txt .= '#pma_navigation_tree div.block i { /* Top and right segments for the tree element connections */';
        $txt .= '    display: block;';
        $txt .= '    border-left: 1px solid #666;';
        $txt .= '    border-bottom: 1px solid #666;';
        $txt .= '    position: relative;';
        $txt .= '    z-index: 0;';
        $txt .= '}';
        $txt .= '#pma_navigation_tree div.block i.first { /* Removes top segment */';
        $txt .= '    border-left: 0;';
        $txt .= '}';
        $txt .= '#pma_navigation_tree div.block b { /* Bottom segment for the tree element connections */';
        $txt .= '    display: block;';
        $txt .= '    height: 0.75em;';
        $txt .= '    bottom: 0;';
        $txt .= '    left: 0.75em;';
        $txt .= '    border-left: 1px solid #666;';
        $txt .= '}';
        $txt .= '#pma_navigation_tree div.block a,';
        $txt .= '#pma_navigation_tree div.block u {';
        $txt .= '    position: absolute;';
        $txt .= '    left: 50%;';
        $txt .= '    top: 50%;';
        $txt .= '    z-index: 10;';
        $txt .= '}';
        $txt .= '#pma_navigation_tree div.block a + a {';
        $txt .= '    left: 100%;';
        $txt .= '}';
        $txt .= '#pma_navigation_tree div.block.double a,';
        $txt .= '#pma_navigation_tree div.block.double u {';
        $txt .= '    left: 33%;';
        $txt .= '}';
        $txt .= '#pma_navigation_tree div.block.double a + a {';
        $txt .= '    left: 85%;';
        $txt .= '}';
        $txt .= '#pma_navigation_tree div.block img {';
        $txt .= '    position: relative;';
        $txt .= '    top: -0.6em;';
        $txt .= '    left: 0;';
        $txt .= '    margin-left: -7px;';
        $txt .= '}';
        $txt .= '#pma_navigation_tree div.throbber img {';
        $txt .= '    top: 2px;';
        $txt .= '    left: 2px;';
        $txt .= '}';
        $txt .= '#pma_navigation_tree li.last > ul {';
        $txt .= '    background: none;';
        $txt .= '}';
        $txt .= '#pma_navigation_tree li > a, #pma_navigation_tree li > i {';
        $txt .= '    line-height: 1.5em;';
        $txt .= '    height: 1.5em;';
        $txt .= '    padding-left: 0.3em;';
        $txt .= '}';
        $txt .= '#pma_navigation_tree .list_container {';
        $txt .= '    border-left: 1px solid #666;';
        $txt .= '    margin-left: 0.75em;';
        $txt .= '    padding-left: 0.75em;';
        $txt .= '}';
        $txt .= '#pma_navigation_tree .last > .list_container {';
        $txt .= '    border-left: 0 solid #666;';
        $txt .= '}';
        $txt .= '';
        $txt .= 'li.fast_filter {';
        $txt .= '    padding-left: 0.75em;';
        $txt .= '    margin-left: 0.75em;';
        $txt .= '    padding-right: 35px;';
        $txt .= '    border-left: 1px solid #666;';
        $txt .= '    list-style: none;';
        $txt .= '}';
        $txt .= 'li.fast_filter input {';
        $txt .= '    margin: 3px 0 0 0;';
        $txt .= '    font-size: 0.7em;';
        $txt .= '    padding-top: 2px;';
        $txt .= '    padding-bottom: 2px;';
        $txt .= '    padding-left: 4px;';
        $txt .= '    padding-right: 1.7em;';
        $txt .= '    width: 100%;';
        $txt .= '}';
        $txt .= 'li.fast_filter span {';
        $txt .= '    position: relative;';
        $txt .= '    right: 1.5em;';
        $txt .= '    padding: 0.2em;';
        $txt .= '    cursor: pointer;';
        $txt .= '    font-weight: bold;';
        $txt .= '    color: #800;';
        $txt .= '    font-size: 0.7em;';
        $txt .= '}';
        /* IE10+ has its own reset X */
        $txt .= 'html.ie li.fast_filter span {';
        $txt .= '    display: none;';
        $txt .= '}';
        $txt .= 'html.ie.ie9 li.fast_filter span,';
        $txt .= 'html.ie.ie8 li.fast_filter span {';
        $txt .= '    display: auto;';
        $txt .= '}';
        $txt .= 'html.ie li.fast_filter input {';
        $txt .= '    padding-right: .2em;';
        $txt .= '}';
        $txt .= 'html.ie.ie9 li.fast_filter input,';
        $txt .= 'html.ie.ie8 li.fast_filter input {';
        $txt .= '    padding-right: 1.7em;';
        $txt .= '}';
        $txt .= 'li.fast_filter.db_fast_filter {';
        $txt .= '    border: 0;';
        $txt .= '    margin-left: 0;';
        $txt .= '    margin-right: 10px;';
        $txt .= '}';
        $txt .= '#navigation_controls_outer {';
        $txt .= '    min-height: 21px !important;';
        $txt .= '}';
        $txt .= '#navigation_controls_outer.activePointer {';
        $txt .= '    background-color: transparent !important;';
        $txt .= '}';
        $txt .= '#navigation_controls {';
        $txt .= '    float: right;';
        $txt .= '    padding-right: 23px;';
        $txt .= '}';
        $txt .= '#pma_navigation_resizer {';
        $txt .= '    width: 3px;';
        $txt .= '    height: 100%;';
        $txt .= '    background-color: #aaa;';
        $txt .= '    cursor: col-resize;';
        $txt .= '    position: fixed;';
        $txt .= '    top: 0;';
        $txt .= '    left: 240px;';
        $txt .= '    z-index: 801;';
        $txt .= '}';
        $txt .= '#pma_navigation_collapser {';
        $txt .= '    width: 20px;';
        $txt .= '    height: 22px;';
        $txt .= '    line-height: 22px;';
        $txt .= '    background: #eee;';
        $txt .= '    color: #555;';
        $txt .= '    font-weight: bold;';
        $txt .= '    position: fixed;';
        $txt .= '    top: 0;';
        $txt .= '    left: $navi-width;';
        $txt .= '    text-align: center;';
        $txt .= '    cursor: pointer;';
        $txt .= '    z-index: 800;';
        $txt .= '    text-shadow: 0 1px 0 #fff;';
        $txt .= '    filter: dropshadow(color=#fff, offx=0, offy=1);';
        $txt .= '    border: 1px solid #888;';
        $txt .= '}';
        $txt .= '.pma_quick_warp {';
        $txt .= '    margin-top: 5px;';
        $txt .= '    margin-left: 2px;';
        $txt .= '    position: relative;';
        $txt .= '}';
        $txt .= '.pma_quick_warp .drop_list {';
        $txt .= '    float: left;';
        $txt .= '    margin-left: 3px;';
        $txt .= '    padding: 2px 0;';
        $txt .= '}';
        $txt .= '.pma_quick_warp .drop_button {';
        $txt .= '    padding: 0 .3em;';
        $txt .= '    border: 1px solid #ddd;';
        $txt .= '    border-radius: .3em;';
        $txt .= '    background: #f2f2f2;';
        $txt .= '    cursor: pointer;';
        $txt .= '}';
        $txt .= '.pma_quick_warp .drop_list:hover .drop_button {';
        $txt .= '    background: #fff;';
        $txt .= '}';
        $txt .= '.pma_quick_warp .drop_list ul {';
        $txt .= '    position: absolute;';
        $txt .= '    margin: 0;';
        $txt .= '    padding: 0;';
        $txt .= '    overflow: hidden;';
        $txt .= '    overflow-y: auto;';
        $txt .= '    list-style: none;';
        $txt .= '    background: #fff;';
        $txt .= '    border: 1px solid #ddd;';
        $txt .= '    border-radius: .3em;';
        $txt .= '    border-top-right-radius: 0;';
        $txt .= '    border-bottom-right-radius: 0;';
        $txt .= '    box-shadow: 0 0 5px #ccc;';
        $txt .= '    top: 100%;';
        $txt .= '    left: 3px;';
        $txt .= '    right: 0;';
        $txt .= '    display: none;';
        $txt .= '    z-index: 802;';
        $txt .= '}';
        $txt .= '.pma_quick_warp .drop_list:hover ul {';
        $txt .= '    display: block;';
        $txt .= '}';
        $txt .= '.pma_quick_warp .drop_list li {';
        $txt .= '    white-space: nowrap;';
        $txt .= '    padding: 0;';
        $txt .= '    border-radius: 0;';
        $txt .= '}';
        $txt .= '.pma_quick_warp .drop_list li img {';
        $txt .= '    vertical-align: sub;';
        $txt .= '}';
        $txt .= '.pma_quick_warp .drop_list li:hover {';
        $txt .= '    background: #f2f2f2;';
        $txt .= '}';
        $txt .= '.pma_quick_warp .drop_list a {';
        $txt .= '    display: block;';
        $txt .= '    padding: .2em .3em;';
        $txt .= '}';
        $txt .= '.pma_quick_warp .drop_list a.favorite_table_anchor {';
        $txt .= '    clear: left;';
        $txt .= '    float: left;';
        $txt .= '    padding: .1em .3em 0;';
        $txt .= '}}';

        // Check if the file is writable as this condition would only occur if files are overwritten.
        if ($file) {
            fwrite($file, $txt);
            fclose($file);
        } else {
            trigger_error("The _navigation.css.php file is not writable by the webserver process. You must change permissions for the theme generator to be able to write the generated theme.", E_USER_ERROR);
        }
        return null;
    }
}
