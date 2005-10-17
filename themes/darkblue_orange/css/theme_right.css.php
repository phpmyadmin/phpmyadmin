/************************************************************************************
 * RIGHT FRAME
 ************************************************************************************/
/* Always enabled stylesheets (right frame) */
body{
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

pre, tt, code{
    font-size:        11px;
}
a:link, a:visited, a:active{
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    font-size:        10px;
    text-decoration:  none;
    color:            #333399;

}
a:hover{
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    font-size:        10px;
    text-decoration:  underline;
    color:            #cc0000;
}
th{
    font-family:         Verdana, Arial, Helvetica, sans-serif;
    font-size:           10px;
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
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    font-size:        10px;
    font-weight:      normal;
    color:            #000099;
}
th.td{
    font-weight: normal;
    color: transparent;
    background-color: transparent;
    background-image: none;
   
}
td{
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    font-size:        10px;
}
form{
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    font-size:        10px;
    padding:          0px 0px 0px 0px;
    margin:           0px 0px 0px 0px;
}
select, textarea, input {
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    font-size:        10px;
}
select, textarea{
    color:            #000000;
    background-color: #FFFFFF;
}
input.textfield{
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    font-size:        10px;
    color:            #000000;
    /*background-color: #FFFFFF;*/
}

h1{
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    font-size:        18px;
    font-weight:      bold;
}
h2{
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    font-size:        13px;
    font-weight:      bold;
}
h3{
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    font-size:        12px;
    font-weight:      bold;
}
a.h1:link, a.h1:active, a.h1:visited{
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    font-size:        18px;
    font-weight:      bold;
    color:            #000000;
}
a.h1:hover{
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    font-size:        18px;
    font-weight:      bold;
    color:            #cc0000;
}
a.h2:link, a.h2:active, a.h2:visited{
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    font-size:        13px;
    font-weight:      bold;
    color:            #000000;
}
a.h2:hover{
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    font-size:        13px;
    font-weight:      bold;
    color:            #cc0000;
}
a.drop:link, a.drop:visited, a.drop:active{
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    color:            #cc0000;
}
a.drop:hover{
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    color:            #ffffff;
    background-color: #cc0000;
    text-decoration:  none;
}
dfn{
    font-style:       normal;
}
dfn:hover{
    font-style:       normal;
    cursor:           help;
}
.warning{
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    font-size:        10px;
    font-weight:      bold;
    color:            #cc0000;
}
td.topline{
    font-size:        1px;
}

fieldset {
    border:     #666699 solid 1px;
    padding:    0.5em;
}
fieldset fieldset {
    margin:     0.8em;
}
legend {
    font-family: Verdana, Arial, Helvetica, sans-serif;
    size:        10px;
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
    font-size:        11px;
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
    cursor: url(themes/original/img/error.ico), default;
}
<?php } ?>
/* end topmenu */


/* Warning showing div with right border and optional icon */

div.warning {
    border: 1px solid #cc0000;
/*
<?php if( $GLOBLAS['cfg']['ErrorIconic'] ) { ?>
*/
    background-image: url(../themes/darkblue_orange/img/s_warn.png);
    background-repeat: no-repeat;
    background-position: 10px 10px;
    padding: 10px 10px 10px 36px;
    margin: 0px;
/*
<?php } ?>
*/
    width: 90%;
}

div.error {
    width: 100%;
    border: 1px solid #cc0000;
    background-color: #ffffcc;
    padding: 0px;
}

div.error  div.text {
    padding: 5px;
}

div.error div.head {
    background-color: #cc0000;
    font-weight: bold;
    color: #ffffff;
/*
<?php if ( $GLOBALS['cfg']['ErrorIconic'] ) { ?>
*/
    background-image: url(../themes/darkblue_orange/img/s_error.png);
    background-repeat: no-repeat;
    background-position: 2px 50%;
    padding: 2px 2px 2px 30px;
/*
<?php } ?>
*/
    margin: 0px;
}
.print{font-family:arial;font-size:8pt;}

/* MySQL Parser */
.syntax {font-family: sans-serif; font-size: 10px;}
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
.tblError {
    border:           1px solid #cc0000;
    background-color: #ffffcc;
}
.tblWarn, div.tblWarn {
    border: 1px solid #cc0000;
    background-color: #ffffff;
}
div.tblWarn {
    padding: 5px 5px 5px 5px;
    margin:  2px 0px 2px 0px;
    width:   100%;
}
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
.tblHeadError {
    font-weight:         bold;
    color:               #ffffff;
    background-color:    #cc0000;
    background-image:    url(../themes/darkblue_orange/img/tbl_error.png);
    background-repeat:   repeat-x;
    background-position: top;
    height:              18px;
}
div.errorhead {
    font-weight: bold;
    color: #ffffff;
    text-align: left;
    <?php if ( $GLOBALS['cfg']['ErrorIconic'] ) { ?>
    background-image: url(../themes/darkblue_orange/img/s_error.png);
    background-repeat: no-repeat;
    background-position: 2px 50%;
    padding: 2px 2px 2px 20px;
    <?php } ?>
    margin: 0px;
}

.tblHeadWarn {
    background-color:    #ffcc00;
    font-weight:         bold;
    color:               #000000;
    background-image:    url(../themes/darkblue_orange/img/tbl_th.png);
    background-repeat:   repeat-x;
    background-position: top;
    height:              18px;
}
div.warnhead {
    font-weight: bold;
    color: #ffffff;
    text-align: left;
    <?php if ( $GLOBALS['cfg']['ErrorIconic'] ) { ?>
    background-image: url(../themes/darkblue_orange/img/s_warn.png);
    background-repeat: no-repeat;
    background-position: 2px 50%;
    padding: 2px 2px 2px 20px;
    <?php } ?>
    margin: 0px;
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
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    font-size:        10px;
    color:            #666666;
}
.disabled a:hover {
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    font-size:        10px;
    color:            #666666;
    text-decoration:  none;
}
tr.disabled td, td.disabled {
    background-color: #cccccc;
}

/* some styles for IDs: */
#buttonNo{
    color:            #CC0000;
    font-size:        10px;
    font-weight:      bold;
    padding:          0px 10px 0px 10px;
}
#buttonYes{
    color:            #006600;
    font-size:        10px;
    font-weight:      bold;
    padding:          0px 10px 0px 10px;
}
#buttonGo{
    color:            #006600;
    font-size:        10px;
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
   font-size:   11px;
}

#TooltipContainer {
    position:   absolute;
    z-index:    99;
    width:      250px;
    height:     50px;
    overflow:   auto;
    visibility: hidden;
    background-color: #ffffcc;
    color:            #006600;
    border:           1px solid #000000;
    padding:          5px;
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    font-size:        10px;
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