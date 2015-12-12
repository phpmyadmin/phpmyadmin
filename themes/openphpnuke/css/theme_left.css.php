<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * navigation css file from theme
 *
 * @version $Id$
 * @package phpMyAdmin-theme
 * @subpackage OpenPHPNuke
 */

    if (isset($num_dbs) && $num_dbs == '0') {
    ?>
/* No layer effects neccessary */
div     {font:<?php echo $font_size; ?> Verdana,Arial,Helvetica,sans-serif; color:#000000;}
.heada  {font:<?php echo $font_size; ?> Verdana,Arial,Helvetica,sans-serif; color:#000000;}
.parent {font:Verdana,Arial,Helvetica,sans-serif; color:#000000; text-decoration:none;}
.item, .item:active, .item:hover, .tblItem, .tblItem:active {font:<?php echo $font_smaller; ?> Verdana,Arial,Helvetica,sans-serif; color:#FFFFFF; text-decoration:none;}
.tblItem:hover {color:#000000; text-decoration:underline;}
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
/* Layer effects neccessary: capable && is_DOM is set. We found a recent CSS-Browser */
div 				{font:<?php echo $font_size; ?> Verdana,Arial,Helvetica,sans-serif; color:#000000;}
.heada 			{font:<?php echo $font_size; ?> Verdana,Arial,Helvetica,sans-serif; color:#000000;}
.headaCnt 	{font:<?php echo $font_smaller; ?> Verdana,Arial,Helvetica,sans-serif; color:#000000;}
.parent 		{font-family:Verdana,Arial,Helvetica,sans-serif; color:#000000; text-decoration:none; display:block;}
.child 			{font:<?php echo $font_smaller; ?> Verdana,Arial,Helvetica,sans-serif; color:#333399; text-decoration:none; display:none;}
.item, .item:active, .item:hover, .tblItem, .tblItem:active {font-size:<?php echo $font_smaller; ?>; color:#333399; text-decoration:none;}
.tblItem:hover {color:#FF0000; text-decoration:underline;}
            <?php
            } else {
            ?>
/* Layer effeccts neccessary: capable, but no is_DOM. We found an older CSS-Browser */
div {font:<?php echo $font_size; ?> Verdana,Arial,Helvetica,sans-serif; color:#000000;}
.heada {font:<?php echo $font_size; ?> Verdana,Arial,Helvetica,sans-serif; color:#000000;}
.headaCnt {font:<?php echo $font_smaller; ?> Verdana,Arial,Helvetica,sans-serif; color:#000000;}
            <?php
                if (isset($js_isIE4) && $js_isIE4 != '0') {
            ?>
/* Additional effects for IE4 */
.parent {font-family:Verdana,Arial,Helvetica,sans-serif; color:#000000; text-decoration:none; display:block;}
.child {font:<?php echo $font_smaller; ?> Verdana,Arial,Helvetica,sans-serif; color:#333399; text-decoration:none; display:none;}
.item, .item:active, .item:hover, .tblItem, .tblItem:active {font-size:<?php echo $font_smaller; ?>; color:#333399; text-decoration:none;}
.tblItem:hover {color:#FF0000; text-decoration:underline;}
            <?php
                } else {
            ?>
/* Additional effects for NON-IE4 */
.parent {font-family:Verdana,Arial,Helvetica,sans-serif; color:#000000; text-decoration:none; position:absolute; visibility:hidden;}
.child 	{font:<?php echo $font_smaller; ?> Verdana,Arial,Helvetica,sans-serif; color:#333399; position:absolute; visibility:hidden;}
.item, .tblItem {font:<?php echo $font_smaller; ?> Verdana,Arial,Helvetica,sans-serif; color:#333399; text-decoration:none;}
            <?php
                }
            }
        } else {
        ?>
/* Additional effects for left frame not required or not possible because of lacking CSS-capability. */
div 			{font:<?php echo $font_size; ?> Verdana,Arial,Helvetica,sans-serif; color:#000000;}
.heada 		{font:<?php echo $font_size; ?> Verdana,Arial,Helvetica,sans-serif; color:#000000;}
.headaCnt {font:<?php echo $font_smaller; ?> Verdana,Arial,Helvetica,sans-serif; color:#000000;}
.parent 	{font-family:Verdana,Arial,Helvetica,sans-serif; color:#000000; text-decoration:none;}
.child 		{font:<?php echo $font_smaller; ?> Verdana,Arial,Helvetica,sans-serif; color:#333399; text-decoration:none;}
.item, .item:active, .item:hover, .tblItem, .tblItem:active {font-size:<?php echo $font_smaller; ?>; color:#333399; text-decoration:none;}
.tblItem:hover {color:#FF0000; text-decoration:underline;}
        <?php
        }
    }
    ?>
/* Always enabled stylesheets (left frame) */
body    {background-color: #1CC2F8; background-image: url(themes/openphpnuke/img/framelinksbg.png); background-position: right; background-repeat: repeat-y; font:<?php echo $font_size; ?> Verdana,Arial,Helvetica,sans-serif;}
input   {font:<?php echo $font_size; ?> Verdana,Arial,Helvetica,sans-serif;}
select  {font:<?php echo $font_size; ?> Verdana,Arial,Helvetica,sans-serif; background:#ffffff; color:#000000;}

img, input, select, button {vertical-align:middle;}
