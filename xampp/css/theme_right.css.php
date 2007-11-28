<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * main css file from theme
 *
 * @version $Id$
 * @package phpMyAdmin-theme
 * @subpackage Arctic_Ocean
 */
    // unplanned execution path
    if (!defined('PMA_MINIMUM_COMMON')) {
        exit();
    }

    // 2007-05-10 (mkkeck)
    //            Added some special fixes
    //            for better behaviors on old IE
    $forIE = false;
    if (defined('PMA_USR_BROWSER_AGENT') && PMA_USR_BROWSER_AGENT == 'IE' && PMA_USR_BROWSER_VER < 7) {
        $forIE = true;
    }

    // 2007-08-24 (mkkeck)
    //            Get the whole http_url for the images
    $ipath = $_SESSION['PMA_Theme']->getImgPath();

    // 2007-08-24 (mkkeck)
    //            Get font-sizes
    $pma_fsize = $_SESSION['PMA_Config']->get('fontsize');
    $pma_fsize = preg_replace("/[^0-9]/", "", $pma_fsize);
    if (!empty($pma_fsize)) {
        $pma_fsize = ($pma_fsize * 0.01);
    } else {
        $pma_fsize = 1;
    }
    if ( isset($GLOBALS['cfg']['FontSize']) && !empty($GLOBALS['cfg']['FontSize']) ) {
        $usr_fsize = preg_replace("/[^0-9]/", "", $GLOBALS['cfg']['FontSize']);
    }
    if (!isset($usr_fsize)) {
        $usr_fsize = 11;
    }
    if ( isset($GLOBALS['cfg']['FontSizePrefix']) && !empty($GLOBALS['cfg']['FontSizePrefix']) ) {
        $funit = strtolower($GLOBALS['cfg']['FontSizePrefix']);
    }
    if (!isset($funit) || ($funit!='px' && $funit != 'pt')) {
        $funit = 'pt';
    }
    $fsize = $usr_fsize;
    if ($pma_fsize) {
        $fsize = number_format( (intval($usr_fsize) * $pma_fsize), 0 );
    }

    // 2007-05-10 (mkkeck)
    //            Get the file name for the css-style
    //    TODO:
    //        replace on /libraries/header_meta_style.inc.php
    //            echo '<link rel="stylesheet" type="text/css" href="'
    //               . (defined('PMA_PATH_TO_BASEDIR') ? PMA_PATH_TO_BASEDIR : './')
    //               . 'css/phpmyadmin.css.php?' . PMA_generate_common_url()
    //               . '&amp;js_frame=' . ( isset($print_view) ? 'print' : 'right')
    //               . '" />';
    //        with the folow lines
    //            echo '<link rel="stylesheet" type="text/css" href="' . (defined('PMA_PATH_TO_BASEDIR') ? PMA_PATH_TO_BASEDIR : './')
    //               . 'css/phpmyadmin.css.php?' . PMA_generate_common_url() . '&amp;'
    //               . 'js_frame=' . (isset($print_view) ? 'print' : 'right')
    //               . ((stristr($_SERVER['PHP_SELF'], 'main.php') || stristr($_SERVER['PHP_SELF'], 'calendar.php')) ? '&amp;type=main' : '')
    //               . (stristr($_SERVER['PHP_SELF'], 'querywindow.php') ? '&amp;type=querywin' : '')
    //               . '" />';

    // default file
    $tmp_css_type = 'browse';
    if (isset($_GET['type'])) {
        if (stristr($_GET['type'], 'main')) {
            // main window
            $tmp_css_type = 'main';
        } else if (stristr($_GET['type'], 'querywin')) {
            // query window
            $tmp_css_type = 'popup';
        } else if (stristr($_GET['type'], 'inline')) {
            // inline popups
            $tmp_css_type = 'inline';
        }
    }
    if ($GLOBALS['cfg']['LightTabs']) {
        $tmp_css_type = '';
    }
?>
/* BASICS */
html, body, td, th {
<?php if (!empty($GLOBALS['cfg']['FontFamily'])) { ?>
    font-family:             <?php echo $GLOBALS['cfg']['FontFamily']; ?>;
<?php } else { ?>
    font-family:             sans-serif;
<?php } ?>
    font-size:               <?php echo $fsize . $funit; ?>;
}
body {
    background:              <?php echo $GLOBALS['cfg']['MainBackground']; ?>;
<?php if ($tmp_css_type != 'inline') { ?>
    background-attachment:   fixed;
    background-image:        url('<?php echo $ipath; ?>logo_right.png');
    background-position:     100% 100%;
    background-repeat:       no-repeat;
<?php } ?>
    color:                   <?php echo $GLOBALS['cfg']['MainColor']; ?>;
<?php if ($tmp_css_type == 'browse') { ?>
    margin:                  55px 5px 5px 5px;
<?php } else if ($tmp_css_type == 'popup') { ?>
    margin:                  25px 5px 5px 5px;
<?php } else if ($tmp_css_type == 'inline') { ?>
    margin:                  0px 0px 0px 0px;
<?php } else { ?>
    margin:                  5px 5px 5px 5px;
<?php } ?>
    padding:                 0px 0px 0px 0px;
}
button, img, input, select { vertical-align:  middle; }
<?php if (!empty($GLOBALS['cfg']['FontFamilyFixed'])) { ?>
textarea, tt, pre, code    { font-family:     <?php echo $GLOBALS['cfg']['FontFamilyFixed']; ?>; }
<?php } ?>

a:link, a:visited, a:active {
    color:                   #bb3902;
    font-weight:             bold;
    text-decoration:         none;
}
a:hover {
    text-decoration:  underline;
    color:            #cc0000;
}
a img         { border:      none;    }
button        { display:     inline;  }
h1, h2, h3    { color: #fb7922; font-weight: bold;    }
h1            { font-size:   <?php echo number_format( ($fsize * 1.75), 0 ) . $funit; ?>; }
h2            { font-size:   <?php echo number_format( ($fsize * 1.50), 0 ) . $funit; ?>; }
h3            { font-size:   <?php echo number_format( ($fsize * 1.25), 0 ) . $funit; ?>; }

img.icon {
    margin-left:             2px;
    margin-right:            2px;
}
img.lightbulb  { cursor:     pointer; }
dfn, dfn:hover { font-style: normal; }
dfn:hover      { cursor:     url('<?php echo $ipath; ?>b_info.png'), default; }
hr {
    color:                   #bb3902;
    background:              #bb3902;
    border:                  1px none #bb3902;
    height:                  1px;
    margin-bottom:           5px;
    margin-top:              5px;
}

/* TABLES */
table caption, table th, table td {
    padding:                 2px 2px 2px 2px;
    vertical-align:          top;
}
table tr.odd th, table tr.odd td, .odd {
    background:              <?php echo $GLOBALS['cfg']['BgOne']; ?>;
}
table tr.even th, table tr.even td, .even {
    background:              <?php echo $GLOBALS['cfg']['BgTwo']; ?>;
}
table tr.odd th, table tr.odd td,
table tr.even th, table tr.even td, .even {
    text-align:              <?php echo $left; ?>;
}
table tr.marked th, table tr.marked td, .marked {
    background:              <?php echo $GLOBALS['cfg']['BrowseMarkerBackground']; ?>;
    color:                   <?php echo $GLOBALS['cfg']['BrowseMarkerColor']; ?>;
}
.odd:hover, .even:hover, .hover {
    background:              <?php echo $GLOBALS['cfg']['BrowsePointerBackground']; ?>;
    color:                   <?php echo $GLOBALS['cfg']['BrowsePointerColor']; ?>;
}
table tr.hover th, table tr.hover td, table tr.odd:hover th, table tr.even:hover th, table tr.odd:hover td, table tr.even:hover td {
    background:              <?php echo $GLOBALS['cfg']['BrowsePointerBackground']; ?>;
    color:                   <?php echo $GLOBALS['cfg']['BrowsePointerColor']; ?>;
}
tr.condition th, tr.condition td, td.condition, th.condition {
    border:                  1px solid <?php echo $GLOBALS['cfg']['BrowseMarkerBackground']; ?>;
}
table [class=value] {
    white-space:             normal;
}
td img.icon, th img.icon { margin: 0px 0px 0px 0px; }
.odd .value, .even .value, .marked .value {
    text-align:              <?php echo $right; ?>;
}
th {
    font-weight:             bold;
    color:                   <?php echo $GLOBALS['cfg']['ThColor']; ?>;
    background:              <?php echo $GLOBALS['cfg']['ThBackground']; ?>;
}
table caption.tblHeaders, th.tblHeaders { background-image: url('<?php echo $ipath; ?>tbl_header.png'); }
thead th                 { background-image: url('<?php echo $ipath; ?>tbl_th.png'); }

/* end TABLES */

/* FORMS */
form {
    display:                 inline;
    margin:                  0px 0px 0px 0px;
    padding:                 0px 0px 0px 0px;
}
fieldset {
    background:              transparent;
    border:                  1px solid #bb3902;
    margin-top:              5px;
    padding:                 5px;

}
fieldset fieldset {
    background:              transparent;
    margin:                  5px;
}
fieldset legend, fieldset fieldset legend {
    background-position:     left top;
    background-repeat:       repeat-x;
    border:                  1px solid #bb3902;
    color:                   <?php echo $GLOBALS['cfg']['BrowsePointerColor']; ?>;
    margin-bottom:           5px;
    padding:                 3px 5px 3px 5px;
}
fieldset legend {
    background-color:        <?php echo $GLOBALS['cfg']['BrowsePointerBackground']; ?>;
    background-image:        url('<?php echo $ipath; ?>tbl_header.png');
    color:                   <?php echo $GLOBALS['cfg']['BrowsePointerColor']; ?>;
}
fieldset fieldset legend {
    background-color:        <?php echo $GLOBALS['cfg']['ThBackground']; ?>;
    background-image:        url('<?php echo $ipath; ?>tbl_th.png');
    color:                   <?php echo $GLOBALS['cfg']['ThColor']; ?>;
}
fieldset legend a:link, fieldset legend a:active, fieldset legend a:visited {
    color:                   #bb3902;
}
fieldset.tblFooters {
    background-color:        <?php echo $GLOBALS['cfg']['ThBackground']; ?>;
    background-image:        url('<?php echo $ipath; ?>tbl_header.png');
    background-position:     left bottom;
    background-repeat:       repeat-x;
    border-bottom:           1px solid #bb3902;
    border-left:             1px solid #bb3902;
    border-right:            1px solid #bb3902;
    border-top:              none;
    clear:                   both;
    color:                   <?php echo $GLOBALS['cfg']['ThColor']; ?>;
    float:                   none;
    margin-top:              0px;
    margin-bottom:           5px;
    text-align:              center;
}

fieldset .formelement {
    float:                   <?php echo $left; ?>;
    margin-<?php echo $right; ?>:               15px;
    /* IE */
    white-space:             nowrap;
}
fieldset div[class=formelement] {
    white-space:             normal;
}
fieldset#exportoptions {
    white-space:             nowrap;
    width:                   25%;
}
button.mult_submit {
    background-color:        transparent;
    border:                  none;
}


.value {
<?php if (! empty($GLOBALS['cfg']['FontFamily'])) { ?>
    font-family:             <?php echo $GLOBALS['cfg']['FontFamily']; ?>;
    white-space:             normal;
<?php } ?>
}
.value .attention { color: #990000; }
.value .allfine   { color: #006600; }



/* PDF */
.pdflayout {
    background-color:        #ffffff;
    border:                  1px solid #000000;
    clip:                    inherit;
    display:                 none;
    overflow:                hidden;
    position:                relative;
}
.pdflayout_table {
    background:              <?php echo $GLOBALS['cfg']['BgOne']; ?>;
    border:                  1px dashed #bb3902;
    clip:                    inherit;
    color:                   #000000;
    cursor:                  move;
    display:                 inline;
    font-size:               <?php echo number_format( ($fsize * 0.7), 0 ) . $funit; ?>;
    overflow:                hidden;
    position:                absolute;
    visibility:              inherit;
    z-index:                 2;
}
/* end PDF */

/* PARSER */
.syntax {
    font-size:               <?php echo number_format( ($fsize * 0.7), 0 ) . $funit; ?>;
}
.syntax_comment {
    padding-left:            5px;
    padding-right:           5px;
}
.syntax_alpha_columnType, .syntax_alpha_columnAttrib, .syntax_alpha_functionName, .syntax_alpha_reservedWord {
    text-transform:          uppercase;
}
.syntax_alpha_reservedWord {
    font-weight:             bold;
}
.syntax_quote {
    white-space:             pre;
}
/* end PARSER */



.selectallarrow {
    margin-<?php echo $right; ?>:           0.3em;
    margin-<?php echo $left; ?>:            0.6em;
}

/* MESSAGE BOXES: warning, error, confirmation */
div.error, div.notice, div.warning, h1.error, h1.notice, h1.warning, p.error, p.notice, p.warning {
    margin:                  5px 0px 5px 0px;
    <?php if ( $GLOBALS['cfg']['ErrorIconic'] ) { ?>
    background-repeat:       no-repeat;
        <?php if ( $GLOBALS['text_dir'] === 'ltr' ) { ?>
    background-position:     5px 5px;
    padding:                 5px 5px 5px 25px;
        <?php } else { ?>
    background-position:     99% 5px;
    padding:                 5px 25px 5px 5px;
        <?php } ?>
    <?php } else { ?>
    padding:                 5px 5px 5px 5px;
    <?php } ?>
    text-align:              <?php echo $left; ?>;
}
div.notice, h1.notice {
    <?php if ( $GLOBALS['cfg']['ErrorIconic'] ) { ?>
    background-image:        url('<?php echo $ipath; ?>s_notice.png');
    <?php } ?>
    border:                  1px solid #ffd700;
}
.notice {
    background-color:        #ffffdd;
    color:                   #000000;
}
.notice h1 {
    border-bottom:           1px solid #ffd700;
    font-weight:             bold;
    margin:                  0px 0px 0px 0px;
    text-align:              <?php echo $left; ?>;
}
div.warning, h1.warning, p.warning {
    <?php if ( $GLOBALS['cfg']['ErrorIconic'] ) { ?>
    background-image:        url('<?php echo $ipath; ?>s_warn.png');
    <?php } ?>
    border:                  1px solid #990000;
    text-align:              <?php echo $left; ?>;
}
.warning {
    background-color:        #fff0f0;
    color:                   #990000;
}
.warning h1 {
    border-bottom:           1px solid #990000;
    font-weight:             bold;
    margin:                  0px 0px 0px 0px;
}
div.error, h1.error {
    <?php if ( $GLOBALS['cfg']['ErrorIconic'] ) { ?>
    background-image:        url('<?php echo $ipath; ?>s_error.png');
    <?php } ?>
    border:                  1px solid #990000;
}
.error h1 {
    border-bottom:           1px solid #990000;
    font-weight:             bold;
    margin:                  0px 0px 0px 0px;
}
.error {
    background-color:        #fff0f0;
    color:                   #990000;
}
fieldset.confirmation {
    border:                  1px solid #990000;
}
fieldset.confirmation legend {
    background-color:        #990000;
    border:                  1px solid #990000;
    color:                   #ffffff;
    font-weight:             bold;
    <?php if ( $GLOBALS['cfg']['ErrorIconic'] ) { ?>
    background-image:        url('<?php echo $ipath; ?>s_really.png');
    background-repeat:       no-repeat;
        <?php if ( $GLOBALS['text_dir'] === 'ltr' ) { ?>
    background-position:     5px 50%;
    padding:                 2px 2px 2px 25px;
        <?php } else { ?>
    background-position:     97% 50%;
    padding:                 2px 25px 2px 2px;
        <?php } ?>
    <?php } ?>
}
.confirmation {
    background-color:        #fff0f0;
}
.confirmation hr {
    background:              #990000;
    border:                  1px none #990000;
    color:                   #990000;
    height:                  1px;
    margin-bottom:           5px;
    margin-top:              5px;
}
/* end MESSAGE BOXES */


.tblcomment {
    color:                   #000099;
    font-size:               70%;
    font-weight:             normal;
}

.tblHeaders, th, caption {
    background:              <?php echo $GLOBALS['cfg']['ThBackground']; ?>;
    color:                   <?php echo $GLOBALS['cfg']['ThColor']; ?>;
    font-weight:             bold;
}

.tblFooters {
    background:              <?php echo $GLOBALS['cfg']['ThBackground']; ?>;
    color:                   <?php echo $GLOBALS['cfg']['ThColor']; ?>;
    font-weight:             normal;
}

.tblHeaders a:link, .tblHeaders a:active, .tblHeaders a:visited,
.tblFooters a:link, .tblFooters a:active, .tblFooters a:visited {
    color:                   #000000;
}

.tblHeaders a:hover, .tblFooters a:hover {
    color:                   #cc0000;
}

/* forbidden, no privilegs */
.noPrivileges {
    color:                   #990000;
    font-weight:             bold;
}

/* disabled text */
.disabled, .disabled a:link, .disabled a:active, .disabled a:visited {
    color:                   #666666;
}
.disabled a:hover {
    color:                   #666666;
    text-decoration:         none;
}

tr.disabled td, td.disabled {
    background-color:        #cccccc;
    color:                   #666666;
}

/**
 * login form
 */
body.loginform h1, body.loginform a.logo {
    display:                 block;
    text-align:              center;
}

body.loginform {
    text-align:              center;
}

body.loginform div.container {
    margin:                  0px auto;
    text-align:              <?php echo $left; ?>;
    width:                   30em;
}

form.login label {
    float:                   <?php echo $left; ?>;
    font-weight:             bolder;
    width:                   10em;
}

/* -- Top-Navi -- */
#serverinfo {
    background-color:        <?php echo $GLOBALS['cfg']['MainBackground']; ?>;
    border-bottom:           1px solid #bb3902;
    font-weight:             bold;
    height:                  16px;
    margin-top:              0px;
    padding:                 5px 5px 5px 5px;
    white-space:             nowrap;
    vertical-align:          middle;
}
#serverinfo .item { white-space:     nowrap;          }
#serverinfo img   { margin:          0px 1px 0px 1px; }
ul#topmenu        { list-style-type: none;            }
ul#topmenu li     { vertical-align:  middle;          }
#topmenu img {
    margin-<?php echo $right; ?>: 2px;
    vertical-align:          middle;
}
.tab, .tabcaution, .tabactive {
    display:                 block;
    margin:                  0px 0px 0px 0px;
    padding:                 4px 2px 4px 2px;
    white-space:             nowrap;
}
span.tab, span.tabcaution { color: #666666; }
a.tabcaution:link, a.tabcaution:active, a.tabcaution:visited { color: #ffffff; }
a.tabcaution:hover {
    color:                   #ffffff;
    background-color:        #cc0000;
}
<?php if ( $GLOBALS['cfg']['LightTabs'] ) { ?>
a.tabactive:link, a.tabactive:active, a.tabactive:visited { color: #bb3902; }
<?php } else { ?>
#serverinfo, #topmenucontainer {
<?php if ($forIE) { ?>
    position:                absolute;
<?php } else { ?>
    position:                fixed;
    width:                   100%;
<?php } ?>
}
#serverinfo {
<?php if ($forIE) { ?>
    left:                    0px;
    top:                     expression(eval(document.documentElement.scrollTop));
    width:                   100%;
<?php } else { ?>
    top:                     0px;
    left:                    0px;
<?php } ?>
}
#serverinfo .separator img {
    width:                   9px;
    height:                  11px;
    margin:                  0px 2px 0px 2px;
    vertical-align:          middle;
}
#topmenucontainer {
    background-color:        <?php echo $GLOBALS['cfg']['MainBackground']; ?>;
    background-image:        url('<?php echo $ipath; ?>tbl_header.png');
    background-repeat:       repeat-x;
    background-position:     center top;
    border-top:              <?php echo (stristr($_GET['type'], 'querywin') ? '1px' : '5px'); ?> solid <?php echo $GLOBALS['cfg']['MainBackground']; ?>;
    border-right:            none;
    border-bottom:           5px solid <?php echo $GLOBALS['cfg']['MainBackground']; ?>;
    border-left:             none;
    color:                   #000000;
    font-weight:             bold;
    margin:                  0px 0px 0px 0px;
    padding:                 0px 0px 0px 0px;
    white-space:             nowrap;
<?php if ($forIE) { ?>
    left:                    0px;
    top:                     expression(eval(document.documentElement.scrollTop<?php echo (stristr($_GET['type'], 'querywin') ? '' : '+27'); ?>));
    width:                   expression(eval(document.documentElement.clientWidth));
<?php } else { ?>
    top:                     <?php echo (stristr($_GET['type'], 'querywin') ? '0px' : '27px'); ?>;
    left:                    0px;
<?php } ?>
}
#topmenu {
    border:                  none;
    float:                   <?php echo $left; ?>;
    margin:                  0px 0px 0px 0px;
    padding:                 0px 0px 0px 0px;
}
ul#topmenu li {
    background-color:        #333333;
    background-image:        url('<?php echo $ipath; ?>tbl_header.png');
    background-repeat:       repeat-x;
    background-position:     center top;
    border-bottom:           none;
    border-right:            1px solid <?php echo $GLOBALS['cfg']['MainBackground']; ?>;
    margin:                  0px 0px 0px 0px;
    padding-right:           0px;
}
.tab, .tabcaution, .tabactive {
    background-color:        <?php echo $GLOBALS['cfg']['BgOne']; ?>;
    background-repeat:       repeat-x;
    background-position:     center top;
    border:                  none;
}
.tab, .tabactive, .tabcaution, a.tab:hover, a.tabactive:hover, a.tabcaution:hover {
    border-top:              1px solid <?php echo $GLOBALS['cfg']['MainBackground']; ?>;
    margin:                  0px 0px 0px 0px;
    padding:                 4px 2px 4px 2px;
    text-decoration:         none;
}
.tab, a.tab:link, a.tab:active, a.tab:visited {
    color:                   #000000;
    background-color:        <?php echo $GLOBALS['cfg']['BgTwo']; ?>;
    background-image:        url('<?php echo $ipath; ?>tbl_header.png');
}
a.tab:hover {
    border-top:              1px solid #fb7922;
    border-right:            1px solid #fb7922;
    border-left:             1px solid #fb7922;
    color:                   #000000;
    background-color:        #ffff99;
    background-image:        url('<?php echo $ipath; ?>tbl_th.png');
}
.tabcaution, a.tabcaution:link, a.tabcaution:active, a.tabcaution:visited {
    color:                   #ffffff;
    background-color:        #cc0000;
    background-image:        url('<?php echo $ipath; ?>tbl_error.png');
}
a.tabcaution:hover {
    border-top:              1px solid #fb7922;
    border-right:            1px solid #fb7922;
    border-left:             1px solid #fb7922;
    color:                   #ffff99;
    background-color:        #cc0000;
    background-image:        url('<?php echo $ipath; ?>tbl_error.png');
}
a.tabactive:link, a.tabactive:active, a.tabactive:visited, a.tabactive:hover {
    color:                   #000000;
    border-top:              1px solid #fb7922;
    border-right:            1px solid #fb7922;
    border-left:             1px solid #fb7922;
    background-color:        <?php echo $GLOBALS['cfg']['MainBackground']; ?>;
    /*background-image:        url('<?php echo $ipath; ?>tbl_th.png');*/
}
span.tab, span.tabcaution { cursor:  url('<?php echo $ipath; ?>s_error.png'), default; }
span.tab img, span.tabcaution img {
<?php if ($forIE) { ?>
    filter:                  progid:DXImageTransform.Microsoft.Alpha(opacity=50);
<?php } else { ?>
    -moz-opacity:            0.5;
<?php } ?>
    opacity:                 0.5;
}
<?php } ?>
/* -- Top-Navi -- */


/* CALENDAR */
table.calendar {
    width:                   100%;
}
table.calendar td {
    color:                   <?php echo $GLOBALS['cfg']['MainColor']; ?>;
    background-color:        <?php echo $GLOBALS['cfg']['BgOne']; ?>;
    text-align:              center;
}
table.calendar td a {
    display:                 block;
}

table.calendar td a:hover {
    color:                   <?php echo $GLOBALS['cfg']['BrowsePointerColor']; ?>;
    background-color:        <?php echo $GLOBALS['cfg']['BrowsePointerBackground']; ?>;
}

table.calendar th {
    color:                   <?php echo $GLOBALS['cfg']['ThColor']; ?>;
    background-color:        <?php echo $GLOBALS['cfg']['ThBackground']; ?>;
}

table.calendar td.selected {
    color:                   <?php echo $GLOBALS['cfg']['BrowseMarkerColor']; ?>;
    background-color:        <?php echo $GLOBALS['cfg']['BrowseMarkerBackground']; ?>;
}
img.calendar {
    border:                  none;
}
#clock_data, form.clock {
    text-align:              center;
}
#clock_data input, form.clock  input {
    text-align:              center;
    width:                   50px;
}
/* end CALENDAR */


/* table stats */
div#tablestatistics {
    border-bottom:           0.1em solid #669999;
    margin-bottom:           0.5em;
    padding-bottom:          0.5em;
}

div#tablestatistics table {
    float:                   <?php echo $left; ?>;
    margin-bottom:           0.5em;
    margin-<?php echo $right; ?>: 0.5em;
}

div#tablestatistics table caption {
    margin-<?php echo $right; ?>: 0.5em;
}
/* END table stats */


/* server privileges */
#tableuserrights td,
#tablespecificuserrights td,
#tabledatabases td {
    vertical-align:          middle;
}
/* END server privileges */



#span_table_comment {
    font-weight:             normal;
    font-style:              italic;
    white-space:             nowrap;
}

#TooltipContainer {
    font-size:               inherit;
    color:                   #ffffff;
    background-color:        #bb3902;
    position:                absolute;
    z-index:                 99;
    width:                   <?php echo number_format( ($fsize * 25), 0 ) . $funit; ?>;
    height:                  auto;
    overflow:                auto;
    visibility:              hidden;
    border:                  1px solid #333333;
    padding:                 0.5em;
<?php if ($forIE) { ?>
    filter:                  progid:DXImageTransform.Microsoft.Alpha(opacity=95);
<?php } else { ?>
    -moz-opacity:            0.95;
<?php } ?>
    opacity:                 0.95;
}

/* user privileges */
#fieldset_add_user_login div.item {
    border-bottom:           1px solid #bb3902;
    padding-bottom:          0.3em;
    margin-bottom:           0.3em;
}

#fieldset_add_user_login label {
    float:                   <?php echo $left; ?>;
    display:                 block;
    width:                   10em;
    max-width:               100%;
    text-align:              <?php echo $right; ?>;
    padding-<?php echo $right; ?>:      0.5em;
}

#fieldset_add_user_login span.options #select_pred_username,
#fieldset_add_user_login span.options #select_pred_hostname,
#fieldset_add_user_login span.options #select_pred_password {
    width:                   100%;
    max-width:               100%;
}

#fieldset_add_user_login span.options {
    float:                   <?php echo $left; ?>;
    display:                 block;
    width:                   12em;
    max-width:               100%;
    padding-<?php echo $right; ?>: 0.5em;
}

#fieldset_add_user_login input {
    width:                   12em;
    clear:                   <?php echo $right; ?>;
    max-width:               100%;
}

#fieldset_add_user_login span.options input {
    width:                   auto;
}

#fieldset_user_priv div.item {
    float:                   <?php echo $left; ?>;
    width:                   9em;
    max-width:               100%;
}

#fieldset_user_priv div.item div.item {
    float:                   none;
}

#fieldset_user_priv div.item label {
    white-space:             nowrap;
}

#fieldset_user_priv div.item select {
    width:                   100%;
}

#fieldset_user_global_rights fieldset {
    float: <?php echo $left; ?>;
}
/* END user privileges */


/* serverstatus */
div#serverstatus table caption a.top {
    float: <?php echo $right; ?>;
}

div#serverstatus div#serverstatusqueriesdetails table,
div#serverstatus table#serverstatustraffic,
div#serverstatus table#serverstatusconnections {
    float: <?php echo $left; ?>;
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
    content: '';
}
div#serverstatus div#statuslinks a:after,
div#serverstatus div#sectionlinks a:after,
div#serverstatus table tbody td.descr a:after,
div#serverstatus table .tblFooters a:after {
    content: '';
}

/* end serverstatus */

/* querywindow */
body#bodyquerywindow {
    margin: 30px 2px 2px 2px;
    padding: 0;
    background-image: none;
    background-color: transparent;
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
    float: <?php echo $left; ?>;
    width: 69%;
    /* height: 15em; */
}

div#tablefieldscontainer {
    float: <?php echo $right; ?>;
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

/* main page */
#maincontainer {
    background-image: none;
    background-position: <?php echo $right; ?> bottom;
    background-repeat: no-repeat;
    border-bottom: none;
}

#mysqlmaininformation,
#pmamaininformation {
    float: <?php echo $left; ?>;
    width: 49%;
}

#maincontainer ul {
    list-style-image: url('<?php echo $ipath; ?>item_<?php echo $GLOBALS['text_dir']; ?>.png');
    vertical-align: middle;
}

#maincontainer li {
    margin-bottom:  3px;
    padding-left:   5px;
}
/* END main page */


<?php if ($GLOBALS['cfg']['MainPageIconic']) { ?>
/* iconic view for ul items */
li#li_create_database        { list-style-image: url('<?php echo $ipath; ?>b_newdb.png');        }
li#li_select_lang            { list-style-image: url('<?php echo $ipath; ?>s_lang.png');         }
li#li_select_mysql_collation { list-style-image: url('<?php echo $ipath; ?>s_asci.png');         }
li#li_select_mysql_charset   { list-style-image: url('<?php echo $ipath; ?>s_asci.png');         }
li#li_select_theme           { list-style-image: url('<?php echo $ipath; ?>s_theme.png');        }
li#li_server_info            { list-style-image: url('<?php echo $ipath; ?>s_host.png');         }
li#li_user_info              { list-style-image: url('<?php echo $ipath; ?>b_dbusr.png');        }
li#li_mysql_status           { list-style-image: url('<?php echo $ipath; ?>s_status.png');       }
li#li_mysql_variables        { list-style-image: url('<?php echo $ipath; ?>s_vars.png');         }
li#li_mysql_processes        { list-style-image: url('<?php echo $ipath; ?>s_process.png');      }
li#li_mysql_collations       { list-style-image: url('<?php echo $ipath; ?>s_asci.png');         }
li#li_mysql_engines          { list-style-image: url('<?php echo $ipath; ?>b_engine.png');       }
li#li_mysql_binlogs          { list-style-image: url('<?php echo $ipath; ?>s_tbl.png');          }
li#li_mysql_databases        { list-style-image: url('<?php echo $ipath; ?>s_db.png');           }
li#li_export                 { list-style-image: url('<?php echo $ipath; ?>b_export.png');       }
li#li_import                 { list-style-image: url('<?php echo $ipath; ?>b_import.png');       }
li#li_change_password        { list-style-image: url('<?php echo $ipath; ?>s_passwd.png');       }
li#li_log_out                { list-style-image: url('<?php echo $ipath; ?>s_loggoff.png');      }
li#li_pma_docs               { list-style-image: url('<?php echo $ipath; ?>b_docs.png');         }
li#li_phpinfo                { list-style-image: url('<?php echo $ipath; ?>php_sym.png');        }
li#li_pma_homepage           { list-style-image: url('<?php echo $ipath; ?>b_home.png');         }
li#li_mysql_privilegs        { list-style-image: url('<?php echo $ipath; ?>s_rights.png');       }
li#li_switch_dbstats         { list-style-image: url('<?php echo $ipath; ?>b_dbstatistics.png'); }
li#li_flush_privileges       { list-style-image: url('<?php echo $ipath; ?>s_reload.png');       }
li#li_mysql_proto            { list-style-image: url('<?php echo $ipath; ?>b_dbsock.png');       }
li#li_mysql_client_version   { list-style-image: url('<?php echo $ipath; ?>b_dbclient.png');     }
li#li_select_fontsize        { list-style-image: url('<?php echo $ipath; ?>b_fontsize.png');     }
li#li_used_php_extension     { list-style-image: url('<?php echo $ipath; ?>b_dbphpext.png');     }
/* END iconic view for ul items */
<?php } /* end if $GLOBALS['cfg']['MainPageIconic'] */ ?>


#body_browse_foreigners {
    background:         <?php echo $GLOBALS['cfg']['MainBackground']; ?>;
    margin:             <?php echo (($tmp_css_type == 'inline') ? 6 : 4); ?>em 0.5em 0 0.5em;
    text-align:         center;
}
#body_browse_foreigners form {
    left:               0px;
    background-color:   <?php echo $GLOBALS['cfg']['MainBackground']; ?>;
    margin:             0 0 0 0;
    padding:            0 0 0 0;
<?php if ($forIE) { ?>
    position:           absolute;
    top:                expression(eval(document.documentElement.scrollTop));
<?php } else { ?>
    position:           fixed;
    top:                0px;
<?php } ?>
    width:              100%;
}
#body_browse_foreigners, #body_browse_foreigners th, #body_browse_foreigners td  {
    font-size:          <?php echo number_format($fsize * 0.9) . $funit; ?>;
    text-align:         <?php echo $left; ?>;
}
#body_browse_foreigners td a {
    display: block; width: 100%;
}
#body_browse_foreigners tfoot th {
    background-color:        <?php echo $GLOBALS['cfg']['ThBackground']; ?>;
    background-image:        url('<?php echo $ipath; ?>tbl_th.png');
    background-position:     left bottom;
}
#body_browse_foreigners .formelement {
    float: none; clear: both;
}
#body_browse_foreigners fieldset { text-align: center; padding: 0.1em 0.1em 0.1em 0.1em; margin: 0.1em 0.1em 0.1em 0.1em; }

#bodyquerywindow {
    background:         <?php echo $GLOBALS['cfg']['MainBackground']; ?>;
}

#bodythemes {
    width: 500px;
    margin: auto;
    text-align: center;
}

#bodythemes img {
    border: 0.1em solid #bb3902;
}

#bodythemes a:hover img {
    border: 0.1em solid #bb3902;
}

#fieldset_select_fields {
    float: <?php echo $left; ?>;
}

#selflink {
    clear: both;
    display: block;
    margin-top: 1em;
    margin-bottom: 1em;
    width: 100%;
    border-top: 0.1em none #bb3902;
    text-align: <?php echo $left; ?>;
}

#table_innodb_bufferpool_usage,
#table_innodb_bufferpool_activity {
    float: <?php echo $left; ?>;
}

#div_mysql_charset_collations table {
    float: <?php echo $left; ?>;
}

#div_table_order, #div_table_rename, #div_table_copy, #div_table_options {
    clear: both;
    float: none;
    min-width: 48%;
}

#qbe_div_table_list {
    float: <?php echo $left; ?>;
}

#qbe_div_sql_query {
    float: <?php echo $left; ?>;
}

label.desc {
    width: 30em;
    float: <?php echo $left; ?>;
}

#buttonGo, #buttonNo, #buttonYes, #cancel, #submit { font-weight: bold; }
#buttonGo     { color: #bb3902; }
#buttonNo, #cancel     { color: #cc0000; }
#buttonYes, #submit    { color: #006600; }
#listTable    { width: 260px;}
#textSqlquery { width: 450px; }
#textSQLDUMP {
    background-color:     transparent;
    border:               1px solid #bb3902;
    color:                #000000;
<?php if (!empty($GLOBALS['cfg']['FontFamilyFixed'])) { ?>
    font-family:           <?php echo $GLOBALS['cfg']['FontFamilyFixed']; ?>;
<?php } ?>
    font-size:            110%;
    width:                99%;
    height:               99%;
}