<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * main css file from theme ClearView 3
 *
 * @version $Id$
 * @package phpMyAdmin-theme
 * @subpackage ClearView3
 */

// unplanned execution path
if (!defined('PMA_MINIMUM_COMMON')) {
    exit();
}
?>
/******************************************************************************/
/* general elements
********************************************************************** */
html{font-size: <?php echo (null !== $GLOBALS['PMA_Config']->get('fontsize') ? $GLOBALS['PMA_Config']->get('fontsize') : $_COOKIE['pma_fontsize']);?>}
body,
body#bodyquerywindow{padding:10px;margin:0;background:<?php echo (isset($_SESSION['tmp_user_values']['custom_color']) ? $_SESSION['tmp_user_values']['custom_color'] : $GLOBALS['cfg']['MainBackground']); ?>;color:<?php echo $GLOBALS['cfg']['MainColor'] ?>;font-family:<?php echo $GLOBALS['cfg']['FontFamily'] ?>;font-size:95%;line-height:130%}
a img{border:none}
a:link,
a:visited{text-decoration:none;color:#483D8B}
a:hover{color:#A0522D}
h1{font-size:1.5em;font-weight:normal}
h2{font-size:1.2em}
dfn{font-style:normal}
dfn:hover{font-style:normal;cursor:help}
hr{color:#ccc;background:#ccc;border:none;height:1px}
table{font-size:100%;border-collapse:collapse}
table caption,
table th,
table td{vertical-align:top;padding:3px}
table th{text-align:left}
table thead th{text-align:center;vertical-align:bottom;border-bottom:2px solid #ccc;font-weight:bold}
fieldset{background:#fff;padding:1em;border:1px solid #ccc;margin:1em 0.5em 0 0.5em}
fieldset legend{background:#fff;padding-bottom:1px;color:#888}
label{cursor:pointer}
ul li{line-height:140%}
form{display:inline}
input,
select,
textarea{font-size:small}
.icon{margin-left:0.3em;margin-right:2px;vertical-align:middle}
/* classes
********************************************************************** */
.tblFooters{text-align:right;background:#eee url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>footer_gradient.png) repeat-x;padding:0.5em 1em;margin:0 0.5em 1em;border:none}
div.formelementrow{margin-bottom:0.5em}
.table_comment{color:#888}
div.success,
div.notice,
div.warning,
div.error{padding:0.5em;margin:0.5em 0}
div.success{background:#D2FFD2}
div.notice{background:#FFE0C1}
div.warning{background:#FFBE7D}
div.error{background:#FF9595}
code.sql{display:block;border:1px solid #ccc;padding:0.5em}
div.tools{text-align:right;background:#eee url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>footer_gradient.png) repeat-x scroll 0%;padding:0.5em}
/* tables */
table tr.odd th,
table tr.odd{/*background:#fff*/}
table tr.even th,
table tr.even{background:<?php if (!empty($GLOBALS['cfg']['zebra'])) echo $GLOBALS['cfg']['zebra']; ?>}
table tr.marked th,
table tr.marked{color:#333;background:#ffe09d}
table tr.odd:hover,
table tr.even:hover{background:#fff}
.odd:hover,
.even:hover,
.hover{background:#eee}
table.data tr.odd:hover,
table.data tr.even:hover,
table.data tr.odd:hover th,
table.data tr.even:hover th,
table.data tr.hover th,
table.data tr.hover td{color:#333;background:#eee}
table tr.disabled,
table tr.disabled a,
table tr.disabled:hover{text-decoration:none;color:#aaa}
th.name{text-align:right}
td.value{text-align:left}
.clearfloat{clear:both}
.syntax{font-family:"Courier New",Courier,monospace}
#selflink{margin-top:2em;padding-top:1em;border-top:0.1em solid silver;clear:both;display:block;margin-bottom:1em;text-align:right;width:100%}
div.warning{border:1px solid #ffe09d;background:#FFFFCC;margin:1em 0.5em;padding:0.5em}
div.notice{background:#FFFFCC;margin:1em 0.5em;padding:0.5em;font-size:0.9em}
div.error{background:#ffe5e9;margin:1em 0.5em;padding:0.5em 0.5em 1em}
label.desc{float:left;width:30em}
fieldset .formelement {float:left;line-height:2.4em;margin-right:0.5em;white-space:nowrap}
/* specific elements
********************************************************************** */
/* first page */
#main_pane_left{float:left;width:60%}
#main_pane_right{float:left;width:35%}
#rainbowform{display:inline}
div#sqlquerycontainer{float:left;width:69%}
div#tablefieldscontainer{float:right;width:29%}
div#tablefieldscontainer select{width:100%;margin-bottom:5px}
textarea#sqlquery{width:100%}
#TooltipContainer{position:absolute;z-index:99;width:20em;height:auto;overflow:visible;visibility:hidden;color:#333;border:0.1em solid #333;padding:0.5em;background:#ffffcc}
/* header - breadcrumbs */
#serverinfo{padding:2em 0 1em;font-size:90%}
#serverinfo .item{text-decoration:none;white-space:nowrap}
#serverinfo .icon{margin:0}
#serverinfo .separator{margin:0 0.5em}
/* topmenu */
#topmenucontainer{padding:1em 0 0;margin-bottom:1em;border:none;border-bottom:1px solid #ccc}
ul#topmenu{list-style-type:none;margin:0 0 0 16px;padding:0}
ul#topmenu li{float:left;padding:0;margin:0;display:block;margin-left:-8px;font-weight:normal;border:none}
ul#topmenu li .tab,
ul#topmenu li .tabactive,
ul#topmenu li .tabcaution{padding:0 17px 0 0;margin:0;background:url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>topmenu_tab_r.png) no-repeat 100% 0;text-decoration:none;display:block;line-height:2.1;float:left;border:none}
ul#topmenu li .tab .icon,
ul#topmenu li .tabactive .icon,
ul#topmenu li .tabcaution .icon{line-height:2.1;padding:0.4em 0 0.3em 7px;margin:0 3px 0 0;float:left;background:url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>topmenu_tab_l.png) no-repeat 0 0}
ul#topmenu li span.tab{color:#aaa}
ul#topmenu li .tabactive{font-weight:bold;z-index:10;position:relative;background:url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>topmenu_tab_r.png) no-repeat 100% -38px}
ul#topmenu li .tabactive .icon{background:url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>topmenu_tab_l.png) no-repeat 0 -38px}
ul#topmenu li a.tabcaution{color:red}
ul#topmenu li a.tabcaution:hover{color:red}
/* Calendar */
table.calendar td.selected{background:#FFC751}
table.calendar td a{text-decoration:none}
table.calendar td a:hover{background:#eee}
/* table stats */
div#tablestatistics{margin-top:3em;color:#888;border-bottom:none}
div#tablestatistics th{text-align:left}
#serverstatustraffic,
#serverstatusqueriesdetails1{float:left;width:33%}
#serverstatusconnections,
#serverstatusqueriesdetails51{float:left;width:33%}
#sectionlinks a{padding:0 0.3em}
#serverstatussection{clear:both}
/* server privileges */
#tableuserrights th{vertical-align:bottom}
#fieldset_user_global_rights fieldset{float:left}
#fieldset_add_user{padding:0.5em;background:#eee url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>footer_gradient.png) repeat-x;border:none;border-top:1px solid #ccc}
#fieldset_add_user a{font-weight:bold;text-decoration:none}
/* user privileges */
#fieldset_add_user_login div.item{padding-bottom:0.3em;margin-bottom:0.3em}
#fieldset_add_user_login label{float:left;display:block;width:10em;max-width:100%;text-align:right;padding-right:0.5em}
#fieldset_add_user_login span.options #select_pred_username,
#fieldset_add_user_login span.options #select_pred_hostname,
#fieldset_add_user_login span.options #select_pred_password{width:100%;max-width:100%}
#fieldset_add_user_login span.options{float:left;display:block;width:12em;max-width:100%;padding-right:0.5em}
#fieldset_add_user_login input{width:12em;clear:right;max-width:100%}
#fieldset_add_user_login span.options input{width:auto}
#fieldset_user_priv div.item{float:left;width:9em;max-width:100%}
#fieldset_user_priv div.item div.item{float:none}
#fieldset_user_priv div.item label{white-space:nowrap}
#fieldset_user_priv div.item select{width:100%}
#fieldset_user_global_rights fieldset{float:left}
#maincontainer{border-bottom:none;/*background:url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>logo_right.png) no-repeat right bottom;*/padding-bottom:5em}
/* iconic view for ul items */
li#li_create_database{list-style-image:url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>b_newdb.png);padding:1em 3px 0.5em}
li#li_select_lang{list-style-image:url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_lang.png);padding:0 3px;margin-top:2em}
li#li_select_mysql_collation,
li#li_select_mysql_charset{list-style-image:url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_asci.png);padding:0 3px;margin:3px 0}
li#li_select_mysql_collation{padding-bottom:1em}
li#li_select_theme{list-style-image:url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_theme.png);padding:0 3px;margin:3px 0}
li#li_server_info{list-style-image:url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_host.png);padding-left:3px;}
li#li_server_version{padding-left:3px;list-style:none}
li#li_mysql_proto,
li#li_user_info{list-style:none;list-style-image:none;padding:0 3px}
li#li_mysql_status{list-style-image:url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_status.png);padding:0 3px;margin:3px 0}
li#li_mysql_variables{list-style-image:url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_vars.png);padding:0 3px;margin:3px 0}
li#li_mysql_processes{list-style-image:url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_process.png);padding:0 3px;margin:3px 0}
li#li_mysql_collations{list-style-image:url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_asci.png);padding:0 3px;margin:3px 0}
li#li_mysql_engines{list-style-image:url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>b_engine.png);padding:0 3px;margin:3px 0}
li#li_mysql_binlogs{list-style-image:url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_tbl.png);padding:0 3px;margin:3px 0}
li#li_mysql_databases{list-style-image:url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_db.png);padding:0 3px;margin:3px 0;font-weight:bold}
li#li_export{list-style-image:url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>b_export.png);padding:0 3px;margin:3px 0}
li#li_import{list-style-image:url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>b_import.png);padding:0 3px;margin:3px 0}
li#li_change_password{list-style-image:url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_passwd.png);padding:0 3px;margin:3px 0}
li#li_log_out{list-style-image:url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_loggoff.png);padding:0 3px;margin:1em 0 3px}
li#li_pma_docs{list-style-image:url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>b_docs.png);padding:0 3px;margin:2em 0 0}
li#li_pma_wiki{list-style:none;padding:0 3px}
li#li_phpinfo{list-style-image:url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>php_sym.png);padding:0 3px;margin:3px 0}
li#li_pma_homepage{list-style-image:url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>b_home.png);padding:0 3px;margin:3px 0}
li#li_pma_homepage + li{list-style:none;padding:0 3px}
li#li_mysql_privilegs{list-style-image:url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_rights.png);padding:0 3px;margin:3px 0 1em}
li#li_switch_dbstats{list-style-image:url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>b_dbstatistics.png);padding:0 3px;margin:3px 0}
li#li_flush_privileges{list-style-image:url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_reload.png);padding:0 3px;margin:1em 0 3px}
li#li_pma_version,
li#li_web_server_software,
li#li_mysql_client_version,
li#li_used_php_extension,
li#li_select_fontsize,
li#li_custom_color{list-style:none;list-style-image:none;padding-left:3px}
li#li_select_fontsize{list-style:none;list-style-image:none;padding-left:3px;padding-top:0.5em}