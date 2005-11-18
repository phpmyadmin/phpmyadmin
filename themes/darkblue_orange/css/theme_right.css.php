/************************************************************************************
 * RIGHT FRAME
 ************************************************************************************/
/* Always enabled stylesheets (right frame) */
body {
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    font-size:        10px;
    color:            #000000;
<?php
    if ($GLOBALS['cfg']['RightBgImage'] != '') {
        echo '    background-image: url(' . $GLOBALS['cfg']['RightBgImage'] . ');' . "\n";
    }
    ?>
    background-color: #ffffff;
    margin: 5px;
}

/* gecko FIX, font size is not correctly assigned to all child elements */
body * {
    font-family:      inherit;
    font-size:        inherit;
}

pre, tt, code{
    font-size:        110%;
}
a:link, a:visited, a:active{
    text-decoration:  none;
    color:            #333399;

}
a:hover{
    text-decoration:  underline;
    color:            #cc0000;
}
th{
    font-weight:         bold;
    color:               #000000;
    background-color:    #ff9900;
    background-image:    url(../themes/darkblue_orange/img/tbl_th.png);
    background-repeat:   repeat-x;
    background-position: top;
    height:              18px;
}
th a:link, th a:active, th a:visited{
    color:            #000000;
    text-decoration:  underline;
}

th a:hover{
    color:            #666666;
    text-decoration:  none;
}
.tblcomment{
    font-weight:      normal;
    color:            #000099;
}
th.td{
    font-weight: normal;
    color: transparent;
    background-color: transparent;
    background-image: none;

}
form{
    padding:          0px 0px 0px 0px;
    margin:           0px 0px 0px 0px;
}
select, textarea{
    color:            #000000;
    background-color: #FFFFFF;
}
input.textfield{
    color:            #000000;
    /*background-color: #FFFFFF;*/
}

h1{
    font-size:        180%;
    font-weight:      bold;
}
h2{
    font-size:        130%;
    font-weight:      bold;
}
h3{
    font-size:        120%;
    font-weight:      bold;
}
dfn{
    font-style:       normal;
}
dfn:hover{
    font-style:       normal;
    cursor:           help;
}

fieldset {
    border:     #666699 solid 1px;
    padding:    0.5em;
}
fieldset fieldset {
    margin:     0.8em;
}
legend {
    color:       #666699;
    font-weight: bold;
    background-color: #ffffff;
    padding: 2px 2px 2px 2px;
}
button.mult_submit {
    border: none;
    background-color: transparent;
}

.pdflayout {
    overflow:         hidden;
    clip:             inherit;
    background-color: #FFFFFF;
    display:          none;
    border:           1px solid #000000;
    position:         relative;
}

.pdflayout_table {
    background:       #ff9900;
    color:            #000000;
    overflow:         hidden;
    clip:             inherit;
    z-index:          2;
    display:          inline;
    visibility:       inherit;
    cursor:           move;
    position:         absolute;
    font-size:        110%;
    border:           1px dashed #000000;
}


/* topmenu */
ul#topmenu {
    font-weight: bold;
    list-style-type: none;
    margin: 0;
    padding: 0;
}

ul#topmenu li {
    float: left;
    margin: 0;
    padding: 0;
    vertical-align: middle;
}

#topmenu img {
    vertical-align: middle;
    margin-right: 0.1em;
}

/* default tab styles */
.tab, .tabcaution, .tabactive {
    display: block;
    margin: 0.2em 0.2em 0 0.2em;
    padding: 0.2em 0.2em 0 0.2em;
    white-space: nowrap;
}

/* disabled tabs */
span.tab {
    color: #666666;
}

/* disabled drop/empty tabs */
span.tabcaution {
    color: #ff6666;
}

/* enabled drop/empty tabs */
a.tabcaution {
    color: #FF0000;
}
a.tabcaution:hover {
    color: #FFFFFF;
    background-color: #FF0000;
}

<?php if ( $GLOBALS['cfg']['LightTabs'] ) { ?>
/* active tab */
a.tabactive {
    color: black;
}
<?php } else { ?>
#topmenu {
    margin-top: 0.5em;
    padding: 0.1em 0.3em 0.1em 0.3em;
}

ul#topmenu li {
    border-bottom: 1pt solid black;
}

/* default tab styles */
.tab, .tabcaution, .tabactive {
    background-color: <?php echo $GLOBALS['cfg']['BgcolorOne']; ?>;
    border: 1pt solid <?php echo $GLOBALS['cfg']['BgcolorTwo']; ?>;
    border-bottom: 0;
    border-radius-topleft: 0.4em;
    border-radius-topright: 0.4em;
    -moz-border-radius-topleft: 0.4em;
    -moz-border-radius-topright: 0.4em;
}

/* enabled hover/active tabs */
a.tab:hover, a.tabcaution:hover, .tabactive, .tabactive:hover {
    margin: 0;
    padding: 0.2em 0.4em 0.2em 0.4em;
    text-decoration: none;
}

a.tab:hover, .tabactive {
    background-color: <?php echo $GLOBALS['cfg']['BgcolorTwo']; ?>;
}

/* disabled drop/empty tabs */
span.tab, span.tabcaution {
    cursor: url(themes/darkblue_orange/img/error.ico), url(../themes/darkblue_orange/img/error.ico), default;
}
<?php } ?>
/* end topmenu */


/* message boxes: warning, error, confirmation */
.notice {
    color: #000000;
    background-color: #FFFFDD;
}
h1.notice,
div.notice {
    margin: 0.5em 0 0.5em 0;
    border: 0.1em solid #FFD700;
    <?php if ( $GLOBALS['cfg']['ErrorIconic'] ) { ?>
    background-image: url(../themes/darkblue_orange/img/s_notice.png);
    background-repeat: no-repeat;
        <?php if ( $GLOBALS['text_dir'] === 'ltr' ) {?>
    background-position: 10px 50%;
    padding: 10px 10px 10px 36px;
        <?php } else {?>
    background-position: 99% 50%;
    padding: 10px 5% 10px 10px;
        <?php }?>
    <?php } else {?>
    padding: 0.5em;
    <?php }?>
}
.notice h1 {
    border-bottom: 0.1em solid #FFD700;
    font-weight: bold;
    text-align: <?php echo $left; ?>;
    margin: 0 0 0.2em 0;
}

.warning {
    color: #CC0000;
    background-color: #FFFFCC;
}
p.warning,
h1.warning,
div.warning {
    margin: 0.5em 0 0.5em 0;
    border: 0.1em solid #CC0000;
    width: 90%;

    <?php if ( $GLOBALS['cfg']['ErrorIconic'] ) { ?>
    background-image: url(../themes/darkblue_orange/img/s_warn.png);
    background-repeat: no-repeat;
        <?php if ( $GLOBALS['text_dir'] === 'ltr' ) {?>
    background-position: 10px 50%;
    padding: 10px 10px 10px 36px;
        <?php } else {?>
    background-position: 99% 50%;
    padding: 10px 5% 10px 10px;
        <?php }?>
    <?php } else {?>
    padding: 0.5em;
    <?php }?>
}
.warning h1 {
    border-bottom: 0.1em solid #cc0000;
    font-weight: bold;
    text-align: <?php echo $left; ?>;
    margin: 0 0 0.2em 0;
}

.error {
    background-color: #FFFFCC;
    color: #ff0000;
}
p.error,
h1.error,
div.error {
    margin: 0.5em 0 0.5em 0;
    border: 0.1em solid #ff0000;
    width: 90%;
    <?php if ( $GLOBALS['cfg']['ErrorIconic'] ) { ?>
    background-image: url(../themes/darkblue_orange/img/s_error.png);
    background-repeat: no-repeat;
        <?php if ( $GLOBALS['text_dir'] === 'ltr' ) {?>
    background-position: 10px 50%;
    padding: 10px 10px 10px 36px;
        <?php } else {?>
    background-position: 99% 50%;
    padding: 10px 5% 10px 10px;
        <?php }?>
    <?php } else {?>
    padding: 0.5em;
    <?php }?>
}
div.error h1 {
    border-bottom: 0.1em solid #ff0000;
    font-weight: bold;
    text-align: <?php echo $left; ?>;
    margin: 0 0 0.2em 0;
}

.confirmation {
    background-color: #FFFFCC;
}
fieldset.confirmation {
    border: 0.1em solid #FF0000;
}
fieldset.confirmation legend {
    border-left: 0.1em solid #FF0000;
    border-right: 0.1em solid #FF0000;
    font-weight: bold;
    <?php if ( $GLOBALS['cfg']['ErrorIconic'] ) { ?>
    background-image: url(../themes/darkblue_orange/img/s_really.png);
    background-repeat: no-repeat;
        <?php if ( $GLOBALS['text_dir'] === 'ltr' ) {?>
    background-position: 5px 50%;
    padding: 0.2em 0.2em 0.2em 25px;
        <?php } else {?>
    background-position: 98% 50%;
    padding: 0.2em 25px 0.2em 0.2em;
        <?php }?>
    <?php }?>
}
/* end messageboxes */


.print{font-size:8pt;}

/* MySQL Parser */
.syntax {}
.syntax_comment            { padding-left: 4pt; padding-right: 4pt;}
.syntax_digit              {}
.syntax_digit_hex          {}
.syntax_digit_integer      {}
.syntax_digit_float        {}
.syntax_punct              {}
.syntax_alpha              {}
.syntax_alpha_columnType   {text-transform: uppercase;}
.syntax_alpha_columnAttrib {text-transform: uppercase;}
.syntax_alpha_reservedWord {text-transform: uppercase; font-weight: bold;}
.syntax_alpha_functionName {text-transform: uppercase;}
.syntax_alpha_identifier   {}
.syntax_alpha_charset      {}
.syntax_alpha_variable     {}
.syntax_quote              {white-space: pre;}
.syntax_quote_backtick     {}

/* tables */
.tblHeaders{
    font-weight:         bold;
    color:               #ffffff;
    background-color:    #666699;
    background-image:    url(../themes/darkblue_orange/img/tbl_header.png);
    background-repeat:   repeat-x;
    background-position: top;
    height:              18px;
}
.tblHeaders a:link, .tblHeaders a:visited, .tblHeaders a:active, .tblFooters a:link, tblFooters a:visited, tblFooters a:active{
    color:            #ffffcc;
    text-decoration:  underline;
}
.tblFooters{
    font-weight:         normal;
    color:               #ffffff;
    background-color:    #666699;
    background-image:    url(../themes/darkblue_orange/img/tbl_header.png);
    background-repeat:   repeat-x;
    background-position: top;
}
.tblHeaders a:hover, tblFooters a:hover{
    text-decoration: none;
    color:           #ffffff;
}

/* forbidden, no privilegs */
.noPrivileges{
    color:            #cc0000;
    font-weight:      bold;
}

/* Heading for server links*/

#serverinfo {
    font-weight: bold;
    margin-bottom: 0.5em;
}

#serverinfo .item {
    white-space: nowrap;
}

#span_table_comment {
    font-weight: normal;
    font-style: italic;
    white-space: nowrap;
}

#serverinfo img {
    margin: 0 0.1em 0 0.1em;
}

hr{
    color: #666699; background-color: #6666cc; border: 0; height: 1px;
}


img, input, select, button {
    vertical-align: middle;
}

/* disabled text */
.disabled, .disabled a:link, disabled a:active, .disabled a:visited {
    color:            #666666;
}
.disabled a:hover {
    color:            #666666;
    text-decoration:  none;
}
tr.disabled td, td.disabled {
    background-color: #cccccc;
}

/* some styles for IDs: */
#buttonNo{
    color:            #CC0000;
    font-weight:      bold;
    padding:          0px 10px 0px 10px;
}
#buttonYes{
    color:            #006600;
    font-weight:      bold;
    padding:          0px 10px 0px 10px;
}
#buttonGo{
    color:            #006600;
    font-weight:      bold;
    padding:          0px 10px 0px 10px;
}

#listTable{
    width:            260px;
}

#textSqlquery{
    width:            450px;
}
#textSQLDUMP {
   width: 95%;
   height: 95%;
   font-family: "Courier New", Courier, mono;
   font-size:   110%;
}

#TooltipContainer {
    position:   absolute;
    z-index:    99;
    width:      20em;
    height:     auto;
    overflow:   visible;
    visibility: hidden;
    background-color: #ffffcc;
    color:            #006600;
    border:           0.1em solid #000000;
    padding:          0.5em;
}

fieldset {
    margin-top: 1em;
}

fieldset.tblFooters {
    margin-top: 0;
    margin-bottom: 0.5em;
    text-align: right;
    float: none;
    clear: both;
}

#fieldset_add_user_login div.item {
    border-bottom: 1px solid silver;
    padding-bottom: 0.3em;
    margin-bottom: 0.3em;
}

#fieldset_add_user_login label {
    float: left;
    display: block;
    width: 10em;
    max-width: 100%;
    text-align: right;
    padding-right: 0.5em;
}

#fieldset_add_user_login span.options #select_pred_username,
#fieldset_add_user_login span.options #select_pred_hostname,
#fieldset_add_user_login span.options #select_pred_password {
    width: 100%;
    max-width: 100%;
}

#fieldset_add_user_login span.options {
    float: left;
    display: block;
    width: 12em;
    max-width: 100%;
    padding-right: 0.5em;
}

#fieldset_add_user_login input {
    width: 12em;
    clear: right;
    max-width: 100%;
}

#fieldset_add_user_login span.options input {
    width: auto;
}

#fieldset_user_priv div.item {
    float: left;
    width: 8em;
    max-width: 100%;
}

#fieldset_user_priv div.item div.item {
    float: none;
}

#fieldset_user_priv div.item label{
    white-space: nowrap;
}

#fieldset_user_priv div.item select {
    width: 100%;
}

#fieldset_user_global_rights fieldset {
    float: left;
}
