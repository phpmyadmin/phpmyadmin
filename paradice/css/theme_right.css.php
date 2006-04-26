<?php
    // unplanned execution path
    if (!defined('PMA_MINIMUM_COMMON')) {
        exit();
    }
?> 
/******************************************************************************/
/* general tags */
html {
	margin: 0px;
	padding: 0px;
}

body {
    font-family:        Verdana, Arial, Helvetica, sans-serif;
    font-size:          10px;
    color:            	 #142F56;
	background-image:	 url(../themes/paradice/img/rightBgnd.jpg);
	background-repeat: 	 no-repeat;
	background-position: right bottom;
    background-color:   #ffffff;
    margin:             5px;
	padding: 			0px;
}

/* gecko FIX, font size is not correctly assigned to all child elements */
body * {
    font-family:        inherit;
    font-size:          inherit;
}

h1 {
    font-size:        	 18px;
    font-weight:      	 bold;
}

h2 {
    font-size:        	 13px;
    font-weight:      	 bold;
}

h3 {
    font-size:        	 12px;
    font-weight:      	 bold;
}

pre, tt, code{
    font-size:        	 11px;
}

a:link,
a:visited,
a:active {
    font-size:        	 11px;
    text-decoration:  	 none;
    color:            	 #1F457E;

}

a:hover {
    font-size:        	 11px;
    text-decoration:  	 underline;
    color:            	 #8897AE;
}

dfn {
    font-style:         normal;
}

dfn:hover {
    font-style:         normal;
    cursor:             help;
}

th {
    font-size:          11px;
    font-weight:        bold;
    color:              #000000;
    background-color:   #ff9900;
    background-image:   url(../themes/paradice/img/tbl_th.png);
    background-repeat:  repeat-x;
    background-position: top;
    height:             18px;
}

th a:link,
th a:active,
th a:visited {
    color:              #000000;
    text-decoration:    underline;
}

th a:hover {
    color:              #666666;
    text-decoration:    none;
}

a img {
    border:             0;
}

hr {
    color: #79A2DF;
	background-color: #6666cc;
	border: 0;
    height:             1px;
}

form {
    font-size:        	 10px;
    padding:          	 0px 0px 0px 0px;
    margin:           	 0px 0px 0px 0px;
}

th.td{
    font-weight: 		 normal;
    color: 				 transparent;
    background-color: 	 transparent;
    background-image: 	 url(../themes/paradice/img/tbl_th.png);

}

td{
    font-size:        	 10px;
}

select, textarea, input {
    font-size:        	 10px;
	border: 			 1px solid #3674CF;
}

select, textarea{
    color:            	 #000000;
    background-color: 	 #FFFFFF;
}

input.textfield{
    font-size:        	 10px;
    color:            	 #000000;
    background-color: 	 #FFFFFF;
}

a.h1:link, a.h1:active, a.h1:visited{
    font-size:        	 18px;
    font-weight:      	 bold;
    color:            	 #000000;
}

a.h1:hover{
    font-size:        	 18px;
    font-weight:      	 bold;
    color:            	 #666666;
}

a.h2:link, a.h2:active, a.h2:visited{
    font-size:        	 13px;
    font-weight:      	 bold;
    color:            	 #000000;
}

a.h2:hover{
    font-size:        	 13px;
    font-weight:      	 bold;
    color:            	 #666666;
}

a.drop:link, a.drop:visited, a.drop:active{
    color:            	 #666666;
}

a.drop:hover{
    color:            	 #ffffff;
    background-color: 	 #666666;
    text-decoration:  	 none;
}

fieldset {
    margin-top:         1em;
    border:     		 #79A2DF solid 1px;
    padding:    		 0.5em;
}

fieldset fieldset {
    margin:             0.8em;
}

fieldset legend {
    color:       		#79A2DF;
    font-weight: 		bold;
    background-color: 	#ffffff;
    padding:            2px 2px 2px 2px;
	size:        		11px;
}

/* buttons in some browsers (eg. Konqueror) are block elements,
   this breaks design */
button {
    display:            inline;
}

table caption,
table th,
table td {
    padding:            0.1em 0.5em 0.1em 0.5em;
    margin:             0.1em;
    vertical-align:     top;
}

img,
input,
select,
button {
    vertical-align:     middle;
}


/******************************************************************************/
/* classes */

fieldset.tblFooters {
    margin-top:         0;
    margin-bottom:      0.5em;
    text-align:         right;
    float:              none;
    clear:              both;
}

fieldset .formelement {
    float:              left;
    margin-right:       0.5em;
    /* IE */
    white-space:        nowrap;
}

/* revert for Gecko */
fieldset div[class=formelement] {
    white-space:        normal;
}

button.mult_submit {
    border:             none;
    background-color:   transparent;
}

/* odd table rows 1,3,5,7,... */
table tr.odd th,
table tr.odd {
    background-image:   none;
    background-color:   #E5E5E5;
    text-align:         left;
}

/* even table rows 2,4,6,8,... */
table tr.even th,
table tr.even {
    background-image:   none;
    background-color:   #D5D5D5;
    text-align:         left;
}

/* marked tbale rows */
table tr.marked th,
table tr.marked {
    background-color:   #FFCC99;
}

/* hovered table rows */
table tr.odd:hover,
table tr.even:hover,
table tr.odd:hover th,
table tr.even:hover th,
table tr.hover th,
table tr.hover {
    background-color:   #CCFFCC;
}

table .value {
    text-align:         right;
    white-space:        nowrap;
}
/* IE doesnt handles 'pre' right */
table [class=value] {
    white-space:        pre;
}

.value {
    font-family:        "Courier New", Courier, monospace;
}
.value .attention {
    color:              red;
    font-weight:        bold;
}
.value .allfine {
    color:              green;
}


img.lightbulb {
    cursor:             pointer;
}

.pdflayout {
    overflow:           hidden;
    clip:               inherit;
    background-color:   #FFFFFF;
    display:            none;
    border:             1px solid #000000;
    position:           relative;
}

.pdflayout_table {
    background:         #ff9900;
    color:              #000000;
    overflow:           hidden;
    clip:               inherit;
    z-index:            2;
    display:            inline;
    visibility:         inherit;
    cursor:             move;
    position:           absolute;
    font-size:          110%;
    border:             1px dashed #000000;
}

.print {
    font-size:          8pt;
}

/* MySQL Parser */
.syntax {
font-size: 10px;}

.syntax_comment {
    padding-left:       4pt;
    padding-right:      4pt;
}

.syntax_digit {
}

.syntax_digit_hex {
}

.syntax_digit_integer {
}

.syntax_digit_float {
}

.syntax_punct {
}

.syntax_alpha {
}

.syntax_alpha_columnType {
    text-transform:     uppercase;
}

.syntax_alpha_columnAttrib {
    text-transform:     uppercase;
}

.syntax_alpha_reservedWord {
    text-transform:     uppercase;
    font-weight:        bold;
}

.syntax_alpha_functionName {
    text-transform:     uppercase;
}

.syntax_alpha_identifier {
}

.syntax_alpha_charset {
}

.syntax_alpha_variable {
}

.syntax_quote {
    white-space:        pre;
}

.syntax_quote_backtick {
}

/* leave some space between icons and text */
.icon {
    vertical-align:     middle;
    margin-right:       0.3em;
    margin-left:        0.3em;
}
/* no extra space in table cells */
td .icon {
    margin: 0;
}

.selectallarrow {
    margin-<?php echo $right; ?>: 0.3em;
    margin-<?php echo $left; ?>: 0.6em;
}

/* message boxes: warning, error, confirmation */
.notice {
    color:              #000000;
    background-color:   #FFFFDD;
}
h1.notice,
div.notice {
    margin:             0.5em 0 0.5em 0;
    border:             0.1em solid #FFD700;
    <?php if ( $GLOBALS['cfg']['ErrorIconic'] ) { ?>
    background-image: url(../themes/paradice/img/s_notice.png);
    background-repeat:  no-repeat;
        <?php if ( $GLOBALS['text_dir'] === 'ltr' ) { ?>
    background-position: 10px 50%;
    padding:            10px 10px 10px 36px;
        <?php } else { ?>
    background-position: 99% 50%;
    padding:            10px 5% 10px 10px;
        <?php } ?>
    <?php } else { ?>
    padding:            0.5em;
    <?php } ?>
}
.notice h1 {
    border-bottom:      0.1em solid #FFD700;
    font-weight:        bold;
    text-align:         <?php echo $left; ?>;
    margin:             0 0 0.2em 0;
}

.warning {
    color:              #CC0000;
    background-color:   #FFFFCC;
}
p.warning,
h1.warning,
div.warning {
    margin:             0.5em 0 0.5em 0;
    border:             0.1em solid #CC0000;
    width:              90%;

    <?php if ( $GLOBALS['cfg']['ErrorIconic'] ) { ?>
    background-image: url(../themes/paradice/img/s_warn.png);
    background-repeat:  no-repeat;
        <?php if ( $GLOBALS['text_dir'] === 'ltr' ) { ?>
    background-position: 10px 50%;
    padding:            10px 10px 10px 36px;
        <?php } else { ?>
    background-position: 99% 50%;
    padding:            10px 5% 10px 10px;
        <?php } ?>
    <?php } else { ?>
    padding:            0.5em;
    <?php } ?>
}
.warning h1 {
    border-bottom:      0.1em solid #cc0000;
    font-weight:        bold;
    text-align:         <?php echo $left; ?>;
    margin:             0 0 0.2em 0;
}

.error {
    background-color:   #FFFFCC;
    color:              #ff0000;
}

h1.error,
div.error {
    margin:             0.5em 0 0.5em 0;
    border:             0.1em solid #ff0000;
    width:              90%;
    <?php if ( $GLOBALS['cfg']['ErrorIconic'] ) { ?>
	background-image:   url(../themes/paradice/img/s_error.png);
    background-repeat:  no-repeat;
        <?php if ( $GLOBALS['text_dir'] === 'ltr' ) { ?>
    background-position: 10px 50%;
    padding:            10px 10px 10px 36px;
        <?php } else { ?>
    background-position: 99% 50%;
    padding:            10px 5% 10px 10px;
        <?php } ?>
    <?php } else { ?>
    padding:            0.5em;
    <?php } ?>
}
div.error h1 {
    border-bottom:      0.1em solid #ff0000;
    font-weight:        bold;
    text-align:         <?php echo $left; ?>;
    margin:             0 0 0.2em 0;
}

.confirmation {
    background-color:   #FFFFCC;
}
fieldset.confirmation {
    border:             0.1em solid #FF0000;
}
fieldset.confirmation legend {
    border-left:        0.1em solid #FF0000;
    border-right:       0.1em solid #FF0000;
    font-weight:        bold;
    <?php if ( $GLOBALS['cfg']['ErrorIconic'] ) { ?>
    background-image: url(../themes/paradice/img/s_error.png);
    background-repeat:  no-repeat;
        <?php if ( $GLOBALS['text_dir'] === 'ltr' ) { ?>
    background-position: 5px 50%;
    padding:            0.2em 0.2em 0.2em 25px;
        <?php } else { ?>
    background-position: 97% 50%;
    padding:            0.2em 25px 0.2em 0.2em;
        <?php } ?>
    <?php } ?>
}
/* end messageboxes */


.tblcomment{
    font-family:      	 Verdana, Arial, Helvetica, sans-serif;
    font-size:         	 10px;
    font-weight:      	 normal;
    color:            	 #000099;
}

.tblHeaders {
    font-weight:        bold;
    color:              #ffffff;
    background-color:    #79A2DF;
    background-image:    url(../themes/paradice/img/tbl_header.png);
    background-repeat:  repeat-x;
    background-position: top;
    height:             18px;
}

.tblFooters {
    font-weight:        normal;
    color:              #ffffff;
    background-color:    #79A2DF;
    background-image:    url(../themes/paradice/img/tbl_header.png);
    background-repeat:  repeat-x;
    background-position: top;
}

.tblHeaders a:link,
.tblHeaders a:active,
.tblHeaders a:visited,
.tblFooters a:link,
.tblFooters a:active,
.tblFooters a:visited {
    color:              #ffffcc;
    text-decoration:    underline;
}

.tblHeaders a:hover,
.tblFooters a:hover {
    text-decoration:    none;
    color:              #ffffff;
}

/* forbidden, no privilegs */
.noPrivileges {
    color:              #cc0000;
    font-weight:        bold;
}

/* disabled text */
.disabled,
.disabled a:link,
.disabled a:active,
.disabled a:visited {
    color:              #666666;
}

.disabled a:hover {
    color:              #666666;
    text-decoration:    none;
}

tr.disabled td,
td.disabled {
    background-color:   #cccccc;
}


/******************************************************************************/
/* specific elements */

/* topmenu */
ul#topmenu {
    font-weight:        bold;
    list-style-type:    none;
    margin:             0;
    padding:            0;
    background-color:    #79A2DF;
	background-image:    url(../themes/paradice/img/tbl_header.png);
    background-repeat:   repeat-x;
    background-position: top;
	height: 			 28px;
}

ul#topmenu li {
    float:              left;
    margin:             0;
    padding:            0;
    vertical-align:     middle;
	height: 			 24px;
}

#topmenu img {
    vertical-align:     middle;
    margin-right:       0.1em;
}

/* default tab styles */
.tab, .tabcaution, .tabactive {
	color:               #000000;
	font-family:         Verdana, Arial, Helvetica, sans-serif;
	padding:			 0;
    white-space:        nowrap;
}

/* disabled tabs */
span.tab {
	font-family:         Verdana, Arial, Helvetica, sans-serif;
	font-size:           11px;
	font-weight:         bold;
	color:               #FFFFFF;
	padding: 			 4px 8px 8px 8px;
	margin: 			-1px 0px 0px -1px;
	background-image:    url(../themes/paradice/img/tbl_header_disabled.png);
	background-repeat:   repeat-x;
    background-position: top;
}

span.tab:hover {
	background-image:    url(../themes/paradice/img/tbl_header_disabled.png);
	font-family:         Verdana, Arial, Helvetica, sans-serif;
	font-size:           11px;
	font-weight:         bold;
	color:               #FFFFFF;
	padding: 			 4px 8px 8px 8px;
	margin: 			-1px 0px 0px -1px;
	background-image:    url(../themes/paradice/img/tbl_header2.png);
	background-repeat:   repeat-x;
    background-position: top;
    text-decoration:		none;
}

/* disabled drop/empty tabs */
span.tabcaution {
    color:              #ff6666;
}

/* enabled drop/empty tabs */
a.tabcaution {
    color:              FF0000;
}
a.tabcaution:hover {
    color: #FFFFFF;
    background-color:   #FF0000;
}

<?php if ( $GLOBALS['cfg']['LightTabs'] ) { ?>
/* active tab */
ul#topmenu li a.tabactive {
    color:              black;
	padding: 			 4px 8px 8px 8px;
}
<?php } else { ?>
#topmenu {
    padding: 0px;
    padding-top:		 4px;
}

ul#topmenu li {
    border-bottom: 0;
}

/* default tab styles */
.tab, .tabcaution, .tabactive {
    background-color:	 #E5E5E5;
    border: 			 0px 1px 0px 0px;
	border-style:		 solid;
	border-color: 		 #FFFFFF;
	background-image:    url(../themes/paradice/img/tbl_header2.png);
    background-repeat:   repeat-x;
    background-position: top;
	/* overwrite default button look */
	border-radius-topleft: 0px;
    border-radius-topright: 0px;
    -moz-border-radius-topleft: 0px;
    -moz-border-radius-topright: 0px;
}

ul#topmenu li a.tab {
    font-family:         Verdana, Arial, Helvetica, sans-serif;
    font-size:           11px;
    font-weight:         bold;
	color:               #FFFFFF;
}

ul#topmenu li a.tab:hover {
    font-family:         Verdana, Arial, Helvetica, sans-serif;
    font-size:           11px;
    font-weight:         bold;
	color:               #FFFFFF;
}

ul#topmenu li:hover {
	background-image:    url(../themes/paradice/img/tbl_header2.png);
	text-decoration: 	 underline;
	font-family:         Verdana, Arial, Helvetica, sans-serif;
    font-size:           11px;
    font-weight:         bold;
	color:               #FFFFFF;
}

/* enabled drop/empty tabs */
ul#topmenu li a.tabcaution {
    font-family:         Verdana, Arial, Helvetica, sans-serif;
    font-size:           11px;
    font-weight:         bold;
    color:               #FFFFFF;
    background-color:    #cc0000;
    background-image:    url(../themes/paradice/img/tbl_error.png);
    background-repeat:   repeat-x;
    background-position: top;
    padding: 			 4px 8px 8px 8px;
    border: 			 0;
    border-right: 		 1px solid #FFFFFF;
	margin-right: 		 0;
	margin: 			 0;
	margin-left: 		 0px;
}
ul#topmenu li a.tabcaution:hover {
	background-image:    url(../themes/paradice/img/tbl_error2.png);
	padding: 			 4px 8px 8px 8px;
}

/* enabled hover/active tabs */
a.tabactive {
    font-family:         Verdana, Arial, Helvetica, sans-serif;
    font-weight:         bold;
    color:               #000000;
    background-image:    url(../themes/paradice/img/tbl_headerActive.png);
    background-repeat:   repeat-x;
    background-position: top;
    background-color:    #ffffff;
    padding: 		 	 4px 8px 8px 8px; /* top left bottom right - I just can't memorize this ;) */
	border: 			 0;
    border-right: 		 1px solid #FFFFFF;
	border-left: 		 1px solid #FFFFFF;
    margin-left: 		 -3px;
	color:               #000000;
}

a.tabactive:hover {
	background-image:    url(../themes/paradice/img/tbl_header.png);
	padding: 		 	 4px 8px 8px 8px; /* top left bottom right - I just can't memorize this ;) */
    margin-left: 		 -3px;
	color:				 #FFFFFF;
}

a.tab:link, a.tab:active,a.tab:hover, a.tab:visited {
    padding: 			 4px 8px 8px 8px; /* top left bottom right - I just can't memorize this ;) */
	border: 			 0;
    border-right: 		 1px solid #FFFFFF;
	margin: 			 0;
	margin-left: 		 0px;
    color:               #FFFFFF;
}

a.tab:hover,
.tabactive {
	background-image:    url(../themes/paradice/img/tbl_header.png);
}

/* disabled drop/empty tabs */
span.tab, span.tabcaution {
        cursor:             url(../themes/darkblue_orange/img/error.ico), default;
}
<?php } ?>
/* end topmenu */


#fieldsetexport #exportoptions {
    float: left;
}


/* Calendar */
table.calendar {
    width:              100%;
}
table.calendar td {
    text-align:         center;
}
table.calendar td a {
    display:            block;
}

table.calendar td a:hover {
    background-color:   #CCFFCC;
}

table.calendar th {
    background-color:   #D3DCE3;
}

table.calendar td.selected {
    background-color:   #FFCC99;
}

img.calendar {
    border:             none;
}
form.clock {
    text-align:         center;
}
/* end Calendar */


/* table stats */
div#tablestatistics {
    border-bottom: 0.1em solid #669999;
    margin-bottom: 0.5em;
    padding-bottom: 0.5em;
}

div#tablestatistics table {
    float: left;
    margin-bottom: 0.5em;
    margin-right: 0.5em;
}

div#tablestatistics table caption {
    margin-right: 0.5em;
}
/* END table stats */


/* server privileges */
#tableuserrights td,
#tablespecificuserrights td,
#tabledatabases td {
    vertical-align: middle;
}
/* END server privileges */



/* Heading */
#serverinfo {
    font-family:    	 Verdana, Arial, Helvetica, sans-serif;
    font-size:      	 12px;
    font-weight:    	 normal;
    padding: 			 0px 0px 10px 0px;
    margin: 			 0px;
    white-space:    	 nowrap;
    vertical-align: 	 middle;
}

#serverinfo .item {
    white-space:        nowrap;
    font-size:      	 12px;
    font-weight:    	 bolder;
	color:				 #142F56;
}

#span_table_comment {
    font-weight:        normal;
    font-style:         italic;
    white-space:        nowrap;
}

#serverinfo img {
    margin:             0 0.1em 0 0.1em;
}

/* some styles for IDs: */
#buttonNo {
    color:              #CC0000;
    font-weight:        bold;
    padding:            0 10px 0 10px;
    font-size:        	 10px;
}

#buttonYes {
    color:              #006600;
    font-weight:        bold;
    padding:            0 10px 0 10px;
    font-size:        	10px;
}

#buttonGo {
    color:              #006600;
    font-weight:        bold;
    padding:            0 10px 0 10px;
	font-size:        	10px;
}

#listTable {
    width:              260px;
}

#textSqlquery {
    width:              450px;
}

#textSQLDUMP {
    width:              95%;
    height:             95%;
    font-family:        "Courier New", Courier, mono;
   font-size:   		 11px;
}

#TooltipContainer {
    position:           absolute;
    z-index:            99;
    width:      	  	 250px;
    height:     	  	 50px;
    overflow:   	  	 auto;
    visibility: 	  	 hidden;
    background-color:   #ffffcc;
    color:              #006600;
    border:           	 1px solid #000000;
    padding:          	 5px;
    font-family: 	  	 Verdana, Arial, Helvetica, sans-serif;
    font-size:   	  	 10px;
}

/* user privileges */
#fieldset_add_user_login div.item {
    border-bottom: 		1px solid #79A2DF;
    padding-bottom:     0.3em;
    margin-bottom:      0.3em;
}

#fieldset_add_user_login label {
    float:              left;
    display:            block;
    width:              10em;
    max-width:          100%;
    text-align:         right;
    padding-right:      0.5em;
}

#fieldset_add_user_login span.options #select_pred_username,
#fieldset_add_user_login span.options #select_pred_hostname,
#fieldset_add_user_login span.options #select_pred_password {
    width:              100%;
    max-width:          100%;
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

#fieldset_user_priv div.item label {
    white-space: nowrap;
}

#fieldset_user_priv div.item select {
    width: 100%;
}

#fieldset_user_global_rights fieldset {
    float: left;
}
/* END user privileges */


/* serverstatus */
div#serverstatus table caption a.top {
    float: right;
}

div#serverstatus div#serverstatusqueriesdetails table,
div#serverstatus table#serverstatustraffic,
div#serverstatus table#serverstatusconnections {
    float: left;
}

#serverstatussection,
.clearfloat {
    clear: both;
}
div#serverstatussection table {
    width: 100%;
    margin-bottom: 1em;
}
div#serverstatussection table .name {
    width: 18em;
}
div#serverstatussection table .value {
    width: 6em;
}

div#serverstatus table tbody td.descr a,
div#serverstatus table .tblFooters a {
    white-space: nowrap;
}
div#serverstatus div#statuslinks a:before,
div#serverstatus div#sectionlinks a:before,
div#serverstatus table tbody td.descr a:before,
div#serverstatus table .tblFooters a:before {
    content: '[';
}
div#serverstatus div#statuslinks a:after,
div#serverstatus div#sectionlinks a:after,
div#serverstatus table tbody td.descr a:after,
div#serverstatus table .tblFooters a:after {
    content: ']';
}
/* end serverstatus */

/* querywindow */
body#bodyquerywindow {
    margin: 0;
    padding: 0;
    background-image: none;
    background-color: #F5F5F5;
}

div#querywindowcontainer {
    margin: 0;
    padding: 0;
    width: 100%;
}

div#querywindowcontainer fieldset {
    margin-top: 0;
}
/* END querywindow */


/* querybox */

div#sqlquerycontainer {
    float: left;
    width: 69%;
    /* height: 15em; */
}

div#tablefieldscontainer {
    float: right;
    width: 29%;
    /* height: 15em; */
}

div#tablefieldscontainer select {
    width: 100%;
    /* height: 12em; */
}

textarea#sqlquery {
    width: 100%;
    /* height: 100%; */
}

div#queryboxcontainer div#bookmarkoptions {
    margin-top: 0.5em;
}
/* end querybox */

