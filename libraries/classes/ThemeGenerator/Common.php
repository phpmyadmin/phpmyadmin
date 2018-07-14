<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Creates common.css.php file
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin\ThemeGenerator;

/**
 * Function to create Common.inc.php in phpMyAdmin
 *
 * @package PhpMyAdmin
 */
class Common
{
    /**
     * Creates navigation.css.php
     *
     * @param string $name name of new theme
     *
     * @return null
     */
    public function createCommonFile($name)
    {
        $file = fopen("themes/" . $name . "/css/common.css.php", "w");
        $txt = "<?php\n";
        $txt .= 'declare(strict_types=1);';
        $txt .= 'if (! defined(\'PHPMYADMIN\') && ! defined(\'TESTSUITE\')) {';
        $txt .= '    exit();';
        $txt .= '}';
        $txt .= '?>';

        /******************************************************************************/
        /* general tags */
        $txt .= 'html {';
        $txt .= '    font-size: 82%;';
        $txt .= '}';

        $txt .= 'input,';
        $txt .= 'select,';
        $txt .= 'textarea {';
        $txt .= '    font-size: 1em;';
        $txt .= '}';

        $txt .= 'body {';
        $txt .= '<?php if (! empty($GLOBALS[\'cfg\'][\'FontFamily\'])) : ?>';
        $txt .= '    font-family: <?php echo $GLOBALS[\'cfg\'][\'FontFamily\']; ?>;';
        $txt .= '<?php endif; ?>';
        $txt .= '    padding: 0;';
        $txt .= '    margin: 0;';
        $txt .= '    margin-<?php echo $left; ?>: 240px;';
        $txt .= '    color: <?php echo $GLOBALS[\'cfg\'][\'MainColor\']; ?>;';
        $txt .= '    background: <?php echo $GLOBALS[\'cfg\'][\'MainBackground\']; ?>;';
        $txt .= '}';

        $txt .= 'body#loginform {';
        $txt .= '    margin: 0;';
        $txt .= '}';

        $txt .= '#page_content {';
        $txt .= '    margin: 0 .5em;';
        $txt .= '}';

        $txt .= '.desktop50 {';
        $txt .= '    width: 50%;';
        $txt .= '}';

        $txt .= '.all100 {';
        $txt .= '    width: 100%;';
        $txt .= '}';

        $txt .= '.all85{';
        $txt .= '    width: 85%;';
        $txt .= '}';

        $txt .= '.auth_config_tbl{';
        $txt .= '    margin: 0 auto;';
        $txt .= '}';

        $txt .= '<?php if (! empty($GLOBALS[\'cfg\'][\'FontFamilyFixed\'])) : ?>';
        $txt .= '    textarea,';
        $txt .= '    tt,';
        $txt .= '    pre,';
        $txt .= '    code {';
        $txt .= '    font-family: <?php echo $GLOBALS[\'cfg\'][\'FontFamilyFixed\']; ?>;';
        $txt .= '    }';
        $txt .= '<?php endif; ?>';

        $txt .= 'h1 {';
        $txt .= '    font-size: 140%;';
        $txt .= '    font-weight: bold;';
        $txt .= '}';

        $txt .= 'h2 {';
        $txt .= '    font-size: 2em;';
        $txt .= '    font-weight: normal;';
        $txt .= '    text-shadow: 0 1px 0 #fff;';
        $txt .= '    padding: 10px 0 10px;';
        $txt .= '    padding-<?php echo $left; ?>: 3px;';
        $txt .= '    color: #777;';
        $txt .= '}';

        /* Hiding icons in the page titles */
        $txt .= 'h2 img {';
        $txt .= '    display: none;';
        $txt .= '}';

        $txt .= 'h2 a img {';
        $txt .= '    display: inline;';
        $txt .= '}';

        $txt .= '.data,';
        $txt .= '.data_full_width {';
        $txt .= '    margin: 0 0 12px;';
        $txt .= '}';

        $txt .= '.data_full_width {';
        $txt .= '    width: 100%;';
        $txt .= '}';

        $txt .= 'h3 {';
        $txt .= '    font-weight: bold;';
        $txt .= '}';

        $txt .= 'a,';
        $txt .= 'a:link,';
        $txt .= 'a:visited,';
        $txt .= 'a:active,';
        $txt .= 'button.mult_submit,';
        $txt .= '.checkall_box+label {';
        $txt .= '    text-decoration: none;';
        $txt .= '    color: <?php echo $GLOBALS[\'cfg\'][\'Hyperlink\']; ?>;';
        $txt .= '    cursor: pointer;';
        $txt .= '    outline: none;';
        $txt .= '}';

        $txt .= 'a:hover,';
        $txt .= 'button.mult_submit:hover,';
        $txt .= 'button.mult_submit:focus,';
        $txt .= '.checkall_box+label:hover {';
        $txt .= '    text-decoration: underline;';
        $txt .= '    color: #235a81;';
        $txt .= '}';

        $txt .= '#initials_table {';
        $txt .= '    background: <?php echo $GLOBALS[\'cfg\'][\'GroupBg\']; ?>;';
        $txt .= '    border: 1px solid #aaa;';
        $txt .= '    margin-bottom: 10px;';
        $txt .= '    -moz-border-radius: 5px;';
        $txt .= '    -webkit-border-radius: 5px;';
        $txt .= '    border-radius: 5px;';
        $txt .= '}';

        $txt .= '#initials_table td {';
        $txt .= '    padding: 8px !important;';
        $txt .= '}';

        $txt .= '#initials_table a {';
        $txt .= '    border: 1px solid #aaa;';
        $txt .= '    background: #fff;';
        $txt .= '    padding: 4px 8px;';
        $txt .= '    -moz-border-radius: 5px;';
        $txt .= '    -webkit-border-radius: 5px;';
        $txt .= '    border-radius: 5px;';
        $txt .= '    <?php echo $theme->getCssGradient(\'ffffff\', \'e0e0e0\'); ?>';
        $txt .= '}';

        $txt .= '#initials_table a.active {';
        $txt .= '    border: 1px solid #666;';
        $txt .= '    box-shadow: 0 0 2px #999;';
        $txt .= '    <?php echo $theme->getCssGradient(\'bbbbbb\', \'ffffff\'); ?>';
        $txt .= '}';

        $txt .= 'dfn {';
        $txt .= '    font-style: normal;';
        $txt .= '}';

        $txt .= 'dfn:hover {';
        $txt .= '    font-style: normal;';
        $txt .= '    cursor: help;';
        $txt .= '}';

        $txt .= 'th {';
        $txt .= '    font-weight: bold;';
        $txt .= '    color: <?php echo $GLOBALS[\'cfg\'][\'ThColor\']; ?>;';
        $txt .= '    background: <?php echo $GLOBALS[\'cfg\'][\'ThBackground\']; ?>;';
        $txt .= '}';

        $txt .= 'a img {';
        $txt .= '    border: 0;';
        $txt .= '}';

        $txt .= 'hr {';
        $txt .= '    color: <?php echo $GLOBALS[\'cfg\'][\'MainColor\']; ?>;';
        $txt .= '    background-color: <?php echo $GLOBALS[\'cfg\'][\'MainColor\']; ?>;';
        $txt .= '    border: 0;';
        $txt .= '    height: 1px;';
        $txt .= '}';

        $txt .= 'form {';
        $txt .= '    padding: 0;';
        $txt .= '    margin: 0;';
        $txt .= '    display: inline;';
        $txt .= '}';

        $txt .= 'input,';
        $txt .= 'select {';
        $txt .= '    outline: none;';
        $txt .= '}';

        $txt .= 'input[type=text],';
        $txt .= 'input[type=password],';
        $txt .= 'input[type=number],';
        $txt .= 'input[type=date] {';
        $txt .= '    border-radius: 2px;';
        $txt .= '    -moz-border-radius: 2px;';
        $txt .= '    -webkit-border-radius: 2px;';

        $txt .= '    background: white;';
        $txt .= '    border: 1px solid #aaa;';
        $txt .= '    color: #555;';
        $txt .= '    padding: 4px;';
        $txt .= '}';

        $txt .= 'input[type=text],';
        $txt .= 'input[type=password],';
        $txt .= 'input[type=number],';
        $txt .= 'input[type=date],';
        $txt .= 'input[type=checkbox],';
        $txt .= 'select {';
        $txt .= '    margin: 6px;';
        $txt .= '}';

        $txt .= 'input[type=number] {';
        $txt .= '    width: 50px;';
        $txt .= '}';

        $txt .= 'input[type=text],';
        $txt .= 'input[type=password],';
        $txt .= 'input[type=number],';
        $txt .= 'input[type=date],';
        $txt .= 'select {';
        $txt .= '    transition: all 0.2s;';
        $txt .= '    -ms-transition: all 0.2s;';
        $txt .= '    -webkit-transition: all 0.2s;';
        $txt .= '    -moz-transition: all 0.2s;';
        $txt .= '}';

        $txt .= 'input[type=text][disabled],';
        $txt .= 'input[type=text][disabled]:hover,';
        $txt .= 'input[type=password][disabled],';
        $txt .= 'input[type=password][disabled]:hover,';
        $txt .= 'input[type=number][disabled],';
        $txt .= 'input[type=number][disabled]:hover,';
        $txt .= 'input[type=date][disabled],';
        $txt .= 'input[type=date][disabled]:hover,';
        $txt .= 'select[disabled],';
        $txt .= 'select[disabled]:hover {';
        $txt .= '    background: #e8e8e8;';
        $txt .= '    box-shadow: none;';
        $txt .= '    -webkit-box-shadow: none;';
        $txt .= '    -moz-box-shadow: none;';
        $txt .= '}';

        $txt .= 'input[type=text]:hover,';
        $txt .= 'input[type=text]:focus,';
        $txt .= 'input[type=password]:hover,';
        $txt .= 'input[type=password]:focus,';
        $txt .= 'input[type=number]:hover,';
        $txt .= 'input[type=number]:focus,';
        $txt .= 'input[type=date]:hover,';
        $txt .= 'input[type=date]:focus,';
        $txt .= 'select:focus {';
        $txt .= '    border: 1px solid #7c7c7c;';
        $txt .= '    background: #fff;';
        $txt .= '}';

        $txt .= 'input[type=text]:hover,';
        $txt .= 'input[type=password]:hover,';
        $txt .= 'input[type=number]:hover,';
        $txt .= 'input[type=date]:hover {';
        $txt .= '    box-shadow: 0 1px 3px #aaa;';
        $txt .= '    -webkit-box-shadow: 0 1px 3px #aaa;';
        $txt .= '    -moz-box-shadow: 0 1px 3px #aaa;';
        $txt .= '}';

        $txt .= 'input[type=submit],';
        $txt .= 'input[type=button],';
        $txt .= 'button[type=submit]:not(.mult_submit) {';
        $txt .= '    font-weight: bold !important;';
        $txt .= '}';

        $txt .= 'input[type=submit],';
        $txt .= 'input[type=button],';
        $txt .= 'button[type=submit]:not(.mult_submit),';
        $txt .= 'input[type=reset],';
        $txt .= 'input[name=submit_reset],';
        $txt .= 'input.button {';
        $txt .= '    margin: 6px 14px;';
        $txt .= '    border: 1px solid #aaa;';
        $txt .= '    padding: 3px 7px;';
        $txt .= '    color: #111;';
        $txt .= '    text-decoration: none;';
        $txt .= '    background: #ddd;';

        $txt .= '    border-radius: 12px;';
        $txt .= '    -webkit-border-radius: 12px;';
        $txt .= '    -moz-border-radius: 12px;';

        $txt .= '    text-shadow: 0 1px 0 #fff;';

        $txt .= '    <?php echo $theme->getCssGradient(\'f8f8f8\', \'d8d8d8\'); ?>';
        $txt .= '}';

        $txt .= 'input[type=submit]:hover,';
        $txt .= 'input[type=button]:hover,';
        $txt .= 'button[type=submit]:not(.mult_submit):hover,';
        $txt .= 'input[type=reset]:hover,';
        $txt .= 'input[name=submit_reset]:hover,';
        $txt .= 'input.button:hover {';
        $txt .= '    position: relative;';
        $txt .= '    <?php echo $theme->getCssGradient(\'fff\', \'ddd\'); ?>';
        $txt .= '    cursor: pointer;';
        $txt .= '}';

        $txt .= 'input[type=submit]:active,';
        $txt .= 'input[type=button]:active,';
        $txt .= 'button[type=submit]:not(.mult_submit):active,';
        $txt .= 'input[type=reset]:active,';
        $txt .= 'input[name=submit_reset]:active,';
        $txt .= 'input.button:active {';
        $txt .= '    position: relative;';
        $txt .= '    <?php echo $theme->getCssGradient(\'eee\', \'ddd\'); ?>';
        $txt .= '    box-shadow: 0 1px 6px -2px #333 inset;';
        $txt .= '    text-shadow: none;';
        $txt .= '}';

        $txt .= 'input[type=submit]:disabled,';
        $txt .= 'input[type=button]:disabled,';
        $txt .= 'button[type=submit]:not(.mult_submit):disabled,';
        $txt .= 'input[type=reset]:disabled,';
        $txt .= 'input[name=submit_reset]:disabled,';
        $txt .= 'input.button:disabled {';
        $txt .= '    background: #ccc;';
        $txt .= '    color: #666;';
        $txt .= '    text-shadow: none;';
        $txt .= '}';

        $txt .= 'textarea {';
        $txt .= '    overflow: visible;';
        $txt .= '    margin: 6px;';
        $txt .= '}';

        $txt .= 'textarea.char {';
        $txt .= '    margin: 6px;';
        $txt .= '}';

        $txt .= 'fieldset, .preview_sql {';
        $txt .= '    margin-top: 1em;';
        $txt .= '    border-radius: 4px 4px 0 0;';
        $txt .= '    -moz-border-radius: 4px 4px 0 0;';
        $txt .= '    -webkit-border-radius: 4px 4px 0 0;';
        $txt .= '    border: #aaa solid 1px;';
        $txt .= '    padding: 0.5em;';
        $txt .= '    background: #eee;';
        $txt .= '    text-shadow: <?php echo $GLOBALS[\'text_dir\'] === \'rtl\' ? \'-\' : \'\'; ?>1px 1px 2px #fff inset;';
        $txt .= '    -moz-box-shadow: <?php echo $GLOBALS[\'text_dir\'] === \'rtl\' ? \'-\' : \'\'; ?>1px 1px 2px #fff inset;';
        $txt .= '    -webkit-box-shadow: <?php echo $GLOBALS[\'text_dir\'] === \'rtl\' ? \'-\' : \'\'; ?>1px 1px 2px #fff inset;';
        $txt .= '    box-shadow: <?php echo $GLOBALS[\'text_dir\'] === \'rtl\' ? \'-\' : \'\'; ?>1px 1px 2px #fff inset;';
        $txt .= '}';

        $txt .= 'fieldset fieldset {';
        $txt .= '    margin: .8em;';
        $txt .= '    background: #fff;';
        $txt .= '    border: 1px solid #aaa;';
        $txt .= '    background: #E8E8E8;';

        $txt .= '}';

        $txt .= 'fieldset legend {';
        $txt .= '    font-weight: bold;';
        $txt .= '    color: #444;';
        $txt .= '    padding: 5px 10px;';
        $txt .= '    border-radius: 2px;';
        $txt .= '    -moz-border-radius: 2px;';
        $txt .= '    -webkit-border-radius: 2px;';
        $txt .= '    border: 1px solid #aaa;';
        $txt .= '    background-color: #fff;';
        $txt .= '    -moz-box-shadow: <?php echo $GLOBALS[\'text_dir\'] === \'rtl\' ? \'-\' : \'\'; ?>3px 3px 15px #bbb;';
        $txt .= '    -webkit-box-shadow: <?php echo $GLOBALS[\'text_dir\'] === \'rtl\' ? \'-\' : \'\'; ?>3px 3px 15px #bbb;';
        $txt .= '    box-shadow: <?php echo $GLOBALS[\'text_dir\'] === \'rtl\' ? \'-\' : \'\'; ?>3px 3px 15px #bbb;';
        $txt .= '    max-width: 100%;';
        $txt .= '}';

        $txt .= '.some-margin {';
        $txt .= '    margin: 1.5em;';
        $txt .= '}';

        /* buttons in some browsers (eg. Konqueror) are block elements, this breaks design */
        $txt .= 'button {';
        $txt .= '    display: inline;';
        $txt .= '}';

        $txt .= 'table caption,';
        $txt .= 'table th,';
        $txt .= 'table td {';
        $txt .= '    padding: .1em .3em;';
        $txt .= '    margin: .1em;';
        $txt .= '    vertical-align: middle;';
        // $txt .= '    text-shadow: 0 1px 0 #fff;';
        $txt .= '}';

        /* 3.4 */
        $txt .= '.datatable{';
        $txt .= '    table-layout: fixed;';
        $txt .= '}';

        $txt .= 'table {';
        $txt .= '    border-collapse: collapse;';
        $txt .= '}';

        $txt .= 'thead th {';
        $txt .= '    border-right: 1px solid #fff;';
        $txt .= '}';

        $txt .= 'th {';
        $txt .= '    text-align: left;';
        $txt .= '}';

        $txt .= 'img,';
        $txt .= 'button {';
        $txt .= '    vertical-align: middle;';
        $txt .= '}';

        $txt .= 'input[type="checkbox"],';
        $txt .= 'input[type="radio"] {';
        $txt .= '    vertical-align: -11%;';
        $txt .= '}';

        $txt .= 'select {';
        $txt .= '    -moz-border-radius: 2px;';
        $txt .= '    -webkit-border-radius: 2px;';
        $txt .= '    border-radius: 2px;';

        $txt .= '    border: 1px solid #bbb;';
        $txt .= '    color: #333;';
        $txt .= '    padding: 3px;';
        $txt .= '    background: white;';
        $txt .= '    margin:6px;';
        $txt .= '}';

        $txt .= 'select[multiple] {';
        $txt .= '    <?php echo $theme->getCssGradient(\'ffffff\', \'f2f2f2\'); ?>';
        $txt .= '}';

        /******************************************************************************/
        /* classes */
        $txt .= '.clearfloat {';
        $txt .= '    clear: both;';
        $txt .= '}';

        $txt .= '.floatleft {';
        $txt .= '    float: <?php echo $left; ?>;';
        $txt .= '    margin-<?php echo $right; ?>: 1em;';
        $txt .= '}';

        $txt .= '.floatright {';
        $txt .= '    float: <?php echo $right; ?>;';
        $txt .= '}';

        $txt .= '.center {';
        $txt .= '    text-align: center;';
        $txt .= '}';

        $txt .= '.displayblock {';
        $txt .= '    display: block;';
        $txt .= '}';

        $txt .= 'table.nospacing {';
        $txt .= '    border-spacing: 0;';
        $txt .= '}';

        $txt .= 'table.nopadding tr th, table.nopadding tr td {';
        $txt .= '    padding: 0;';
        $txt .= '}';

        $txt .= 'th.left, td.left {';
        $txt .= '    text-align: left;';
        $txt .= '}';

        $txt .= 'th.center, td.center {';
        $txt .= '    text-align: center;';
        $txt .= '}';

        $txt .= 'th.right, td.right {';
        $txt .= '    text-align: right;';
        $txt .= '    padding-right: 1em;';
        $txt .= '}';

        $txt .= 'tr.vtop th, tr.vtop td, th.vtop, td.vtop {';
        $txt .= '    vertical-align: top;';
        $txt .= '}';

        $txt .= 'tr.vmiddle th, tr.vmiddle td, th.vmiddle, td.vmiddle {';
        $txt .= '    vertical-align: middle;';
        $txt .= '}';

        $txt .= 'tr.vbottom th, tr.vbottom td, th.vbottom, td.vbottom {';
        $txt .= '    vertical-align: bottom;';
        $txt .= '}';

        $txt .= '.paddingtop {';
        $txt .= '    padding-top: 1em;';
        $txt .= '}';

        $txt .= '.separator {';
        $txt .= '    color: #fff;';
        $txt .= '    text-shadow: 0 1px 0 #000;';
        $txt .= '}';

        $txt .= 'div.tools {';
        $txt .= '    /* border: 1px solid #000; */';
        $txt .= '    padding: .2em;';
        $txt .= '}';

        $txt .= 'div.tools a {';
        $txt .= '    color: #3a7ead !important;';
        $txt .= '}';

        $txt .= 'div.tools,';
        $txt .= 'fieldset.tblFooters {';
        $txt .= '    margin-top: 0;';
        $txt .= '    margin-bottom: .5em;';
        $txt .= '    border-top: 0;';
        $txt .= '    text-align: <?php echo $right; ?>;';
        $txt .= '    float: none;';
        $txt .= '    clear: both;';
        $txt .= '    -webkit-border-radius: 0 0 4px 4px;';
        $txt .= '    -moz-border-radius: 0 0 4px 4px;';
        $txt .= '    border-radius: 0 0 4px 5px;';
        $txt .= '}';

        $txt .= 'div.null_div {';
        $txt .= '    height: 20px;';
        $txt .= '    text-align: center;';
        $txt .= '    font-style: normal;';
        $txt .= '    min-width: 50px;';
        $txt .= '}';

        $txt .= 'fieldset .formelement {';
        $txt .= '    float: <?php echo $left; ?>;';
        $txt .= '    margin-<?php echo $right; ?>: .5em;';
        $txt .= '    white-space: nowrap;';
        $txt .= '}';

        /* revert for Gecko */
        $txt .= 'fieldset div[class=formelement] {';
        $txt .= '    white-space: normal;';
        $txt .= '}';

        $txt .= 'button.mult_submit {';
        $txt .= '    border: none;';
        $txt .= '    background-color: transparent;';
        $txt .= '}';

        /* odd items 1,3,5,7,... */
        $txt .= 'table tbody:first-of-type tr:nth-child(odd),';
        $txt .= 'table tbody:first-of-type tr:nth-child(odd) th,';
        $txt .= '#table_index tbody:nth-of-type(odd) tr,';
        $txt .= '#table_index tbody:nth-of-type(odd) th {';
        $txt .= '    background: <?php echo $GLOBALS[\'cfg\'][\'BgOne\']; ?>;';
        $txt .= '}';

        /* even items 2,4,6,8,... */
        $txt .= 'table tbody:first-of-type tr:nth-child(even),';
        $txt .= 'table tbody:first-of-type tr:nth-child(even) th,';
        $txt .= '#table_index tbody:nth-of-type(even) tr,';
        $txt .= '#table_index tbody:nth-of-type(even) th {';
        $txt .= '    background: <?php echo $GLOBALS[\'cfg\'][\'BgTwo\']; ?>;';
        $txt .= '}';

        $txt .= 'table tr th,';
        $txt .= 'table tr {';
        $txt .= '    text-align: <?php echo $left; ?>;';
        $txt .= '}';

        /* marked table rows */
        $txt .= 'td.marked:not(.nomarker),';
        $txt .= 'table tr.marked:not(.nomarker) td,';
        $txt .= 'table tbody:first-of-type tr.marked:not(.nomarker) th,';
        $txt .= 'table tr.marked:not(.nomarker) {';
        $txt .= '    background: <?php echo $GLOBALS[\'cfg\'][\'BgThree\']; ?>;';
        $txt .= '    color: <?php echo $GLOBALS[\'cfg\'][\'BrowseMarkerColor\']; ?>;';
        $txt .= '}';

        /* hovered items */
        $txt .= 'table tbody:first-of-type tr:not(.nopointer):hover,';
        $txt .= 'table tbody:first-of-type tr:not(.nopointer):hover th,';
        $txt .= '.hover:not(.nopointer) {';
        $txt .= '    background: <?php echo $GLOBALS[\'cfg\'][\'BgThree\']; ?>;';
        $txt .= '    color: <?php echo $GLOBALS[\'cfg\'][\'BrowsePointerColor\']; ?>;';
        $txt .= '}';

        /* hovered table rows */
        $txt .= '#table_index tbody:hover tr,';
        $txt .= '#table_index tbody:hover th,';
        $txt .= 'table tr.hover:not(.nopointer) th {';
        $txt .= '    background: <?php echo $GLOBALS[\'cfg\'][\'BgThree\']; ?>;';
        $txt .= '    color: <?php echo $GLOBALS[\'cfg\'][\'BrowsePointerColor\']; ?>;';
        $txt .= '}';

        /**
        * marks table rows/cells if the db field is in a where condition
        */
        $txt .= '.condition {';
        $txt .= '    border-color: <?php echo $GLOBALS[\'cfg\'][\'BrowseMarkerBackground\']; ?> !important;';
        $txt .= '}';

        $txt .= 'th.condition {';
        $txt .= '    border-width: 1px 1px 0 1px;';
        $txt .= '    border-style: solid;';
        $txt .= '}';

        $txt .= 'td.condition {';
        $txt .= '    border-width: 0 1px 0 1px;';
        $txt .= '    border-style: solid;';
        $txt .= '}';

        $txt .= 'tr:last-child td.condition {';
        $txt .= '    border-width: 0 1px 1px 1px;';
        $txt .= '}';

        $txt .= '<?php if ($GLOBALS[\'text_dir\'] === \'ltr\') : ?>';
        $txt .= '    /* for first th which must have right border set (ltr only) */';
        $txt .= '    .before-condition {';
        $txt .= '    border-right: 1px solid <?php echo $GLOBALS[\'cfg\'][\'BrowseMarkerBackground\']; ?>;';
        $txt .= '    }';
        $txt .= '<?php endif; ?>';

        /**
        * cells with the value NULL
        */
        $txt .= 'td.null {';
        $txt .= '    font-style: italic;';
        $txt .= '    color: #7d7d7d;';
        $txt .= '}';

        $txt .= 'table .valueHeader {';
        $txt .= '    text-align: <?php echo $right; ?>;';
        $txt .= '    white-space: normal;';
        $txt .= '}';
        $txt .= 'table .value {';
        $txt .= '    text-align: <?php echo $right; ?>;';
        $txt .= '    white-space: normal;';
        $txt .= '}';
        /* IE doesnt handles 'pre' right */
        $txt .= 'table [class=value] {';
        $txt .= '    white-space: normal;';
        $txt .= '}';

        $txt .= '<?php if (! empty($GLOBALS[\'cfg\'][\'FontFamilyFixed\'])) : ?>';
        $txt .= '    .value {';
        $txt .= '    font-family: <?php echo $GLOBALS[\'cfg\'][\'FontFamilyFixed\']; ?>;';
        $txt .= '    }';
        $txt .= '<?php endif; ?>';
        $txt .= '.attention {';
        $txt .= '    color: red;';
        $txt .= '    font-weight: bold;';
        $txt .= '}';
        $txt .= '.allfine {';
        $txt .= '    color: green;';
        $txt .= '}';

        $txt .= 'img.lightbulb {';
        $txt .= '    cursor: pointer;';
        $txt .= '}';

        $txt .= '.pdflayout {';
        $txt .= '    overflow: hidden;';
        $txt .= '    clip: inherit;';
        $txt .= '    background-color: #fff;';
        $txt .= '    display: none;';
        $txt .= '    border: 1px solid #000;';
        $txt .= '    position: relative;';
        $txt .= '}';

        $txt .= '.pdflayout_table {';
        $txt .= '    background: #D3DCE3;';
        $txt .= '    color: #000;';
        $txt .= '    overflow: hidden;';
        $txt .= '    clip: inherit;';
        $txt .= '    z-index: 2;';
        $txt .= '    display: inline;';
        $txt .= '    visibility: inherit;';
        $txt .= '    cursor: move;';
        $txt .= '    position: absolute;';
        $txt .= '    font-size: 80%;';
        $txt .= '    border: 1px dashed #000;';
        $txt .= '}';

        /* Doc links in SQL */
        $txt .= '.cm-sql-doc {';
        $txt .= '    text-decoration: none;';
        $txt .= '    border-bottom: 1px dotted #000;';
        $txt .= '    color: inherit !important;';
        $txt .= '}';

        /* no extra space in table cells */
        $txt .= 'td .icon {';
        $txt .= '    image-rendering: pixelated;';
        $txt .= '    margin: 0;';
        $txt .= '}';

        $txt .= '.selectallarrow {';
        $txt .= '    margin-<?php echo $right; ?>: .3em;';
        $txt .= '    margin-<?php echo $left; ?>: .6em;';
        $txt .= '}';

        /* message boxes: error, confirmation */
        $txt .= '#pma_errors, #pma_demo, #pma_footer {';
        $txt .= '    padding: 0 0.5em;';
        $txt .= '}';

        $txt .= '.success h1,';
        $txt .= '.notice h1,';
        $txt .= 'div.error h1 {';
        $txt .= '    border-bottom: 2px solid;';
        $txt .= '    font-weight: bold;';
        $txt .= '    text-align: <?php echo $left; ?>;';
        $txt .= '    margin: 0 0 .2em 0;';
        $txt .= '}';

        $txt .= 'div.success,';
        $txt .= 'div.notice,';
        $txt .= 'div.error {';
        $txt .= '    margin: .5em 0 0.5em;';
        $txt .= '    border: 1px solid;';
        $txt .= '    background-repeat: no-repeat;';
        $txt .= '    <?php if ($GLOBALS[\'text_dir\'] === \'ltr\') : ?>';
        $txt .= '        background-position: 10px 50%;';
        $txt .= '        padding: 10px 10px 10px 10px;';
        $txt .= '    <?php else : ?>';
        $txt .= '        background-position: 99% 50%;';
        $txt .= '        padding: 10px 35px 10px 10px;';
        $txt .= '        <?php';
        $txt .= '    endif; ?>';

        $txt .= '    -moz-border-radius: 5px;';
        $txt .= '    -webkit-border-radius: 5px;';
        $txt .= '    border-radius: 5px;';

        $txt .= '    -moz-box-shadow: 0 1px 1px #fff inset;';
        $txt .= '    -webkit-box-shadow: 0 1px 1px #fff inset;';
        $txt .= '    box-shadow: 0 1px 1px #fff inset;';
        $txt .= '}';

        $txt .= '.success a,';
        $txt .= '.notice a,';
        $txt .= '.error a {';
        $txt .= '    text-decoration: underline;';
        $txt .= '}';

        $txt .= '.success {';
        $txt .= '    color: #000;';
        $txt .= '    background-color: #ebf8a4;';
        $txt .= '}';

        $txt .= 'h1.success,';
        $txt .= 'div.success {';
        $txt .= '    border-color: #a2d246;';
        $txt .= '}';
        $txt .= '.success h1 {';
        $txt .= '    border-color: #00FF00;';
        $txt .= '}';

        $txt .= '.notice {';
        $txt .= '    color: #000;';
        $txt .= '    background-color: #e8eef1;';
        $txt .= '}';

        $txt .= 'h1.notice,';
        $txt .= 'div.notice {';
        $txt .= '    border-color: #3a6c7e;';
        $txt .= '}';

        $txt .= '.notice h1 {';
        $txt .= '    border-color: #ffb10a;';
        $txt .= '}';

        $txt .= '.error {';
        $txt .= '    border: 1px solid maroon !important;';
        $txt .= '    color: #000;';
        $txt .= '    background: pink;';
        $txt .= '}';

        $txt .= 'h1.error,';
        $txt .= 'div.error {';
        $txt .= '    border-color: #333;';
        $txt .= '}';

        $txt .= 'div.error h1 {';
        $txt .= '    border-color: #ff0000;';
        $txt .= '}';

        $txt .= '.confirmation {';
        $txt .= '    color: #000;';
        $txt .= '    background-color: pink;';
        $txt .= '}';

        $txt .= 'fieldset.confirmation {';
        $txt .= '}';

        $txt .= 'fieldset.confirmation legend {';
        $txt .= '}';

        /* end messageboxes */

        $txt .= '.new_central_col{';
        $txt .= '    width: 100%;';
        $txt .= '}';

        $txt .= '.tblcomment {';
        $txt .= '    font-size: 70%;';
        $txt .= '    font-weight: normal;';
        $txt .= '    color: #000099;';
        $txt .= '}';

        $txt .= '.tblHeaders {';
        $txt .= '    font-weight: bold;';
        $txt .= '    color: <?php echo $GLOBALS[\'cfg\'][\'ThColor\']; ?>;';
        $txt .= '    background: <?php echo $GLOBALS[\'cfg\'][\'ThBackground\']; ?>;';
        $txt .= '}';

        $txt .= 'div.tools,';
        $txt .= '.tblFooters {';
        $txt .= '    font-weight: normal;';
        $txt .= '    color: <?php echo $GLOBALS[\'cfg\'][\'ThColor\']; ?>;';
        $txt .= '    background: <?php echo $GLOBALS[\'cfg\'][\'ThBackground\']; ?>;';
        $txt .= '}';

        $txt .= '.tblHeaders a:link,';
        $txt .= '.tblHeaders a:active,';
        $txt .= '.tblHeaders a:visited,';
        $txt .= 'div.tools a:link,';
        $txt .= 'div.tools a:visited,';
        $txt .= 'div.tools a:active,';
        $txt .= '.tblFooters a:link,';
        $txt .= '.tblFooters a:active,';
        $txt .= '.tblFooters a:visited {';
        $txt .= '    color: #0000FF;';
        $txt .= '}';

        $txt .= '.tblHeaders a:hover,';
        $txt .= 'div.tools a:hover,';
        $txt .= '.tblFooters a:hover {';
        $txt .= '    color: #FF0000;';
        $txt .= '}';

        /* forbidden, no privileges */
        $txt .= '.noPrivileges {';
        $txt .= '    color: #FF0000;';
        $txt .= '    font-weight: bold;';
        $txt .= '}';

        /* disabled text */
        $txt .= '.disabled,';
        $txt .= '.disabled a:link,';
        $txt .= '.disabled a:active,';
        $txt .= '.disabled a:visited {';
        $txt .= '    color: #666;';
        $txt .= '}';

        $txt .= '.disabled a:hover {';
        $txt .= '    color: #666;';
        $txt .= '    text-decoration: none;';
        $txt .= '}';

        $txt .= 'tr.disabled td,';
        $txt .= 'td.disabled {';
        $txt .= '    background-color: <?php echo $GLOBALS[\'cfg\'][\'GroupBg\']; ?>;';
        $txt .= '    color: #aaa;';
        $txt .= '}';

        $txt .= '.nowrap {';
        $txt .= '    white-space: nowrap;';
        $txt .= '}';

        /**
        * login form
        */
        $txt .= 'body#loginform h1,';
        $txt .= 'body#loginform a.logo {';
        $txt .= '    display: block;';
        $txt .= '    text-align: center;';
        $txt .= '}';

        $txt .= 'body#loginform {';
        $txt .= '    margin-top: 1em;';
        $txt .= '    text-align: center;';
        $txt .= '}';

        $txt .= 'body#loginform div.container {';
        $txt .= '    text-align: <?php echo $left; ?>;';
        $txt .= '    width: 30em;';
        $txt .= '    margin: 0 auto;';
        $txt .= '}';

        $txt .= 'form.login label {';
        $txt .= '    float: <?php echo $left; ?>;';
        $txt .= '    width: 10em;';
        $txt .= '    font-weight: bolder;';
        $txt .= '}';

        $txt .= 'form.login input[type=text],';
        $txt .= 'form.login input[type=password],';
        $txt .= 'form.login select {';
        $txt .= '    box-sizing: border-box;';
        $txt .= '    width: 14em;';
        $txt .= '}';

        $txt .= '.commented_column {';
        $txt .= '    border-bottom: 1px dashed #000;';
        $txt .= '}';

        $txt .= '.column_attribute {';
        $txt .= '    font-size: 70%;';
        $txt .= '}';

        $txt .= '.cfg_dbg_demo{';
        $txt .= '    margin: 0.5em 1em 0.5em 1em;';
        $txt .= '}';

        $txt .= '.central_columns_navigation{';
        $txt .= '    padding:1.5% 0em !important;';
        $txt .= '}';

        $txt .= '.central_columns_add_column{';
        $txt .= '    display:inline-block;';
        $txt .= '    margin-left:1%;';
        $txt .= '    max-width:50%';
        $txt .= '}';

        $txt .= '.message_errors_found{';
        $txt .= '    margin-top: 20px;';
        $txt .= '}';

        $txt .= '.repl_gui_skip_err_cnt{';
        $txt .= '    width: 30px;';
        $txt .= '}';

        $txt .= '.font_weight_bold{';
        $txt .= '    font-weight: bold;';
        $txt .= '}';

        $txt .= '.color_gray{';
        $txt .= '    color: gray;';
        $txt .= '}';

        $txt .= '.pma_sliding_message{';
        $txt .= '    display: inline-block;';
        $txt .= '}';

        /******************************************************************************/
        /* specific elements */

        /* topmenu */
        $txt .= '#topmenu a {';
        $txt .= '    text-shadow: 0 1px 0 #fff;';
        $txt .= '}';

        $txt .= '#topmenu .error {';
        $txt .= '    background: #eee;border: 0 !important;color: #aaa;';
        $txt .= '}';

        $txt .= 'ul#topmenu,';
        $txt .= 'ul#topmenu2,';
        $txt .= 'ul.tabs {';
        $txt .= '    font-weight: bold;';
        $txt .= '    list-style-type: none;';
        $txt .= '    margin: 0;';
        $txt .= '    padding: 0;';
        $txt .= '}';

        $txt .= 'ul#topmenu2 {';
        $txt .= '    margin: .25em .5em 0;';
        $txt .= '    height: 2em;';
        $txt .= '    clear: both;';
        $txt .= '}';

        $txt .= 'ul#topmenu li,';
        $txt .= 'ul#topmenu2 li {';
        $txt .= '    float: <?php echo $left; ?>;';
        $txt .= '    margin: 0;';
        $txt .= '    vertical-align: middle;';
        $txt .= '}';

        $txt .= '#topmenu img,';
        $txt .= '#topmenu2 img {';
        $txt .= '    margin-right: .5em;';
        $txt .= '    vertical-align: -3px;';
        $txt .= '}';

        $txt .= '.menucontainer {';
        $txt .= '    <?php echo $theme->getCssGradient(\'dddddd\', \'dcdcdc\'); ?>';
        $txt .= '    border-top: 1px solid #aaa;';
        $txt .= '}';

        $txt .= '.scrollindicator {';
        $txt .= '    display: none;';
        $txt .= '}';

        /* default tab styles */
        $txt .= '.tabactive {';
        $txt .= '    background: #fff !important;';
        $txt .= '}';

        $txt .= 'ul#topmenu2 a {';
        $txt .= '    display: block;';
        $txt .= '    margin: 7px 6px 7px;';
        $txt .= '    margin-<?php echo $left; ?>: 0;';
        $txt .= '    padding: 4px 10px;';
        $txt .= '    white-space: nowrap;';
        $txt .= '    border: 1px solid #ddd;';
        $txt .= '    border-radius: 20px;';
        $txt .= '    -moz-border-radius: 20px;';
        $txt .= '    -webkit-border-radius: 20px;';
        $txt .= '    background: #f2f2f2;';

        $txt .= '}';

        $txt .= 'span.caution {';
        $txt .= '    color: #FF0000;';
        $txt .= '}';
        $txt .= 'span.success {';
        $txt .= '    color: green;';
        $txt .= '}';
        $txt .= 'fieldset.caution a {';
        $txt .= '    color: #FF0000;';
        $txt .= '}';
        $txt .= 'fieldset.caution a:hover {';
        $txt .= '    color: #fff;';
        $txt .= '    background-color: #FF0000;';
        $txt .= '}';

        $txt .= '#topmenu {';
        $txt .= '    margin-top: .5em;';
        $txt .= '    padding: .1em .3em;';
        $txt .= '}';

        $txt .= 'ul#topmenu ul {';
        $txt .= '    -moz-box-shadow: <?php echo $GLOBALS[\'text_dir\'] === \'rtl\' ? \'-\' : \'\'; ?>1px 1px 6px #ddd;';
        $txt .= '    -webkit-box-shadow: <?php echo $GLOBALS[\'text_dir\'] === \'rtl\' ? \'-\' : \'\'; ?>2px 2px 3px #666;';
        $txt .= '    box-shadow: <?php echo $GLOBALS[\'text_dir\'] === \'rtl\' ? \'-\' : \'\'; ?>2px 2px 3px #666;';
        $txt .= '}';

        $txt .= 'ul#topmenu ul.only {';
        $txt .= '    <?php echo $left; ?>: 0;';
        $txt .= '}';

        $txt .= 'ul#topmenu > li {';
        $txt .= '    border-right: 1px solid #fff;';
        $txt .= '    border-left: 1px solid #ccc;';
        $txt .= '    border-bottom: 1px solid #ccc;';
        $txt .= '}';

        $txt .= 'ul#topmenu > li:first-child {';
        $txt .= '    border-left: 0;';
        $txt .= '}';

        /* default tab styles */
        $txt .= 'ul#topmenu a,';
        $txt .= 'ul#topmenu span {';
        $txt .= '    padding: .6em;';
        $txt .= '}';

        $txt .= 'ul#topmenu ul a {';
        $txt .= '    border-width: 1pt 0 0 0;';
        $txt .= '    -moz-border-radius: 0;';
        $txt .= '    -webkit-border-radius: 0;';
        $txt .= '    border-radius: 0;';
        $txt .= '}';

        $txt .= 'ul#topmenu ul li:first-child a {';
        $txt .= '    border-width: 0;';
        $txt .= '}';

        /* enabled hover/active tabs */
        $txt .= 'ul#topmenu > li > a:hover,';
        $txt .= 'ul#topmenu > li > .tabactive {';
        $txt .= '    text-decoration: none;';
        $txt .= '}';

        $txt .= 'ul#topmenu ul a:hover,';
        $txt .= 'ul#topmenu ul .tabactive {';
        $txt .= '    text-decoration: none;';
        $txt .= '}';

        $txt .= 'ul#topmenu a.tab:hover,';
        $txt .= 'ul#topmenu .tabactive {';
        $txt .= '    /* background-color: <?php echo $GLOBALS[\'cfg\'][\'MainBackground\']; ?>;  */';
        $txt .= '}';

        $txt .= 'ul#topmenu2 a.tab:hover,';
        $txt .= 'ul#topmenu2 a.tabactive {';
        $txt .= '    background-color: <?php echo $GLOBALS[\'cfg\'][\'BgOne\']; ?>;';
        $txt .= '    border-radius: .3em;';
        $txt .= '    -moz-border-radius: .3em;';
        $txt .= '    -webkit-border-radius: .3em;';
        $txt .= '    text-decoration: none;';
        $txt .= '}';

        /* to be able to cancel the bottom border, use <li class="active"> */
        $txt .= 'ul#topmenu > li.active {';
        $txt .= '    /* border-bottom: 0pt solid <?php echo $GLOBALS[\'cfg\'][\'MainBackground\']; ?>; */';
        $txt .= '    border-right: 0;';
        $txt .= '    border-bottom-color: #fff;';
        $txt .= '}';
        /* end topmenu */

        /* zoom search */
        $txt .= 'div#dataDisplay input,';
        $txt .= 'div#dataDisplay select {';
        $txt .= '    margin: 0;';
        $txt .= '    margin-<?php echo $right; ?>: .5em;';
        $txt .= '}';
        $txt .= 'div#dataDisplay th {';
        $txt .= '    line-height: 2em;';
        $txt .= '}';
        $txt .= 'table#tableFieldsId {';
        $txt .= '    width: 100%;';
        $txt .= '}';

        /* Calendar */
        $txt .= 'table.calendar {';
        $txt .= '    width: 100%;';
        $txt .= '}';
        $txt .= 'table.calendar td {';
        $txt .= '    text-align: center;';
        $txt .= '}';
        $txt .= 'table.calendar td a {';
        $txt .= '    display: block;';
        $txt .= '}';

        $txt .= 'table.calendar td a:hover {';
        $txt .= '    background-color: #CCFFCC;';
        $txt .= '}';

        $txt .= 'table.calendar th {';
        $txt .= '    background-color: #D3DCE3;';
        $txt .= '}';

        $txt .= 'table.calendar td.selected {';
        $txt .= '    background-color: #FFCC99;';
        $txt .= '}';

        $txt .= 'img.calendar {';
        $txt .= '    border: none;';
        $txt .= '}';
        $txt .= 'form.clock {';
        $txt .= '    text-align: center;';
        $txt .= '}';
        /* end Calendar */

        /* table stats */
        $txt .= 'div#tablestatistics table {';
        $txt .= '    float: <?php echo $left; ?>;';
        $txt .= '    margin-bottom: .5em;';
        $txt .= '    margin-<?php echo $right; ?>: 1.5em;';
        $txt .= '    margin-top: .5em;';
        $txt .= '    min-width: 16em;';
        $txt .= '}';

        /* end table stats */

        /* server privileges */
        $txt .= '#tableuserrights td,';
        $txt .= '#tablespecificuserrights td,';
        $txt .= '#tabledatabases td {';
        $txt .= '    vertical-align: middle;';
        $txt .= '}';
        /* end server privileges */

        /* Heading */
        $txt .= '#topmenucontainer {';
        $txt .= '    padding-<?php echo $right; ?>: 1em;';
        $txt .= '    width: 100%;';
        $txt .= '}';

        $txt .= '#serverinfo {';
        $txt .= '    background: <?php echo $GLOBALS[\'cfg\'][\'Header\']; ?>;';
        $txt .= '    padding: .3em .9em;';
        $txt .= '    padding-<?php echo $left; ?>: 2.2em;';
        // $txt .= '    text-shadow: 0 1px 0 #000;';
        $txt .= '    max-width: 100%;';
        $txt .= '    max-height: 16px;';
        $txt .= '    overflow: hidden;';
        $txt .= '}';

        $txt .= '#serverinfo .item {';
        $txt .= '    white-space: nowrap;';
        $txt .= '    color: #fff;';
        $txt .= '}';

        $txt .= '#page_nav_icons {';
        $txt .= '    position: fixed;';
        $txt .= '    top: 0;';
        $txt .= '    <?php echo $right; ?>: 0;';
        $txt .= '    z-index: 99;';
        $txt .= '    padding: .25em 0;';
        $txt .= '}';

        $txt .= '#goto_pagetop, #lock_page_icon, #page_settings_icon {';
        $txt .= '    padding: .25em;';
        $txt .= '    background: <?php echo $GLOBALS[\'cfg\'][\'Header\']; ?>;;';
        $txt .= '}';

        $txt .= '#page_settings_icon {';
        $txt .= '    cursor: pointer;';
        $txt .= '    display: none;';
        $txt .= '}';

        $txt .= '#page_settings_modal {';
        $txt .= '    display: none;';
        $txt .= '}';

        $txt .= '#pma_navigation_settings {';
        $txt .= '    display: none;';
        $txt .= '}';

        $txt .= '#span_table_comment {';
        $txt .= '    font-weight: bold;';
        $txt .= '    font-style: italic;';
        $txt .= '    white-space: nowrap;';
        $txt .= '    margin-left: 10px;';
        $txt .= '    color: #D6D6D6;';
        $txt .= '    text-shadow: none;';
        $txt .= '}';

        $txt .= '#serverinfo img {';
        $txt .= '    margin: 0 .1em 0;';
        $txt .= '    margin-<?php echo $left; ?>: .2em;';
        $txt .= '}';

        $txt .= '#textSQLDUMP {';
        $txt .= '    width: 95%;';
        $txt .= '    height: 95%;';
        $txt .= '    font-family: Consolas, "Courier New", Courier, mono;';
        $txt .= '    font-size: 110%;';
        $txt .= '}';

        $txt .= '#TooltipContainer {';
        $txt .= '    position: absolute;';
        $txt .= '    z-index: 99;';
        $txt .= '    width: 20em;';
        $txt .= '    height: auto;';
        $txt .= '    overflow: visible;';
        $txt .= '    visibility: hidden;';
        $txt .= '    background-color: #ffffcc;';
        $txt .= '    color: #006600;';
        $txt .= '    border: .1em solid #000;';
        $txt .= '    padding: .5em;';
        $txt .= '}';

        /* user privileges */
        $txt .= '#fieldset_add_user_login div.item {';
        $txt .= '    border-bottom: 1px solid silver;';
        $txt .= '    padding-bottom: .3em;';
        $txt .= '    margin-bottom: .3em;';
        $txt .= '}';

        $txt .= '#fieldset_add_user_login label {';
        $txt .= '    float: <?php echo $left; ?>;';
        $txt .= '    display: block;';
        $txt .= '    width: 10em;';
        $txt .= '    max-width: 100%;';
        $txt .= '    text-align: <?php echo $right; ?>;';
        $txt .= '    padding-<?php echo $right; ?>: .5em;';
        $txt .= '}';

        $txt .= '#fieldset_add_user_login span.options #select_pred_username,';
        $txt .= '#fieldset_add_user_login span.options #select_pred_hostname,';
        $txt .= '#fieldset_add_user_login span.options #select_pred_password {';
        $txt .= '    width: 100%;';
        $txt .= '    max-width: 100%;';
        $txt .= '}';

        $txt .= '#fieldset_add_user_login span.options {';
        $txt .= '    float: <?php echo $left; ?>;';
        $txt .= '    display: block;';
        $txt .= '    width: 12em;';
        $txt .= '    max-width: 100%;';
        $txt .= '    padding-<?php echo $right; ?>: .5em;';
        $txt .= '}';

        $txt .= '#fieldset_add_user_login input {';
        $txt .= '    width: 12em;';
        $txt .= '    clear: <?php echo $right; ?>;';
        $txt .= '    max-width: 100%;';
        $txt .= '}';

        $txt .= '#fieldset_add_user_login span.options input {';
        $txt .= '    width: auto;';
        $txt .= '}';

        $txt .= '#fieldset_user_priv div.item {';
        $txt .= '    float: <?php echo $left; ?>;';
        $txt .= '    width: 9em;';
        $txt .= '    max-width: 100%;';
        $txt .= '}';

        $txt .= '#fieldset_user_priv div.item div.item {';
        $txt .= '    float: none;';
        $txt .= '}';

        $txt .= '#fieldset_user_priv div.item label {';
        $txt .= '    white-space: nowrap;';
        $txt .= '}';

        $txt .= '#fieldset_user_priv div.item select {';
        $txt .= '    width: 100%;';
        $txt .= '}';

        $txt .= '#fieldset_user_global_rights fieldset {';
        $txt .= '    float: <?php echo $left; ?>;';
        $txt .= '}';

        $txt .= '#fieldset_user_group_rights fieldset {';
        $txt .= '    float: <?php echo $left; ?>;';
        $txt .= '}';

        $txt .= '#fieldset_user_global_rights>legend input {';
        $txt .= '    margin-<?php echo $left; ?>: 2em;';
        $txt .= '}';
        /* end user privileges */

        /* serverstatus */

        $txt .= '.linkElem:hover {';
        $txt .= '    text-decoration: underline;';
        $txt .= '    color: #235a81;';
        $txt .= '    cursor: pointer;';
        $txt .= '}';

        $txt .= 'h3#serverstatusqueries span {';
        $txt .= '    font-size: 60%;';
        $txt .= '    display: inline;';
        $txt .= '}';

        $txt .= '.buttonlinks {';
        $txt .= '    float: <?php echo $right; ?>;';
        $txt .= '    white-space: nowrap;';
        $txt .= '}';

        /* Also used for the variables page */
        $txt .= 'fieldset#tableFilter {';
        $txt .= '    padding: 0.1em 1em;';
        $txt .= '}';

        $txt .= 'div#serverStatusTabs {';
        $txt .= '    margin-top: 1em;';
        $txt .= '}';

        $txt .= 'caption a.top {';
        $txt .= '    float: <?php echo $right; ?>;';
        $txt .= '}';

        $txt .= 'div#serverstatusquerieschart {';
        $txt .= '    float: <?php echo $left; ?>;';
        $txt .= '    width: 500px;';
        $txt .= '    height: 350px;';
        $txt .= '    margin-<?php echo $right;?>: 50px;';
        $txt .= '}';

        $txt .= 'table#serverstatusqueriesdetails,';
        $txt .= 'table#serverstatustraffic {';
        $txt .= '    float: <?php echo $left; ?>;';
        $txt .= '}';

        $txt .= 'table#serverstatusqueriesdetails th {';
        $txt .= '    min-width: 35px;';
        $txt .= '}';

        $txt .= 'table#serverstatusvariables {';
        $txt .= '    width: 100%;';
        $txt .= '    margin-bottom: 1em;';
        $txt .= '}';
        $txt .= 'table#serverstatusvariables .name {';
        $txt .= '    width: 18em;';
        $txt .= '    white-space: nowrap;';
        $txt .= '}';
        $txt .= 'table#serverstatusvariables .value {';
        $txt .= '    width: 6em;';
        $txt .= '}';
        $txt .= 'table#serverstatusconnections {';
        $txt .= '    float: <?php echo $left; ?>;';
        $txt .= '    margin-<?php echo $left; ?>: 30px;';
        $txt .= '}';

        $txt .= 'div#serverstatus table tbody td.descr a,';
        $txt .= 'div#serverstatus table .tblFooters a {';
        $txt .= '    white-space: nowrap;';
        $txt .= '}';

        $txt .= 'div.liveChart {';
        $txt .= '    clear: both;';
        $txt .= '    min-width: 500px;';
        $txt .= '    height: 400px;';
        $txt .= '    padding-bottom: 80px;';
        $txt .= '}';

        $txt .= '#addChartDialog input[type="text"] {';
        $txt .= '    margin: 0;';
        $txt .= '    padding: 3px;';
        $txt .= '}';

        $txt .= 'div#chartVariableSettings {';
        $txt .= '    border: 1px solid #ddd;';
        $txt .= '    background-color: #E6E6E6;';
        $txt .= '    margin-left: 10px;';
        $txt .= '}';

        $txt .= 'table#chartGrid td {';
        $txt .= '    padding: 3px;';
        $txt .= '    margin: 0;';
        $txt .= '}';

        $txt .= 'table#chartGrid div.monitorChart {';
        $txt .= '    background: #EBEBEB;';
        $txt .= '    overflow: hidden;';
        $txt .= '    border: none;';
        $txt .= '}';

        $txt .= 'div.tabLinks {';
        $txt .= '    margin-left: 0.3em;';
        $txt .= '    float: <?php echo $left; ?>;';
        $txt .= '    padding: 5px 0;';
        $txt .= '}';

        $txt .= 'div.tabLinks a, div.tabLinks label {';
        $txt .= '    margin-right: 7px;';
        $txt .= '}';

        $txt .= 'div.tabLinks .icon {';
        $txt .= '    margin: -0.2em 0.3em 0 0;';
        $txt .= '}';

        $txt .= '.popupContent {';
        $txt .= '    display: none;';
        $txt .= '    position: absolute;';
        $txt .= '    border: 1px solid #CCC;';
        $txt .= '    margin: 0;';
        $txt .= '    padding: 3px;';
        $txt .= '    -moz-box-shadow: <?php echo $GLOBALS[\'text_dir\'] === \'rtl\' ? \'-\' : \'\'; ?>2px 2px 3px #666;';
        $txt .= '    -webkit-box-shadow: <?php echo $GLOBALS[\'text_dir\'] === \'rtl\' ? \'-\' : \'\'; ?>2px 2px 3px #666;';
        $txt .= '    box-shadow: <?php echo $GLOBALS[\'text_dir\'] === \'rtl\' ? \'-\' : \'\'; ?>2px 2px 3px #666;';
        $txt .= '    background-color: #fff;';
        $txt .= '    z-index: 2;';
        $txt .= '}';

        $txt .= 'div#logTable {';
        $txt .= '    padding-top: 10px;';
        $txt .= '    clear: both;';
        $txt .= '}';

        $txt .= 'div#logTable table {';
        $txt .= '    width: 100%;';
        $txt .= '}';

        $txt .= 'div#queryAnalyzerDialog {';
        $txt .= '    min-width: 700px;';
        $txt .= '}';

        $txt .= 'div#queryAnalyzerDialog div.CodeMirror-scroll {';
        $txt .= '    height: auto;';
        $txt .= '}';

        $txt .= 'div#queryAnalyzerDialog div#queryProfiling {';
        $txt .= '    height: 300px;';
        $txt .= '}';

        $txt .= 'div#queryAnalyzerDialog td.explain {';
        $txt .= '    width: 250px;';
        $txt .= '}';

        $txt .= 'div#queryAnalyzerDialog table.queryNums {';
        $txt .= '    display: none;';
        $txt .= '    border: 0;';
        $txt .= '    text-align: left;';
        $txt .= '}';

        $txt .= '.smallIndent {';
        $txt .= '    padding-<?php echo $left; ?>: 7px;';
        $txt .= '}';

        /* end serverstatus */

        /* server variables */
        $txt .= '#serverVariables {';
        $txt .= '    width: 100%;';
        $txt .= '}';
        $txt .= '#serverVariables .var-row > td {';
        $txt .= '    line-height: 2em;';
        $txt .= '}';
        $txt .= '#serverVariables .var-header {';
        $txt .= '    color: <?php echo $GLOBALS[\'cfg\'][\'ThColor\']; ?>;';
        $txt .= '    background: <?php echo $GLOBALS[\'cfg\'][\'ThBackground\']; ?>;';
        $txt .= '    font-weight: bold;';
        $txt .= '    text-align: <?php echo $left; ?>;';
        $txt .= '}';
        $txt .= '#serverVariables .var-row {';
        $txt .= '    padding: 0.5em;';
        $txt .= '    min-height: 18px;';
        $txt .= '}';
        $txt .= '#serverVariables .var-name {';
        $txt .= '    font-weight: bold;';
        $txt .= '}';
        $txt .= '#serverVariables .var-name.session {';
        $txt .= '    font-weight: normal;';
        $txt .= '    font-style: italic;';
        $txt .= '}';
        $txt .= '#serverVariables .var-value {';
        $txt .= '    float: <?php echo $right; ?>;';
        $txt .= '    text-align: <?php echo $right; ?>;';
        $txt .= '}';
        $txt .= '#serverVariables .var-doc {';
        $txt .= '    overflow:visible;';
        $txt .= '    float: <?php echo $right; ?>;';
        $txt .= '}';

        /* server variables editor */
        $txt .= '#serverVariables .editLink {';
        $txt .= '    padding-<?php echo $right; ?>: 1em;';
        $txt .= '    font-family: sans-serif;';
        $txt .= '}';
        $txt .= '#serverVariables .serverVariableEditor {';
        $txt .= '    width: 100%;';
        $txt .= '    overflow: hidden;';
        $txt .= '}';
        $txt .= '#serverVariables .serverVariableEditor input {';
        $txt .= '    width: 100%;';
        $txt .= '    margin: 0 0.5em;';
        $txt .= '    box-sizing: border-box;';
        $txt .= '    -ms-box-sizing: border-box;';
        $txt .= '    -moz-box-sizing: border-box;';
        $txt .= '    -webkit-box-sizing: border-box;';
        $txt .= '    height: 2.2em;';
        $txt .= '}';
        $txt .= '#serverVariables .serverVariableEditor div {';
        $txt .= '    display: block;';
        $txt .= '    overflow: hidden;';
        $txt .= '    padding-<?php echo $right; ?>: 1em;';
        $txt .= '}';
        $txt .= '#serverVariables .serverVariableEditor a {';
        $txt .= '    margin: 0 0.5em;';
        $txt .= '    line-height: 2em;';
        $txt .= '}';
        /* end server variables */

        $txt .= 'p.notice {';
        $txt .= '    margin: 1.5em 0;';
        $txt .= '    border: 1px solid #000;';
        $txt .= '    background-repeat: no-repeat;';
        $txt .= '    <?php if ($GLOBALS[\'text_dir\'] === \'ltr\') : ?>';
        $txt .= '        background-position: 10px 50%;';
        $txt .= '        padding: 10px 10px 10px 25px;';
        $txt .= '    <?php else : ?>';
        $txt .= '        background-position: 99% 50%;';
        $txt .= '        padding: 25px 10px 10px 10px';
        $txt .= '    <?php endif; ?>';
        $txt .= '    -moz-border-radius: 5px;';
        $txt .= '    -webkit-border-radius: 5px;';
        $txt .= '    border-radius: 5px;';
        $txt .= '    -moz-box-shadow: 0 1px 2px #fff inset;';
        $txt .= '    -webkit-box-shadow: 0 1px 2px #fff inset;';
        $txt .= '    box-shadow: 0 1px 2px #fff inset;';
        $txt .= '    background: #555;';
        $txt .= '    color: #d4fb6a;';
        $txt .= '}';

        $txt .= 'p.notice a {';
        $txt .= '    color: #fff;';
        $txt .= '    text-decoration: underline;';
        $txt .= '}';

        /* profiling */

        $txt .= 'div#profilingchart {';
        $txt .= '    width: 850px;';
        $txt .= '    height: 370px;';
        $txt .= '    float: <?php echo $left; ?>;';
        $txt .= '}';

        $txt .= '#profilingchart .jqplot-highlighter-tooltip{';
        $txt .= '    top: auto !important;';
        $txt .= '    left: 11px;';
        $txt .= '    bottom:24px;';
        $txt .= '}';
        /* end profiling */

        /* table charting */
        $txt .= '#resizer {';
        $txt .= '    border: 1px solid silver;';
        $txt .= '}';
        $txt .= '#inner-resizer { /* make room for the resize handle */';
        $txt .= '    padding: 10px;';
        $txt .= '}';
        $txt .= '.chartOption {';
        $txt .= '    float: <?php echo $left; ?>;';
        $txt .= '    margin-<?php echo $right;?>: 40px;';
        $txt .= '}';
        /* end table charting */

        /* querybox */

        $txt .= '#togglequerybox {';
        $txt .= '    margin: 0 10px;';
        $txt .= '}';

        $txt .= '#serverstatus h3';
        $txt .= '{';
        $txt .= '    margin: 15px 0;';
        $txt .= '    font-weight: normal;';
        $txt .= '    color: #999;';
        $txt .= '    font-size: 1.7em;';
        $txt .= '}';
        $txt .= '#sectionlinks {';
        $txt .= '    margin-bottom: 15px;';
        $txt .= '    padding: 16px;';
        $txt .= '    background: <?php echo $GLOBALS[\'cfg\'][\'GroupBg\']; ?>;';
        $txt .= '    border: 1px solid #aaa;';
        $txt .= '    border-radius: 5px;';
        $txt .= '    -webkit-border-radius: 5px;';
        $txt .= '    -moz-border-radius: 5px;';
        $txt .= '    box-shadow: 0 1px 1px #fff inset;';
        $txt .= '    -webkit-box-shadow: 0 1px 1px #fff inset;';
        $txt .= '    -moz-box-shadow: 0 1px 1px #fff inset;';
        $txt .= '}';
        $txt .= '#sectionlinks a,';
        $txt .= '.buttonlinks a,';
        $txt .= 'a.button {';
        $txt .= '    font-weight: bold;';
        $txt .= '    text-shadow: 0 1px 0 #fff;';
        $txt .= '    line-height: 35px;';
        $txt .= '    margin-<?php echo $left; ?>: 7px;';
        $txt .= '    border: 1px solid #aaa;';
        $txt .= '    padding: 3px 7px;';
        $txt .= '    color: #111 !important;';
        $txt .= '    text-decoration: none;';
        $txt .= '    background: #ddd;';
        $txt .= '    white-space: nowrap;';
        $txt .= '    border-radius: 20px;';
        $txt .= '    -webkit-border-radius: 20px;';
        $txt .= '    -moz-border-radius: 20px;';
        $txt .= '    <?php echo $theme->getCssGradient(\'f8f8f8\', \'d8d8d8\'); ?>';
        $txt .= '}';
        $txt .= '#sectionlinks a:hover,';
        $txt .= '.buttonlinks a:hover,';
        $txt .= 'a.button:hover {';
        $txt .= '    <?php echo $theme->getCssGradient(\'ffffff\', \'dddddd\'); ?>';
        $txt .= '}';

        $txt .= 'div#sqlquerycontainer {';
        $txt .= '    float: <?php echo $left; ?>;';
        $txt .= '    width: 69%;';
        $txt .= '    /* height: 15em; */';
        $txt .= '}';

        $txt .= 'div#tablefieldscontainer {';
        $txt .= '    float: <?php echo $right; ?>;';
        $txt .= '    width: 29%;';
        $txt .= '    margin-top: -20px;';
        $txt .= '    /* height: 15em; */';
        $txt .= '}';

        $txt .= 'div#tablefieldscontainer select {';
        $txt .= '    width: 100%;';
        $txt .= '    background: #fff;';
        $txt .= '    /* height: 12em; */';
        $txt .= '}';

        $txt .= 'textarea#sqlquery {';
        $txt .= '    width: 100%;';
        $txt .= '    /* height: 100%; */';
        $txt .= '    -moz-border-radius: 4px;';
        $txt .= '    -webkit-border-radius: 4px;';
        $txt .= '    border-radius: 4px;';
        $txt .= '    border: 1px solid #aaa;';
        $txt .= '    padding: 5px;';
        $txt .= '    font-family: inherit;';
        $txt .= '}';
        $txt .= 'textarea#sql_query_edit {';
        $txt .= '    height: 7em;';
        $txt .= '    width: 95%;';
        $txt .= '    display: block;';
        $txt .= '}';
        $txt .= 'div#queryboxcontainer div#bookmarkoptions {';
        $txt .= '    margin-top: .5em;';
        $txt .= '}';
        /* end querybox */

        /* main page */
        $txt .= '#maincontainer {';
        $txt .= '    /* background-image: url(<?php echo $theme->getImgPath(\'logo_right.png\');?>); */';
        $txt .= '    /* background-position: <?php echo $right; ?> bottom; */';
        $txt .= '    /* background-repeat: no-repeat; */';
        $txt .= '}';

        $txt .= '#mysqlmaininformation,';
        $txt .= '#pmamaininformation {';
        $txt .= '    float: <?php echo $left; ?>;';
        $txt .= '    width: 49%;';
        $txt .= '}';

        $txt .= '#maincontainer ul {';
        $txt .= '    list-style-type: disc;';
        $txt .= '    vertical-align: middle;';
        $txt .= '}';

        $txt .= '#maincontainer li {';
        $txt .= '    margin-bottom: .3em;';
        $txt .= '}';

        $txt .= '#full_name_layer {';
        $txt .= '    position: absolute;';
        $txt .= '    padding: 2px;';
        $txt .= '    margin-top: -3px;';
        $txt .= '    z-index: 801;';

        $txt .= '    border-radius: 3px;';
        $txt .= '    border: solid 1px <?php echo $GLOBALS[\'cfg\'][\'Header\']; ?>;;';
        $txt .= '    background: #fff;';

        $txt .= '}';
        /* end main page */

        /* iconic view for ul items */

        $txt .= 'li.no_bullets {';
        $txt .= '    list-style-type:none !important;';
        $txt .= '    margin-left: -25px !important;      //align with other list items which have bullets';
        $txt .= '}';

        /* end iconic view for ul items */

        $txt .= '#body_browse_foreigners {';
        $txt .= '    background: <?php echo $GLOBALS[\'cfg\'][\'NaviBackground\']; ?>;';
        $txt .= '    margin: .5em .5em 0 .5em;';
        $txt .= '}';

        $txt .= '#bodythemes {';
        $txt .= '    width: 500px;';
        $txt .= '    margin: auto;';
        $txt .= '    text-align: center;';
        $txt .= '}';

        $txt .= '#bodythemes img {';
        $txt .= '    border: .1em solid #000;';
        $txt .= '}';

        $txt .= '#bodythemes a:hover img {';
        $txt .= '    border: .1em solid red;';
        $txt .= '}';

        $txt .= '#fieldset_select_fields {';
        $txt .= '    float: <?php echo $left; ?>;';
        $txt .= '}';

        $txt .= '#selflink {';
        $txt .= '    clear: both;';
        $txt .= '    display: block;';
        $txt .= '    margin-top: 1em;';
        $txt .= '    margin-bottom: 1em;';
        $txt .= '    width: 98%;';
        $txt .= '    margin-<?php echo $left; ?>: 1%;';
        $txt .= '    border-top: .1em solid silver;';
        $txt .= '    text-align: <?php echo $right; ?>;';
        $txt .= '}';

        $txt .= '#table_innodb_bufferpool_usage,';
        $txt .= '#table_innodb_bufferpool_activity {';
        $txt .= '    float: <?php echo $left; ?>;';
        $txt .= '}';

        $txt .= '#div_mysql_charset_collations table {';
        $txt .= '    float: <?php echo $left; ?>;';
        $txt .= '}';

        $txt .= '#div_mysql_charset_collations table th,';
        $txt .= '#div_mysql_charset_collations table td {';
        $txt .= '    padding: 0.4em;';
        $txt .= '}';

        $txt .= '#div_mysql_charset_collations table th#collationHeader {';
        $txt .= '    width: 35%;';
        $txt .= '}';

        $txt .= '#qbe_div_table_list {';
        $txt .= '    float: <?php echo $left; ?>;';
        $txt .= '}';

        $txt .= '#qbe_div_sql_query {';
        $txt .= '    float: <?php echo $left; ?>;';
        $txt .= '}';

        $txt .= 'label.desc {';
        $txt .= '    width: 30em;';
        $txt .= '    float: <?php echo $left; ?>;';
        $txt .= '}';

        $txt .= 'label.desc sup {';
        $txt .= '    position: absolute;';
        $txt .= '}';

        $txt .= 'code.php {';
        $txt .= '    display: block;';
        $txt .= '    padding-left: 1em;';
        $txt .= '    margin-top: 0;';
        $txt .= '    margin-bottom: 0;';
        $txt .= '    max-height: 10em;';
        $txt .= '    overflow: auto;';
        $txt .= '    direction: ltr;';
        $txt .= '}';

        $txt .= 'code.sql,';
        $txt .= 'div.sqlvalidate {';
        $txt .= '    display: block;';
        $txt .= '    padding: 1em;';
        $txt .= '    margin-top: 0;';
        $txt .= '    margin-bottom: 0;';
        $txt .= '    max-height: 10em;';
        $txt .= '    overflow: auto;';
        $txt .= '    direction: ltr;';
        $txt .= '}';

        $txt .= '.result_query div.sqlOuter {';
        $txt .= '    background: <?php echo $GLOBALS[\'cfg\'][\'BgOne\']; ?>;';
        $txt .= '    text-align: <?php echo $left; ?>;';
        $txt .= '}';

        $txt .= '.result_query .success, .result_query .error {';
        $txt .= '    margin-bottom: 0;';
        $txt .= '    border-bottom: none !important;';
        $txt .= '    border-bottom-left-radius: 0;';
        $txt .= '    border-bottom-right-radius: 0;';
        $txt .= '    padding-bottom: 5px;';
        $txt .= '}';

        $txt .= '#PMA_slidingMessage code.sql,';
        $txt .= 'div.sqlvalidate {';
        $txt .= '    background: <?php echo $GLOBALS[\'cfg\'][\'BgOne\']; ?>;';
        $txt .= '}';

        $txt .= '#main_pane_left {';
        $txt .= '    width: 60%;';
        $txt .= '    min-width: 260px;';
        $txt .= '    float: <?php echo $left; ?>;';
        $txt .= '    padding-top: 1em;';
        $txt .= '}';

        $txt .= '#main_pane_right {';
        $txt .= '    overflow: hidden;';
        $txt .= '    min-width: 160px;';
        $txt .= '    padding-top: 1em;';
        $txt .= '    padding-<?php echo $left; ?>: 1em;';
        $txt .= '    padding-<?php echo $right; ?>: .5em;';
        $txt .= '}';

        $txt .= '.group {';

        $txt .= '    border: 1px solid #999;';
        $txt .= '    background: <?php echo $GLOBALS[\'cfg\'][\'GroupBg\']; ?>;';
        $txt .= '    -moz-border-radius: 4px;';
        $txt .= '    -webkit-border-radius: 4px;';
        $txt .= '    border-radius: 4px;';
        $txt .= '    margin-bottom: 1em;';
        $txt .= '    padding-bottom: 1em;';
        $txt .= '}';

        $txt .= '.group h2 {';
        $txt .= '    background-color: #bbb;';
        $txt .= '    padding: .1em .3em;';
        $txt .= '    margin-top: 0;';
        $txt .= '    color: #fff;';
        $txt .= '    font-size: 1.6em;';
        $txt .= '    font-weight: normal;';
        $txt .= '    text-shadow: 0 1px 0 #777;';
        $txt .= '    -moz-box-shadow: <?php echo $GLOBALS[\'text_dir\'] === \'rtl\' ? \'-\' : \'\'; ?>1px 1px 15px #999 inset;';
        $txt .= '    -webkit-box-shadow: <?php echo $GLOBALS[\'text_dir\'] === \'rtl\' ? \'-\' : \'\'; ?>1px 1px 15px #999 inset;';
        $txt .= '    box-shadow: <?php echo $GLOBALS[\'text_dir\'] === \'rtl\' ? \'-\' : \'\'; ?>1px 1px 15px #999 inset;';
        $txt .= '}';

        $txt .= '.group-cnt {';
        $txt .= '    padding: 0;';
        $txt .= '    padding-<?php echo $left; ?>: .5em;';
        $txt .= '    display: inline-block;';
        $txt .= '    width: 98%;';
        $txt .= '}';

        $txt .= 'textarea#partitiondefinition {';
        $txt .= '    height: 3em;';
        $txt .= '}';

        /* for elements that should be revealed only via js */
        $txt .= '.hide {';
        $txt .= '    display: none;';
        $txt .= '}';

        $txt .= '#list_server {';
        $txt .= '    list-style-type: none;';
        $txt .= '    padding: 0;';
        $txt .= '}';

        /**';
        $txt .= '  *  Progress bar styles';
        $txt .= '  */
        $txt .= 'div.upload_progress';
        $txt .= '{';
        $txt .= '    width: 400px;';
        $txt .= '    margin: 3em auto;';
        $txt .= '    text-align: center;';
        $txt .= '}';

        $txt .= 'div.upload_progress_bar_outer';
        $txt .= '{';
        $txt .= '    border: 1px solid #000;';
        $txt .= '    width: 202px;';
        $txt .= '    position: relative;';
        $txt .= '    margin: 0 auto 1em;';
        $txt .= '    color: <?php echo $GLOBALS[\'cfg\'][\'MainColor\']; ?>;';
        $txt .= '}';

        $txt .= 'div.upload_progress_bar_inner';
        $txt .= '{';
        $txt .= '    background-color: <?php echo $GLOBALS[\'cfg\'][\'NaviPointerBackground\']; ?>;';
        $txt .= '    width: 0;';
        $txt .= '    height: 12px;';
        $txt .= '    margin: 1px;';
        $txt .= '    overflow: hidden;';
        $txt .= '    color: <?php echo $GLOBALS[\'cfg\'][\'BrowseMarkerColor\']; ?>;';
        $txt .= '    position: relative;';
        $txt .= '}';

        $txt .= 'div.upload_progress_bar_outer div.percentage';
        $txt .= '{';
        $txt .= '    position: absolute;';
        $txt .= '    top: 0;';
        $txt .= '    <?php echo $left; ?>: 0;';
        $txt .= '    width: 202px;';
        $txt .= '}';

        $txt .= 'div.upload_progress_bar_inner div.percentage';
        $txt .= '{';
        $txt .= '    top: -1px;';
        $txt .= '    <?php echo $left; ?>: -1px;';
        $txt .= '}';

        $txt .= 'div#statustext {';
        $txt .= '    margin-top: .5em;';
        $txt .= '}';

        $txt .= 'table#serverconnection_src_remote,';
        $txt .= 'table#serverconnection_trg_remote,';
        $txt .= 'table#serverconnection_src_local,';
        $txt .= 'table#serverconnection_trg_local  {';
        $txt .= '  float: <?php echo $left; ?>;';
        $txt .= '}';
        /**
        *  Validation error message styles
        */
        $txt .= 'input[type=text].invalid_value,';
        $txt .= 'input[type=password].invalid_value,';
        $txt .= 'input[type=number].invalid_value,';
        $txt .= 'input[type=date].invalid_value,';
        $txt .= 'select.invalid_value,';
        $txt .= '.invalid_value {';
        $txt .= '    background: #FFCCCC;';
        $txt .= '}';

        /**
          *  Ajax notification styling
          */
        $txt .= '.ajax_notification {';
        $txt .= '   top: 0;           /** The notification needs to be shown on the top of the page */';
        $txt .= '   position: fixed;';
        $txt .= '   margin-top: 0;';
        $txt .= '   margin-right: auto;';
        $txt .= '   margin-bottom: 0;';
        $txt .= '   margin-<?php echo $left; ?>: auto;';
        $txt .= '   padding: 5px;   /** Keep a little space on the sides of the text */';
        $txt .= '   width: 350px;';

        $txt .= '   z-index: 1100;      /** If this is not kept at a high z-index, the jQueryUI modal dialogs (z-index: 1000) might hide this */';
        $txt .= '   text-align: center;';
        $txt .= '   display: inline;';
        $txt .= '   left: 0;';
        $txt .= '   right: 0;';
        $txt .= '   background-image: url(<?php echo $theme->getImgPath(\'ajax_clock_small.gif\');?>);';
        $txt .= '   background-repeat: no-repeat;';
        $txt .= '   background-position: 2%;';
        $txt .= '   border: 1px solid #e2b709;';
        $txt .= '}';

        /* additional styles */
        $txt .= '.ajax_notification {';
        $txt .= '    margin-top: 200px;';
        $txt .= '    background: #ffe57e;';
        $txt .= '    border-radius: 5px;';
        $txt .= '    -moz-border-radius: 5px;';
        $txt .= '    -webkit-border-radius: 5px;';
        $txt .= '    box-shadow: 0 5px 90px <?php echo $GLOBALS[\'cfg\'][\'Header\']; ?>;;';
        $txt .= '    -moz-box-shadow: 0 5px 90px <?php echo $GLOBALS[\'cfg\'][\'Header\']; ?>;;';
        $txt .= '    -webkit-box-shadow: 0 5px 90px <?php echo $GLOBALS[\'cfg\'][\'Header\']; ?>;;';
        $txt .= '}';

        $txt .= '#loading_parent {';
        $txt .= '    /** Need this parent to properly center the notification division */';
        $txt .= '    position: relative;';
        $txt .= '    width: 100%;';
        $txt .= ' }';
        /**
        * Export and Import styles
        */

        $txt .= '.export_table_list_container {';
        $txt .= '    display: inline-block;';
        $txt .= '    max-height: 20em;';
        $txt .= '    overflow-y: scroll;';
        $txt .= '}';

        $txt .= '.export_table_select th {';
        $txt .= '    text-align: center;';
        $txt .= '    vertical-align: middle;';
        $txt .= '}';

        $txt .= '.export_table_select .all {';
        $txt .= '    font-weight: bold;';
        $txt .= '    border-bottom: 1px solid black;';
        $txt .= '}';

        $txt .= '.export_structure, .export_data {';
        $txt .= '    text-align: center;';
        $txt .= '}';

        $txt .= '.export_table_name {';
        $txt .= '    vertical-align: middle;';
        $txt .= '}';

        $txt .= '.exportoptions h2 {';
        $txt .= '    word-wrap: break-word;';
        $txt .= '}';

        $txt .= '.exportoptions h3,';
        $txt .= '.importoptions h3 {';
        $txt .= '    border-bottom: 1px #999 solid;';
        $txt .= '    font-size: 110%;';
        $txt .= '}';

        $txt .= '.exportoptions ul,';
        $txt .= '.importoptions ul,';
        $txt .= '.format_specific_options ul {';
        $txt .= '    list-style-type: none;';
        $txt .= '    margin-bottom: 15px;';
        $txt .= '}';

        $txt .= '.exportoptions li,';
        $txt .= '.importoptions li {';
        $txt .= '    margin: 7px;';
        $txt .= '}';
        $txt .= '.exportoptions label,';
        $txt .= '.importoptions label,';
        $txt .= '.exportoptions p,';
        $txt .= '.importoptions p {';
        $txt .= '    margin: 5px;';
        $txt .= '    float: none;';
        $txt .= '}';

        $txt .= '#csv_options label.desc,';
        $txt .= '#ldi_options label.desc,';
        $txt .= '#latex_options label.desc,';
        $txt .= '#output label.desc {';
        $txt .= '    float: <?php echo $left; ?>;';
        $txt .= '    width: 15em;';
        $txt .= '}';

        $txt .= '.exportoptions,';
        $txt .= '.importoptions {';
        $txt .= '    margin: 20px 30px 30px;';
        $txt .= '    margin-<?php echo $left; ?>: 10px;';
        $txt .= '}';

        $txt .= '.exportoptions #buttonGo,';
        $txt .= '.importoptions #buttonGo {';
        $txt .= '    font-weight: bold;';
        $txt .= '    margin-<?php echo $left; ?>: 14px;';
        $txt .= '    border: 1px solid #aaa;';
        $txt .= '    padding: 5px 12px;';
        $txt .= '    color: #111;';
        $txt .= '    text-decoration: none;';

        $txt .= '    border-radius: 12px;';
        $txt .= '    -webkit-border-radius: 12px;';
        $txt .= '    -moz-border-radius: 12px;';

        $txt .= '    text-shadow: 0 1px 0 #fff;';

        $txt .= '    <?php echo $theme->getCssGradient(\'ffffff\', \'cccccc\'); ?>';
        $txt .= '    cursor: pointer;';
        $txt .= '}';

        $txt .= '.format_specific_options h3 {';
        $txt .= '    margin: 10px 0 0;';
        $txt .= '    margin-<?php echo $left; ?>: 10px;';
        $txt .= '    border: 0;';
        $txt .= '}';

        $txt .= '.format_specific_options {';
        $txt .= '    border: 1px solid #999;';
        $txt .= '    margin: 7px 0;';
        $txt .= '    padding: 3px;';
        $txt .= '}';

        $txt .= 'p.desc {';
        $txt .= '    margin: 5px;';
        $txt .= '}';

        /**
        * Export styles only
        */
        $txt .= 'select#db_select,';
        $txt .= 'select#table_select {';
        $txt .= '    width: 400px;';
        $txt .= '}';

        $txt .= '.export_sub_options {';
        $txt .= '    margin: 20px 0 0;';
        $txt .= '    margin-<?php echo $left; ?>: 30px;';
        $txt .= '}';

        $txt .= '.export_sub_options h4 {';
        $txt .= '    border-bottom: 1px #999 solid;';
        $txt .= '}';

        $txt .= '.export_sub_options li.subgroup {';
        $txt .= '    display: inline-block;';
        $txt .= '    margin-top: 0;';
        $txt .= '}';

        $txt .= '.export_sub_options li {';
        $txt .= '    margin-bottom: 0;';
        $txt .= '}';
        $txt .= '#export_refresh_form {';
        $txt .= '    margin-left: 20px;';
        $txt .= '}';
        $txt .= '#export_back_button {';
        $txt .= '    display: inline;';
        $txt .= '}';
        $txt .= '#output_quick_export {';
        $txt .= '    display: none;';
        $txt .= '}';
        /**
        * Import styles only
        */

        $txt .= '.importoptions #import_notification {';
        $txt .= '    margin: 10px 0;';
        $txt .= '    font-style: italic;';
        $txt .= '}';

        $txt .= 'input#input_import_file {';
        $txt .= '    margin: 5px;';
        $txt .= '}';

        $txt .= '.formelementrow {';
        $txt .= '    margin: 5px 0 5px 0;';
        $txt .= '}';

        $txt .= '#filterText {';
        $txt .= '    vertical-align: baseline;';
        $txt .= '}';

        $txt .= '#popup_background {';
        $txt .= '    display: none;';
        $txt .= '    position: fixed;';
        $txt .= '    _position: absolute; /* hack for IE6 */';
        $txt .= '    width: 100%;';
        $txt .= '    height: 100%;';
        $txt .= '    top: 0;';
        $txt .= '    <?php echo $left; ?>: 0;';
        $txt .= '    background: #000;';
        $txt .= '    z-index: 1000;';
        $txt .= '    overflow: hidden;';
        $txt .= '}';

        /**
        * Table structure styles
        */
        $txt .= '#fieldsForm ul.table-structure-actions {';
        $txt .= '    margin: 0;';
        $txt .= '    padding: 0;';
        $txt .= '    list-style: none;';
        $txt .= '}';
        $txt .= '#fieldsForm ul.table-structure-actions li {';
        $txt .= '    float: <?php echo $left; ?>;';
        $txt .= '    margin-<?php echo $right; ?>: 0.3em; /* same as padding of "table td" */';
        $txt .= '}';
        $txt .= '#fieldsForm ul.table-structure-actions .submenu li {';
        $txt .= '    padding: 0;';
        $txt .= '    margin: 0;';
        $txt .= '}';
        $txt .= '#fieldsForm ul.table-structure-actions .submenu li span {';
        $txt .= '    padding: 0.3em;';
        $txt .= '    margin: 0.1em;';
        $txt .= '}';
        $txt .= '#structure-action-links a {';
        $txt .= '    margin-<?php echo $right; ?>: 1em;';
        $txt .= '}';
        $txt .= '#addColumns input[type="radio"] {';
        $txt .= '    margin: 3px 0 0;';
        $txt .= '    margin-<?php echo $left; ?>: 1em;';
        $txt .= '}';
        /**
        * Indexes
        */
        $txt .= '#index_frm .index_info input[type="text"],';
        $txt .= '#index_frm .index_info select {';
        $txt .= '    width: 100%;';
        $txt .= '    margin: 0;';
        $txt .= '    box-sizing: border-box;';
        $txt .= '    -ms-box-sizing: border-box;';
        $txt .= '    -moz-box-sizing: border-box;';
        $txt .= '    -webkit-box-sizing: border-box;';
        $txt .= '}';

        $txt .= '#index_frm .index_info div {';
        $txt .= '    padding: .2em 0;';
        $txt .= '}';

        $txt .= '#index_frm .index_info .label {';
        $txt .= '    float: <?php echo $left; ?>;';
        $txt .= '    min-width: 12em;';
        $txt .= '}';

        $txt .= '#index_frm .slider {';
        $txt .= '    width: 10em;';
        $txt .= '    margin: .6em;';
        $txt .= '    float: <?php echo $left; ?>;';
        $txt .= '}';

        $txt .= '#index_frm .add_fields {';
        $txt .= '    float: <?php echo $left; ?>;';
        $txt .= '}';

        $txt .= '#index_frm .add_fields input {';
        $txt .= '    margin-<?php echo $left; ?>: 1em;';
        $txt .= '}';

        $txt .= '#index_frm input {';
        $txt .= '    margin: 0;';
        $txt .= '}';

        $txt .= '#index_frm td {';
        $txt .= '    vertical-align: middle;';
        $txt .= '}';

        $txt .= 'table#index_columns {';
        $txt .= '    width: 100%;';
        $txt .= '}';

        $txt .= 'table#index_columns select {';
        $txt .= '    width: 85%;';
        $txt .= '    float: <?php echo $left; ?>;';
        $txt .= '}';

        $txt .= '#move_columns_dialog div {';
        $txt .= '    padding: 1em;';
        $txt .= '}';

        $txt .= '#move_columns_dialog ul {';
        $txt .= '    list-style: none;';
        $txt .= '    margin: 0;';
        $txt .= '    padding: 0;';
        $txt .= '}';

        $txt .= '#move_columns_dialog li {';
        $txt .= '    background: <?php echo $GLOBALS[\'cfg\'][\'ThBackground\']; ?>;';
        $txt .= '    border: 1px solid #aaa;';
        $txt .= '    color: <?php echo $GLOBALS[\'cfg\'][\'ThColor\']; ?>;';
        $txt .= '    font-weight: bold;';
        $txt .= '    margin: .4em;';
        $txt .= '    padding: .2em;';
        $txt .= '    -webkit-border-radius: 2px;';
        $txt .= '    -moz-border-radius: 2px;';
        $txt .= '    border-radius: 2px;';
        $txt .= '}';

        /* config forms */
        $txt .= '.config-form ul.tabs {';
        $txt .= '    margin: 1.1em .2em 0;';
        $txt .= '    padding: 0 0 .3em 0;';
        $txt .= '    list-style: none;';
        $txt .= '    font-weight: bold;';
        $txt .= '}';

        $txt .= '.config-form ul.tabs li {';
        $txt .= '    float: <?php echo $left; ?>;';
        $txt .= '    margin-bottom: -1px;';
        $txt .= '}';

        $txt .= '.config-form ul.tabs li a {';
        $txt .= '    display: block;';
        $txt .= '    margin: .1em .2em 0;';
        $txt .= '    white-space: nowrap;';
        $txt .= '    text-decoration: none;';
        $txt .= '    border: 1px solid <?php echo $GLOBALS[\'cfg\'][\'BgTwo\']; ?>;';
        $txt .= '    border-bottom: 1px solid #aaa;';
        $txt .= '}';

        $txt .= '.config-form ul.tabs li a {';
        $txt .= '    padding: 7px 10px;';
        $txt .= '    -webkit-border-radius: 5px 5px 0 0;';
        $txt .= '    -moz-border-radius: 5px 5px 0 0;';
        $txt .= '    border-radius: 5px 5px 0 0;';
        $txt .= '    background: #f2f2f2;';
        $txt .= '    color: #555;';
        $txt .= '    text-shadow: 0 1px 0 #fff;';
        $txt .= '}';

        $txt .= '.config-form ul.tabs li a:hover,';
        $txt .= '.config-form ul.tabs li a:active {';
        $txt .= '    background: #e5e5e5;';
        $txt .= '}';

        $txt .= '.config-form ul.tabs li.active a {';
        $txt .= '    background-color: #fff;';
        $txt .= '    margin-top: 1px;';
        $txt .= '    color: #000;';
        $txt .= '    text-shadow: none;';
        $txt .= '    border-color: #aaa;';
        $txt .= '    border-bottom: 1px solid #fff;';
        $txt .= '}';

        $txt .= '.config-form fieldset {';
        $txt .= '    margin-top: 0;';
        $txt .= '    padding: 0;';
        $txt .= '    clear: both;';
        $txt .= '    -webkit-border-radius: 0;';
        $txt .= '    -moz-border-radius: 0;';
        $txt .= '    border-radius: 0;';
        $txt .= '}';

        $txt .= '.config-form legend {';
        $txt .= '    display: none;';
        $txt .= '}';

        $txt .= '.config-form fieldset p {';
        $txt .= '    margin: 0;';
        $txt .= '    padding: .5em;';
        $txt .= '    background: #fff;';
        $txt .= '    border-top: 0;';
        $txt .= '}';

        $txt .= '.config-form fieldset .errors { /* form error list */';
        $txt .= '    margin: 0 -2px 1em;';
        $txt .= '    padding: .5em 1.5em;';
        $txt .= '    background: #FBEAD9;';
        $txt .= '    border: 0 #C83838 solid;';
        $txt .= '    border-width: 1px 0;';
        $txt .= '    list-style: none;';
        $txt .= '    font-family: sans-serif;';
        $txt .= '    font-size: small;';
        $txt .= '}';

        $txt .= '.config-form fieldset .inline_errors { /* field error list */';
        $txt .= '    margin: .3em .3em .3em;';
        $txt .= '    margin-<?php echo $left; ?>: 0;';
        $txt .= '    padding: 0;';
        $txt .= '    list-style: none;';
        $txt .= '    color: #9A0000;';
        $txt .= '    font-size: small;';
        $txt .= '}';

        $txt .= '.config-form fieldset th {';
        $txt .= '    padding: .3em .3em .3em;';
        $txt .= '    padding-<?php echo $left; ?>: .5em;';
        $txt .= '    text-align: <?php echo $left; ?>;';
        $txt .= '    vertical-align: top;';
        $txt .= '    width: 40%;';
        $txt .= '    background: transparent;';
        $txt .= '    filter: none;';
        $txt .= '}';

        $txt .= '.config-form fieldset .doc,';
        $txt .= '.config-form fieldset .disabled-notice {';
        $txt .= '    margin-<?php echo $left; ?>: 1em;';
        $txt .= '}';

        $txt .= '.config-form fieldset .disabled-notice {';
        $txt .= '    font-size: 80%;';
        $txt .= '    text-transform: uppercase;';
        $txt .= '    color: #E00;';
        $txt .= '    cursor: help;';
        $txt .= '}';

        $txt .= '.config-form fieldset td {';
        $txt .= '    padding-top: .3em;';
        $txt .= '    padding-bottom: .3em;';
        $txt .= '    vertical-align: top;';
        $txt .= '}';

        $txt .= '.config-form fieldset th small {';
        $txt .= '    display: block;';
        $txt .= '    font-weight: normal;';
        $txt .= '    font-family: sans-serif;';
        $txt .= '    font-size: x-small;';
        $txt .= '    color: #444;';
        $txt .= '}';

        $txt .= '.config-form fieldset th,';
        $txt .= '.config-form fieldset td {';
        $txt .= '    border-top: 1px <?php echo $GLOBALS[\'cfg\'][\'BgTwo\']; ?> solid;';
        $txt .= '    border-<?php echo $right; ?>: none;';
        $txt .= '}';

        $txt .= 'fieldset .group-header th {';
        $txt .= '    background: <?php echo $GLOBALS[\'cfg\'][\'BgTwo\']; ?>;';
        $txt .= '}';

        $txt .= 'fieldset .group-header + tr th {';
        $txt .= '    padding-top: .6em;';
        $txt .= '}';

        $txt .= 'fieldset .group-field-1 th,';
        $txt .= 'fieldset .group-header-2 th {';
        $txt .= '    padding-<?php echo $left; ?>: 1.5em;';
        $txt .= '}';

        $txt .= 'fieldset .group-field-2 th,';
        $txt .= 'fieldset .group-header-3 th {';
        $txt .= '    padding-<?php echo $left; ?>: 3em;';
        $txt .= '}';

        $txt .= 'fieldset .group-field-3 th {';
        $txt .= '    padding-<?php echo $left; ?>: 4.5em;';
        $txt .= '}';

        $txt .= 'fieldset .disabled-field th,';
        $txt .= 'fieldset .disabled-field th small,';
        $txt .= 'fieldset .disabled-field td {';
        $txt .= '    color: #666;';
        $txt .= '    background-color: #ddd;';
        $txt .= '}';

        $txt .= '.config-form .lastrow {';
        $txt .= '    border-top: 1px #000 solid;';
        $txt .= '}';

        $txt .= '.config-form .lastrow {';
        $txt .= '    background: <?php echo $GLOBALS[\'cfg\'][\'ThBackground\']; ?>;';
        $txt .= '    padding: .5em;';
        $txt .= '    text-align: center;';
        $txt .= '}';

        $txt .= '.config-form .lastrow input {';
        $txt .= '    font-weight: bold;';
        $txt .= '}';

        /* form elements */

        $txt .= '.config-form span.checkbox {';
        $txt .= '    padding: 2px;';
        $txt .= '    display: inline-block;';
        $txt .= '}';

        $txt .= '.config-form .custom { /* customized field */';
        $txt .= '    background: #FFC;';
        $txt .= '}';

        $txt .= '.config-form span.checkbox.custom {';
        $txt .= '    padding: 1px;';
        $txt .= '    border: 1px #EDEC90 solid;';
        $txt .= '    background: #FFC;';
        $txt .= '}';

        $txt .= '.config-form .field-error {';
        $txt .= '    border-color: #A11 !important;';
        $txt .= '}';

        $txt .= '.config-form input[type="text"],';
        $txt .= '.config-form input[type="password"],';
        $txt .= '.config-form input[type="number"],';
        $txt .= '.config-form select,';
        $txt .= '.config-form textarea {';
        $txt .= '    border: 1px #A7A6AA solid;';
        $txt .= '    height: auto;';
        $txt .= '}';

        $txt .= '.config-form input[type="text"]:focus,';
        $txt .= '.config-form input[type="password"]:focus,';
        $txt .= '.config-form input[type="number"]:focus,';
        $txt .= '.config-form select:focus,';
        $txt .= '.config-form textarea:focus {';
        $txt .= '    border: 1px #6676FF solid;';
        $txt .= '    background: #F7FBFF;';
        $txt .= '}';

        $txt .= '.config-form .field-comment-mark {';
        $txt .= '    font-family: serif;';
        $txt .= '    color: #007;';
        $txt .= '    cursor: help;';
        $txt .= '    padding: 0 .2em;';
        $txt .= '    font-weight: bold;';
        $txt .= '    font-style: italic;';
        $txt .= '}';

        $txt .= '.config-form .field-comment-warning {';
        $txt .= '    color: #A00;';
        $txt .= '}';

        /* error list */
        $txt .= '.config-form dd {';
        $txt .= '    margin-<?php echo $left; ?>: .5em;';
        $txt .= '}';

        $txt .= '.config-form dd:before {';
        $txt .= '    content: "\25B8  ";';
        $txt .= '}';

        $txt .= '.click-hide-message {';
        $txt .= '    cursor: pointer;';
        $txt .= '}';

        $txt .= '.prefsmanage_opts {';
        $txt .= '    margin-<?php echo $left; ?>: 2em;';
        $txt .= '}';

        $txt .= '#prefs_autoload {';
        $txt .= '    margin-bottom: .5em;';
        $txt .= '    margin-left: .5em;';
        $txt .= '}';

        $txt .= '#placeholder .button {';
        $txt .= '    position: absolute;';
        $txt .= '    cursor: pointer;';
        $txt .= '}';

        $txt .= '#placeholder div.button {';
        $txt .= '    font-size: smaller;';
        $txt .= '    color: #999;';
        $txt .= '    background-color: #eee;';
        $txt .= '    padding: 2px;';
        $txt .= '}';

        $txt .= '.wrapper {';
        $txt .= '    float: <?php echo $left; ?>;';
        $txt .= '    margin-bottom: 1.5em;';
        $txt .= '}';
        $txt .= '.toggleButton {';
        $txt .= '    position: relative;';
        $txt .= '    cursor: pointer;';
        $txt .= '    font-size: .8em;';
        $txt .= '    text-align: center;';
        $txt .= '    line-height: 1.4em;';
        $txt .= '    height: 1.55em;';
        $txt .= '    overflow: hidden;';
        $txt .= '    border-right: .1em solid <?php echo $GLOBALS[\'cfg\'][\'Header\']; ?>;;';
        $txt .= '    border-left: .1em solid <?php echo $GLOBALS[\'cfg\'][\'Header\']; ?>;;';
        $txt .= '    -webkit-border-radius: .3em;';
        $txt .= '    -moz-border-radius: .3em;';
        $txt .= '    border-radius: .3em;';
        $txt .= '}';
        $txt .= '.toggleButton table,';
        $txt .= '.toggleButton td,';
        $txt .= '.toggleButton img {';
        $txt .= '    padding: 0;';
        $txt .= '    position: relative;';
        $txt .= '}';
        $txt .= '.toggleButton .container {';
        $txt .= '    position: absolute;';
        $txt .= '}';
        $txt .= '.toggleButton .container td,';
        $txt .= '.toggleButton .container tr {';
        $txt .= '    background-image: none;';
        $txt .= '    background: none !important;';
        $txt .= '}';
        $txt .= '.toggleButton .toggleOn {';
        $txt .= '    color: #fff;';
        $txt .= '    padding: 0 1em;';
        $txt .= '    text-shadow: 0 0 .2em #000;';
        $txt .= '}';
        $txt .= '.toggleButton .toggleOff {';
        $txt .= '    padding: 0 1em;';
        $txt .= '}';

        $txt .= '.doubleFieldset fieldset {';
        $txt .= '    width: 48%;';
        $txt .= '    float: <?php echo $left; ?>;';
        $txt .= '    padding: 0;';
        $txt .= '}';
        $txt .= '.doubleFieldset fieldset.left {';
        $txt .= '    margin-<?php echo $right; ?>: 1%;';
        $txt .= '}';
        $txt .= '.doubleFieldset fieldset.right {';
        $txt .= '    margin-<?php echo $left; ?>: 1%;';
        $txt .= '}';
        $txt .= '.doubleFieldset legend {';
        $txt .= '    margin-<?php echo $left; ?>: 1.5em;';
        $txt .= '}';
        $txt .= '.doubleFieldset div.wrap {';
        $txt .= '    padding: 1.5em;';
        $txt .= '}';

        $txt .= '#table_name_col_no_outer {';
        $txt .= '    margin-top: 45px;';
        $txt .= '}';

        $txt .= '#table_name_col_no {';
        $txt .= '    position: fixed;';
        $txt .= '    top: 55px;';
        $txt .= '    width: 100%;';
        $txt .= '    background: #ffffff;';
        $txt .= '}';

        $txt .= '#table_columns input[type="text"],';
        $txt .= '#table_columns input[type="password"],';
        $txt .= '#table_columns input[type="number"],';
        $txt .= '#table_columns select {';
        $txt .= '    width: 10em;';
        $txt .= '    box-sizing: border-box;';
        $txt .= '    -ms-box-sizing: border-box;';
        $txt .= '    -moz-box-sizing: border-box;';
        $txt .= '    -webkit-box-sizing: border-box;';
        $txt .= '}';

        $txt .= '#placeholder {';
        $txt .= '    position: relative;';
        $txt .= '    border: 1px solid #aaa;';
        $txt .= '    float: <?php echo $right; ?>;';
        $txt .= '    overflow: hidden;';
        $txt .= '    width: 450px;';
        $txt .= '    height: 300px;';
        $txt .= '}';

        $txt .= '#openlayersmap{';
        $txt .= '    width: 450px;';
        $txt .= '    height: 300px;';
        $txt .= '}';

        $txt .= '.placeholderDrag {';
        $txt .= '    cursor: move;';
        $txt .= '}';

        $txt .= '#placeholder .button {';
        $txt .= '    position: absolute;';
        $txt .= '}';

        $txt .= '#left_arrow {';
        $txt .= '    left: 8px;';
        $txt .= '    top: 26px;';
        $txt .= '}';

        $txt .= '#right_arrow {';
        $txt .= '    left: 26px;';
        $txt .= '    top: 26px;';
        $txt .= '}';

        $txt .= '#up_arrow {';
        $txt .= '    left: 17px;';
        $txt .= '    top: 8px;';
        $txt .= '}';

        $txt .= '#down_arrow {';
        $txt .= '    left: 17px;';
        $txt .= '    top: 44px;';
        $txt .= '}';

        $txt .= '#zoom_in {';
        $txt .= '    left: 17px;';
        $txt .= '    top: 67px;';
        $txt .= '}';

        $txt .= '#zoom_world {';
        $txt .= '    left: 17px;';
        $txt .= '    top: 85px;';
        $txt .= '}';

        $txt .= '#zoom_out {';
        $txt .= '    left: 17px;';
        $txt .= '    top: 103px;';
        $txt .= '}';

        $txt .= '.colborder {';
        $txt .= '    cursor: col-resize;';
        $txt .= '    height: 100%;';
        $txt .= '    margin-<?php echo $left; ?>: -6px;';
        $txt .= '    position: absolute;';
        $txt .= '    width: 5px;';
        $txt .= '}';

        $txt .= '.colborder_active {';
        $txt .= '    border-<?php echo $right; ?>: 2px solid #a44;';
        $txt .= '}';

        $txt .= '.pma_table td {';
        $txt .= '    position: static;';
        $txt .= '}';

        $txt .= '.pma_table th.draggable span,';
        $txt .= '.pma_table tbody td span {';
        $txt .= '    display: block;';
        $txt .= '    overflow: hidden;';
        $txt .= '}';

        $txt .= '.pma_table tbody td span code span {';
        $txt .= '    display: inline;';
        $txt .= '}';

        $txt .= '.pma_table th.draggable.right span {';
        $txt .= '    margin-<?php echo $right; ?>: 0px;';
        $txt .= '}';

        $txt .= '.pma_table th.draggable span {';
        $txt .= '    margin-<?php echo $right; ?>: 10px;';
        $txt .= '}';

        $txt .= '.modal-copy input {';
        $txt .= '    display: block;';
        $txt .= '    width: 100%;';
        $txt .= '    margin-top: 1.5em;';
        $txt .= '    padding: .3em 0;';
        $txt .= '}';

        $txt .= '.cRsz {';
        $txt .= '    position: absolute;';
        $txt .= '}';

        $txt .= '.cCpy {';
        $txt .= '    background: #333;';
        $txt .= '    color: #FFF;';
        $txt .= '    font-weight: bold;';
        $txt .= '    margin: .1em;';
        $txt .= '    padding: .3em;';
        $txt .= '    position: absolute;';
        $txt .= '    text-shadow: -1px -1px #000;';

        $txt .= '    -moz-box-shadow: 0 0 .7em #000;';
        $txt .= '    -webkit-box-shadow: 0 0 .7em #000;';
        $txt .= '    box-shadow: 0 0 .7em #000;';
        $txt .= '    -moz-border-radius: .3em;';
        $txt .= '    -webkit-border-radius: .3em;';
        $txt .= '    border-radius: .3em;';
        $txt .= '}';

        $txt .= '.cPointer {';
        $txt .= '    background: url(<?php echo $theme->getImgPath(\'col_pointer.png\');?>);';
        $txt .= '    height: 20px;';
        $txt .= '    margin-<?php echo $left; ?>: -5px;  /* must be minus half of its width */';
        $txt .= '    margin-top: -10px;';
        $txt .= '    position: absolute;';
        $txt .= '    width: 10px;';
        $txt .= '}';

        $txt .= '.tooltip {';
        $txt .= '    background: #333 !important;';
        $txt .= '    opacity: .8 !important;';
        $txt .= '    border: 1px solid #000 !important;';
        $txt .= '    -moz-border-radius: .3em !important;';
        $txt .= '    -webkit-border-radius: .3em !important;';
        $txt .= '    border-radius: .3em !important;';
        $txt .= '    text-shadow: -1px -1px #000 !important;';
        $txt .= '    font-size: .8em !important;';
        $txt .= '    font-weight: bold !important;';
        $txt .= '    padding: 1px 3px !important;';
        $txt .= '}';

        $txt .= '.tooltip * {';
        $txt .= '    background: none !important;';
        $txt .= '    color: #FFF !important;';
        $txt .= '}';

        $txt .= '.cDrop {';
        $txt .= '    left: 0;';
        $txt .= '    position: absolute;';
        $txt .= '    top: 0;';
        $txt .= '}';

        $txt .= '.coldrop {';
        $txt .= '    background: url(<?php echo $theme->getImgPath(\'col_drop.png\');?>);';
        $txt .= '    cursor: pointer;';
        $txt .= '    height: 16px;';
        $txt .= '    margin-<?php echo $left; ?>: .3em;';
        $txt .= '    margin-top: .3em;';
        $txt .= '    position: absolute;';
        $txt .= '    width: 16px;';
        $txt .= '}';

        $txt .= '.coldrop:hover,';
        $txt .= '.coldrop-hover {';
        $txt .= '    background-color: #999;';
        $txt .= '}';

        $txt .= '.cList {';
        $txt .= '    background: #EEE;';
        $txt .= '    border: solid 1px #999;';
        $txt .= '    position: absolute;';
        $txt .= '    -moz-box-shadow: 0 .2em .5em #333;';
        $txt .= '    -webkit-box-shadow: 0 .2em .5em #333;';
        $txt .= '    box-shadow: 0 .2em .5em #333;';
        $txt .= '}';

        $txt .= '.cList .lDiv div {';
        $txt .= '    padding: .2em .5em .2em;';
        $txt .= '    padding-<?php echo $left; ?>: .2em;';
        $txt .= '}';

        $txt .= '.cList .lDiv div:hover {';
        $txt .= '    background: #DDD;';
        $txt .= '    cursor: pointer;';
        $txt .= '}';

        $txt .= '.cList .lDiv div input {';
        $txt .= '    cursor: pointer;';
        $txt .= '}';

        $txt .= '.showAllColBtn {';
        $txt .= '    border-bottom: solid 1px #999;';
        $txt .= '    border-top: solid 1px #999;';
        $txt .= '    cursor: pointer;';
        $txt .= '    font-size: .9em;';
        $txt .= '    font-weight: bold;';
        $txt .= '    padding: .35em 1em;';
        $txt .= '    text-align: center;';
        $txt .= '}';

        $txt .= '.showAllColBtn:hover {';
        $txt .= '    background: #DDD;';
        $txt .= '}';

        $txt .= '.turnOffSelect {';
        $txt .= '  -moz-user-select: none;';
        $txt .= '  -khtml-user-select: none;';
        $txt .= '  -webkit-user-select: none;';
        $txt .= '  user-select: none;';
        $txt .= '}';

        $txt .= '.navigation {';
        $txt .= '    margin: .8em 0;';

        $txt .= '    border-radius: 5px;';
        $txt .= '    -webkit-border-radius: 5px;';
        $txt .= '    -moz-border-radius: 5px;';

        $txt .= '    <?php echo $theme->getCssGradient(\'eeeeee\', \'cccccc\'); ?>';
        $txt .= '}';

        $txt .= '.navigation td {';
        $txt .= '    margin: 0;';
        $txt .= '    padding: 0;';
        $txt .= '    vertical-align: middle;';
        $txt .= '    white-space: nowrap;';
        $txt .= '}';

        $txt .= '.navigation_separator {';
        $txt .= '    color: #999;';
        $txt .= '    display: inline-block;';
        $txt .= '    font-size: 1.5em;';
        $txt .= '    text-align: center;';
        $txt .= '    height: 1.4em;';
        $txt .= '    width: 1.2em;';
        $txt .= '    text-shadow: 1px 0 #FFF;';
        $txt .= '}';

        $txt .= '.navigation input[type=submit] {';
        $txt .= '    background: none;';
        $txt .= '    border: 0;';
        $txt .= '    filter: none;';
        $txt .= '    margin: 0;';
        $txt .= '    padding: .8em .5em;';

        $txt .= '    border-radius: 0;';
        $txt .= '    -webkit-border-radius: 0;';
        $txt .= '    -moz-border-radius: 0;';
        $txt .= '}';

        $txt .= '.navigation input[type=submit]:hover,';
        $txt .= '.navigation input.edit_mode_active {';
        $txt .= '    color: #fff;';
        $txt .= '    cursor: pointer;';
        $txt .= '    text-shadow: none;';

        $txt .= '    <?php echo $theme->getCssGradient(\'333333\', \'555555\'); ?>';
        $txt .= '}';

        $txt .= '.navigation select {';
        $txt .= '    margin: 0 .8em;';
        $txt .= '}';

        $txt .= '.cEdit {';
        $txt .= '    margin: 0;';
        $txt .= '    padding: 0;';
        $txt .= '    position: absolute;';
        $txt .= '}';

        $txt .= '.cEdit input[type=text] {';
        $txt .= '    background: #FFF;';
        $txt .= '    height: 100%;';
        $txt .= '    margin: 0;';
        $txt .= '    padding: 0;';
        $txt .= '}';

        $txt .= '.cEdit .edit_area {';
        $txt .= '    background: #FFF;';
        $txt .= '    border: 1px solid #999;';
        $txt .= '    min-width: 10em;';
        $txt .= '    padding: .3em .5em;';
        $txt .= '}';

        $txt .= '.cEdit .edit_area select,';
        $txt .= '.cEdit .edit_area textarea {';
        $txt .= '    width: 97%;';
        $txt .= '}';

        $txt .= '.cEdit .cell_edit_hint {';
        $txt .= '    color: #555;';
        $txt .= '    font-size: .8em;';
        $txt .= '    margin: .3em .2em;';
        $txt .= '}';

        $txt .= '.cEdit .edit_box {';
        $txt .= '    overflow-x: hidden;';
        $txt .= '    overflow-y: scroll;';
        $txt .= '    padding: 0;';
        $txt .= '    margin: 0;';
        $txt .= '}';

        $txt .= '.cEdit .edit_box_posting {';
        $txt .= '    background: #FFF url(<?php echo $theme->getImgPath(\'ajax_clock_small.gif\');?>) no-repeat right center;';
        $txt .= '    padding-<?php echo $right; ?>: 1.5em;';
        $txt .= '}';

        $txt .= '.cEdit .edit_area_loading {';
        $txt .= '    background: #FFF url(<?php echo $theme->getImgPath(\'ajax_clock_small.gif\');?>) no-repeat center;';
        $txt .= '    height: 10em;';
        $txt .= '}';

        $txt .= '.cEdit .goto_link {';
        $txt .= '    background: #EEE;';
        $txt .= '    color: #555;';
        $txt .= '    padding: .2em .3em;';
        $txt .= '}';

        $txt .= '.saving_edited_data {';
        $txt .= '    background: url(<?php echo $theme->getImgPath(\'ajax_clock_small.gif\');?>) no-repeat left;';
        $txt .= '    padding-<?php echo $left; ?>: 20px;';
        $txt .= '}';

        $txt .= '.relationalTable td {';
        $txt .= '    vertical-align: top;';
        $txt .= '}';

        $txt .= '.relationalTable select {';
        $txt .= '    width: 125px;';
        $txt .= '    margin-right: 5px;';
        $txt .= '}';

        /* css for timepicker */
        $txt .= '.ui-timepicker-div .ui-widget-header { margin-bottom: 8px; }';
        $txt .= '.ui-timepicker-div dl { text-align: <?php echo $left; ?>; }';
        $txt .= '.ui-timepicker-div dl dt { height: 25px; margin-bottom: -25px; }';
        $txt .= '.ui-timepicker-div dl dd { margin: 0 10px 10px 85px; }';
        $txt .= '.ui-timepicker-div td { font-size: 90%; }';
        $txt .= '.ui-tpicker-grid-label { background: none; border: none; margin: 0; padding: 0; }';
        $txt .= '.ui-timepicker-rtl { direction: rtl; }';
        $txt .= '.ui-timepicker-rtl dl { text-align: right; }';
        $txt .= '.ui-timepicker-rtl dl dd { margin: 0 65px 10px 10px; }';

        $txt .= 'input.btn {';
        $txt .= '    color: #333;';
        $txt .= '    background-color: #D0DCE0;';
        $txt .= '}';

        $txt .= 'body .ui-widget {';
        $txt .= '    font-size: 1em;';
        $txt .= '}';
        $txt .= '.ui-dialog fieldset legend a {';
        $txt .= '    color: #235A81;';
        $txt .= '}';
        $txt .= '.ui-draggable {';
        $txt .= '    z-index: 801;';
        $txt .= '}';
        /* over-riding jqplot-yaxis class */
        $txt .= '.jqplot-yaxis {';
        $txt .= '    left:0 !important;';
        $txt .= '    min-width:25px;';
        $txt .= '    width:auto;';
        $txt .= '}';
        $txt .= '.jqplot-axis {';
        $txt .= '    overflow:hidden;';
        $txt .= '}';
        $txt .= '.report-data {';
        $txt .= '    height:13em;';
        $txt .= '    overflow:scroll;';
        $txt .= '    width:570px;';
        $txt .= '    border: solid 1px;';
        $txt .= '    background: white;';
        $txt .= '    padding: 2px;';
        $txt .= '}';
        $txt .= '.report-description {';
        $txt .= '    height:10em;';
        $txt .= '    width:570px;';
        $txt .= '}';
        $txt .= 'div#page_content div#tableslistcontainer table.data {';
        $txt .= '    border-top: 0.1px solid #EEEEEE;';
        $txt .= '}';
        $txt .= 'div#page_content div#tableslistcontainer, div#page_content div.notice, div#page_content div.result_query {';
        $txt .= '    margin-top: 1em;';
        $txt .= '}';
        $txt .= 'table.show_create {';
        $txt .= '    margin-top: 1em;';
        $txt .= '}';
        $txt .= 'table.show_create td {';
        $txt .= '    border-right: 1px solid #bbb;';
        $txt .= '}';
        $txt .= '#alias_modal table {';
        $txt .= '    width: 100%;';
        $txt .= '}';
        $txt .= '#alias_modal label {';
        $txt .= '    font-weight: bold;';
        $txt .= '}';
        $txt .= '.ui-dialog {';
        $txt .= '    position: fixed;';
        $txt .= '}';
        $txt .= '.small_font {';
        $txt .= '    font-size: smaller;';
        $txt .= '}';
        /* Console styles */
        $txt .= '#pma_console_container {';
        $txt .= '    width: 100%;';
        $txt .= '    position: fixed;';
        $txt .= '    bottom: 0;';
        $txt .= '    <?php echo $left; ?>: 0;';
        $txt .= '    z-index: 100;';
        $txt .= '}';
        $txt .= '#pma_console {';
        $txt .= '    position: relative;';
        $txt .= '    margin-<?php echo $left; ?>: 240px;';
        $txt .= '}';
        $txt .= '#pma_console .templates {';
        $txt .= '    display: none;';
        $txt .= '}';
        $txt .= '#pma_console .mid_text,';
        $txt .= '#pma_console .toolbar span {';
        $txt .= '    vertical-align: middle;';
        $txt .= '}';
        $txt .= '#pma_console .toolbar {';
        $txt .= '    position: relative;';
        $txt .= '    background: #ccc;';
        $txt .= '    border-top: solid 1px #aaa;';
        $txt .= '    cursor: n-resize;';
        $txt .= '}';
        $txt .= '#pma_console .toolbar.collapsed:not(:hover) {';
        $txt .= '    display: inline-block;';
        $txt .= '    border-top-<?php echo $right; ?>-radius: 3px;';
        $txt .= '    border-<?php echo $right; ?>: solid 1px #aaa;';
        $txt .= '}';
        $txt .= '#pma_console .toolbar.collapsed {';
        $txt .= '    cursor: default;';
        $txt .= '}';
        $txt .= '#pma_console .toolbar.collapsed>.button {';
        $txt .= '    display: none;';
        $txt .= '}';
        $txt .= '#pma_console .message span.text,';
        $txt .= '#pma_console .message span.action,';
        $txt .= '#pma_console .toolbar .button,';
        $txt .= '#pma_console .toolbar .text,';
        $txt .= '#pma_console .switch_button {';
        $txt .= '    padding: 0 3px;';
        $txt .= '    display: inline-block;';
        $txt .= '}';
        $txt .= '#pma_console .message span.action,';
        $txt .= '#pma_console .toolbar .button,';
        $txt .= '#pma_console .switch_button {';
        $txt .= '    cursor: pointer;';
        $txt .= '}';
        $txt .= '#pma_console .message span.action:hover,';
        $txt .= '#pma_console .toolbar .button:hover,';
        $txt .= '#pma_console .switch_button:hover,';
        $txt .= '#pma_console .toolbar .button.active {';
        $txt .= '    background: #ddd;';
        $txt .= '}';
        $txt .= '#pma_console .toolbar .text {';
        $txt .= '    font-weight: bold;';
        $txt .= '}';
        $txt .= '#pma_console .toolbar .button,';
        $txt .= '#pma_console .toolbar .text {';
        $txt .= '    margin-<?php echo $right; ?>: .4em;';
        $txt .= '}';
        $txt .= '#pma_console .toolbar .button,';
        $txt .= '#pma_console .toolbar .text {';
        $txt .= '    float: <?php echo $right; ?>;';
        $txt .= '}';
        $txt .= '#pma_console .content {';
        $txt .= '    overflow-x: hidden;';
        $txt .= '    overflow-y: auto;';
        $txt .= '    margin-bottom: -65px;';
        $txt .= '    border-top: solid 1px #aaa;';
        $txt .= '    background: #fff;';
        $txt .= '    padding-top: .4em;';
        $txt .= '}';
        $txt .= '#pma_console .content.console_dark_theme {';
        $txt .= '    background: #000;';
        $txt .= '    color: #fff;';
        $txt .= '}';
        $txt .= '#pma_console .content.console_dark_theme .CodeMirror-wrap {';
        $txt .= '    background: #000;';
        $txt .= '    color: #fff;';
        $txt .= '}';
        $txt .= '#pma_console .content.console_dark_theme .action_content {';
        $txt .= '    color: #000;';
        $txt .= '}';
        $txt .= '#pma_console .content.console_dark_theme .message {';
        $txt .= '    border-color: #373B41;';
        $txt .= '}';
        $txt .= '#pma_console .content.console_dark_theme .CodeMirror-cursor {';
        $txt .= '    border-color: #fff;';
        $txt .= '}';
        $txt .= '#pma_console .content.console_dark_theme .cm-keyword {';
        $txt .= '    color: #de935f;';
        $txt .= '}';
        $txt .= '#pma_console .message,';
        $txt .= '#pma_console .query_input {';
        $txt .= '    position: relative;';
        $txt .= '    font-family: Monaco, Consolas, monospace;';
        $txt .= '    cursor: text;';
        $txt .= '    margin: 0 10px .2em 1.4em;';
        $txt .= '}';
        $txt .= '#pma_console .message {';
        $txt .= '    border-bottom: solid 1px #ccc;';
        $txt .= '    padding-bottom: .2em;';
        $txt .= '}';
        $txt .= '#pma_console .message.expanded>.action_content {';
        $txt .= '    position: relative;';
        $txt .= '}';
        $txt .= '#pma_console .message:before,';
        $txt .= '#pma_console .query_input:before {';
        $txt .= '    left: -0.7em;';
        $txt .= '    position: absolute;';
        $txt .= '    content: ">";';
        $txt .= '}';
        $txt .= '#pma_console .query_input:before {';
        $txt .= '    top: -2px;';
        $txt .= '}';
        $txt .= '#pma_console .query_input textarea {';
        $txt .= '    width: 100%;';
        $txt .= '    height: 4em;';
        $txt .= '    resize: vertical;';
        $txt .= '}';
        $txt .= '#pma_console .message:hover:before {';
        $txt .= '    color: #7cf;';
        $txt .= '    font-weight: bold;';
        $txt .= '}';
        $txt .= '#pma_console .message.expanded:before {';
        $txt .= '    content: "]";';
        $txt .= '}';
        $txt .= '#pma_console .message.welcome:before {';
        $txt .= '    display: none;';
        $txt .= '}';
        $txt .= '#pma_console .message.failed:before,';
        $txt .= '#pma_console .message.failed.expanded:before,';
        $txt .= '#pma_console .message.failed:hover:before {';
        $txt .= '    content: "=";';
        $txt .= '    color: #944;';
        $txt .= '}';
        $txt .= '#pma_console .message.pending:before {';
        $txt .= '    opacity: .3;';
        $txt .= '}';
        $txt .= '#pma_console .message.collapsed>.query {';
        $txt .= '    white-space: nowrap;';
        $txt .= '    text-overflow: ellipsis;';
        $txt .= '    overflow: hidden;';
        $txt .= '}';
        $txt .= '#pma_console .message.expanded>.query {';
        $txt .= '    display: block;';
        $txt .= '    white-space: pre;';
        $txt .= '    word-wrap: break-word;';
        $txt .= '}';
        $txt .= '#pma_console .message .text.targetdb,';
        $txt .= '#pma_console .message.collapsed .action.collapse,';
        $txt .= '#pma_console .message.expanded .action.expand,';
        $txt .= '#pma_console .message .action.requery,';
        $txt .= '#pma_console .message .action.profiling,';
        $txt .= '#pma_console .message .action.explain,';
        $txt .= '#pma_console .message .action.bookmark {';
        $txt .= '    display: none;';
        $txt .= '}';
        $txt .= '#pma_console .message.select .action.profiling,';
        $txt .= '#pma_console .message.select .action.explain,';
        $txt .= '#pma_console .message.history .text.targetdb,';
        $txt .= '#pma_console .message.successed .text.targetdb,';
        $txt .= '#pma_console .message.history .action.requery,';
        $txt .= '#pma_console .message.history .action.bookmark,';
        $txt .= '#pma_console .message.bookmark .action.requery,';
        $txt .= '#pma_console .message.bookmark .action.bookmark,';
        $txt .= '#pma_console .message.successed .action.requery,';
        $txt .= '#pma_console .message.successed .action.bookmark {';
        $txt .= '    display: inline-block;';
        $txt .= '}';
        $txt .= '#pma_console .message .action_content {';
        $txt .= '    position: absolute;';
        $txt .= '    bottom: 100%;';
        $txt .= '    background: #ccc;';
        $txt .= '    border: solid 1px #aaa;';
        $txt .= '    border-top-<?php echo $left; ?>-radius: 3px;';
        $txt .= '}';
        $txt .= 'html.ie8 #pma_console .message .action_content {';
        $txt .= '    position: relative!important;';
        $txt .= '}';
        $txt .= '#pma_console .message.bookmark .text.targetdb,';
        $txt .= '#pma_console .message .text.query_time {';
        $txt .= '    margin: 0;';
        $txt .= '    display: inline-block;';
        $txt .= '}';
        $txt .= '#pma_console .message.failed .text.query_time,';
        $txt .= '#pma_console .message .text.failed {';
        $txt .= '    display: none;';
        $txt .= '}';
        $txt .= '#pma_console .message.failed .text.failed {';
        $txt .= '    display: inline-block;';
        $txt .= '}';
        $txt .= '#pma_console .message .text {';
        $txt .= '    background: #fff;';
        $txt .= '}';
        $txt .= '#pma_console .message.collapsed>.action_content {';
        $txt .= '    display: none;';
        $txt .= '}';
        $txt .= '#pma_console .message.collapsed:hover>.action_content {';
        $txt .= '    display: block;';
        $txt .= '}';
        $txt .= '#pma_console .message .bookmark_label {';
        $txt .= '    padding: 0 4px;';
        $txt .= '    top: 0;';
        $txt .= '    background: #369;';
        $txt .= '    color: #fff;';
        $txt .= '    border-radius: 3px;';
        $txt .= '}';
        $txt .= '#pma_console .message .bookmark_label.shared {';
        $txt .= '    background: #396;';
        $txt .= '}';
        $txt .= '#pma_console .message.expanded .bookmark_label {';
        $txt .= '    border-top-left-radius: 0;';
        $txt .= '    border-top-right-radius: 0;';
        $txt .= '}';
        $txt .= '#pma_console .query_input {';
        $txt .= '    position: relative;';
        $txt .= '}';
        $txt .= '#pma_console .mid_layer {';
        $txt .= '    height: 100%;';
        $txt .= '    width: 100%;';
        $txt .= '    position: absolute;';
        $txt .= '    top: 0;';
        /* For support IE8, this layer doesn't use filter:opacity or opacity
        js code will fade this layer opacity to 0.18(using animation) */
        $txt .= '    background: #666;';
        $txt .= '    display: none;';
        $txt .= '    cursor: pointer;';
        $txt .= '    z-index: 200;';
        $txt .= '}';
        $txt .= '#pma_console .card {';
        $txt .= '    position: absolute;';
        $txt .= '    width: 94%;';
        $txt .= '    height: 100%;';
        $txt .= '    min-height: 48px;';
        $txt .= '    <?php echo $left; ?>: 100%;';
        $txt .= '    top: 0;';
        $txt .= '    border-<?php echo $left; ?>: solid 1px #999;';
        $txt .= '    z-index: 300;';
        $txt .= '    transition: <?php echo $left; ?> 0.2s;';
        $txt .= '    -ms-transition: <?php echo $left; ?> 0.2s;';
        $txt .= '    -webkit-transition: <?php echo $left; ?> 0.2s;';
        $txt .= '    -moz-transition: <?php echo $left; ?> 0.2s;';
        $txt .= '}';
        $txt .= '#pma_console .card.show {';
        $txt .= '    <?php echo $left; ?>: 6%;';
        $txt .= '    box-shadow: -2px 1px 4px -1px #999;';
        $txt .= '}';
        $txt .= 'html.ie7 #pma_console .query_input {';
        $txt .= '    display: none;';
        $txt .= '}';
        $txt .= '#pma_bookmarks .content.add_bookmark,';
        $txt .= '#pma_console_options .content {';
        $txt .= '    padding: 4px 6px;';
        $txt .= '}';
        $txt .= '#pma_bookmarks .content.add_bookmark .options {';
        $txt .= '    margin-<?php echo $left; ?>: 1.4em;';
        $txt .= '    padding-bottom: .4em;';
        $txt .= '    margin-bottom: .4em;';
        $txt .= '    border-bottom: solid 1px #ccc;';
        $txt .= '}';
        $txt .= '#pma_bookmarks .content.add_bookmark .options button {';
        $txt .= '    margin: 0 7px;';
        $txt .= '    vertical-align: bottom;';
        $txt .= '}';
        $txt .= '#pma_bookmarks .content.add_bookmark input[type=text] {';
        $txt .= '    margin: 0;';
        $txt .= '    padding: 2px 4px;';
        $txt .= '}';
        $txt .= '#pma_console .button.hide,';
        $txt .= '#pma_console .message span.text.hide {';
        $txt .= '    display: none;';
        $txt .= '}';
        $txt .= '#debug_console.grouped .ungroup_queries,';
        $txt .= '#debug_console.ungrouped .group_queries {';
        $txt .= '    display: inline-block;';
        $txt .= '}';
        $txt .= '#debug_console.ungrouped .ungroup_queries,';
        $txt .= '#debug_console.ungrouped .sort_count,';
        $txt .= '#debug_console.grouped .group_queries {';
        $txt .= '    display: none;';
        $txt .= '}';
        $txt .= '#debug_console .count {';
        $txt .= '    margin-right: 8px;';
        $txt .= '}';
        $txt .= '#debug_console .show_trace .trace,';
        $txt .= '#debug_console .show_args .args {';
        $txt .= '    display: block;';
        $txt .= '}';
        $txt .= '#debug_console .hide_trace .trace,';
        $txt .= '#debug_console .hide_args .args,';
        $txt .= '#debug_console .show_trace .action.dbg_show_trace,';
        $txt .= '#debug_console .hide_trace .action.dbg_hide_trace,';
        $txt .= '#debug_console .traceStep.hide_args .action.dbg_hide_args,';
        $txt .= '#debug_console .traceStep.show_args .action.dbg_show_args {';
        $txt .= '    display: none;';
        $txt .= '}';
        $txt .= '#debug_console .traceStep:after,';
        $txt .= '#debug_console .trace.welcome:after,';
        $txt .= '#debug_console .debug>.welcome:after {';
        $txt .= '    content: "";';
        $txt .= '    display: table;';
        $txt .= '    clear: both;';
        $txt .= '}';
        $txt .= '#debug_console .debug_summary {';
        $txt .= '    float: left;';
        $txt .= '}';
        $txt .= '#debug_console .trace.welcome .time {';
        $txt .= '    float: right;';
        $txt .= '}';
        $txt .= '#debug_console .traceStep .file,';
        $txt .= '#debug_console .script_name {';
        $txt .= '    float: right;';
        $txt .= '}';
        $txt .= '#debug_console .traceStep .args pre {';
        $txt .= '    margin: 0;';
        $txt .= '}';
        /* Code mirror console style*/
        $txt .= '.cm-s-pma .CodeMirror-code pre,';
        $txt .= '.cm-s-pma .CodeMirror-code {';
        $txt .= '    font-family: Monaco, Consolas, monospace;';
        $txt .= '}';
        $txt .= '.cm-s-pma .CodeMirror-measure>pre,';
        $txt .= '.cm-s-pma .CodeMirror-code>pre,';
        $txt .= '.cm-s-pma .CodeMirror-lines {';
        $txt .= '    padding: 0;';
        $txt .= '}';
        $txt .= '.cm-s-pma.CodeMirror {';
        $txt .= '    resize: none;';
        $txt .= '    height: auto;';
        $txt .= '    width: 100%;';
        $txt .= '    min-height: initial;';
        $txt .= '    max-height: initial;';
        $txt .= '}';
        $txt .= '.cm-s-pma .CodeMirror-scroll {';
        $txt .= '    cursor: text;';
        $txt .= '}';
        /* PMA drop-improt style */
        $txt .= '.pma_drop_handler {';
        $txt .= '    display: none;';
        $txt .= '    position: fixed;';
        $txt .= '    top: 0;';
        $txt .= '    left: 0;';
        $txt .= '    width: 100%;';
        $txt .= '    background: rgba(0, 0, 0, 0.6);';
        $txt .= '    height: 100%;';
        $txt .= '    z-index: 999;';
        $txt .= '    color: white;';
        $txt .= '    font-size: 30pt;';
        $txt .= '    text-align: center;';
        $txt .= '    padding-top: 20%;';
        $txt .= '}';
        $txt .= '.pma_sql_import_status {';
        $txt .= '    display: none;';
        $txt .= '    position: fixed;';
        $txt .= '    bottom: 0;';
        $txt .= '    right: 25px;';
        $txt .= '    width: 400px;';
        $txt .= '    border: 1px solid #999;';
        $txt .= '    background: <?php echo $GLOBALS[\'cfg\'][\'GroupBg\']; ?>;';
        $txt .= '    -moz-border-radius: 4px;';
        $txt .= '    -webkit-border-radius: 4px;';
        $txt .= '    border-radius: 4px;';
        $txt .= '    -moz-box-shadow: <?php echo $GLOBALS[\'text_dir\'] === \'rtl\' ? \'-\' : \'\'; ?>2px 2px 5px #ccc;';
        $txt .= '    -webkit-box-shadow: <?php echo $GLOBALS[\'text_dir\'] === \'rtl\' ? \'-\' : \'\'; ?>2px 2px 5px #ccc;';
        $txt .= '    box-shadow: <?php echo $GLOBALS[\'text_dir\'] === \'rtl\' ? \'-\' : \'\'; ?>2px 2px 5px #ccc;';
        $txt .= '}';
        $txt .= '.pma_sql_import_status h2,';
        $txt .= '.pma_drop_result h2 {';
        $txt .= '    background-color: #bbb;';
        $txt .= '    padding: .1em .3em;';
        $txt .= '    margin-top: 0;';
        $txt .= '    margin-bottom: 0;';
        $txt .= '    color: #fff;';
        $txt .= '    font-size: 1.6em;';
        $txt .= '    font-weight: normal;';
        $txt .= '    text-shadow: 0 1px 0 #777;';
        $txt .= '    -moz-box-shadow: <?php echo $GLOBALS[\'text_dir\'] === \'rtl\' ? \'-\' : \'-\'; ?>1px 1px 15px #999 inset;';
        $txt .= '    -webkit-box-shadow: <?php echo $GLOBALS[\'text_dir\'] === \'rtl\' ? \'-\' : \'-\'; ?>1px 1px 15px #999 inset;';
        $txt .= '    box-shadow: <?php echo $GLOBALS[\'text_dir\'] === \'rtl\' ? \'-\' : \'-\'; ?>1px 1px 15px #999 inset;';
        $txt .= '}';
        $txt .= '.pma_sql_import_status div {';
        $txt .= '    height: 270px;';
        $txt .= '    overflow-y:auto;';
        $txt .= '    overflow-x:hidden;';
        $txt .= '    list-style-type: none;';
        $txt .= '}';
        $txt .= '.pma_sql_import_status div li {';
        $txt .= '    padding: 8px 10px;';
        $txt .= '    border-bottom: 1px solid #bbb;';
        $txt .= '    color: rgb(148, 14, 14);';
        $txt .= '    background: white;';
        $txt .= '}';
        $txt .= '.pma_sql_import_status div li .filesize {';
        $txt .= '    float: right;';
        $txt .= '}';
        $txt .= '.pma_sql_import_status h2 .minimize {';
        $txt .= '    float: right;';
        $txt .= '    margin-right: 5px;';
        $txt .= '    padding: 0 10px;';
        $txt .= '}';
        $txt .= '.pma_sql_import_status h2 .close {';
        $txt .= '    float: right;';
        $txt .= '    margin-right: 5px;';
        $txt .= '    padding: 0 10px;';
        $txt .= '    display: none;';
        $txt .= '}';
        $txt .= '.pma_sql_import_status h2 .minimize:hover,';
        $txt .= '.pma_sql_import_status h2 .close:hover,';
        $txt .= '.pma_drop_result h2 .close:hover {';
        $txt .= '    background: rgba(155, 149, 149, 0.78);';
        $txt .= '    cursor: pointer;';
        $txt .= '}';
        $txt .= '.pma_drop_file_status {';
        $txt .= '    color: #235a81;';
        $txt .= '}';
        $txt .= '.pma_drop_file_status span.underline:hover {';
        $txt .= '    cursor: pointer;';
        $txt .= '    text-decoration: underline;';
        $txt .= '}';
        $txt .= '.pma_drop_result {';
        $txt .= '    position: fixed;';
        $txt .= '    top: 10%;';
        $txt .= '    left: 20%;';
        $txt .= '    width: 60%;';
        $txt .= '    background: white;';
        $txt .= '    min-height: 300px;';
        $txt .= '    z-index: 800;';
        $txt .= '    -webkit-box-shadow: 0 0 15px #999;';
        $txt .= '    border-radius: 10px;';
        $txt .= '    cursor: move;';
        $txt .= '}';
        $txt .= '.pma_drop_result h2 .close {';
        $txt .= '    float: right;';
        $txt .= '    margin-right: 5px;';
        $txt .= '    padding: 0 10px;';
        $txt .= '}';
        $txt .= '.dependencies_box {';
        $txt .= '    background-color: white;';
        $txt .= '    border: 3px ridge black;';
        $txt .= '}';
        $txt .= '#composite_index_list {';
        $txt .= '    list-style-type: none;';
        $txt .= '    list-style-position: inside;';
        $txt .= '}';
        $txt .= 'span.drag_icon {';
        $txt .= '    display: inline-block;';
        $txt .= '    background-image: url(\'<?php echo $theme->getImgPath(\'s_sortable.png\');?>\');';
        $txt .= '    background-position: center center;';
        $txt .= '    background-repeat: no-repeat;';
        $txt .= '    width: 1em;';
        $txt .= '    height: 3em;';
        $txt .= '    cursor: move;';
        $txt .= '}';
        $txt .= '.topmargin {';
        $txt .= '    margin-top: 1em;';
        $txt .= '}';
        $txt .= 'meter[value="1"]::-webkit-meter-optimum-value {';
        $txt .= '    background: linear-gradient(white 3%, #E32929 5%, transparent 10%, #E32929);';
        $txt .= '}';
        $txt .= 'meter[value="2"]::-webkit-meter-optimum-value {';
        $txt .= '    background: linear-gradient(white 3%, #FF6600 5%, transparent 10%, #FF6600);';
        $txt .= '}';
        $txt .= 'meter[value="3"]::-webkit-meter-optimum-value {';
        $txt .= '    background: linear-gradient(white 3%, #FFD700 5%, transparent 10%, #FFD700);';
        $txt .= '}';
        /* styles for sortable tables created with tablesorter jquery plugin */
        $txt .= 'th.header {';
        $txt .= '    cursor: pointer;';
        $txt .= '    color: #235a81;';
        $txt .= '}';
        $txt .= 'th.header:hover {';
        $txt .= '    text-decoration: underline;';
        $txt .= '}';
        $txt .= 'th.header .sorticon {';
        $txt .= '    width: 16px;';
        $txt .= '    height: 16px;';
        $txt .= '    background-repeat: no-repeat;';
        $txt .= '    background-position: right center;';
        $txt .= '    display: inline-table;';
        $txt .= '    vertical-align: middle;';
        $txt .= '    float: right;';
        $txt .= '}';
        $txt .= 'th.headerSortUp .sorticon, th.headerSortDown:hover .sorticon {';
        $txt .= '    background-image: url(<?php echo $theme->getImgPath(\'s_desc.png\');?>);';
        $txt .= '}';
        $txt .= 'th.headerSortDown .sorticon, th.headerSortUp:hover .sorticon {';
        $txt .= '    background-image: url(<?php echo $theme->getImgPath(\'s_asc.png\');?>);';
        $txt .= '}';
        /* end of styles of sortable tables */
        /* styles for jQuery-ui to support rtl languages */
        $txt .= 'body .ui-dialog .ui-dialog-titlebar-close {';
        $txt .= '    <?php echo $right; ?>: .3em;';
        $txt .= '    <?php echo $left; ?>: initial;';
        $txt .= '}';
        $txt .= 'body .ui-dialog .ui-dialog-title {';
        $txt .= '    float: <?php echo $left; ?>;';
        $txt .= '}';
        $txt .= 'body .ui-dialog .ui-dialog-buttonpane .ui-dialog-buttonset {';
        $txt .= '    float: <?php echo $right; ?>;';
        $txt .= '}';
        /* end of styles for jQuery-ui to support rtl languages */
        $txt .= '@media only screen and (max-width: 768px) {';
        $txt .= '    #main_pane_left {';
        $txt .= '        width: 100%;';
        $txt .= '    }';
        $txt .= '    #main_pane_right {';
        $txt .= '        padding-top: 0;';
        $txt .= '        padding-<?php echo $left; ?>: 1px;';
        $txt .= '        padding-<?php echo $right; ?>: 1px;';
        $txt .= '    }';

        $txt .= '    ul#topmenu,';
        $txt .= '    ul.tabs {';
        $txt .= '        display: flex;';
        $txt .= '    }';

        $txt .= '    .navigationbar {';
        $txt .= '        display: inline-flex;';
        $txt .= '        margin: 0 !important;';
        $txt .= '        border-radius: 0 !important;';
        $txt .= '        overflow: auto;';
        $txt .= '    }';

        $txt .= '    .scrollindicator {';
        $txt .= '        padding: 5px;';
        $txt .= '        cursor: pointer;';
        $txt .= '        display: inline;';
        $txt .= '    }';

        $txt .= '    .responsivetable {';
        $txt .= '        overflow-x: auto;';
        $txt .= '    }';

        $txt .= '    body#loginform div.container {';
        $txt .= '        width: 100%;';
        $txt .= '    }';

        $txt .= '    .largescreenonly {';
        $txt .= '        display: none;';
        $txt .= '    }';

        $txt .= '    .width100, .desktop50 {';
        $txt .= '        width: 100%;';
        $txt .= '    }';

        $txt .= '    .width96 {';
        $txt .= '        width: 96% !important;';
        $txt .= '    }';

        $txt .= '    #page_nav_icons {';
        $txt .= '        display: none;';
        $txt .= '    }';

        $txt .= '    table#serverstatusconnections {';
        $txt .= '        margin-left: 0;';
        $txt .= '    }';

        $txt .= '    #table_name_col_no {';
        $txt .= '        top: 62px';
        $txt .= '    }';

        $txt .= '    .tdblock tr td {';
        $txt .= '        display: block;';
        $txt .= '    }';

        $txt .= '    #table_columns {';
        $txt .= '        margin-top: 60px;';
        $txt .= '    }';

        $txt .= '    #table_columns .tablesorter {';
        $txt .= '        min-width: 100%;';
        $txt .= '    }';

        $txt .= '    .doubleFieldset fieldset {';
        $txt .= '        width: 98%;';
        $txt .= '    }';

        $txt .= '    div#serverstatusquerieschart {';
        $txt .= '        width: 100%;';
        $txt .= '        height: 450px;';
        $txt .= '    }';

        $txt .= '    .ui-dialog {';
        $txt .= '        margin: 1%;';
        $txt .= '        width: 95% !important;';
        $txt .= '    }';
        $txt .= '}';
        /* templates/database/designer */
        /* side menu */
        $txt .= '#name-panel {';
        $txt .= '    overflow:hidden;';
        $txt .= '}';
        fwrite($file, $txt);
        fclose($file);
        return null;
    }
}
