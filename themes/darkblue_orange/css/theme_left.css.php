body {
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    font-size:        10px;
    background-color: #666699;
    color:            #ffffff;
    margin: 0;
    padding: 2px 2px 2px 2px;
}

/* gecko FIX, font size is not correctly assigned to all child elements */
body * {
    font-family:      inherit;
    font-size:        inherit;
}

select{
    background-color: #ffffff;
    color:            #000000;
    width:            150px;
}

img, input, select, button {
    vertical-align: middle;
}

div#pmalogo,
div#leftframelinks,
div#databaseList {
    text-align: center;
    border-bottom: 0.1em solid #ffffff;
    margin-bottom: 0.5em;
    padding-bottom: 0.5em;
}

div#leftframelinks a {
    margin: 0.1em;
}

div#leftframelinks a:hover {
    background-color: #ffffff;
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
}

div#left_tableList a {
    color: #ffffff;
    text-decoration: none;
}

div#left_tableList a:hover {
    color: #ffffff;
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
    border-left: 0.1em solid #ffffff;
    padding-bottom: 0.1em;
    border-bottom: 0.1em solid #ffffff;
}