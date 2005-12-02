/******************************************************************************/
/* general tags */
body {
    font-family:        <?php echo $GLOBALS['left_font_family']; ?>;
    background-color:   #D0DCE0;
    color:              #000000;
}

a img {
    border: 0;
}

form {
    margin:             0;
    padding:            0;
    display:            inline;
}

select {
    background-color:   #ffffff;
    color:              #000000;
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
    border-bottom:      0.05em solid #669999;
    margin-bottom:      0.5em;
    padding-bottom:     0.5em;
}

div#leftframelinks .icon {
    padding:            0;
    margin:             0;
}

div#leftframelinks a {
    margin:             0.1em;
    padding:            0.2em;
    border:             0.05em solid #669999;
}

div#leftframelinks a:hover {
    background-color:   #669999;
}

/* leftdatabaselist */
div#left_tableList ul {
    list-style-type:    none;
    list-style-position: outside;
    margin:             0;
    padding:            0;
    font-size:          80%;
    background-color:   #D0DCE0;
}

div#left_tableList ul ul {
    font-size:          100%;
}

div#left_tableList a {
    color:              #333399;
    text-decoration:    none;
}

div#left_tableList a:hover {
    color:              #FF0000;
    text-decoration:    underline;
}

div#left_tableList li {
    margin:             0;
    padding:            0;
    white-space:        nowrap;
}

<?php if ( $GLOBALS['cfg']['LeftPointerEnable'] ) { ?>
div#left_tableList li:hover {
    background-color:   #CCFFCC;
}
<?php } ?>

div#left_tableList img {
    padding:            0;
    vertical-align:     middle;
}

div#left_tableList ul ul {
    margin-left:        0em;
    padding-left:       0.1em;
    border-left:        0.1em solid #669999;
    padding-bottom:     0.1em;
    border-bottom:      0.1em solid #669999;
}