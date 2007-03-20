<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * navigation css file from theme
 *
 * @version $Id$
 * @package phpMyAdmin-theme
 * @subpackage WinXP_blue
 */

    if (isset($num_dbs) && $num_dbs == '0') {
?>
/* No layer effects neccessary */
div{
    font-family:      Arial, Helvetica, Verdana, sans-serif;
    font-size:        12px;
    color:            #d6dff7;
}
.heada{
    font-family:      Arial, Helvetica, Verdana, sans-serif;
    font-size:        12px;
    color:            #d6dff7;
}
.parent{
    font-family:      Arial, Helvetica, Verdana, sans-serif;
    color:            #d6dff7;
    text-decoration:  none;
}
.item, .tblItem, .item:active, .item:hover, .tblItem, .tblItem:active{
    font-family:      Arial, Helvetica, Verdana, sans-serif;
    font-size:        12px;
    color:            #d6dff7;
    text-decoration:  none;
}
.tblItem:hover{
    color:            #ffffff;
    text-decoration:  underline;
}
/*
<?php
    } else {
        if (isset($js_capable) && $js_capable != '0') {
            // Brian Birtles : This is not the ideal method of doing this
            // but under the 7th June '00 Mozilla build (and many before
            // it) Mozilla did not treat text between <style> tags as
            // style information unless it was written with the one call
            // to write().
            if (isset($js_isDOM) && $js_isDOM != '0') {
?>
*/
/* Layer effects neccessary: capable && is_DOM is set. We found a recent CSS-Browser */
div{
    font-family:      Arial, Helvetica, Verdana, sans-serif;
    font-size:        12px;
    color:            #d6dff7;
}
.heada{
    font-family:      Arial, Helvetica, Verdana, sans-serif;
    font-size:        12px;
    color:            #d6dff7;
}
.headaCnt{
    font-family:      Arial, Helvetica, Verdana, sans-serif;
    font-size:        12px;
    color:            #d6dff7;
}
.parent{
    font-family:      Arial, Helvetica, Verdana, sans-serif;
    color:            #d6dff7;
    text-decoration:  none;
    display:          block;
}
.child{
    font-family:      Arial, Helvetica, Verdana, sans-serif;
    font-size:        12px;
    color:            #d6dff7;
    text-decoration:  none;
    display:          none;
}
.item, .item:active, .item:hover, .tblItem, .tblItem:active{
    font-size:        12px;
    color:            #d6dff7;
    text-decoration:  none;
}
.tblItem:hover{
    color:            #ffffff;
    text-decoration:  underline;
}
/*
<?php
            } else {
?>
*/
/* Layer effeccts neccessary: capable, but no is_DOM. We found an older CSS-Browser */
div{
    font-family:      Arial, Helvetica, Verdana, sans-serif;
    font-size:        12px;
    color:            #000000;
}
.heada{
    font-family:      Arial, Helvetica, Verdana, sans-serif;
    font-size:        12px;
    color:            #d6dff7;
}
.headaCnt{
    font-family:      Arial, Helvetica, Verdana, sans-serif;
    font-size:        12px;
    color:            #d6dff7;
}
/*
<?php
            if (isset($js_isIE4) && $js_isIE4 != '0') {
?>
*/
/* Additional effects for IE4 */
.parent{
    font-family:      Arial, Helvetica, Verdana, sans-serif;
    color:            #d6dff7;
    text-decoration:  none;
    display:          block;
}
.child{
    font-family:      Arial, Helvetica, Verdana, sans-serif;
    font-size:        12px;
    color:            #d6dff7;
    text-decoration:  none;
    display:          none;
}
.item, .item:active, .item:hover, .tblItem, .tblItem:active{
    font-size:        12px;
    color:            #d6dff7;
    text-decoration:  none;
}
.tblItem:hover{
    color:            #ffffff;
    text-decoration:  underline;
}
/*

<?php
                } else {
?>
*/
/* Additional effects for NON-IE4 */
.parent{
    font-family:      Arial, Helvetica, Verdana, sans-serif;
    color:            #d6dff7;
    text-decoration:  none;
    position:         absolute;   /* don't edit! */
    visibility:       hidden;     /* don't edit! */
}
.child{
    font-family:      Arial, Helvetica, Verdana, sans-serif;
    font-size:        12px;
    color:            #d6dff7;
    position:         absolute;   /* don't edit! */
    visibility:       hidden;     /* don't edit! */
}
.item, .tblItem{
    font-family:      Arial, Helvetica, Verdana, sans-serif;
    font-size:        12px;
    color:            #d6dff7;
    text-decoration:  none;
}
/*
<?php
                }
            }
        } else {
?>
*/
/* Additional effects for left frame not required or not possible because of lacking CSS-capability. */
div{
    font-family:      Arial, Helvetica, Verdana, sans-serif;
    font-size:        12px;
    color:            #d6dff7;
}
.heada{
    font-family:      Arial, Helvetica, Verdana, sans-serif;
    font-size:        12px;
    color:            #d6dff7;
}
.headaCnt{
    font-family:      Arial, Helvetica, Verdana, sans-serif;
    font-size:        12px;
    color:            #d6dff7;
}
.parent{
    font-family:      Arial, Helvetica, Verdana, sans-serif;
    color:            #d6dff7;
    text-decoration:  none;
}
.child{
    font-family:      Arial, Helvetica, Verdana, sans-serif;
    font-size:        12px;
    color:            #d6dff7;
    text-decoration:  none;
}
.item, .item:active, .item:hover, .tblItem, .tblItem:active{
    font-size:        12px;
    color:            #d6dff7;
    text-decoration:  none;
}
.tblItem:hover{
    color:            #ffffff;
    text-decoration:  underline;
}
/*
<?php
        }
    }
?>
*/
/* Always enabled stylesheets (left frame) */
body{
    font-family:      Arial, Helvetica, Verdana, sans-serif;
    font-size:        12px;
    background-color: #0043cf;
    margin: 0px;
    padding: 2px 2px 2px 2px;
}
input{
    font-family:      Arial, Helvetica, Verdana, sans-serif;
    font-size:        12px;
}
select{
    font-family:      Arial, Helvetica, Verdana, sans-serif;
    font-size:        12px;
    background-color: #ffffff;
    color:            #7aa1e6;
    width:            150px;
}
hr{
    color:            #7aa1e6;
    background-color: #7aa1e6;
    border:           0;
    height:           1px;
}
img, input, select, button {
    vertical-align: middle;
}
