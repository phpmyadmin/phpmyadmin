<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Designer styles for the pmahomme theme
 *
 * @package    PhpMyAdmin-theme
 * @subpackage PMAHomme
 */

// unplanned execution path
if (! defined('PMA_MINIMUM_COMMON') && ! defined('TESTSUITE')) {
    exit();
}
?>

/* Designer */
.input_tab {
    background-color: #A6C7E1;
    color: #000;
}

.content_fullscreen {
    position: relative;
    overflow: auto;
}

#canvas_outer {
    position: relative;
}

#canvas {
    background-color: #fff;
    color: #000;
}

canvas.pmd {
    display: inline-block;
    overflow: hidden;
    text-align: left;
}

canvas.pmd * {
    behavior: url(#default#VML);
}

.pmd_tab {
    background-color: #fff;
    color: #000;
    border-collapse: collapse;
    border: 1px solid #aaa;
    z-index: 1;
    -moz-user-select: none;
}

.tab_zag {
    background-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath('pmd/Header.png'); ?>);
    background-repeat: repeat-x;
    text-align: center;
    cursor: move;
    padding: 1px;
    font-weight: bold;
}

.tab_zag_2 {
    background-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath('pmd/Header_Linked.png'); ?>);
    background-repeat: repeat-x;
    text-align: center;
    cursor: move;
    padding: 1px;
    font-weight: bold;
}

.tab_field {
    background: #fff;
    color: #000;
    cursor: default;
}

.tab_field_2 {
    background-color: #CCFFCC;
    color: #000;
    background-repeat: repeat-x;
    cursor: default;
}

.tab_field_3 {
    background-color: #FFE6E6; /*#DDEEFF*/
    color: #000;
    cursor: default;
}

#pmd_hint {
    white-space: nowrap;
    position: absolute;
    background-color: #99FF99;
    color: #000;
    <?php echo $left; ?>: 200px;
    top: 50px;
    z-index: 3;
    border: #00CC66 solid 1px;
    display: none;
}

.scroll_tab {
    overflow: auto;
    width: 100%;
    height: 500px;
}

.pmd_Tabs {
    cursor: default;
    color: #0055bb;
    white-space: nowrap;
    text-decoration: none;
    text-indent: 3px;
    font-weight: bold;
    margin-left: 2px;
    text-align: <?php echo $left; ?>;
    background-color: #fff;
    background-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath('pmd/left_panel_butt.png'); ?>);
    border: #ccc solid 1px;
}

.pmd_Tabs2 {
    cursor: default;
    color: #0055bb;
    background: #FFEE99;
    text-indent: 3px;
    font-weight: bold;
    white-space: nowrap;
    text-decoration: none;
    border: #9999FF solid 1px;
    text-align: <?php echo $left; ?>;
}

.owner {
    font-weight: normal;
    color: #888;
}

.option_tab {
    padding-left: 2px;
    padding-right: 2px;
    width: 5px;
}

.select_all {
    vertical-align: top;
    padding-left: 2px;
    padding-right: 2px;
    cursor: default;
    width: 1px;
    color: #000;
    background-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath('pmd/Header.png'); ?>);
    background-repeat: repeat-x;
}

.small_tab {
    vertical-align: top;
    background-color: #0064ea;
    color: #fff;
    background-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath('pmd/small_tab.png'); ?>);
    cursor: default;
    text-align: center;
    font-weight: bold;
    padding-left: 2px;
    padding-right: 2px;
    width: 1px;
    text-decoration: none;
}

.small_tab2 {
    vertical-align: top;
    color: #fff;
    background-color: #FF9966;
    cursor: default;
    padding-left: 2px;
    padding-right: 2px;
    text-align: center;
    font-weight: bold;
    width: 1px;
    text-decoration: none;
}

.small_tab_pref {
    background-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath('pmd/Header.png'); ?>);
    background-repeat: repeat-x;
    text-align: center;
    width: 1px;
}

.small_tab_pref2 {
    vertical-align: top;
    color: #fff;
    background-color: #FF9966;
    cursor: default;
    text-align: center;
    font-weight: bold;
    width: 1px;
    text-decoration: none;
}

.butt {
    border: #4477aa solid 1px;
    font-weight: bold;
    height: 19px;
    width: 70px;
    background-color: #fff;
    color: #000;
    vertical-align: baseline;
}

.L_butt2_1 {
    padding: 1px;
    text-decoration: none;
    vertical-align: middle;
    cursor: default;
}

.L_butt2_2 {
    padding: 0;
    border: #0099CC solid 1px;
    background: #FFEE99;
    color: #000;
    text-decoration: none;
    vertical-align: middle;
    cursor: default;
}

/* ---------------------------------------------------------------------------*/
.bor {
    width: 10px;
    height: 10px;
}

.frams1 {
    background: url(<?php echo $_SESSION['PMA_Theme']->getImgPath('pmd/1.png'); ?>) no-repeat right bottom;
}

.frams2 {
    background: url(<?php echo $_SESSION['PMA_Theme']->getImgPath('pmd/2.png'); ?>) no-repeat left bottom;
}

.frams3 {
    background: url(<?php echo $_SESSION['PMA_Theme']->getImgPath('pmd/3.png'); ?>) no-repeat left top;
}

.frams4 {
    background: url(<?php echo $_SESSION['PMA_Theme']->getImgPath('pmd/4.png'); ?>) no-repeat right top;
}

.frams5 {
    background: url(<?php echo $_SESSION['PMA_Theme']->getImgPath('pmd/5.png'); ?>) repeat-x center bottom;
}

.frams6 {
    background: url(<?php echo $_SESSION['PMA_Theme']->getImgPath('pmd/6.png'); ?>) repeat-y left;
}

.frams7 {
    background: url(<?php echo $_SESSION['PMA_Theme']->getImgPath('pmd/7.png'); ?>) repeat-x top;
}

.frams8 {
    background: url(<?php echo $_SESSION['PMA_Theme']->getImgPath('pmd/8.png'); ?>) repeat-y right;
}

#osn_tab {
    background-color: #fff;
    color: #000;
    border: #A9A9A9 solid 1px;
}

.pmd_header {
    background-color: #EAEEF0;
    color: #000;
    text-align: center;
    font-weight: bold;
    margin: 0;
    padding: 0;
    background-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath('pmd/top_panel.png'); ?>);
    background-position: top;
    background-repeat: repeat-x;
    border-right: #999 solid 1px;
    border-left: #999 solid 1px;
    height: 28px;
    z-index: 101;
    width: 100%;
    position: fixed;
}

.pmd_header a {
    display: block;
    float: <?php echo $left; ?>;
    margin: 3px 1px 4px;
    height: 20px;
    border: 1px dotted #fff;
}

.pmd_header .M_bord {
    display: block;
    float: <?php echo $left; ?>;
    margin: 4px;
    height: 20px;
    width: 2px;
}

.pmd_header a.first {
    margin-right: 1em;
}

.pmd_header a.last {
    margin-left: 1em;
}

a.M_butt_Selected_down_IE,
a.M_butt_Selected_down {
    border: 1px solid #C0C0BB;
    background-color: #99FF99;
    color: #000;
}

a.M_butt_Selected_down_IE:hover,
a.M_butt_Selected_down:hover,
a.M_butt:hover {
    border: 1px solid #0099CC;
    background-color: #FFEE99;
    color: #000;
}

#layer_menu {
    z-index: 100;
    position: absolute;
    <?php echo $left; ?>: 0;
    background-color: #EAEEF0;
    border: #999 solid 1px;
}

#layer_upd_relation {
    position: absolute;
    <?php echo $left; ?>: 637px;
    top: 224px;
    z-index: 100;
}

#layer_new_relation {
    position: absolute;
    <?php echo $left; ?>: 636px;
    top: 85px;
    z-index: 100;
    width: 153px;
}

#pmd_optionse {
    position: absolute;
    <?php echo $left; ?>: 636px;
    top: 85px;
    z-index: 100;
    width: 153px;
}

#layer_menu_sizer {
    background-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath('pmd/resize.png'); ?>);
    cursor: nw-resize;
    width: 16px;
    height: 16px;
}

.panel {
    position: fixed;
    top: 60px;
    <?php echo $right; ?>: 0;
    display: none;
    background: #FFF;
    border: 1px solid gray;
    width: 350 px;
    height: auto;
    padding: 30px 170px 30px;
    padding-<?php echo $left; ?>: 30px;
    color: #FFF;
    z-index: 102;
}

a.trigger {
    position: fixed;
    text-decoration: none;
    top: 60px;
    <?php echo $right; ?>: 0;
    color: #fff;
    padding: 10px 40px 10px 15px;
    background: #333 url(<?php echo $_SESSION['PMA_Theme']->getImgPath('pmd/plus.png'); ?>) 85% 55% no-repeat;
    border: 1px solid #444;
    display: block;
    z-index: 102;
}

a.trigger:hover {
    color: #080808;
    background: #fff696 url(<?php echo $_SESSION['PMA_Theme']->getImgPath('pmd/plus.png'); ?>) 85% 55% no-repeat;
    border: 1px solid #999;
}

a.active.trigger {
    background: #222 url(<?php echo $_SESSION['PMA_Theme']->getImgPath('pmd/minus.png'); ?>) 85% 55% no-repeat;
    z-index: 999;
}

a.active.trigger:hover {
    background: #fff696 url(<?php echo $_SESSION['PMA_Theme']->getImgPath('pmd/minus.png'); ?>) 85% 55% no-repeat;
}

h2.tiger {
    background-repeat: repeat-x;
    padding: 1px;
    font-weight: bold;
    padding: 50px 20px 50px;
    margin: 0 0 5px 0;
    width: 250px;
    float: <?php echo $left; ?>;
    color : #333;
    text-align: center;
}

h2.tiger a {
    background-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath('pmd/Header.png'); ?>);
    text-align: center;
    text-decoration: none;
    color : #333;
    display: block;
}

h2.tiger a:hover {
    color: #000;
    background-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath('pmd/Header_Linked.png'); ?>);
}

h2.active {
    background-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath('pmd/Header.png'); ?>);
    background-repeat: repeat-x;
    padding: 1px;
    background-position: left bottom;
}

.toggle_container {
    margin: 0 0 5px;
    padding: 0;
    border-top: 1px solid #d6d6d6;
    background: #FFF;
    width: 250px;
    overflow: hidden;
    font-size: 1.2em;
    clear: both;
}

.toggle_container .block {
    background-color: #DBE4E8;
    padding: 40px 15px 40px 15px; /*--Padding of Container--*/
    border:1px solid #999;
    color: #000;
}

.history_table {
    text-align: center;
    background-color: #9999CC;
}

.history_table2 {
    text-align: center;
    background-color: #DBE4E8;
}

#filter {
    display: none;
    position: absolute;
    top: 0%;
    left: 0%;
    width: 100%;
    height: 100%;
    background-color: #CCA;
    z-index: 10;
    opacity: .5;
    filter: alpha(opacity=50);
}

#box {
    display: none;
    position: absolute;
    top: 20%;
    <?php echo $left; ?>: 30%;
    width: 500px;
    height: 220px;
    padding: 48px;
    margin: 0;
    border: 1px solid #000;
    background-color: #fff;
    z-index: 101;
    overflow: visible;
}

#boxtitle {
    position: absolute;
    float: center;
    top: 0;
    <?php echo $left; ?>: 0;
    width: 593px;
    height: 20px;
    padding: 0;
    padding-top: 4px;
    margin: 0;
    border-bottom: 4px solid #3CF;
    background-color: #D0DCE0;
    color: black;
    font-weight: bold;
    padding-<?php echo $left; ?>: 2px;
    text-align: <?php echo $left; ?>;
}

#tblfooter {
    background-color: #D3DCE3;
    float: <?php echo $right; ?>;
    padding-top: 10px;
    color: black;
    font-weight: normal;
}

#foreignkeychk {
    text-align: <?php echo $left; ?>;
    position: absolute;
    cursor: pointer;
}
