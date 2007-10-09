<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * navigation css file from theme
 *
 * @version $Id$
 * @package phpMyAdmin-theme
 * @subpackage Silkline
 */

// unplanned execution path
if (!defined('PMA_MINIMUM_COMMON')) {
    exit();
}
?> 
html {
    height:100%;
    border-right:1px solid #AAA;
}
html,body{padding-top:0;margin:0;}
body, input, select {
    font-family:    Tahoma, Arial, Helvetica, Verdana, sans-serif;
    font-size:         11px;
    color: #333333;
}

body#body_leftFrame {
    color: #000000;
    height:100%;
    overflow:auto;
     background: <?php echo $GLOBALS['cfg']['LeftBgColor']; ?> url(themes/silkline/img/silkline_light.png) top left repeat-x;
}

select {
    background-color: #ffffff;
    color: #000000;
}


div#leftframelinks,
div#databaseList {
    text-align: center;
    border-bottom: 0.05em solid #FFF;
    margin-bottom: 0.5em;
    padding-bottom: 0.5em;
    color: #000000;

}
div#pmalogo{
background:#FFF url(themes/silkline/img/logo_background.png) top left repeat-x;
padding:0;
margin:0;
border-bottom:0px;
}
div#leftframelinks img {
    vertical-align: middle;
}

div#leftframelinks a {
    margin: 0.5em;
    padding: 0.2em;
    border: 0.05em solid #FFF;
    color: #000000;
}

div#leftframelinks a:hover {
    background-color: #669999;
    color:#FFF;
}

div#databaseList form {
    display: inline;
}

/* leftdatabaselist */
div#left_tableList {
    list-style-type: none;
    list-style-position: outside;
    margin: 0;
    padding: 0;
    font-size: <?php echo $font_smaller; ?>;
    color:#FFF;

}
div#left_tableList p a.item, form#left label{
font-weight:bold;
color:#000;
font-size:110%;
text-align:left;
}
div#left_tableList a {
    color: #505050;
    text-decoration: none;
}

div#left_tableList a:hover {
    color: #FFF;
    background-color:#505050;
    text-decoration:none;
}

div#left_tableList li {
    margin: 0;
    padding: 0;
    white-space: nowrap;
    line-height:1.5;
}

<?php if ( $GLOBALS['cfg']['LeftPointerEnable'] ) { ?>
div#left_tableList li:hover, div#left_tableList li:hover a {
background-color: <?php echo $GLOBALS['cfg']['LeftPointerColor']; ?>;
color:#FFF;
}
<?php } ?>

div#left_tableList img {
    vertical-align: middle;
}
div#left_tableList p {color:#333;padding:10px;}
div#left_tableList ul ul {
    margin-left: 0em;
    padding-left: 0.1em;
    border-left: 0.1em solid #669999;
    padding-bottom: 0.1em;
    border-bottom: 0.1em solid #669999;
    background-color:#FEFEFE;
}

/** Thanks Vince ;) - vincekruger@gmail.com**/
ul#databaseList{
list-style:none;

}
ul#databaseList li{
	line-height:1.5em;
	font-weight:bold;

}
ul#databaseList li a{
	font-weight:normal;
	text-decoration:none;
	color:#333;
}
ul#databaseList li a:hover, ul#databaseList li a:active, ul#databaseList li a:focus{
font-weight:bold;
}
ul#databaseList li ul{
padding-left:10px;
margin-left:0px;

	list-style:none;
	padding-bottom:1em;
}
ul#databaseList li ul li a{
	border-left:1px solid #333;
}
