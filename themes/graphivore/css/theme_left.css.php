<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * navigation css file from theme
 *
 * @version $Id$
 * @package phpMyAdmin-theme
 * @subpackage Graphivore
 */
?>
/************************************************************************************
 * LEFT FRAME
 ************************************************************************************/

    /**
     * Add styles for positioned layers
    **/
/*
<?php
    if (isset($num_dbs) && $num_dbs == '0') {
?>
*/
/* No layer effects neccessary */
div{
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    font-size:        11px;
    color:            #fffaef;
}
.heada{
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    font-size:        11px;
    color:            #fffaef;
}
.parent{
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    color:            #fffaef;
    text-decoration:  none;
}
.item, .tblItem, .item:active, .item:hover, .tblItem, .tblItem:active{
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    font-size:        11px;
    color:            #fffaef;
    text-decoration:  none;
}
.tblItem:hover{
    color:            #b36448;
    text-decoration:  none;
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
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    font-size:        11px;
    color:            #fffaef;
}
.heada{
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    font-size:        11px;
    color:            #fffaef;
}
.headaCnt{
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    font-size:        11px;
    color:            #fffaef;
}
.parent{
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    color:            #fffaef;
    text-decoration:  none;
    display:          block;
}
.child{
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    font-size:        11px;
    color:            #fffaef;
    text-decoration:  none;
    display:          none;
}
.item, .item:active, .item:hover, .tblItem, .tblItem:active{
    font-size:        11px;
    color:            #fffaef;
    text-decoration:  none;
}
.tblItem:hover{
    color:            #b36448;
    text-decoration:  none;
}
/*
<?php
            } else {
?>
*/
/* Layer effeccts neccessary: capable, but no is_DOM. We found an older CSS-Browser */
div{
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    font-size:        11px;
    color:            #000000;
}
.heada{
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    font-size:        11px;
    color:            #fffaef;
}
.headaCnt{
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    font-size:        11px;
    color:            #fffaef;
}
/*
<?php
            if (isset($js_isIE4) && $js_isIE4 != '0') {
?>
*/
/* Additional effects for IE4 */
.parent{
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    color:            #fffaef;
    text-decoration:  none;
    display:          block;
}
.child{
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    font-size:        11px;
    color:            #fffaef;
    text-decoration:  none;
    display:          none;
}
.item, .item:active, .item:hover, .tblItem, .tblItem:active{
    font-size:        11px;
    color:            #fffaef;
    text-decoration:  none;
}
.tblItem:hover{
    color:            #b36448;
    text-decoration:  none;
}
/*

<?php
                } else {
?>
*/
/* Additional effects for NON-IE4 */
.parent{
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    color:            #fffaef;
    text-decoration:  none;
    position:         absolute;   /* don't edit! */
    visibility:       hidden;     /* don't edit! */
}
.child{
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    font-size:        11px;
    color:            #fffaef;
    position:         absolute;   /* don't edit! */
    visibility:       hidden;     /* don't edit! */
}
.item, .tblItem{
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    font-size:        11px;
    color:            #fffaef;
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
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    font-size:        11px;
    color:            #fffaef;
}
.heada{
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    font-size:        11px;
    color:            #fffaef;
}
.headaCnt{
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    font-size:        11px;
    color:            #fffaef;
}
.parent{
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    color:            #fffaef;
    text-decoration:  none;
}
.child{
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    font-size:        11px;
    color:            #fffaef;
    text-decoration:  none;
}
.item, .item:active, .item:hover, .tblItem, .tblItem:active{
    font-size:        11px;
    color:            #fffaef;
    text-decoration:  none;
}
.tblItem:hover{
    color:            #b36448;
    text-decoration:  none;
}
/*
<?php
        }
    }
?>
*/
/* Always enabled stylesheets (left frame) */
body{
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    font-size:        11px;
    background-image: url(themes/graphivore/img/bgmenu.gif);
    margin: 0px;
    padding: 2px 2px 2px 2px;
}
input{
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    font-size:        11px;
}
select{
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    font-size:        11px;
    background-color: #fffaef;
    color:            #000000;
    width:            150px;
}
hr{
    color:            #fffaef;
    background-color: #fffaef;
    border:           0;
    height:           1px;
}
img, input, select, button {
    vertical-align: middle;
}
