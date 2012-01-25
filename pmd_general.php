<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @package PhpMyAdmin-Designer
 */

/**
 *
 */
require_once './libraries/pmd_common.php';
require './libraries/db_common.inc.php';
require './libraries/db_info.inc.php';

$tab_column       = get_tab_info();
$script_tabs      = get_script_tabs();
$script_contr     = get_script_contr();
$tab_pos          = get_tab_pos();
$tables_pk_or_unique_keys = get_pk_or_unique_keys();
$tables_all_keys  = get_all_keys();

$params = array('lang' => $GLOBALS['lang']);
if (isset($GLOBALS['db'])) {
    $params['db'] = $GLOBALS['db'];
}
require_once './libraries/header_scripts.inc.php';
?>
    <script type="text/javascript">
    // <![CDATA[
<?php
echo '
    var server = "' . PMA_escapeJsString($server) . '";
    var db = "' . PMA_escapeJsString($db) . '";
    var token = "' . PMA_escapeJsString($token) . '";';
    echo "\n";
    if (isset($_REQUEST['query'])) {
    echo '
     $(document).ready(function() {
        $(".trigger").click(function() {
        $(".panel").toggle("fast");
        $(this).toggleClass("active");
        return false;
        });
    });';
    }
?>
    // ]]>
    </script>
    <script src="js/pmd/ajax.js" type="text/javascript"></script>
    <script src="js/pmd/history.js" type="text/javascript"></script>
    <script src="js/pmd/move.js" type="text/javascript"></script>
    <!--[if IE]>
    <script src="js/pmd/iecanvas.js" type="text/javascript"></script>
    <![endif]-->
<?php
echo $script_tabs . $script_contr . $script_display_field;
?>

</head>
<body onload="Main()" class="general_body" id="pmd_body">

<div class="pmd_header" id="top_menu">
        <a href="#"
            onclick="Show_left_menu(document.getElementById('key_Show_left_menu')); return false" class="M_butt first" target="_self">
            <img id='key_Show_left_menu' title="<?php echo __('Show/Hide left menu'); ?>"
                alt="v" src="<?php echo $GLOBALS['pmaThemeImage'] ?>pmd/downarrow2_m.png" /></a>
        <a href="#" onclick="Save2(); return false"
            class="M_butt" target="_self"
        ><img title="<?php echo __('Save position') ?>" src="<?php echo $GLOBALS['pmaThemeImage'] ?>pmd/save.png" alt=""
        /></a><a href="#" onclick="Start_table_new(); return false"
            class="M_butt" target="_self"
        ><img title="<?php echo __('Create table')?>" src="<?php echo $GLOBALS['pmaThemeImage'] ?>pmd/table.png" alt=""
        /></a><a href="#" onclick="Start_relation(); return false"
            class="M_butt" id="rel_button" target="_self"
        ><img title="<?php echo __('Create relation') ?>" src="<?php echo $GLOBALS['pmaThemeImage'] ?>pmd/relation.png" alt=""
        /></a><a href="#" onclick="Start_display_field(); return false"
            class="M_butt" id="display_field_button" target="_self"
        ><img title="<?php echo __('Choose column to display') ?>" src="<?php echo $GLOBALS['pmaThemeImage'] ?>pmd/display_field.png" alt=""
        /></a><a href="#" onclick="location.reload(); return false"
            class="M_butt" target="_self"
        ><img title="<?php echo __('Reload'); ?>" src="<?php echo $GLOBALS['pmaThemeImage'] ?>pmd/reload.png" alt=""
        /></a><a href="Documentation.html#faq6_31" target="documentation"
            class="M_butt" target="_self"
        ><img title="<?php echo __('Help'); ?>" src="<?php echo $GLOBALS['pmaThemeImage'] ?>pmd/help.png" alt=""
        /></a><img class="M_bord" src="<?php echo $GLOBALS['pmaThemeImage'] ?>pmd/bord.png" alt=""
        /><a href="#" onclick="Angular_direct(); return false"
            class="M_butt" id="angular_direct_button" target="_self"
        ><img title="<?php echo __('Angular links') . ' / ' . __('Direct links'); ?>"
                src="<?php echo $GLOBALS['pmaThemeImage'] ?>pmd/ang_direct.png" alt=""
        /></a><a href="#" onclick="Grid(); return false"
            class="M_butt" id="grid_button" target="_self"
        ><img title="<?php echo __('Snap to grid') ?>" src="<?php echo $GLOBALS['pmaThemeImage'] ?>pmd/grid.png" alt=""
        /></a><img class="M_bord" src="<?php echo $GLOBALS['pmaThemeImage'] ?>pmd/bord.png" alt=""
        /><a href="#"
            onclick="Small_tab_all(document.getElementById('key_SB_all')); return false" class="M_butt" target="_self"
        ><img id='key_SB_all' title="<?php echo __('Small/Big All'); ?>" alt="v"
                src="<?php echo $GLOBALS['pmaThemeImage'] ?>pmd/downarrow1.png"
        /></a>
<a href="#" onclick="Small_tab_invert(); return false" class="M_butt" target="_self" ><img title="<?php echo __('Toggle small/big'); ?>" alt="key" src="<?php echo $GLOBALS['pmaThemeImage'] ?>pmd/bottom.png" /></a>
<a href="#" onclick="Relation_lines_invert(); return false" class="M_butt" target="_self" ><img title="<?php echo __('Toggle relation lines'); ?>" alt="key" src="<?php echo $GLOBALS['pmaThemeImage'] ?>pmd/toggle_lines.png" /></a>
<img class="M_bord" src="<?php echo $GLOBALS['pmaThemeImage'] ?>pmd/bord.png" alt="" />
<a href="#" onclick="PDF_save(); return false"
            class="M_butt" target="_self"
        ><img src="<?php echo $GLOBALS['pmaThemeImage'] ?>pmd/pdf.png" alt="key" width="20" height="20"
                title="<?php echo __('Import/Export coordinates for PDF schema'); ?>" /></a
         >
        <?php if (isset($_REQUEST['query'])) {
            echo '<a href="#" onclick="build_query(\'SQL Query on Database\', 0)" onmousedown="return false;"
            class="M_butt" target="_self">';
            echo '<img src="'. $GLOBALS['pmaThemeImage'] . 'pmd/query_builder.png" alt="key" width="20" height="20" title="';
            echo __('Build Query');
            echo '"/></a>'; }?>
         <a href="#"
            onclick="Top_menu_right(document.getElementById('key_Left_Right')); return false" class="M_butt last" target="_self">
            <img src="<?php echo $GLOBALS['pmaThemeImage'] ?>pmd/2rightarrow_m.png" id="key_Left_Right" alt=">"
                title="<?php echo __('Move Menu'); ?>" /></a>
</div>

<form action="" method="post" name="form1">
<div id="osn_tab">
  <canvas class="pmd" id="canvas" width="100" height="100" onclick="Canvas_click(this)"></canvas>
</div>
<div id="layer_menu" style="display:none;">
<div align="center" style="padding-top:5px;">
    <a href="#"
        onclick="Hide_tab_all(document.getElementById('key_HS_all')); return false" class="M_butt" target="_self">
    <img title="<?php echo __('Hide/Show all'); ?>" alt="v"
        src="<?php echo $GLOBALS['pmaThemeImage'] ?>pmd/downarrow1.png" id='key_HS_all' /></a>
    <a href="#"
        onclick="No_have_constr(document.getElementById('key_HS')); return false" class="M_butt" target="_self">
    <img title="<?php echo __('Hide/Show Tables with no relation'); ?>" alt="v"
        src="<?php echo $GLOBALS['pmaThemeImage'] ?>pmd/downarrow2.png" id='key_HS' /></a>
</div>

<div id="id_scroll_tab" class="scroll_tab">
    <table width="100%" style="padding-left: 3px;">
<?php
$name_cnt = count($GLOBALS['PMD']['TABLE_NAME']);
for ($i = 0; $i < $name_cnt; $i++) {
    ?>
    <tr><td title="<?php echo __('Structure'); ?>" width="1px"
            onmouseover="this.className='L_butt2_2'"
            onmouseout="this.className='L_butt2_1'">
            <img onclick="Start_tab_upd('<?php echo $GLOBALS['PMD_URL']["TABLE_NAME_SMALL"][$i]; ?>');"
                src="<?php echo $GLOBALS['pmaThemeImage'] ?>pmd/exec.png" alt="" /></td>
        <td width="1px">
            <input onclick="VisibleTab(this,'<?php echo $GLOBALS['PMD_URL']["TABLE_NAME"][$i]; ?>')"
                title="<?php echo __('Hide'); ?>"
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
        <td class="pmd_Tabs" onmouseover="this.className='pmd_Tabs2'"
            onmouseout="this.className='pmd_Tabs'"
            onclick="Select_tab('<?php echo $GLOBALS['PMD_URL']["TABLE_NAME"][$i]; ?>');">
            <?php echo $GLOBALS['PMD_OUT']["TABLE_NAME"][$i]; ?></td>
    </tr>
    <?php
}
?>
    </table>
</div>

<div align="center">
    <?php echo __('Number of tables') . ': ' . $name_cnt; ?>
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

<table id="<?php echo $t_n_url ?>" cellpadding="0" cellspacing="0" class="pmd_tab"
   style="position: absolute;
          left: <?php if (isset($tab_pos[$t_n])) echo $tab_pos[$t_n]["X"]; else echo rand(180, 800); ?>px;
          top: <?php if (isset($tab_pos[$t_n])) echo $tab_pos[$t_n]["Y"]; else echo rand(30, 500); ?>px;
          visibility: <?php if (isset($tab_pos[$t_n])) echo $tab_pos[$t_n]["H"] ? "visible" : "hidden"; ?>;
         ">
<thead>
<tr>
    <?php
    if (isset($_REQUEST['query'])) {
        echo '<td class="select_all">';
        echo '<input type="checkbox" value="select_all_'.htmlspecialchars($t_n_url).'" style="margin: 0px;" ';
        echo 'id="select_all_'.htmlspecialchars($t_n_url).'" title="select all" ';
        echo 'onclick="Select_all(\''. htmlspecialchars($t_n_url) .'\',\''.htmlspecialchars($GLOBALS['PMD_OUT']["OWNER"][$i]).'\')"></td>';
    }?>
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
        <img src="<?php echo $GLOBALS['pmaThemeImage'] ?>pmd/exec_small.png" alt="" /></td>
    <td nowrap="nowrap" id="id_zag_<?php echo $t_n_url ?>" class="tab_zag"
        onmousedown="cur_click=document.getElementById('<?php echo $t_n_url ?>');"/
        onmouseover="Table_onover('<?php echo $t_n_url ?>',0,<?php echo (isset($_REQUEST['query'])? 1 : 0 )?> )"
        onmouseout="Table_onover('<?php echo $t_n_url ?>',1,<?php echo (isset($_REQUEST['query']) ? 1 : 0 )?>)">
        <span class='owner'>
        <?php
        echo $GLOBALS['PMD_OUT']["OWNER"][$i];
        echo '.</span>';
        echo $GLOBALS['PMD_OUT']["TABLE_NAME_SMALL"][$i];
        ?></td>
    <?php
    if (isset($_REQUEST['query'])) {
        echo '<td class="tab_zag"  onmouseover="Table_onover(\''.htmlspecialchars($t_n_url).'\',0,1)"  id="id_zag_'.htmlspecialchars($t_n_url).'_2"';
        echo 'onmousedown="cur_click=document.getElementById(\''.htmlspecialchars($t_n_url).'\');"';
        echo 'onmouseout="Table_onover(\''.htmlspecialchars($t_n_url).'\',1,1)">';
    }?>
</tr>
</thead>
<tbody id="id_tbody_<?php echo $t_n_url ?>"
    <?php
    if (isset($tab_pos[$t_n]) && empty($tab_pos[$t_n]["V"])) {
        echo 'style="display: none;"';
    }?>>
    <?php
    $display_field = PMA_getDisplayField($db, $GLOBALS['PMD']["TABLE_NAME_SMALL"][$i]);
    for ($j = 0, $id_cnt = count($tab_column[$t_n]["COLUMN_ID"]); $j < $id_cnt; $j++) {
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
        if (! PMA_foreignkey_supported($GLOBALS['PMD']['TABLE_TYPE'][$i])) {
            echo (isset($tables_pk_or_unique_keys[$t_n . "." . $tab_column[$t_n]["COLUMN_NAME"][$j]]) ? 1 : 0);
        } else {
            // if foreign keys are supported, it's not necessary that the
            // index is a primary key
            echo (isset($tables_all_keys[$t_n.".".$tab_column[$t_n]["COLUMN_NAME"][$j]]) ? 1 : 0);
        }
        ?>)">
    <?php
    if (isset($_REQUEST['query'])) {
        echo '<td class="select_all">';
        echo '<input value="'.htmlspecialchars($t_n_url).urlencode($tab_column[$t_n]["COLUMN_NAME"][$j]).'"';
        echo 'type="checkbox" id="select_'.htmlspecialchars($t_n_url).'._'.urlencode($tab_column[$t_n]["COLUMN_NAME"][$j]).'" ';
        echo 'style="margin: 0px;" title="select_'.urlencode($tab_column[$t_n]["COLUMN_NAME"][$j]).'" ';
        echo 'onclick="store_column(\''.urlencode($GLOBALS['PMD_OUT']["TABLE_NAME_SMALL"][$i]).'\',\''.htmlspecialchars($GLOBALS['PMD_OUT']["OWNER"][$i]).'\',\''.urlencode($tab_column[$t_n]["COLUMN_NAME"][$j]).'\')"></td>';
    }?>
    <td width="10px" colspan="3"
        id="<?php echo $t_n_url.".".urlencode($tab_column[$t_n]["COLUMN_NAME"][$j]) ?>">
        <div style="white-space:nowrap">
        <?php
        if (isset($tables_pk_or_unique_keys[$t_n.".".$tab_column[$t_n]["COLUMN_NAME"][$j]])) {
            ?>
                <img src="<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>pmd/FieldKey_small.png"
                    alt="*" />
            <?php
        } else {
            ?>
                    <img src="<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>pmd/Field_small<?php
            if (strstr($tab_column[$t_n]["TYPE"][$j], 'char')
             || strstr($tab_column[$t_n]["TYPE"][$j], 'text')) {
                echo '_char';
            } elseif (strstr($tab_column[$t_n]["TYPE"][$j], 'int')
             || strstr($tab_column[$t_n]["TYPE"][$j], 'float')
             || strstr($tab_column[$t_n]["TYPE"][$j], 'double')
             || strstr($tab_column[$t_n]["TYPE"][$j], 'decimal')) {
                echo '_int';
            } elseif (strstr($tab_column[$t_n]["TYPE"][$j], 'date')
             || strstr($tab_column[$t_n]["TYPE"][$j], 'time')
             || strstr($tab_column[$t_n]["TYPE"][$j], 'year')) {
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
   <?php
   if (isset($_REQUEST['query'])) {
       //$temp = $GLOBALS['PMD_OUT']["OWNER"][$i].'.'.$GLOBALS['PMD_OUT']["TABLE_NAME_SMALL"][$i];
       echo '<td class="small_tab_pref" onmouseover="this.className=\'small_tab_pref2\';"';
       echo 'onmouseout="this.className=\'small_tab_pref\';"';
       echo 'onclick="Click_option(\'pmd_optionse\',\''.urlencode($tab_column[$t_n]["COLUMN_NAME"][$j]).'\',\''.$GLOBALS['PMD_OUT']["TABLE_NAME_SMALL"][$i].'\')" >';
       echo  '<img src="' . $GLOBALS['pmaThemeImage'] . 'pmd/exec_small.png" title="options" alt="" /></td> ';
    } ?>
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
<div id="pmd_hint"></div>
<div id='layer_action' style="display:none;">Load...</div>

<table id="layer_new_relation" style="display:none;"
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
            <td colspan="2" align="center" nowrap="nowrap"><strong><?php echo __('Create relation'); ?></strong></td>
        </tr>
        </thead>
        <tbody id="foreign_relation">
        <tr>
        <td colspan="2" align="center" nowrap="nowrap"><strong>FOREIGN KEY</strong></td>
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
                    value="<?php echo __('OK'); ?>" onclick="New_relation()" />
                <input type="button" class="butt" name="Button"
                    value="<?php echo __('Cancel'); ?>"
                    onclick="document.getElementById('layer_new_relation').style.display = 'none';" />
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

<table id="layer_upd_relation" style="display:none;"
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
            <td colspan="3" align="center" nowrap="nowrap"><strong><?php echo __('Delete relation'); ?></strong></td>
        </tr>
        <tr>
            <td colspan="3" align="center" nowrap="nowrap">
                <input name="Button" type="button" class="butt"
                    onclick="Upd_relation()" value="<?php echo __('Delete'); ?>" />
                <input type="button" class="butt" name="Button"
                    value="<?php echo __('Cancel'); ?>"
                    onclick="document.getElementById('layer_upd_relation').style.display = 'none'; Re_load();" />
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

<table id="pmd_optionse" style="display:none;"
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
            <td colspan="2" rowspan="2" id="option_col_name" nowrap="nowrap" align="center"></td>
        </tr>
        </thead>
        <tbody id="where">
        <tr><td align="center" nowrap="nowrap"><b>WHERE</b></td></tr>
        <tr>
        <td width="58" nowrap="nowrap"><?php echo __('Relation operator'); ?></td>
            <td width="102"><select name="rel_opt" id="rel_opt">
                    <option value="--" selected="selected"> -- </option>
                    <option value="=" > = </option>
                    <option value=">"> > </option>
                    <option value="<"> < </option>
                    <option value=">="> >= </option>
                    <option value="<="> <= </option>
                    <option value="NOT"> NOT </option>
                    <option value="IN"> IN </option>
                    <option value="EXCEPT"> <?php echo __('Except'); ?> </option>
                    <option value="NOT IN"> NOT IN </option>
                </select>
            </td>
        </tr>
        <tr>
        <td nowrap="nowrap"><?php echo __('Value'); ?>/<br /><?php echo __('subquery'); ?></td>
            <td><textarea id="Query" value="" cols="18"></textarea>
            </td>
        </tr>
        <tr><td align="center" nowrap="nowrap"><b><?php echo __('Rename to'); ?></b></td></tr>
        <tr>
        <td width="58" nowrap="nowrap"><?php echo __('New name'); ?></td>
            <td width="102"><input type="text" value="" id="new_name"/></td>
        </tr>
        <tr><td align="center" nowrap="nowrap"><b><?php echo __('Aggregate'); ?></b></td></tr>
         <tr>
         <td width="58" nowrap="nowrap"><?php echo __('Operator'); ?></td>
            <td width="102"><select name="operator" id="operator">
                    <option value="---" selected="selected">---</option>
                    <option value="sum" > SUM </option>
                    <option value="min"> MIN </option>
                    <option value="max"> MAX </option>
                    <option value="avg"> AVG </option>
                    <option value="count"> COUNT </option>
                    </select>
           </td></tr>
           <tr>
                <td nowrap="nowrap" width="58" align="center"><b>GROUP BY</b></td>
                <td><input type="checkbox" value="groupby" id="groupby"/></td>
           </tr>
           <tr>
                <td nowrap="nowrap" width="58" align="center"><b>ORDER BY</b></td>
                <td><input type="checkbox" value="orderby" id="orderby"/></td>
           </tr>
          <tr><td align="center" nowrap="nowrap"><b>HAVING</b></td></tr>
          <tr>
          <td width="58" nowrap="nowrap"><?php echo __('Operator'); ?></td>
            <td width="102"><select name="h_operator" id="h_operator">
                    <option value="---" selected="selected">---</option>
                    <option value="None" > <?php echo __('None'); ?> </option>
                    <option value="sum" > SUM </option>
                    <option value="min"> MIN </option>
                    <option value="max"> MAX </option>
                    <option value="avg"> AVG </option>
                    <option value="count"> COUNT </option>
                    </select>
               </td></tr>
            <tr>
            <td width="58" nowrap="nowrap"><?php echo __('Relation operator'); ?></td>
            <td width="102"><select name="h_rel_opt" id="h_rel_opt">
                    <option value="--" selected="selected"> -- </option>
                    <option value="=" > = </option>
                    <option value=">"> > </option>
                    <option value="<"> < </option>
                    <option value=">="> >= </option>
                    <option value="<="> <= </option>
                    <option value="NOT"> NOT </option>
                    <option value="IN"> IN </option>
                    <option value="EXCEPT"> <?php echo __('Except'); ?> </option>
                    <option value="NOT IN"> NOT IN </option>
                </select>
            </td>
            </tr>
            <tr>
            <td width="58" nowrap="nowrap"><?php echo __('Value'); ?>/<br/><?php echo __('subquery'); ?></td>
                <td width="102"><textarea id="having" value="" cols="18"></textarea></td>
            </tr>
        </tbody>
        <tbody>
        <tr>
            <td colspan="2" align="center" nowrap="nowrap">
                <input type="button" class="butt" name="Button"
                    value="<?php echo __('OK'); ?>" onclick="add_object()" />
                <input type="button" class="butt" name="Button"
                    value="<?php echo __('Cancel'); ?>"
                    onclick="Close_option()" />
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

<table id="query_rename_to" style="display:none;"
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
        <td colspan="2" align="center" nowrap="nowrap"><strong><?php echo __('Rename to'); ?></strong></td>
        </tr>
        </thead>
        <tbody id="rename_to">
        <tr>
        <td width="58" nowrap="nowrap"><?php echo __('New name'); ?></td>
            <td width="102">
                <input type="text" value="" id="e_rename"/>
            </td>
        </tr>
        </tbody>
        <tbody>
        <tr>
            <td colspan="2" align="center" nowrap="nowrap">
                <input type="button" class="butt" name="Button"
                    value="<?php echo __('OK'); ?>" onclick="edit('Rename')" />
                <input type="button" class="butt" name="Button"
                    value="<?php echo __('Cancel'); ?>"
                    onclick="document.getElementById('query_rename_to').style.display = 'none';" />
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

<table id="query_having" style="display:none;"
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
          <td colspan="2" align="center" nowrap="nowrap"><strong>HAVING</strong></td>
        </tr>
        </thead>
        <tbody id="rename_to">
        <tr>
        <td width="58" nowrap="nowrap"><?php echo __('Operator'); ?></td>
            <td width="102"><select name="hoperator" id="hoperator">
                    <option value="---" selected="selected">---</option>
                    <option value="None" > None </option>
                    <option value="sum" > SUM </option>
                    <option value="min"> MIN </option>
                    <option value="max"> MAX </option>
                    <option value="avg"> AVG </option>
                    <option value="count"> COUNT </option>
                    </select>
           </td></tr>
        <tr>
        <tr>
        <td width="58" nowrap="nowrap"><?php echo __('Operator'); ?></td>
            <td width="102"><select name="hrel_opt" id="hrel_opt">
                <option value="--" selected="selected"> -- </option>
                    <option value="=" > = </option>
                    <option value=">"> > </option>
                    <option value="<"> < </option>
                    <option value=">="> >= </option>
                    <option value="<="> <= </option>
                    <option value="NOT"> NOT </option>
                    <option value="IN"> IN </option>
                    <option value="EXCEPT"> <?php echo __('Except'); ?> </option>
                    <option value="NOT IN"> NOT IN </option>
                </select>
            </td>
        </tr>
        <tr>
        <td nowrap="nowrap"><?php echo __('Value'); ?>/<br /><?php echo __('subquery'); ?></td>
            <td><textarea id="hQuery" value="" cols="18"></textarea>
            </td>
            </tr>
         </tbody>
        <tbody>
        <tr>
            <td colspan="2" align="center" nowrap="nowrap">
                <input type="button" class="butt" name="Button"
                    value="<?php echo __('OK'); ?>" onclick="edit('Having')" />
                <input type="button" class="butt" name="Button"
                    value="<?php echo __('Cancel'); ?>"
                    onclick="document.getElementById('query_having').style.display = 'none';" />
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

<table id="query_Aggregate" style="display:none;"
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
        <td colspan="2" align="center" nowrap="nowrap"><strong><?php echo __('Aggregate'); ?></strong></td>
        </tr>
        </thead>
        <tbody>
        <tr>
        <td width="58" nowrap="nowrap"><?php echo __('Operator'); ?></td>
            <td width="102">
                <select name="operator" id="e_operator">
                    <option value="---" selected="selected">---</option>
                    <option value="sum" > SUM </option>
                    <option value="min"> MIN </option>
                    <option value="max"> MAX </option>
                       <option value="avg"> AVG </option>
                    <option value="avg"> COUNT </option>
                </select>
           </td></tr>
        </tbody>
        <tbody>
        <tr>
            <td colspan="2" align="center" nowrap="nowrap">
                <input type="button" class="butt" name="Button"
                    value="<?php echo __('OK'); ?>" onclick="edit('Aggregate')" />
                <input type="button" class="butt" name="Button"
                    value="<?php echo __('Cancel'); ?>"
                    onclick="document.getElementById('query_Aggregate').style.display = 'none';" />
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

<table id="query_where" style="display:none;"
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
          <td colspan="2" align="center" nowrap="nowrap"><strong>WHERE</strong></td>
        </tr>
        </thead>
        <tbody id="rename_to">
        <tr>
        <td width="58" nowrap="nowrap"><?php echo __('Operator'); ?></td>
            <td width="102"><select name="erel_opt" id="erel_opt">
                <option value="--" selected="selected"> -- </option>
                    <option value="=" > = </option>
                    <option value=">"> > </option>
                    <option value="<"> < </option>
                    <option value=">="> >= </option>
                    <option value="<="> <= </option>
                    <option value="NOT"> NOT </option>
                    <option value="IN"> IN </option>
                    <option value="EXCEPT"> <?php echo __('Except'); ?> </option>
                    <option value="NOT IN"> NOT IN </option>
                </select>
            </td>
        </tr>
        <tr>
        <td nowrap="nowrap"><?php echo __('Value'); ?>/<br /><?php echo __('subquery'); ?></td>
            <td><textarea id="eQuery" value="" cols="18"></textarea>
            </td>
            </tr>
         </tbody>
        <tbody>
        <tr>
            <td colspan="2" align="center" nowrap="nowrap">
                <input type="button" class="butt" name="Button"
                    value="<?php echo __('OK'); ?>" onclick="edit('Where')" />
                <input type="button" class="butt" name="Button"
                    value="<?php echo __('Cancel'); ?>"
                    onclick="document.getElementById('query_where').style.display = 'none';" />
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

<?php
if (! empty($_REQUEST['query'])) {
    echo '<div class="panel">';
    echo '<div style="clear:both;"></div>';
    echo '<div id="ab"></div>';
    echo '<div style="clear:both;"></div>';
    echo '</div>';
    echo '<a class="trigger" href="#">' . __('Active options') . '</a>';
    echo '<div id="filter"></div>';
    echo '<div id="box">';
    echo '<span id="boxtitle"></span>';
    echo '<form method="post" action="db_qbe.php" >';
    echo '<textarea cols="80" name="sql_query" id="textSqlquery" rows="15"></textarea><div id="tblfooter">';
    echo '  <input type="submit" name="submit_sql" class="btn">';
    echo '  <input type="button" name="cancel" value="' . __('Cancel') . '" onclick="closebox()" class="btn">';
    echo PMA_generate_common_hidden_inputs($GLOBALS['db']);
    echo '</div></p>';
    echo '</form></div>';

} ?>


<!-- cache images -->
<img src="<?php echo $GLOBALS['pmaThemeImage'] ?>pmd/2leftarrow_m.png" width="0" height="0" alt="" />
<img src="<?php echo $GLOBALS['pmaThemeImage'] ?>pmd/rightarrow1.png" width="0" height="0" alt="" />
<img src="<?php echo $GLOBALS['pmaThemeImage'] ?>pmd/rightarrow2.png" width="0" height="0" alt="" />
<img src="<?php echo $GLOBALS['pmaThemeImage'] ?>pmd/uparrow2_m.png" width="0" height="0" alt="" />
<div id="PMA_disable_floating_menubar"></div>
</body>
</html>
