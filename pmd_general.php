<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * phpMyAdmin designer general code
 *
 * @package PhpMyAdmin-Designer
 */

/**
 *
 */
require_once 'libraries/common.inc.php';
require_once 'libraries/pmd_common.php';

$script_display_field = PMA_getTablesInfo();
$tab_column       = PMA_getColumnsInfo();
$script_tables    = PMA_getScriptTabs();
$script_contr     = PMA_getScriptContr();
$tab_pos          = PMA_getTabPos();
$tables_pk_or_unique_keys = PMA_getPKOrUniqueKeys();
$tables_all_keys  = PMA_getAllKeys();

$params = array('lang' => $GLOBALS['lang']);
if (isset($_GET['db'])) {
    $params['db'] = $_GET['db'];
}

$response = PMA_Response::getInstance();
$response->getFooter()->setMinimal();
$header   = $response->getHeader();
$header->setBodyId('pmd_body');
$scripts = $header->getScripts();
$scripts->addFile('jquery/jquery.fullscreen.js');
$scripts->addFile('pmd/ajax.js');
$scripts->addFile('pmd/history.js');
$scripts->addFile('pmd/move.js');
$scripts->addFile('pmd/iecanvas.js', true);
$scripts->addFile('pmd/init.js');

require 'libraries/db_common.inc.php';
require 'libraries/db_info.inc.php';

// Embed some data into HTML, later it will be read
// by pmd/init.js and converted to JS variables.
echo '<div id="script_server" class="hide">';
echo htmlspecialchars($GLOBALS['server']);
echo '</div>';
echo '<div id="script_db" class="hide">';
echo htmlspecialchars($_GET['db']);
echo '</div>';
echo '<div id="script_token" class="hide">';
echo htmlspecialchars($_GET['token']);
echo '</div>';
echo '<div id="script_tables" class="hide">';
echo htmlspecialchars(json_encode($script_tables));
echo '</div>';
echo '<div id="script_contr" class="hide">';
echo htmlspecialchars(json_encode($script_contr));
echo '</div>';
echo '<div id="script_display_field" class="hide">';
echo htmlspecialchars(json_encode($script_display_field));
echo '</div>';

?>
<div class="pmd_header" id="top_menu">
    <a href="#" onclick="Show_left_menu(document.getElementById('key_Show_left_menu')); return false"
        class="M_butt first" target="_self">
        <img id='key_Show_left_menu'
            title="<?php echo __('Show/Hide left menu'); ?>" alt="v"
            src="<?php echo $_SESSION['PMA_Theme']->getImgPath('pmd/downarrow2_m.png'); ?>" />
    </a>
    <a href="#" id="enterFullscreen" onclick="Enter_fullscreen(); return false"
        class="M_butt" target="_self">
        <img title="<?php echo __('View in fullscreen') ?>" alt=""
            src="<?php echo $_SESSION['PMA_Theme']->getImgPath('pmd/viewInFullscreen.png'); ?>" />
    </a>
    <a href="#" id="exitFullscreen" onclick="Exit_fullscreen(); return false"
        class="M_butt hide" target="_self">
        <img title="<?php echo __('Exit fullscreen') ?>" alt=""
            src="<?php echo $_SESSION['PMA_Theme']->getImgPath('pmd/exitFullscreen.png'); ?>" />
    </a>
    <img class="M_bord"
        src="<?php echo $_SESSION['PMA_Theme']->getImgPath('pmd/bord.png'); ?>"
        alt="" />
    <a href="#" onclick="Save2(); return false" class="M_butt" target="_self">
        <img title="<?php echo __('Save position') ?>" alt=""
            src="<?php echo $_SESSION['PMA_Theme']->getImgPath('pmd/save.png'); ?>" />
    </a>
    <a href="#" onclick="Start_table_new(); return false"
        class="M_butt" target="_self">
        <img title="<?php echo __('Create table')?>" alt=""
            src="<?php echo $_SESSION['PMA_Theme']->getImgPath('pmd/table.png'); ?>" />
    </a>
    <a href="#" onclick="Start_relation(); return false" class="M_butt"
        id="rel_button" target="_self">
        <img title="<?php echo __('Create relation') ?>" alt=""
            src="<?php echo $_SESSION['PMA_Theme']->getImgPath('pmd/relation.png'); ?>" />
    </a>
    <a href="#" onclick="Start_display_field(); return false"
        class="M_butt" id="display_field_button" target="_self">
        <img title="<?php echo __('Choose column to display') ?>" alt=""
            src="<?php echo $_SESSION['PMA_Theme']->getImgPath('pmd/display_field.png'); ?>" />
    </a>
    <a href="#" onclick="location.reload(); return false" class="M_butt"
        target="_self">
        <img title="<?php echo __('Reload'); ?>" alt=""
            src="<?php echo $_SESSION['PMA_Theme']->getImgPath('pmd/reload.png'); ?>" />
    </a>
    <a href="<?php echo PMA_Util::getDocuLink('faq', 'faq6-31') ?>"
        target="documentation" class="M_butt" target="_self">
        <img title="<?php echo __('Help'); ?>" alt=""
            src="<?php echo $_SESSION['PMA_Theme']->getImgPath('pmd/help.png'); ?>" />
    </a>
    <img class="M_bord"
        src="<?php echo $_SESSION['PMA_Theme']->getImgPath('pmd/bord.png'); ?>"
        alt="" />
    <a href="#" onclick="Angular_direct(); return false"
        class="M_butt" id="angular_direct_button" target="_self">
        <img title="<?php echo __('Angular links') . ' / ' . __('Direct links'); ?>" alt=""
            src="<?php echo $_SESSION['PMA_Theme']->getImgPath('pmd/ang_direct.png'); ?>" />
    </a>
    <a href="#" onclick="Grid(); return false" class="M_butt" id="grid_button"
        target="_self">
        <img title="<?php echo __('Snap to grid') ?>"
            src="<?php echo $_SESSION['PMA_Theme']->getImgPath('pmd/grid.png'); ?>" alt="" />
    </a>
    <img class="M_bord" src="<?php echo $_SESSION['PMA_Theme']->getImgPath('pmd/bord.png'); ?>" alt="" />
    <a href="#" onclick="Small_tab_all(document.getElementById('key_SB_all')); return false"
        class="M_butt" target="_self">
        <img id='key_SB_all' title="<?php echo __('Small/Big All'); ?>" alt="v"
            src="<?php echo $_SESSION['PMA_Theme']->getImgPath('pmd/downarrow1.png'); ?>" />
    </a>
    <a href="#" onclick="Small_tab_invert(); return false" class="M_butt"
        target="_self" >
        <img title="<?php echo __('Toggle small/big'); ?>" alt="key"
            src="<?php echo $_SESSION['PMA_Theme']->getImgPath('pmd/bottom.png'); ?>" />
    </a>
    <a href="#" onclick="Relation_lines_invert(); return false"
        class="M_butt" target="_self" >
        <img title="<?php echo __('Toggle relation lines'); ?>" alt="key"
            src="<?php echo $_SESSION['PMA_Theme']->getImgPath('pmd/toggle_lines.png'); ?>" />
    </a>
    <img class="M_bord" src="<?php echo $_SESSION['PMA_Theme']->getImgPath('pmd/bord.png'); ?>" alt="" />
    <a href="#" onclick="PDF_save(); return false" class="M_butt ajax">
        <img src="<?php echo $_SESSION['PMA_Theme']->getImgPath('pmd/pdf.png'); ?>" alt="key"
            width="20" height="20" title="<?php echo __('Import/Export coordinates for relational schema'); ?>" />
    </a>
<?php
if (isset($_REQUEST['query'])) {
    echo '<a href="#" onclick="build_query(\'SQL Query on Database\', 0)" onmousedown="return false;"
    class="M_butt" target="_self">';
    echo '<img src="'
        . $_SESSION['PMA_Theme']->getImgPath('pmd/query_builder.png')
        . '" alt="key" width="20" height="20" title="';
    echo __('Build Query');
    echo '"/></a>';
}
?>
    <a href="#" onclick="Top_menu_right(document.getElementById('key_Left_Right')); return false"
        class="M_butt last" target="_self">
        <img src="<?php echo $_SESSION['PMA_Theme']->getImgPath('pmd/2rightarrow_m.png'); ?>"
            id="key_Left_Right" alt=">" title="<?php echo __('Move Menu'); ?>" />
    </a>
</div>

<div id="canvas_outer">
<form action="" method="post" name="form1">
<div id="osn_tab">
  <canvas class="pmd" id="canvas" width="100" height="100"
          onclick="Canvas_click(this)"></canvas>
</div>
<div id="layer_menu" style="display:none;">
<div class="center" style="padding-top:5px;">
    <a href="#"
        onclick="Hide_tab_all(document.getElementById('key_HS_all')); return false" class="M_butt" target="_self">
    <img title="<?php echo __('Hide/Show all'); ?>" alt="v"
        src="<?php echo $_SESSION['PMA_Theme']->getImgPath('pmd/downarrow1.png'); ?>" id='key_HS_all' /></a>
    <a href="#"
        onclick="No_have_constr(document.getElementById('key_HS')); return false" class="M_butt" target="_self">
    <img title="<?php echo __('Hide/Show Tables with no relation'); ?>" alt="v"
        src="<?php echo $_SESSION['PMA_Theme']->getImgPath('pmd/downarrow2.png'); ?>" id='key_HS' /></a>
</div>

<div id="id_scroll_tab" class="scroll_tab">
    <table width="100%" style="padding-left: 3px;">
<?php
$name_cnt = count($GLOBALS['PMD']['TABLE_NAME']);
for ($i = 0; $i < $name_cnt; $i++) {

    echo '<tr><td title="' . __('Structure') . '" width="1px" '
        . 'onmouseover="this.className=\'L_butt2_2\'" '
        . 'onmouseout="this.className=\'L_butt2_1\'" class="L_butt2_1">';
    echo '<img '
        . 'onclick="Start_tab_upd(\''
        . $GLOBALS['PMD_URL']["TABLE_NAME_SMALL"][$i] . '\');" '
        . 'src="' . $_SESSION['PMA_Theme']->getImgPath('pmd/exec.png')
        . '" alt="" />';
    echo '</td>';
    echo '<td width="1px">';
    echo '<input onclick="VisibleTab(this,\''
        . $GLOBALS['PMD_URL']["TABLE_NAME"][$i] . '\')" '
        . 'title="' . __('Hide') . '" '
        . 'id="check_vis_' . $GLOBALS['PMD_URL']["TABLE_NAME"][$i] . '" '
        . 'style="margin:0px;" type="checkbox" '
        . 'value="' . $GLOBALS['PMD_URL']["TABLE_NAME"][$i] . '"';
    if (isset($tab_pos[$GLOBALS['PMD']["TABLE_NAME"][$i]])) {
        echo $tab_pos[$GLOBALS['PMD']["TABLE_NAME"][$i]]["H"]
            ? 'checked="checked"'
            : '';
    } else {
        echo 'checked="checked"';
    }
    echo '/></td>';
    echo '<td class="pmd_Tabs" onmouseover="this.className=\'pmd_Tabs2\'" '
        . 'onmouseout="this.className=\'pmd_Tabs\'" ' . 'onclick="Select_tab(\''
        . $GLOBALS['PMD_URL']["TABLE_NAME"][$i] . '\');">';
    echo $GLOBALS['PMD_OUT']["TABLE_NAME"][$i];
    echo '</td>';
    echo '</tr>';
}
echo '</table>';
echo '</div>';

echo '<div class="center">';
echo __('Number of tables:') . ' ' . $name_cnt;
echo '</div>';
echo '<div class="floatright">';
echo '<div id="layer_menu_sizer" onmousedown="layer_menu_cur_click=1">';
echo '</div>';
echo '</div>';
echo '</div>';

for ($i = 0; $i < count($GLOBALS['PMD']["TABLE_NAME"]); $i++) {
    $t_n = $GLOBALS['PMD']["TABLE_NAME"][$i];
    $t_n_url = $GLOBALS['PMD_URL']["TABLE_NAME"][$i];

    echo '<input name="t_x[' . $t_n_url . ']" type="hidden" id="t_x_'
        . $t_n_url . '_" />'
        . '<input name="t_y[' . $t_n_url . ']" type="hidden" id="t_y_'
        . $t_n_url . '_" />'
        . '<input name="t_v[' . $t_n_url . ']" type="hidden" id="t_v_'
        . $t_n_url . '_" />'
        . '<input name="t_h[' . $t_n_url . ']" type="hidden" id="t_h_'
        . $t_n_url . '_" />';
?>
<table id="<?php echo $t_n_url ?>" cellpadding="0" cellspacing="0" class="pmd_tab"
    style="position: absolute;
          left: <?php
          echo isset($tab_pos[$t_n]) ? $tab_pos[$t_n]["X"] : rand(20, 700); ?>px;
          top: <?php
          echo isset($tab_pos[$t_n]) ? $tab_pos[$t_n]["Y"] : rand(20, 550); ?>px;
          visibility: <?php
          echo ! isset($tab_pos[$t_n]) || $tab_pos[$t_n]["H"]
            ? "visible"
            : "hidden"; ?>;
         z-index: 1;">
<thead>
<tr>
    <?php
    if (isset($_REQUEST['query'])) {
        echo '<td class="select_all">';
        echo '<input type="checkbox" value="select_all_'
            . htmlspecialchars($t_n_url) . '" style="margin: 0px;" ';
        echo 'id="select_all_' . htmlspecialchars($t_n_url) . '" title="select all" ';
        echo 'onclick="Select_all(\'' . htmlspecialchars($t_n_url) . '\',\''
            . htmlspecialchars($GLOBALS['PMD_OUT']["OWNER"][$i]) . '\')"></td>';
    }
    ?>
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
        <img src="<?php echo $_SESSION['PMA_Theme']->getImgPath('pmd/exec_small.png'); ?>" alt="" /></td>
    <td id="id_zag_<?php echo $t_n_url ?>" class="tab_zag nowrap"
        onmousedown="cur_click=document.getElementById('<?php echo $t_n_url ?>');"
        onmouseover="Table_onover('<?php echo $t_n_url ?>',0,<?php echo (isset($_REQUEST['query'])? 1 : 0 )?> )"
        onmouseout="Table_onover('<?php echo $t_n_url ?>',1,<?php echo (isset($_REQUEST['query']) ? 1 : 0 )?>)">
        <span class='owner'>
            <?php echo $GLOBALS['PMD_OUT']["OWNER"][$i]; ?>.
        </span>
        <?php echo $GLOBALS['PMD_OUT']["TABLE_NAME_SMALL"][$i]; ?>
    </td>
    <?php
    if (isset($_REQUEST['query'])) {
        echo '<td class="tab_zag"  onmouseover="Table_onover(\''
            . htmlspecialchars($t_n_url) . '\',0,1)"  id="id_zag_'
            . htmlspecialchars($t_n_url) . '_2"';
        echo 'onmousedown="cur_click=document.getElementById(\''
            . htmlspecialchars($t_n_url) . '\');"';
        echo 'onmouseout="Table_onover(\'' . htmlspecialchars($t_n_url) . '\',1,1)">';
    }
    ?>
    </tr>
    </thead>
    <tbody id="id_tbody_<?php echo $t_n_url ?>"
    <?php
    if (isset($tab_pos[$t_n]) && empty($tab_pos[$t_n]["V"])) {
        echo 'style="display: none;"';
    }
    echo '>';
    $display_field = PMA_getDisplayField(
        $_GET['db'],
        $GLOBALS['PMD']["TABLE_NAME_SMALL"][$i]
    );
    for (
        $j = 0, $id_cnt = count($tab_column[$t_n]["COLUMN_ID"]);
        $j < $id_cnt;
        $j++
    ) {
        echo '<tr id="id_tr_'
            . $GLOBALS['PMD_URL']["TABLE_NAME_SMALL"][$i] . '.'
            . urlencode($tab_column[$t_n]["COLUMN_NAME"][$j]) . '"';
        if ($display_field == $tab_column[$t_n]["COLUMN_NAME"][$j]) {
            echo ' class="tab_field_3" ';
        } else {
            echo ' class="tab_field" ';
        }
        ?>
        onmouseover="old_class = this.className; this.className = 'tab_field_2';"
        onmouseout="this.className = old_class;"
        onmousedown="Click_field('<?php
        echo $GLOBALS['PMD_URL']["TABLE_NAME_SMALL"][$i] . "','"
            . urlencode($tab_column[$t_n]["COLUMN_NAME"][$j]) . "',";
        $tmpColumn = $t_n . "." . $tab_column[$t_n]["COLUMN_NAME"][$j];
        if (!PMA_Util::isForeignKeySupported($GLOBALS['PMD']['TABLE_TYPE'][$i])) {
            echo (isset($tables_pk_or_unique_keys[$tmpColumn]) ? 1 : 0);
        } else {
            // if foreign keys are supported, it's not necessary that the
            // index is a primary key
            echo (isset($tables_all_keys[$tmpColumn]) ? 1 : 0);
        }
        ?>)">
    <?php
        if (isset($_REQUEST['query'])) {
            echo '<td class="select_all">';
            echo '<input value="' . htmlspecialchars($t_n_url)
            . urlencode($tab_column[$t_n]["COLUMN_NAME"][$j]) . '"';
            echo 'type="checkbox" id="select_' . htmlspecialchars($t_n_url) . '._'
            . urlencode($tab_column[$t_n]["COLUMN_NAME"][$j]) . '" ';
            echo  'style="margin: 0px;" title="select_'
            . urlencode($tab_column[$t_n]["COLUMN_NAME"][$j]) . '" ';
            echo 'onclick="store_column(\''
            . urlencode($GLOBALS['PMD_OUT']["TABLE_NAME_SMALL"][$i]) . '\',\''
            . htmlspecialchars($GLOBALS['PMD_OUT']["OWNER"][$i]) . '\',\''
            . urlencode($tab_column[$t_n]["COLUMN_NAME"][$j]) . '\')"></td>';
        }?>
        <td width="10px" colspan="3"
        id="<?php echo $t_n_url
             . "."
             . urlencode($tab_column[$t_n]["COLUMN_NAME"][$j]) ?>">
        <div class="nowrap">
        <?php
        if (isset($tables_pk_or_unique_keys[$t_n . "." . $tab_column[$t_n]["COLUMN_NAME"][$j]])) {
            ?>
                <img src="<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>pmd/FieldKey_small.png"
                    alt="*" />
            <?php
        } else {
            ?>
                    <img src="<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>pmd/Field_small<?php
            if (strstr($tab_column[$t_n]["TYPE"][$j], 'char')
                || strstr($tab_column[$t_n]["TYPE"][$j], 'text')
            ) {
                echo '_char';
            } elseif (strstr($tab_column[$t_n]["TYPE"][$j], 'int')
                || strstr($tab_column[$t_n]["TYPE"][$j], 'float')
                || strstr($tab_column[$t_n]["TYPE"][$j], 'double')
                || strstr($tab_column[$t_n]["TYPE"][$j], 'decimal')
            ) {
                echo '_int';
            } elseif (strstr($tab_column[$t_n]["TYPE"][$j], 'date')
                || strstr($tab_column[$t_n]["TYPE"][$j], 'time')
                || strstr($tab_column[$t_n]["TYPE"][$j], 'year')
            ) {
                echo '_date';
            }
            ?>.png" alt="*" />
            <?php
        }
        echo htmlspecialchars(
            $tab_column[$t_n]["COLUMN_NAME"][$j] . " : "
            . $tab_column[$t_n]["TYPE"][$j],
            ENT_QUOTES
        );
        echo "</div>\n</td>\n";
        if (isset($_REQUEST['query'])) {
            /*$temp = $GLOBALS['PMD_OUT']["OWNER"][$i] . '.'
            . $GLOBALS['PMD_OUT']["TABLE_NAME_SMALL"][$i];*/
            echo '<td class="small_tab_pref" '
                . 'onmouseover="this.className=\'small_tab_pref2\';"';
            echo 'onmouseout="this.className=\'small_tab_pref\';"';
            echo 'onclick="Click_option(\'pmd_optionse\',\''
                . urlencode($tab_column[$t_n]["COLUMN_NAME"][$j]) . '\',\''
                . $GLOBALS['PMD_OUT']["TABLE_NAME_SMALL"][$i] . '\')" >';
            echo  '<img src="'
                . $_SESSION['PMA_Theme']->getImgPath('pmd/exec_small.png')
                . '" title="options" alt="" /></td> ';
        }
        echo "</tr>\n";
    }
    echo "</tbody>\n</table>\n";
}
?>
</form>
</div>
<div id="pmd_hint"></div>

<table id="layer_new_relation" style="display:none;"
    width="5%" cellpadding="0" cellspacing="0">
<tbody>
<tr>
    <td class="frams1" width="10px"></td>
    <td class="frams5" width="99%" ></td>
    <td class="frams2" width="10px"><div class="bor"></div></td>
</tr>
<tr>
    <td class="frams8"></td>
    <td class="input_tab">
        <table width="168" class="center" cellpadding="2" cellspacing="0">
        <thead>
        <tr>
            <td colspan="2" class="center nowrap">
                <strong><?php echo __('Create relation'); ?></strong>
            </td>
        </tr>
        </thead>
        <tbody id="foreign_relation">
        <tr>
        <td colspan="2" class="center nowrap"><strong>FOREIGN KEY</strong></td>
        </tr>
        <tr>
            <td width="58" class="nowrap">on delete</td>
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
            <td class="nowrap">on update</td>
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
            <td colspan="2" class="center nowrap">
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
    width="5%" cellpadding="0" cellspacing="0">
<tbody>
<tr>
    <td class="frams1" width="10px"></td>
    <td class="frams5" width="99%"></td>
    <td class="frams2" width="10px"><div class="bor"></div></td>
</tr>
<tr>
    <td class="frams8"></td>
    <td class="input_tab">
        <table width="100%" class="center" cellpadding="2" cellspacing="0">
        <tr>
            <td colspan="3" class="center nowrap">
                <strong><?php echo __('Delete relation'); ?></strong>
            </td>
        </tr>
        <tr>
            <td colspan="3" class="center nowrap">
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
    width="5%" cellpadding="0" cellspacing="0">
<tbody>
<tr>
    <td class="frams1" width="10px"></td>
    <td class="frams5" width="99%" ></td>
    <td class="frams2" width="10px"><div class="bor"></div></td>
</tr>
<tr>
    <td class="frams8"></td>
    <td class="input_tab">
        <table width="168" class="center" cellpadding="2" cellspacing="0">
       <thead>
        <tr>
            <td colspan="2" rowspan="2" id="option_col_name"
                class="center nowrap">
            </td>
        </tr>
        </thead>
        <tbody id="where">
        <tr><td class="center nowrap"><b>WHERE</b></td></tr>
        <tr>
        <td width="58" class="nowrap"><?php echo __('Relation operator'); ?></td>
            <td width="102"><select name="rel_opt" id="rel_opt">
                    <option value="--" selected="selected"> -- </option>
                    <option value="="> = </option>
                    <option value="&gt;"> &gt; </option>
                    <option value="&lt;"> &lt; </option>
                    <option value="&gt;="> &gt;= </option>
                    <option value="&lt;="> &lt;= </option>
                    <option value="NOT"> NOT </option>
                    <option value="IN"> IN </option>
                    <option value="EXCEPT"> <?php echo __('Except'); ?> </option>
                    <option value="NOT IN"> NOT IN </option>
                </select>
            </td>
        </tr>
        <tr>
        <td class="nowrap"><?php echo __('Value'); ?>/<br /><?php echo __('subquery'); ?></td>
            <td><textarea id="Query" value="" cols="18"></textarea>
            </td>
        </tr>
        <tr><td class="center nowrap"><b><?php echo __('Rename to'); ?></b></td></tr>
        <tr>
        <td width="58" class="nowrap"><?php echo __('New name'); ?></td>
            <td width="102"><input type="text" value="" id="new_name"/></td>
        </tr>
        <tr><td class="center nowrap"><b><?php echo __('Aggregate'); ?></b></td></tr>
         <tr>
         <td width="58" class="nowrap"><?php echo __('Operator'); ?></td>
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
                <td width="58" class="center nowrap"><b>GROUP BY</b></td>
                <td><input type="checkbox" value="groupby" id="groupby"/></td>
           </tr>
           <tr>
                <td width="58" class="center nowrap"><b>ORDER BY</b></td>
                <td><input type="checkbox" value="orderby" id="orderby"/></td>
           </tr>
          <tr><td class="center nowrap"><b>HAVING</b></td></tr>
          <tr>
          <td width="58" class="nowrap"><?php echo __('Operator'); ?></td>
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
            <td width="58" class="nowrap"><?php echo __('Relation operator'); ?></td>
            <td width="102"><select name="h_rel_opt" id="h_rel_opt">
                    <option value="--" selected="selected"> -- </option>
                    <option value="="> = </option>
                    <option value="&gt;"> &gt; </option>
                    <option value="&lt;"> &lt; </option>
                    <option value="&gt;="> &gt;= </option>
                    <option value="&lt;="> &lt;= </option>
                    <option value="NOT"> NOT </option>
                    <option value="IN"> IN </option>
                    <option value="EXCEPT"> <?php echo __('Except'); ?> </option>
                    <option value="NOT IN"> NOT IN </option>
                </select>
            </td>
            </tr>
            <tr>
                <td width="58" class="nowrap">
                    <?php echo __('Value'); ?>/<br/>
                    <?php echo __('subquery'); ?>
                </td>
                <td width="102">
                    <textarea id="having" value="" cols="18"></textarea>
                </td>
            </tr>
        </tbody>
        <tbody>
        <tr>
            <td colspan="2" class="center nowrap">
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
    width="5%" cellpadding="0" cellspacing="0">
<tbody>
<tr>
    <td class="frams1" width="10px"></td>
    <td class="frams5" width="99%" ></td>
    <td class="frams2" width="10px"><div class="bor"></div></td>
</tr>
<tr>
    <td class="frams8"></td>
    <td class="input_tab">
        <table width="168" class="center" cellpadding="2" cellspacing="0">
        <thead>
            <tr>
                <td colspan="2" class="center nowrap">
                    <strong><?php echo __('Rename to'); ?></strong>
                </td>
            </tr>
        </thead>
        <tbody id="rename_to">
        <tr>
        <td width="58" class="nowrap"><?php echo __('New name'); ?></td>
            <td width="102">
                <input type="text" value="" id="e_rename"/>
            </td>
        </tr>
        </tbody>
        <tbody>
        <tr>
            <td colspan="2" class="center nowrap">
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
    width="5%" cellpadding="0" cellspacing="0">
<tbody>
    <tr>
        <td class="frams1" width="10px"></td>
        <td class="frams5" width="99%" ></td>
        <td class="frams2" width="10px"><div class="bor"></div></td>
    </tr>
<tr>
    <td class="frams8"></td>
    <td class="input_tab">
    <table width="168" class="center" cellpadding="2" cellspacing="0">
       <thead>
        <tr>
          <td colspan="2" class="center nowrap"><strong>HAVING</strong></td>
        </tr>
        </thead>
        <tbody id="rename_to">
        <tr>
        <td width="58" class="nowrap"><?php echo __('Operator'); ?></td>
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
        <td width="58" class="nowrap"><?php echo __('Operator'); ?></td>
            <td width="102"><select name="hrel_opt" id="hrel_opt">
                <option value="--" selected="selected"> -- </option>
                    <option value="="> = </option>
                    <option value="&gt;"> &gt; </option>
                    <option value="&lt;"> &lt; </option>
                    <option value="&gt;="> &gt;= </option>
                    <option value="&lt;="> &lt;= </option>
                    <option value="NOT"> NOT </option>
                    <option value="IN"> IN </option>
                    <option value="EXCEPT"> <?php echo __('Except'); ?> </option>
                    <option value="NOT IN"> NOT IN </option>
                </select>
            </td>
        </tr>
        <tr>
        <td class="nowrap"><?php echo __('Value'); ?>/<br /><?php echo __('subquery'); ?></td>
            <td><textarea id="hQuery" value="" cols="18"></textarea>
            </td>
            </tr>
         </tbody>
        <tbody>
        <tr>
            <td colspan="2" class="center nowrap">
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
    width="5%" cellpadding="0" cellspacing="0">
<tbody>
<tr>
    <td class="frams1" width="10px"></td>
    <td class="frams5" width="99%" ></td>
    <td class="frams2" width="10px"><div class="bor"></div></td>
</tr>
<tr>
    <td class="frams8"></td>
    <td class="input_tab">
        <table width="168" class="center" cellpadding="2" cellspacing="0">
            <thead>
                <tr>
                    <td colspan="2" class="center nowrap">
                        <strong><?php echo __('Aggregate'); ?></strong>
                    </td>
                </tr>
            </thead>
        <tbody>
        <tr>
        <td width="58" class="nowrap"><?php echo __('Operator'); ?></td>
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
            <td colspan="2" class="center nowrap">
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
    width="5%" cellpadding="0" cellspacing="0">
<tbody>
    <tr>
        <td class="frams1" width="10px"></td>
        <td class="frams5" width="99%" ></td>
        <td class="frams2" width="10px"><div class="bor"></div></td>
    </tr>
<tr>
    <td class="frams8"></td>
    <td class="input_tab">
    <table width="168" class="center" cellpadding="2" cellspacing="0">
       <thead>
        <tr>
          <td colspan="2" class="center nowrap"><strong>WHERE</strong></td>
        </tr>
        </thead>
        <tbody id="rename_to">
        <tr>
        <td width="58" class="nowrap"><?php echo __('Operator'); ?></td>
            <td width="102"><select name="erel_opt" id="erel_opt">
                <option value="--" selected="selected"> -- </option>
                    <option value="=" > = </option>
                    <option value="&gt;"> &gt; </option>
                    <option value="&lt;"> &lt; </option>
                    <option value="&gt;="> &gt;= </option>
                    <option value="&lt;="> &lt;= </option>
                    <option value="NOT"> NOT </option>
                    <option value="IN"> IN </option>
                    <option value="EXCEPT"> <?php echo __('Except'); ?> </option>
                    <option value="NOT IN"> NOT IN </option>
                </select>
            </td>
        </tr>
        <tr>
        <td class="nowrap"><?php echo __('Value'); ?>/<br /><?php echo __('subquery'); ?></td>
            <td><textarea id="eQuery" value="" cols="18"></textarea>
            </td>
            </tr>
         </tbody>
        <tbody>
        <tr>
            <td colspan="2" class="center nowrap">
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
    echo '<form method="post" action="db_qbe.php">';
    echo '<textarea cols="80" name="sql_query" id="textSqlquery"'
        . ' rows="15"></textarea><div id="tblfooter">';
    echo '  <input type="submit" name="submit_sql" class="btn" />';
    echo '  <input type="button" name="cancel" value="'
        . __('Cancel') . '" onclick="closebox()" class="btn" />';
    echo PMA_URL_getHiddenInputs($_GET['db']);
    echo '</div></p>';
    echo '</form></div>';

} ?>


<!-- cache images -->
<?php
echo '<img src="'
    . $_SESSION['PMA_Theme']->getImgPath('pmd/2leftarrow_m.png')
    . '" width="0" height="0" alt="" />'
    . '<img src="'
    . $_SESSION['PMA_Theme']->getImgPath('pmd/rightarrow1.png')
    . '" width="0" height="0" alt="" />'
    . '<img src="'
    . $_SESSION['PMA_Theme']->getImgPath('pmd/rightarrow2.png')
    . '" width="0" height="0" alt="" />'
    . '<img src="'
    . $_SESSION['PMA_Theme']->getImgPath('pmd/uparrow2_m.png')
    . '" width="0" height="0" alt="" />'
    . '<div id="PMA_disable_floating_menubar"></div>';
?>
