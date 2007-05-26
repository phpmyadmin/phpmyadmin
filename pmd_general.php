<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @author  Ivan A Kirillov (Ivan.A.Kirillov@gmail.com)
 * @version $Id$
 * @package phpMyAdmin-Designer
 */

/**
 *
 */
require_once "./pmd_common.php";

$tab_column       = get_tab_info();
$script_tabs      = get_script_tabs();
$script_contr     = get_script_contr();
$tab_pos          = get_tab_pos();
$tables_pk_or_unique_keys = get_pk_or_unique_keys();
$tables_all_keys  = get_all_keys();
$hidden           = "hidden";

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns:v="urn:schemas-microsoft-com:vml" xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $GLOBALS['available_languages'][$GLOBALS['lang']][2]; ?>" lang="<?php echo $GLOBALS['available_languages'][$GLOBALS['lang']][2]; ?>" dir="<?php echo $GLOBALS['text_dir']; ?>">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset ?>" />
    <link rel="icon" href="pmd/images/favicon.ico" type="image/x-icon" />
    <link rel="shortcut icon" href="pmd/images/favicon.ico" type="image/x-icon" />
    <link rel="stylesheet" type="text/css" href="pmd/styles/<?php echo $GLOBALS['PMD']['STYLE'] ?>/style1.css" />
    <title>Designer</title>
    <script type="text/javascript">
    // <![CDATA[
<?php
echo '
    var server = "' . PMA_escapeJsString($server) . '";
    var db = "' . PMA_escapeJsString($db) . '";
    var token = "' . PMA_escapeJsString($token) . '";
    var LangSelectReferencedKey = "' . PMA_escapeJsString($strSelectReferencedKey) . '";
    var LangSelectForeignKey = "' . PMA_escapeJsString($strSelectForeignKey) . '";
    var LangPleaseSelectPrimaryOrUniqueKey = "' . PMA_escapeJsString($strPleaseSelectPrimaryOrUniqueKey) . '";
    var LangIEnotSupport = "' . PMA_escapeJsString($strIEUnsupported) . '";
    var LangChangeDisplay = "' . PMA_escapeJsString($strChangeDisplay) . '";

    var strLang = Array();
    strLang["strModifications"] = "' . PMA_escapeJsString($strModifications) . '";
    strLang["strRelationDeleted"] = "' . PMA_escapeJsString($strRelationDeleted) . '";
    strLang["strInnoDBRelationAdded"] = "' . PMA_escapeJsString($strInnoDBRelationAdded). '";
    strLang["strGeneralRelationFeat:strDisabled"] = "' . PMA_escapeJsString($strGeneralRelationFeat . ' : ' . $strDisabled) . '";
    strLang["strInternalRelationAdded"] = "' . PMA_escapeJsString($strInternalRelationAdded) . '";
    strLang["strErrorRelationAdded"] = "' . PMA_escapeJsString($strErrorRelationAdded) . '";
    strLang["strErrorRelationExists"] = "' . PMA_escapeJsString($strErrorRelationExists) . '";
    strLang["strErrorSaveTable"] = "' . PMA_escapeJsString($strErrorSaveTable) . '";';
?>

    // ]]>
    </script>
    <script src="pmd/scripts/ajax.js" type="text/javascript"></script>
    <script src="pmd/scripts/move.js" type="text/javascript"></script>
    <!--[if IE]>
    <script src="pmd/scripts/iecanvas.js" type="text/javascript"></script>
    <![endif]-->
<?php
echo $script_tabs . $script_contr . $script_display_field;
?>

</head>
<body onload="Main()" class="general_body" id="pmd_body">

<div class="header" id="top_menu">
        <a href="javascript:Show_left_menu(document.getElementById('key_Show_left_menu'));"
            onmousedown="return false;" class="M_butt first" target="_self">
            <img id='key_Show_left_menu' title="<?php echo $strShowHideLeftMenu; ?>"
                alt="v" src="pmd/images/downarrow2_m.png" /></a>
        <a href="javascript:Save2();" onmousedown="return false;"
            class="M_butt" target="_self"
        ><img title="<?php echo $strSavePosition ?>" src="pmd/images/save.png" alt=""
        /></a><a href="javascript:Start_table_new();" onmousedown="return false;"
            class="M_butt" target="_self"
        ><img title="<?php echo $strCreateTable ?>" src="pmd/images/table.png" alt=""
        /></a><a href="javascript:Start_relation();" onmousedown="return false;"
            class="M_butt" id="rel_button" target="_self"
        ><img title="<?php echo $strCreateRelation ?>" src="pmd/images/relation.png" alt=""
        /></a><a href="javascript:Start_display_field();" onmousedown="return false;"
            class="M_butt" id="display_field_button" target="_self"
        ><img title="<?php echo $strChangeDisplay ?>" src="pmd/images/display_field.png" alt=""
        /></a><a href="javascript:location.reload();" onmousedown="return false;"
            class="M_butt" target="_self"
        ><img title="<?php echo $strReload; ?>" src="pmd/images/reload.png" alt=""
        /></a><a href="javascript:Help();" onmousedown="return false;"
            class="M_butt" target="_self"
        ><img title="<?php echo $strHelp; ?>" src="pmd/images/help.png" alt=""
        /></a><img class="M_bord" src="pmd/images/bord.png" alt=""
        /><a href="javascript:Angular_direct();" onmousedown="return false;"
            class="M_butt" id="angular_direct_button" target="_self"
        ><img title="<?php echo $strAngularLinks . ' / ' . $strDirectLinks; ?>"
                src="pmd/images/ang_direct.png" alt=""
        /></a><a href="javascript:Grid();" onmousedown="return false;"
            class="M_butt" id="grid_button" target="_self"
        ><img title="<?php echo $strSnapToGrid ?>" src="pmd/images/grid.png" alt=""
        /></a><img class="M_bord" src="pmd/images/bord.png" alt=""
        /><a href="javascript:Small_tab_all(document.getElementById('key_SB_all'));"
            onmousedown="return false;" class="M_butt" target="_self"
        ><img id='key_SB_all' title="<?php echo $strSmallBigAll; ?>" alt="v"
                src="pmd/images/downarrow1.png"
        /></a><a href="javascript:Small_tab_invert();" onmousedown="return false;"
            class="M_butt" target="_self"
        ><img title="<?php echo $strToggleSmallBig; ?>" alt="key" src="pmd/images/bottom.png"
        /></a><img class="M_bord" src="pmd/images/bord.png" alt=""
        /><a href="javascript:PDF_save();" onmousedown="return false;"
            class="M_butt" target="_self"
        ><img src="pmd/images/pdf.png" alt="key" width="20" height="20"
                title="<?php echo $strImportExportCoords; ?>" /></a>
        <a href="javascript:Top_menu_right(document.getElementById('key_Left_Right'));"
            onmousedown="return false;" class="M_butt last" target="_self">
            <img src="pmd/images/2rightarrow_m.png" id="key_Left_Right" alt=">"
                title="<?php echo $strMoveMenu; ?>" /></a>
</div>

<div id="osn_tab">
  <CANVAS id="canvas" width="100" height="100" onclick="Canvas_click(this)"></CANVAS>
</div>

<form action="" method="post" name="form1">
<div id="layer_menu" style="visibility:<?php echo $hidden ?>;">
<div align="center" style="padding-top:5px;">
    <a href="javascript:Hide_tab_all(document.getElementById('key_HS_all'));"
        onmousedown="return false;" class="M_butt" target="_self">
    <img title="<?php echo $strHideShowAll; ?>" alt="v"
        src="pmd/images/downarrow1.png" id='key_HS_all' /></a>
    <a href="javascript:No_have_constr(document.getElementById('key_HS'));"
        onmousedown="return false;" class="M_butt" target="_self">
    <img title="<?php echo $strHideShowNoRelation; ?>" alt="v"
        src="pmd/images/downarrow2.png" id='key_HS' /></a>
</div>

<div id="id_scroll_tab" class="scroll_tab">
    <table width="100%" style="padding-left: 3px;">
<?php
for ($i = 0; $i < count($GLOBALS['PMD']['TABLE_NAME']); $i++) {
    ?>
    <tr><td title="<?php echo $strStructure; ?>" width="1px"
            onmouseover="this.className='L_butt2_2'"
            onmouseout="this.className='L_butt2_1'">
            <img onclick="Start_tab_upd('<?php echo $GLOBALS['PMD_URL']["TABLE_NAME_SMALL"][$i]; ?>');"
                src="pmd/images/exec.png" alt="" /></td>
        <td width="1px">
            <input onclick="VisibleTab(this,'<?php echo $GLOBALS['PMD_URL']["TABLE_NAME"][$i]; ?>')"
                title="<?php echo $strHide ?>"
                id="check_vis_<?php echo $GLOBALS['PMD_URL']["TABLE_NAME"][$i]; ?>"
                style="margin:0px;" type="checkbox"
                value="<?php echo $GLOBALS['PMD_URL']["TABLE_NAME"][$i]; ?>"
                <?php
                if (isset($tab_pos[$GLOBALS['PMD']["TABLE_NAME"][$i]])) {
                    echo $tab_pos[$GLOBALS['PMD']["TABLE_NAME"][$i]]["H"] ? 'checked="checked"' : '';
                } else {
                    echo 'checked="checked"';
                }
                ?> /></td>
        <td class="Tabs" onmouseover="this.className='Tabs2'"
            onmouseout="this.className='Tabs'"
            onclick="Select_tab('<?php echo $GLOBALS['PMD_URL']["TABLE_NAME"][$i]; ?>');">
            <?php echo $GLOBALS['PMD_OUT']["TABLE_NAME"][$i]; ?></td>
    </tr>
    <?php
}
?>
    </table>
</div>

<div align="center">
    <?php echo $strNumberOfTables ?>: <?php echo count($GLOBALS['PMD']['TABLE_NAME']) ?>
</div>
<div align="right">
    <div id="layer_menu_sizer" onmousedown="layer_menu_cur_click=1">
    </div>
</div>
</div>
<?php
for ($i = 0; $i < count($GLOBALS['PMD']["TABLE_NAME"]); $i++) {
    $t_n = $GLOBALS['PMD']["TABLE_NAME"][$i];
    $t_n_url = $GLOBALS['PMD_URL']["TABLE_NAME"][$i];

    ?>
<input name="t_x[<?php echo $t_n_url ?>]" type="hidden" id="t_x_<?php echo $t_n_url ?>_" />
<input name="t_y[<?php echo $t_n_url ?>]" type="hidden" id="t_y_<?php echo $t_n_url ?>_" />
<input name="t_v[<?php echo $t_n_url ?>]" type="hidden" id="t_v_<?php echo $t_n_url ?>_" />
<input name="t_h[<?php echo $t_n_url ?>]" type="hidden" id="t_h_<?php echo $t_n_url ?>_" />

<table id="<?php echo $t_n_url ?>" cellpadding="0" cellspacing="0" class="tab"
   style="position: absolute;
          left: <?php if (isset($tab_pos[$t_n])) echo $tab_pos[$t_n]["X"]; else echo rand(180, 800); ?>px;
          top: <?php if (isset($tab_pos[$t_n])) echo $tab_pos[$t_n]["Y"]; else echo rand(30, 500); ?>px;
          visibility: <?php if (isset($tab_pos[$t_n])) echo $tab_pos[$t_n]["H"] ? "visible" : "hidden"; ?>;
         ">
<thead>
<tr>
    <td class="small_tab" onmouseover="this.className='small_tab2';"
        onmouseout="this.className='small_tab';"
        id="id_hide_tbody_<?php echo $t_n_url ?>"
        onclick="Small_tab('<?php echo $t_n_url ?>', 1)"><?php
        // no space alloawd here, between tags and content !!!
        // JavaScript function does require this
        if (! isset($tab_pos[$t_n]) || ! empty($tab_pos[$t_n]["V"])) {
            echo 'v';
        } else {
            echo '&gt;';
        }
        ?></td>
    <td class="small_tab_pref" onmouseover="this.className='small_tab_pref2';"
        onmouseout="this.className='small_tab_pref';"
        onclick="Start_tab_upd('<?php echo $GLOBALS['PMD_URL']["TABLE_NAME_SMALL"][$i]; ?>');">
        <img src="pmd/images/exec_small.png" alt="" /></td>
    <td nowrap="nowrap" id="id_zag_<?php echo $t_n_url ?>" class="tab_zag"
        onmousedown="cur_click=document.getElementById('<?php echo $t_n_url ?>');"
        onmouseover="this.className = 'tab_zag_2'"
        onmouseout="this.className = 'tab_zag'">
        <span class='owner'>
        <?php
        echo $GLOBALS['PMD_OUT']["OWNER"][$i];
        echo '.</span>';
        echo $GLOBALS['PMD_OUT']["TABLE_NAME_SMALL"][$i];
        ?></td>
</tr>
</thead>
<tbody id="id_tbody_<?php echo $t_n_url ?>"
    <?php if (! isset($tab_pos[$t_n])) echo 'style="display: none;"'; ?>>
    <?php
    $display_field = PMA_getDisplayField($db, $GLOBALS['PMD']["TABLE_NAME_SMALL"][$i]);
    for ($j = 0; $j < count($tab_column[$t_n]["COLUMN_ID"]); $j++) {
        ?>
<tr id="id_tr_<?php
        echo $GLOBALS['PMD_URL']["TABLE_NAME_SMALL"][$i] . '.'
            . urlencode($tab_column[$t_n]["COLUMN_NAME"][$j]) ?>"
        <?php
        if ($display_field == $tab_column[$t_n]["COLUMN_NAME"][$j]) {
            echo ' class="tab_field_3" ';
        } else {
            echo ' class="tab_field" ';
        }
        ?>
    onmouseover="old_class = this.className; this.className = 'tab_field_2';"
    onmouseout="this.className = old_class;"
    onmousedown="Click_field('<?php
        echo $GLOBALS['PMD_URL']["TABLE_NAME_SMALL"][$i]."','".urlencode($tab_column[$t_n]["COLUMN_NAME"][$j])."',";
        if ($GLOBALS['PMD']['TABLE_TYPE'][$i] != 'INNODB') {
            echo (isset($tables_pk_or_unique_keys[$t_n . "." . $tab_column[$t_n]["COLUMN_NAME"][$j]]) ? 1 : 0);
        } else {
            // if this is an InnoDB table, it's not necessary that the
            // index is a primary key
            echo (isset($tables_all_keys[$t_n.".".$tab_column[$t_n]["COLUMN_NAME"][$j]]) ? 1 : 0);
        }
        ?>)">
    <td width="10px" colspan="3"
        id="<?php echo $t_n_url.".".urlencode($tab_column[$t_n]["COLUMN_NAME"][$j]) ?>">
        <div style="white-space:nowrap">
        <?php
        if (isset($tables_pk_or_unique_keys[$t_n.".".$tab_column[$t_n]["COLUMN_NAME"][$j]])) {
            ?>
                <img src="pmd/styles/<?php echo $GLOBALS['PMD']['STYLE'];?>/images/FieldKey_small.png"
                    alt="*" />
            <?php
        } else {
            ?>
                    <img src="pmd/styles/<?php echo $GLOBALS['PMD']['STYLE']?>/images/Field_small<?php
            if (strstr($tab_column[$t_n]["TYPE"][$j],'char')
             || strstr($tab_column[$t_n]["TYPE"][$j],'text')) {
                echo '_char';
            } elseif (strstr($tab_column[$t_n]["TYPE"][$j],'int')
             || strstr($tab_column[$t_n]["TYPE"][$j],'float')
             || strstr($tab_column[$t_n]["TYPE"][$j],'double')
             || strstr($tab_column[$t_n]["TYPE"][$j],'decimal')) {
                echo '_int';
            } elseif (strstr($tab_column[$t_n]["TYPE"][$j],'date')
             || strstr($tab_column[$t_n]["TYPE"][$j],'time')
             || strstr($tab_column[$t_n]["TYPE"][$j],'year')) {
                echo '_date';
            }
            ?>.png" alt="*" />
            <?php
        }
        echo htmlspecialchars($tab_column[$t_n]["COLUMN_NAME"][$j]
            . " : " . $tab_column[$t_n]["TYPE"][$j], ENT_QUOTES);
        ?>
        </div>
   </td>
</tr>
        <?php
    }
    ?>
</tbody>
</table>
    <?php
}
?>
</form>
<div id="hint"></div>
<div id='layer_action' style="visibility:<?php echo $hidden ?>;">Load...</div>

<table id="layer_new_relation" style="visibility:<?php echo $hidden ?>;"
    width="5%" border="0" cellpadding="0" cellspacing="0">
<tbody>
<tr>
    <td class="frams1" width="10px"></td>
    <td class="frams5" width="99%" ></td>
    <td class="frams2" width="10px"><div class="bor"></div></td>
</tr>
<tr>
    <td class="frams8"></td>
    <td class="input_tab">
        <table width="168" border="0" align="center" cellpadding="2" cellspacing="0">
        <thead>
        <tr>
            <td colspan="2" align="center" nowrap="nowrap"><b><?php echo $strCreateRelation; ?></b></td>
        </tr>
        </thead>
        <tbody id="InnoDB_relation">
        <tr>
            <td colspan="2" align="center" nowrap="nowrap"><b>InnoDB</b></td>
        </tr>
        <tr>
            <td width="58" nowrap="nowrap">on delete</td>
            <td width="102"><select name="on_delete" id="on_delete">
                    <option value="nix" selected="selected">--</option>
                    <option value="CASCADE">CASCADE</option>
                    <option value="SET NULL">SET NULL</option>
                    <option value="NO ACTION">NO ACTION</option>
                    <option value="RESTRICT">RESTRICT</option>
                </select>
            </td>
        </tr>
        <tr>
            <td nowrap="nowrap">on update</td>
            <td><select name="on_update" id="on_update">
                    <option value="nix" selected="selected">--</option>
                    <option value="CASCADE">CASCADE</option>
                    <option value="SET NULL">SET NULL</option>
                    <option value="NO ACTION">NO ACTION</option>
                    <option value="RESTRICT">RESTRICT</option>
                </select>
            </td>
        </tr>
        </tbody>
        <tbody>
        <tr>
            <td colspan="2" align="center" nowrap="nowrap">
                <input type="button" class="butt" name="Button"
                    value="<?php echo $strOK; ?>" onclick="New_relation()" />
                <input type="button" class="butt" name="Button"
                    value="<?php echo $strCancel; ?>"
                    onclick="document.getElementById('layer_new_relation').style.visibility = 'hidden';" />
            </td>
        </tr>
        </tbody>
        </table>
    </td>
    <td class="frams6"></td>
</tr>
<tr>
    <td class="frams4"><div class="bor"></div></td>
    <td class="frams7"></td>
    <td class="frams3"></td>
</tr>
</tbody>
</table>

<table id="layer_upd_relation" style="visibility:<?PHP echo $hidden ?>;"
    width="5%" border="0" cellpadding="0" cellspacing="0">
<tbody>
<tr>
    <td class="frams1" width="10px"></td>
    <td class="frams5" width="99%"></td>
    <td class="frams2" width="10px"><div class="bor"></div></td>
</tr>
<tr>
    <td class="frams8"></td>
    <td class="input_tab">
        <table width="100%" border="0" align="center" cellpadding="2" cellspacing="0">
        <tr>
            <td colspan="3" align="center" nowrap="nowrap"><b><?php echo $strDeleteRelation; ?></b></td>
        </tr>
        <tr>
            <td colspan="3" align="center" nowrap="nowrap">
                <input name="Button" type="button" class="butt"
                    onclick="Upd_relation()" value="<?php echo $strDelete; ?>" />
                <input type="button" class="butt" name="Button"
                    value="<?php echo $strCancel; ?>"
                    onclick="document.getElementById('layer_upd_relation').style.visibility = 'hidden'; Re_load();" />
            </td>
        </tr>
    </table></td>
    <td class="frams6"></td>
</tr>
<tr>
    <td class="frams4"><div class="bor"></div></td>
    <td class="frams7"></td>
    <td class="frams3"></td>
</tr>
</tbody>
</table>
<!-- cache images -->
<img src="pmd/images/2leftarrow_m.png" width="0" height="0" alt="" />
<img src="pmd/images/rightarrow1.png" width="0" height="0" alt="" />
<img src="pmd/images/rightarrow2.png" width="0" height="0" alt="" />
<img src="pmd/images/uparrow2_m.png" width="0" height="0" alt="" />
</body>
</html>
