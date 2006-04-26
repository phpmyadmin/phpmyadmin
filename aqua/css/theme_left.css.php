<?php
    // unplanned execution path
    if (!defined('PMA_MINIMUM_COMMON')) {
        exit();
    }
?> 
/* No layer effects neccessary */
body,
body#body_leftFrame {
    background-position: right;
    background-image: url(../themes/aqua/img/bg_aquaGrad.png);
    background-repeat: repeat-y;
    background-color: #3E7BB6;
    color: #ffffff;
}

div#pmalogo,
div#leftframelinks,
div#databaseList {
    text-align: center;
    border-bottom: 0.05em solid #669999;
    margin-bottom: 0.5em;
    padding-bottom: 0.5em;
}

div#leftframelinks img {
    vertical-align: middle;
}

div#leftframelinks a {
    margin: 0.1em;
    padding: 0.2em;
    border: 0.05em solid #669999;
}

div#leftframelinks a:hover {
    background-color: Aqua;
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
}

div#left_tableList a {
    color: #ffffff;
    text-decoration: none;
}

div#left_tableList a:hover {
    color: #FFff00;
    text-decoration: underline;
}

div#left_tableList li {
    margin: 0;
    padding: 0;
    white-space: nowrap;
}

<?php if ( $GLOBALS['cfg']['LeftPointerEnable'] ) { ?>
div#left_tableList li:hover a,
div#left_tableList li:hover {
    background-color: Aqua;
    color: #3E7BB6;
}
<?php } ?>

div#left_tableList img {
    vertical-align: middle;
}

/* leftdatabaselist */
div#left_tableList ul {
    list-style-type: none;
    list-style-position: outside;
    margin: 0;
    padding: 0;
    background-color: transparent;
}

div#left_tableList ul ul {
    margin-left: 0em;
    padding-left: 0.1em;
    border-left: 0.1em solid #669999;
    padding-bottom: 0.1em;
    border-bottom: 0.1em solid #669999;
}
