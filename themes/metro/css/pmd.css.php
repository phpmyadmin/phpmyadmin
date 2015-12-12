<?php

// unplanned execution path
if (! defined('PMA_MINIMUM_COMMON') && ! defined('TESTSUITE')) {
    exit();
}
?>

/* Designer */
.input_tab {
    background-color: #efefef;
    color: #000;
    border: 1px solid #ccc;
}

.content_fullscreen {
    position: relative;
    overflow: auto;
}

#canvas_outer {
    position: relative;
    width: 100%;
    display: block;
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
    color: #333;
    border-collapse: collapse;
    border: 1px solid #ccc;
    z-index: 1;
    -moz-user-select: none;
}

.pmd_tab .header {
    background-color: #f6f6f6;
}

.tab_zag {
    text-align: center;
    cursor: move;
    padding: 1px;
    font-weight: bold;
    vertical-align: middle;
}

.tab_zag_2 {
    text-align: center;
    cursor: move;
    padding: 1px;
    font-weight: bold;
    background-color: #f6f6f6;
    vertical-align: middle;
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
    color: #333;
    white-space: nowrap;
    text-decoration: none;
    text-indent: 3px;
    margin-left: 2px;
    text-align: <?php echo $left; ?>;
    border: #ccc solid 0;
}

.pmd_Tabs2 {
    cursor: default;
    color: #666;
    background: #eee;
    text-indent: 3px;
    white-space: nowrap;
    text-decoration: none;
    border: #eee solid 0;
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
    background-repeat: repeat-x;
}

.small_tab {
    vertical-align: top;
    background-color: #666;
    color: #fff;
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
    background-color: #f6f6f6;
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

.L_butt2_1 {
    padding-left: 5px;
    text-decoration: none;
    vertical-align: middle;
    cursor: default;
}

.L_butt2_2 {
    padding-left: 5px;
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

#osn_tab {
    position: absolute;
    background-color: #fff;
    color: #000;
    width: 100% !important;
}

.pmd_header {
    background-color: #F6F6F6;
    border-top: 20px solid #FFFFFF;
    color: #333333;
    display: block;
    height: 28px;
    margin-left: -20px;
    margin-top: -60px;
    padding: 5px 20px;
    position: fixed;
    text-align: center;
    width: 100%;
    z-index: 101;
}

.pmd_header a {
    display: block;
    float: <?php echo $left; ?>;
    margin: 3px 1px 4px;
    height: 20px;
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
    background-color: #eee;
    color: #000;
}

a.M_butt_Selected_down_IE:hover,
a.M_butt_Selected_down:hover,
a.M_butt:hover {
    background-color: #eee;
    color: #000;
}

#layer_menu {
    z-index: 98;
    position: relative;
    float: right;;
    background-color: #f6f6f6;
    border: #ccc solid 1px;
    border-top: 0;
    margin-right: -20px;
}

.content_fullscreen #layer_menu {
    margin-right: 0;
}

#layer_menu.left {
    float: left;
    margin-left: -20px;
}

.content_fullscreen #layer_menu.left {
    margin-left: 0;
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
    cursor: ew-resize;
}

#layer_menu_sizer .icon {
    margin: 0;
}

.panel {
    position: fixed;
    top: 90px;
    <?php echo $right; ?>: 0;
    width: 350px;
    max-height: 500px;
    display: none;
    overflow: auto;
    padding-top: 34px;
    z-index: 102;
}

a.trigger {
    position: fixed;
    text-decoration: none;
    top: 90px;
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

.toggle_container .block {
    background-color: <?php echo $GLOBALS['cfg']['BrowseMarkerBackground'] ?>;
    border-top: 1px solid #999;
}

.toggle_container .block img.ic_s_info {
    -webkit-filter: invert(70%);
    filter: invert(70%);
}

.history_table {
    opacity: 1.0;
    cursor: pointer;
}

.history_table2 {
    opacity: 0.7;
}

#ab {
    min-width: 300px;
}

#ab .ui-accordion-content {
    padding: 0;
}

#box {
    display: none;
}

#foreignkeychk {
    text-align: <?php echo $left; ?>;
    position: absolute;
    cursor: pointer;
}

.side-menu {
    float: left;
    position: fixed;
    width: auto;
    height: auto;
    background: #efefef;
    border: 1px solid #ccc;
    border-top: 0;
    overflow: hidden;
    z-index: 50;
    padding: 2px;
    margin-top: 0;
    margin-left: -20px;
}

.content_fullscreen .side-menu {
    margin-left: 0;
}

.side-menu.right {
    float: right;
    right: 0;
}

.content_fullscreen .side-menu.right {
    margin-right: 0;
}

.side-menu .hide {
    display: none;
}

.side-menu a {
    display: block;
    float: none;
    overflow: hidden;
}

.side-menu img,
.side-menu span,
.side-menu .text {
    float: left;
    padding-left: 2px;
}

#name-panel {
    border-bottom: 1px solid #ccc;
    text-align: center;
    background: #efefef;
    font-size: 1.2em;
    padding: 10px;
    font-weight: bold;
    margin-top: -20px;
    margin-left: -20px;
    margin-right: -20px;
}

.content_fullscreen #name-panel {
    margin-top: 0;
    margin-left: 0;
    margin-right: 0;
}

#container-form {
    width: 100%;
    position: absolute;
    left: 0;
}