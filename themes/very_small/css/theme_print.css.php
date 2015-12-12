<?php $pma_http_url = ''; ?>
/* vim: set expandtab sw=4 ts=4 sts=4: */ 	 
/** 	 
 * print css file from theme 	 
 * 	 
 * @version $Id$ 	 
 * @package phpMyAdmin-theme 	 
 * @subpackage Very_small 	 
 */ 	 
?>
/* For printview */
body {
    font-family:       Verdana, Arial, Helvetica, sans-serif;
    font-size:         10px;
    color:             #000000;
    background-color:  #ffffff;
    margin:            0px;
    padding:           0px;
}
h1 {
    font-family:       Verdana, Arial, Helvetica, sans-serif;
    font-size:         14px;
    font-weight:       bold;
    color:             #000000;
}
big {
    font-family:       Verdana, Arial, Helvetica, sans-serif;
    font-size:         12px;
    font-weight:       bold;
    color:             #000000;
}
table {
    border-width:      1px;
    border-color:      #000000;
    border-style:      solid;
    border-collapse:   collapse;
    border-spacing:    0;
}
th {
    font-family:       Verdana, Arial, Helvetica, sans-serif;
    font-size:         10px;
    font-weight:       bold;
    color:             #000000;
    background-color:  #e5e5e5;
    border-width:      1px;
    border-color:      #000000;
    border-style:      solid;
    padding:           2px;
}
td, .print {
    font-family:       Verdana, Arial, Helvetica, sans-serif;
    font-size:         10px;
    color:             #000000;
    background-color:  #ffffff;
    border-width:      1px;
    border-color:      #000000;
    border-style:      solid;
    padding:           2px;
}
a:link, a:visited, a:active {
    text-decoration:     none;
    font-weight:         bold;
    color:               #696ab5;

}
a:hover {
    text-decoration:     none;
    color:               #585880;
}

input[type=button], input[type=submit], input[type=reset] {
    color:               #585880;
    font-size:           11px;
    font-weight:         bold;
    padding:             2px;
    border:              1px solid #585880;
    background-color:    #e5e5e5;
}

/* -- SERVER & DB INFO -- */
.serverinfo {
    font-family:        Arial, Helvetica, Verdana, Geneva, sans-serif;
    font-size:          12px;
    font-weight:        bold;
    padding:            0px 0px 10px 0px;
    margin:             0px;
    white-space:        nowrap;
    vertical-align:     middle;
}

.serverinfo a:link, .serverinfo a:active, .serverinfo a:visited {
    font-family:       Arial, Helvetica, Verdana, Geneva, sans-serif;
    font-size:         12px;
    font-weight:       bold;
}
.serverinfo a img {
    vertical-align:    middle;
    margin:            0px 1px 0px 1px;
}

.serverinfo div {
    background-image:    url(<?php echo ( (isset($pma_http_url) && !empty($pma_http_url)) ? $pma_http_url : '../' ); ?>themes/arctic_ocean/img/item_ltr.png);
    background-repeat:   no-repeat;
    background-position: 50% 50%;
    width:               20px;
    height:              16px;
}
