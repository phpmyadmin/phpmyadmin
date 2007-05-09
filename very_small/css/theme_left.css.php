<?php $pma_http_url = ''; ?>
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * navigation css file from theme
 * 	 
 * @version $Id$ 	 
 * @package phpMyAdmin-theme 	 
 * @subpackage Very_small 	 
 */ 	 
?>

#body_leftFrame {
    padding-top:           0px;
    padding-right:         0px;
    padding-bottom:        13px;
    padding-left:          1px;
    margin-top:            0px;
    margin-right:          0px;
    margin-bottom:         13px;
    margin-left:           1px;
}

#body_queryFrame {
    padding-top:           2px;
    padding-right:         2px;
    padding-bottom:        0px;
    padding-left:          2px;
    margin-top:            2px;
    margin-right:          2px;
    margin-bottom:         0px;
    margin-left:           2px;
}
body {
    background-color:    #d9e4f4;
    background-image:    url('<?php echo ( (isset($pma_http_url) && !empty($pma_http_url)) ? $pma_http_url : '../' ); ?>themes/arctic_ocean/img/wbg_left.jpg');
    background-repeat:   repeat-y;
    background-position: 0px 0px;
}
body, input, textarea, select, th, td, .item, .tblItem {
    font-family:          Arial, Helvetica, Verdana, Geneva, sans-serif;
   font-size:           10px;
}
#body_queryFrame select, #body_queryFrame table {
    width:                100%;
}
#body_queryFrame div {
				white-space:         nowrap;
}
input, select, textarea {
    color:               #000000;
}

a:link, a:visited, a:active {
    color:               #585880;
}

hr {
    color:               #585880;
    background-color:    #585880;
    border:              1px none #585880;
    height:              1px;
}
img, input, select, button {
    vertical-align:      middle;
}
img {
    margin:              0px 0px 0px 0px;
}

.parent {
    text-decoration:     none;
    display:             block;
}

.child {
    text-decoration:     none;
    /* display:             none; */
}

.item, .item:active, .tblItem, .tblItem:active {
    text-decoration:     none;
}

.item:hover, .tblItem:hover {
    text-decoration:     underline;
}

td.heada, span.heada {
    background-image:    url(<?php echo ( (isset($pma_http_url) && !empty($pma_http_url)) ? $pma_http_url : '../' ); ?>themes/arctic_ocean/img/b_sdb.png);
    background-position: 2px center;
    background-repeat:   no-repeat;
    font-weight: bold;
}
span.heada, span.heada a:link, span.heada a:active, span.heada a:visited, span.heada a:hover {
    color:               #696ab5;
    text-decoration:     none;
}
td.heada, span.heada {
    text-align:          left;
    padding-left:        12px;
				white-space:         nowrap;
}

bdo {
    display:             none;
}
#hr_third {
    display:             none;
}
select optgroup, select option {
    font-family:         Arial, Helvetica, Verdana, Geneva, sans-serif;
    font-size:           10px;
    font-style:          normal;
}
