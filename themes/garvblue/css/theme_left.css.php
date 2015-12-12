<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * navigation css file from theme
 *
 * Lightweight CSS for garvBlue theme. Even though some styles may not apply to every
 * state of the left frame, it's easier to drop all those IF-structures and do it the
 * human-readable way.
 *
 * @version $Id$
 * @package phpMyAdmin-theme
 * @subpackage garvBlue
 */
?>
.parent {
    text-decoration:  none;
    display:          block;
}

.child {
    text-decoration:  none;
    /* display:          none; */
}

.item, .item:active, .tblItem, .tblItem:active {
    text-decoration:  none;
}

.item:hover, .tblItem:hover {
    color:            #F4A227;
    text-decoration:  underline;
}

/* Always enabled stylesheets (left frame) */
body {
    margin:              0px;
    padding:             2px;
}

body, input, textarea, select, th, td, .item, .tblItem {
    font-family:         Tahoma, Arial, sans-serif;
    color:               #2D3867;
    background-color:    #E8EAF1;
    font-size:           10pt;
}

select {
    margin-left: auto;
    margin-right: auto;
    display: block;
}

hr {
    color:            #A4ABCA;
    background-color: #A4ABCA;
    border:           0;
    height:           1px;
}

img, input, select, button {
    vertical-align: middle;
}

#body_queryFrame, #body_leftFrame {
    background-image:    url('themes/garvblue/img/background4.gif');
    background-repeat:   repeat-y;
    background-position: 100% 0%;
}

#body_leftFrame {
    padding-top: 5px;
    margin-top: 5px;
    padding-left: 2px;
}

td.heada {
    text-align: center;
    font-size: 9pt;
}

.tblItem {
    font-size: 8pt;
}

#left_tableList {
    position: absolute;
    margin-top: 5px;
    margin-bottom: 5px;
    top: 5px;
    bottom: 5px;
    overflow: auto;
    width: 95%;
}
