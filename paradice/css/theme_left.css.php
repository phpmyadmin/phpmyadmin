<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * navigation css file from theme
 *
 * @version $Id$
 * @package phpMyAdmin-theme
 * @subpackage Paradice
 */

    // unplanned execution path
    if (!defined('PMA_MINIMUM_COMMON')) {
        exit();
    }
?> 
/******************************************************************************/
/* general tags */
body {
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    font-size:        12px;
    background-color: #3674CF;
    color:            #ffffff;
    margin: 0;
    padding: 2px 2px 2px 2px;
	background-image: 		url(themes/paradice/img/leftBgnd.jpg);
	background-position: 	left top;
	background-repeat: 		repeat-y;
}

a img {
    border: 0;
}

/* gecko FIX, font size is not correctly assigned to all child elements */
body * {
    font-family:      inherit;
    font-size:        inherit;
}

form {
    margin:             0;
    padding:            0;
    display:            inline;
}

select {
    background-color:   #ffffff;
    color:              #000000;
    width:              150px;
	font-size:			10px;
}

/* buttons in some browsers (eg. Konqueror) are block elements,
   this breaks design */
button {
    display:            inline;
}


/******************************************************************************/
/* classes */

/* leave some space between icons and text */
.icon {
    vertical-align:     middle;
    margin-right:       0.3em;
    margin-left:        0.3em;
}

img.lightbulb {
    cursor:             pointer;
}


/******************************************************************************/
/* specific elements */

div#pmalogo,
div#leftframelinks,
div#databaseList {
    text-align:         center;
    border-bottom:      0.05em solid #ffffff;
    margin-bottom:      0.5em;
    padding-bottom:     0.5em;
}

div#leftframelinks img {
    vertical-align: middle;
    padding:            0;
    margin:             0;
}

div#leftframelinks a {
    margin:             0.1em;
    padding:            0.2em;
    border:             0;
}

div#leftframelinks a:hover {
	background-color: #3674CF;
    color: #FFFFFF;	
}

/* leftdatabaselist */
div#left_tableList ul {
    list-style-type:    none;
    list-style-position: outside;
    margin:             0;
    padding:            0;
    font-size:          10px;
    background-color:   #3674CF;
}

div#left_tableList ul ul {
    font-size:          100%;
}

div#left_tableList a {
    color:              #ffffff;
    text-decoration:    none;
}

div#left_tableList a:hover {
    color:              #ffffcc;
    text-decoration: 	none;
}

div#left_tableList li {
    margin:             0;
    padding:            0;
    white-space:        nowrap;
}

<?php if ( $GLOBALS['cfg']['LeftPointerEnable'] ) { ?>
div#left_tableList li:hover {
    background-color: #2563BE;
}
<?php } ?>

div#left_tableList img {
    padding:            0;
    vertical-align:     middle;
}

div#left_tableList ul ul {
    margin-left:        0em;
    padding-left:       0.1em;
    border-left:        0.1em solid #ffffff;
    padding-bottom:     0.1em;
    border-bottom:      0.1em solid #ffffff;
}
