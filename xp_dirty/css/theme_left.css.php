<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * navigation css file from theme
 *
 * @version $Id$
 * @package phpMyAdmin-theme
 * @subpackage WinXP_Dirty
 */
?>
body, input, select {
    font-family: <?php echo $left_font_family; ?>;
    font-size: <?php echo $font_size; ?>;
    color: #000000
}

body#body_leftFrame {
    background-color: <?php echo $GLOBALS['cfg']['LeftBgColor']; ?>;
}

select {
    background-color: #ffffff;
    color: #000000;
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
    background-color: #669999;
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
//    color: #333399;
    color: #FFFFFF;
    text-decoration: none;
}

div#left_tableList a:hover {
//    color: #FF0000;
    color: #333399;
    text-decoration: underline;
}

div#left_tableList li {
    margin: 0;
    padding: 0;
    white-space: nowrap;
}

<?php if ( $GLOBALS['cfg']['LeftPointerEnable'] ) { ?>
div#left_tableList li:hover {
    background-color: <?php echo $GLOBALS['cfg']['LeftPointerColor']; ?>;
}
<?php } ?>

div#left_tableList img {
    vertical-align: middle;
}

div#left_tableList ul ul {
    margin-left: 0em;
    padding-left: 0.1em;
    border-left: 0.1em solid #669999;
    padding-bottom: 0.1em;
    border-bottom: 0.1em solid #669999;
}