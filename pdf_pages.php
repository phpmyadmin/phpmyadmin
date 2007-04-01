<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id$
 */

/**
 * Gets some core libraries
 */
require_once './libraries/common.inc.php';
require_once './libraries/db_common.inc.php';


/**
 * Settings for relation stuff
 */
require_once './libraries/relation.lib.php';
$cfgRelation = PMA_getRelationsParam();

// This is to avoid "Command out of sync" errors. Before switching this to
// a value of 0 (for MYSQLI_USE_RESULT), please check the logic
// to free results wherever needed.
$query_default_option = PMA_DBI_QUERY_STORE;

/**
 * Now in ./libraries/relation.lib.php we check for all tables
 * that we need, but if we don't find them we are quiet about it
 * so people can work without.
 * This page is absolutely useless if you didn't set up your tables
 * correctly, so it is a good place to see which tables we can and
 * complain ;-)
 */
if (!$cfgRelation['relwork']) {
    echo sprintf($strNotSet, 'relation', 'config.inc.php') . '<br />' . "\n"
         . '<a href="./Documentation.html#relation" target="documentation">' . $strDocu . '</a>' . "\n";
    require_once './libraries/footer.inc.php';
}

if (!$cfgRelation['displaywork']) {
    echo sprintf($strNotSet, 'table_info', 'config.inc.php') . '<br />' . "\n"
         . '<a href="./Documentation.html#table_info" target="documentation">' . $strDocu . '</a>' . "\n";
    require_once './libraries/footer.inc.php';
}

if (!isset($cfgRelation['table_coords'])){
    echo sprintf($strNotSet, 'table_coords', 'config.inc.php') . '<br />' . "\n"
         . '<a href="./Documentation.html#table_coords" target="documentation">' . $strDocu . '</a>' . "\n";
    exit();
}
if (!isset($cfgRelation['pdf_pages'])) {
    echo sprintf($strNotSet, 'pdf_page', 'config.inc.php') . '<br />' . "\n"
         . '<a href="./Documentation.html#pdf_pages" target="documentation">' . $strDocu . '</a>' . "\n";
    exit();
}

if ($cfgRelation['pdfwork']) {
    // Now is the time to work on all changes
    if (isset($do)) {
        switch ($do) {
            case 'choosepage':
                if ($action_choose=="1") {
                    $ch_query = 'DELETE FROM ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['table_coords'])
                              .   ' WHERE db_name = \'' . PMA_sqlAddslashes($db) . '\''
                              .   ' AND   pdf_page_number = ' . $chpage;
                    PMA_query_as_cu($ch_query, FALSE, $query_default_option);

                    $ch_query = 'DELETE FROM ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['pdf_pages'])
                              .   ' WHERE db_name = \'' . PMA_sqlAddslashes($db) . '\''
                              .   ' AND   page_nr = ' . $chpage;
                    PMA_query_as_cu($ch_query, FALSE, $query_default_option);

                    unset($chpage);
                }
                break;
            case 'createpage':
                if (!isset($newpage) || $newpage == '') {
                    $newpage = $strNoDescription;
                }
                $ins_query   = 'INSERT INTO ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['pdf_pages'])
                             . ' (db_name, page_descr)'
                             . ' VALUES (\'' . PMA_sqlAddslashes($db) . '\', \'' . PMA_sqlAddslashes($newpage) . '\')';
                PMA_query_as_cu($ins_query, FALSE, $query_default_option);
                $pdf_page_number = PMA_DBI_insert_id(isset($controllink) ? $controllink : '');

                // A u t o m a t i c    l a y o u t
                // ================================
                if (isset($auto_layout_internal) || isset($auto_layout_innodb)) {
                    $all_tables = array();
                }

                if (isset($auto_layout_innodb)) {
                    // get the tables list
                    $tables = PMA_DBI_get_tables_full($db);
                    // find the InnoDB ones
                    $innodb_tables = array();
                    foreach($tables as $table_name => $table_properties) {
                        if ($table_properties['ENGINE'] == 'InnoDB') {
                            $innodb_tables[] = $table_name;
                        }
                    }
                    $all_tables = $innodb_tables;
                    // could be improved by finding the tables which have the
                    // most references keys and place them at the beginning
                    // of the array (so that they are all center of schema)
                    unset($tables, $innodb_tables);
                } // endif auto_layout_innodb

                if (isset($auto_layout_internal)) {
                    // get the tables that have relations, by descending
                    // number of links
                    $master_tables = 'SELECT COUNT(master_table), master_table'
                                . ' FROM ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['relation'])
                                . ' WHERE master_db = \'' . $db . '\''
                                . ' GROUP BY master_table'
                                . ' ORDER BY ' . PMA_backquote('COUNT(master_table)') . ' DESC ';
                    $master_tables_rs = PMA_query_as_cu($master_tables, FALSE, $query_default_option);
                    if ($master_tables_rs && PMA_DBI_num_rows($master_tables_rs) > 0) {
                        // first put all the master tables at beginning
                        // of the list, so they are near the center of
                        // the schema
                        while (list(, $master_table) = PMA_DBI_fetch_row($master_tables_rs)) {
                            $all_tables[] = $master_table;
                        }

                        // then for each master, add its foreigns into an array
                        // of foreign tables, if not already there
                        // (a foreign might be foreign for more than
                        // one table, and might be a master itself)

                        $foreign_tables = array();
                        foreach ($all_tables AS $master_table) {
                            $foreigners = PMA_getForeigners($db, $master_table);
                            foreach ($foreigners AS $foreigner) {
                                if (!in_array($foreigner['foreign_table'], $foreign_tables)) {
                                    $foreign_tables[] = $foreigner['foreign_table'];
                                }
                            }
                        }

                        // then merge the arrays
                        foreach ($foreign_tables AS $foreign_table) {
                            if (!in_array($foreign_table, $all_tables)) {
                                $all_tables[] = $foreign_table;
                            }
                        }
                    } // endif there are master tables
                } // endif auto_layout_internal

                if (isset($auto_layout_internal) || isset($auto_layout_innodb)) {
                    // now generate the coordinates for the schema,
                    // in a clockwise spiral

                    $pos_x = 300;
                    $pos_y = 300;
                    $delta = 110;
                    $delta_mult = 1.10;
                    $direction = "right";
                    foreach ($all_tables AS $current_table) {

                        // save current table's coordinates
                        $insert_query = 'INSERT INTO ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['table_coords']) . ' '
                                      . '(db_name, table_name, pdf_page_number, x, y) '
                                      . 'VALUES (\'' . PMA_sqlAddslashes($db) . '\', \'' . PMA_sqlAddslashes($current_table) . '\',' . $pdf_page_number . ',' . $pos_x . ',' . $pos_y . ')';
                        PMA_query_as_cu($insert_query, FALSE, $query_default_option);

                        // compute for the next table
                        switch ($direction) {
                            case 'right':
                                $pos_x += $delta;
                                $direction = "down";
                                $delta *= $delta_mult;
                                break;
                            case 'down':
                                $pos_y += $delta;
                                $direction = "left";
                                $delta *= $delta_mult;
                                break;
                            case 'left':
                                $pos_x -= $delta;
                                $direction = "up";
                                $delta *= $delta_mult;
                                break;
                            case 'up':
                                $pos_y -= $delta;
                                $direction = "right";
                                $delta *= $delta_mult;
                                break;
                        } // end switch
                    } // end foreach
                } // end if some auto-layout to do

                $chpage = $pdf_page_number;

                break;

            case 'edcoord':
                for ($i = 0; $i < $c_table_rows; $i++) {
                    $arrvalue = 'c_table_' . $i;
                    $arrvalue = $$arrvalue;
                    if (!isset($arrvalue['x']) || $arrvalue['x'] == '') {
                        $arrvalue['x'] = 0;
                    }
                    if (!isset($arrvalue['y']) || $arrvalue['y'] == '') {
                        $arrvalue['y'] = 0;
                    }
                    if (isset($arrvalue['name']) && $arrvalue['name'] != '--') {
                        $test_query = 'SELECT * FROM ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['table_coords'])
                                    .   ' WHERE db_name = \'' .  PMA_sqlAddslashes($db) . '\''
                                    .   ' AND   table_name = \'' . PMA_sqlAddslashes($arrvalue['name']) . '\''
                                    .   ' AND   pdf_page_number = ' . $chpage;
                        $test_rs    = PMA_query_as_cu($test_query, FALSE, $query_default_option);
                        if ($test_rs && PMA_DBI_num_rows($test_rs) > 0) {
                            if (isset($arrvalue['delete']) && $arrvalue['delete'] == 'y') {
                                $ch_query = 'DELETE FROM ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['table_coords'])
                                          .   ' WHERE db_name = \'' . PMA_sqlAddslashes($db) . '\''
                                          .   ' AND   table_name = \'' . PMA_sqlAddslashes($arrvalue['name']) . '\''
                                          .   ' AND   pdf_page_number = ' . $chpage;
                            } else {
                                $ch_query = 'UPDATE ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['table_coords']) . ' '
                                          . 'SET x = ' . $arrvalue['x'] . ', y= ' . $arrvalue['y']
                                          .   ' WHERE db_name = \'' . PMA_sqlAddslashes($db) . '\''
                                          .   ' AND   table_name = \'' . PMA_sqlAddslashes($arrvalue['name']) . '\''
                                          .   ' AND   pdf_page_number = ' . $chpage;
                            }
                        } else {
                            $ch_query     = 'INSERT INTO ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['table_coords']) . ' '
                                          . '(db_name, table_name, pdf_page_number, x, y) '
                                          . 'VALUES (\'' . PMA_sqlAddslashes($db) . '\', \'' . PMA_sqlAddslashes($arrvalue['name']) . '\',' . $chpage . ',' . $arrvalue['x'] . ',' . $arrvalue['y'] . ')';
                        }
                        PMA_query_as_cu($ch_query, FALSE, $query_default_option);
                    } // end if
                } // end for
                break;
            case 'deleteCrap':
                foreach ($delrow AS $current_row) {
                    $d_query = 'DELETE FROM ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['table_coords']) . ' ' . "\n"
                             .   ' WHERE db_name = \'' . PMA_sqlAddslashes($db) . '\'' . "\n"
                             .   ' AND   table_name = \'' . PMA_sqlAddslashes($current_row) . '\'' . "\n"
                             .   ' AND   pdf_page_number = ' . $chpage;
                    PMA_query_as_cu($d_query, FALSE, $query_default_option);
                }
                break;
        } // end switch
    } // end if (isset($do))

    // We will need an array of all tables in this db
    $selectboxall = array('--');
    $alltab_rs    = PMA_DBI_query('SHOW TABLES FROM ' . PMA_backquote($db) . ';', null, PMA_DBI_QUERY_STORE);
    while ($val = @PMA_DBI_fetch_row($alltab_rs)) {
        $selectboxall[] = $val[0];
    }

    // Now first show some possibility to choose a page for the pdf
    $page_query = 'SELECT * FROM ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['pdf_pages'])
                . ' WHERE db_name = \'' . PMA_sqlAddslashes($db) . '\'';
    $page_rs    = PMA_query_as_cu($page_query, FALSE, $query_default_option);

    if ($page_rs && PMA_DBI_num_rows($page_rs) > 0) {
        ?>
<form method="get" action="pdf_pages.php" name="selpage">
    <fieldset>
     <legend>
    <?php echo $strChoosePage . "\n"; ?>
     </legend>
    <?php echo PMA_generate_common_hidden_inputs($db, $table); ?>
    <input type="hidden" name="do" value="choosepage" />
    <select name="chpage" onchange="this.form.submit()">
        <?php
        while ($curr_page = PMA_DBI_fetch_assoc($page_rs)) {
            echo "\n" . '        '
                 . '<option value="' . $curr_page['page_nr'] . '"';
            if (isset($chpage) && $chpage == $curr_page['page_nr']) {
                echo ' selected="selected"';
            }
            echo '>' . $curr_page['page_nr'] . ': ' . $curr_page['page_descr'] . '</option>';
        } // end while
        echo "\n";
        ?>
    </select>
    <input type="radio" name="action_choose" value="0" id="radio_choose0" checked="checked" style="vertical-align: middle" /><label for="radio_choose0">
<?php echo $strEdit; ?> </label>
    <input type="radio" name="action_choose" value="1" id="radio_choose1"  style="vertical-align: middle" /><label for="radio_choose1">
<?php echo $strDelete; ?> </label>
       <input type="submit" value="<?php echo $strGo; ?>" /><br />
    </fieldset>
</form>
        <?php
    }
    echo "\n";

    // Possibility to create a new page:
    ?>
<form method="post" action="pdf_pages.php" name="crpage">
    <fieldset>
     <legend>
    <?php echo $strCreatePage . "\n"; ?>
     </legend>
    <?php echo PMA_generate_common_hidden_inputs($db, $table); ?>
    <input type="hidden" name="do" value="createpage" />
    <input type="text" name="newpage" size="20" maxlength="50" />
       <input type="checkbox" name="auto_layout_internal" />
   <?php echo '(' . $strAutomaticLayout . ' / ' . $strInternalRelations . ')' . "\n"; ?>
        <input type="checkbox" name="auto_layout_innodb" />
    <?php echo '(' . $strAutomaticLayout . ' / InnoDB)' . "\n"; ?>
        <input type="submit" value="<?php echo $strGo; ?>" />
    </fieldset>
</form>
    <?php
    // Now if we already have chosen a page number then we should show the
    // tables involved
    if (isset($chpage) && $chpage > 0) {
        echo "\n";
        ?>
<hr />

<h2><?php echo $strSelectTables ;?></h2>

<?php
$page_query = 'SELECT * FROM ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['table_coords'])
            . ' WHERE db_name = \'' . PMA_sqlAddslashes($db) . '\''
            . ' AND pdf_page_number = ' . $chpage;
$page_rs    = PMA_query_as_cu($page_query, FALSE, $query_default_option);
$array_sh_page = array();
$draginit = '';
$reset_draginit = '';
$i = 0;
while ($temp_sh_page = @PMA_DBI_fetch_assoc($page_rs)) {
    $array_sh_page[] = $temp_sh_page;
}

// garvin: Display WYSIWYG-PDF parts?
if ($cfg['WYSIWYG-PDF']) {
    if (!isset($_POST['with_field_names']) && !isset($_POST['showwysiwyg'])) {
        $with_field_names = TRUE;
    }
?>
<script type="text/javascript" src="./js/dom-drag.js"></script>
<form method="post" action="pdf_pages.php" name="dragdrop">
<input type="button" name="dragdrop" value="<?php echo $strToggleScratchboard; ?>" onclick="ToggleDragDrop('pdflayout');" />
 <input type="button" name="dragdropreset" value="<?php echo $strReset; ?>" onclick="resetDrag();" />
</form>
<div id="pdflayout" class="pdflayout" style="visibility: hidden;">
<?php
foreach ($array_sh_page AS $key => $temp_sh_page) {
    $drag_x = $temp_sh_page['x'];
    $drag_y = $temp_sh_page['y'];

    $draginit       .= '    Drag.init(getElement("table_' . $i . '"), null, 0, parseInt(myid.style.width)-2, 0, parseInt(myid.style.height)-5);' . "\n";
    $draginit       .= '    getElement("table_' . $i . '").onDrag = function (x, y) { document.edcoord.elements["c_table_' . $i . '[x]"].value = parseInt(x); document.edcoord.elements["c_table_' . $i . '[y]"].value = parseInt(y) }' . "\n";
    $draginit       .= '    getElement("table_' . $i . '").style.left = "' . $drag_x . 'px";' . "\n";
    $draginit       .= '    getElement("table_' . $i . '").style.top  = "' . $drag_y . 'px";' . "\n";
    $reset_draginit .= '    getElement("table_' . $i . '").style.left = "2px";' . "\n";
    $reset_draginit .= '    getElement("table_' . $i . '").style.top  = "' . (15 * $i) . 'px";' . "\n";
    $reset_draginit .= '    document.edcoord.elements["c_table_' . $i . '[x]"].value = "2"' . "\n";
    $reset_draginit .= '    document.edcoord.elements["c_table_' . $i . '[y]"].value = "' . (15 * $i) . '"' . "\n";

    $local_query = 'SHOW FIELDS FROM '
                 .  PMA_backquote($temp_sh_page['table_name'])
                . ' FROM ' . PMA_backquote($db);
    $fields_rs = PMA_DBI_query($local_query);
    unset($local_query);
    $fields_cnt = PMA_DBI_num_rows($fields_rs);

    echo '<div id="table_' . $i . '" class="pdflayout_table"><u>' . $temp_sh_page['table_name'] . '</u>';
    if (isset($with_field_names)) {
        while ($row = PMA_DBI_fetch_assoc($fields_rs)) {
            echo '<br />' . htmlspecialchars($row['Field']) . "\n";
        }
    }
    echo '</div>' . "\n";
    PMA_DBI_free_result($fields_rs);
    unset($fields_rs);

    $i++;
}
?>
</div>
<script type="text/javascript">
//<![CDATA[
function init() {
    refreshLayout();
    myid = getElement('pdflayout');
    <?php echo $draginit; ?>
}

function resetDrag() {
    <?php echo $reset_draginit; ?>
}
//]]>
</script>
<?php
} // end if WYSIWYG-PDF
?>

<form method="post" action="pdf_pages.php" name="edcoord">
    <?php echo PMA_generate_common_hidden_inputs($db, $table); ?>
    <input type="hidden" name="chpage" value="<?php echo $chpage; ?>" />
    <input type="hidden" name="do" value="edcoord" />
    <table border="0">
    <tr>
        <th><?php echo $strTable; ?></th>
        <th><?php echo $strDelete; ?></th>
        <th>X</th>
        <th>Y</th>
    </tr>
        <?php
        if (isset($ctable)) {
            unset($ctable);
        }


        $i = 0;
        $odd_row = true;
        foreach ($array_sh_page AS $dummy_sh_page => $sh_page) {
            $_mtab       = $sh_page['table_name'];
            $tabExist[$_mtab] = FALSE;
            echo "\n" . '    <tr class="';
            if ($odd_row) {
                echo 'odd';
            } else {
                echo 'even';
            }
            echo '">';
            $odd_row != $odd_row;
            echo "\n" . '        <td>'
                 . "\n" . '            <select name="c_table_' . $i . '[name]">';
            foreach ($selectboxall AS $key => $value) {
                echo "\n" . '                <option value="' . $value . '"';
                if ($value == $sh_page['table_name']) {
                    echo ' selected="selected"';
                    $tabExist[$_mtab] = TRUE;
                }
                echo '>' . $value . '</option>';
            } // end while
            echo "\n" . '            </select>'
                 . "\n" . '        </td>';
            echo "\n" . '        <td>'
                 . "\n" . '            <input type="checkbox" name="c_table_' . $i . '[delete]" value="y" />' . $strDelete;
            echo "\n" . '        </td>';
            echo "\n" . '        <td>'
                 . "\n" . '            <input type="text" ' . ($cfg['WYSIWYG-PDF'] ? 'onchange="dragPlace(' . $i . ', \'x\', this.value)"' : '') . ' name="c_table_' . $i . '[x]" value="' . $sh_page['x'] . '" />';
            echo "\n" . '        </td>';
            echo "\n" . '        <td>'
                 . "\n" . '            <input type="text" ' . ($cfg['WYSIWYG-PDF'] ? 'onchange="dragPlace(' . $i . ', \'y\', this.value)"' : '') . ' name="c_table_' . $i . '[y]" value="' . $sh_page['y'] . '" />';
            echo "\n" . '        </td>';
            echo "\n" . '    </tr>';
            $i++;
        } // end while
        // Do one more empty row
        echo "\n" . '    <tr class="';
        if ($odd_row) {
            echo 'odd';
        } else {
            echo 'even';
        }
        $odd_row != $odd_row;
        echo '">';
        echo "\n" . '        <td>'
             . "\n" . '            <select name="c_table_' . $i . '[name]">';
        foreach ($selectboxall AS $key => $value) {
            echo "\n" . '                <option value="' . $value . '">' . $value . '</option>';
        }
        echo "\n" . '            </select>'
             . "\n" . '        </td>';
        echo "\n" . '        <td>'
             . "\n" . '            <input type="checkbox" name="c_table_' . $i . '[delete]" value="y" />' . $strDelete;
        echo "\n" . '        </td>';
        echo "\n" . '        <td>'
             . "\n" . '            <input type="text" name="c_table_' . $i . '[x]" value="' . (isset($sh_page['x'])?$sh_page['x']:'') . '" />';
        echo "\n" . '        </td>';
        echo "\n" . '        <td>'
             . "\n" . '            <input type="text" name="c_table_' . $i . '[y]" value="' . (isset($sh_page['y'])?$sh_page['y']:'') . '" />';
        echo "\n" . '        </td>';
        echo "\n" . '    </tr>';
        echo "\n" . '    </table>' . "\n";

        echo "\n" . '    <input type="hidden" name="c_table_rows" value="' . ($i + 1) . '" />';
        echo ($cfg['WYSIWYG-PDF'] ? "\n" . '    <input type="hidden" name="showwysiwyg" value="' . ((isset($showwysiwyg) && $showwysiwyg == '1') ? '1' : '0') . '" />' : '');
        echo "\n" . '    <input type="checkbox" name="with_field_names" ' . (isset($with_field_names) ? 'checked="checked"' : ''). ' />' . $strColumnNames . '<br />';
        echo "\n" . '    <input type="submit" value="' . $strSave . '" />';
        echo "\n" . '</form>' . "\n\n";
    } // end if

    //  Check if there are tables that need to be deleted,
    //  if there are, ask the user for allowance
    $_strtrans  = '';
    $_strname   = '';
    $shoot      = FALSE;
    if (!empty($tabExist) && is_array($tabExist)) {
        foreach ($tabExist AS $key => $value) {
            if (!$value) {
                $_strtrans  .= '<input type="hidden" name="delrow[]" value="' . $key . '" />' . "\n";
                $_strname   .= '<li>' . $key . '</li>' . "\n";
                $shoot       = TRUE;
            }
        }
        if ($shoot) {
            echo '<form action="pdf_pages.php" method="post">' . "\n"
               . PMA_generate_common_hidden_inputs($db, $table)
               . '<input type="hidden" name="do" value="deleteCrap" />' . "\n"
               . '<input type="hidden" name="chpage" value="' . $chpage . '" />' . "\n"
               . $strDelOld
               . '<ul>' . "\n"
               . $_strname
               . '</ul>' . "\n"
               . $_strtrans
               . '<input type="submit" value="' . $strGo . '" />' . "\n"
               . '</form>';
        }
    }
    //    ------------------------------------
    //    d i s p l a y   p d f    s c h e m a
    //    ------------------------------------

    if (isset($do)
    && ($do == 'edcoord'
       || ($do == 'choosepage' && isset($chpage))
       || ($do == 'createpage' && isset($chpage)))) {
        ?>
<form method="post" action="pdf_schema.php" name="pdfoptions">
    <?php echo PMA_generate_common_hidden_inputs($db); ?>
    <input type="hidden" name="pdf_page_number" value="<?php echo $chpage; ?>" />

    <?php echo '<br /><b>' . $strDisplayPDF . '</b>'; ?>:&nbsp;<br />
    <input type="checkbox" name="show_grid" id="show_grid_opt" /><label for="show_grid_opt"><?php echo $strShowGrid; ?></label><br />
    <input type="checkbox" name="show_color" id="show_color_opt" checked="checked" /><label for="show_color_opt"><?php echo $strShowColor; ?></label><br />
    <input type="checkbox" name="show_table_dimension" id="show_table_dim_opt" /><label for="show_table_dim_opt"><?php echo $strShowTableDimension; ?></label><br />
    <input type="checkbox" name="all_tab_same_wide" id="all_tab_same_wide" /><label for="all_tab_same_wide"><?php echo $strAllTableSameWidth; ?></label><br />
    <input type="checkbox" name="with_doc" id="with_doc" checked="checked" /><label for="with_doc"><?php echo $strDataDict; ?></label>
    <br />
    <label for="orientation_opt"><?php echo $strShowDatadictAs; ?></label>
    <select id="orientation_opt" name="orientation" <?php echo ($cfg['WYSIWYG-PDF'] ? 'onchange="refreshDragOption(\'pdflayout\');"' : ''); ?>>
        <option value="L"><?php echo $strLandscape;?></option>
        <option value="P"><?php echo $strPortrait;?></option>
    </select><br />

    <label for="paper_opt"><?php echo $strPaperSize; ?></label>
    <select id="paper_opt" name="paper" <?php echo ($cfg['WYSIWYG-PDF'] ? 'onchange="refreshDragOption(\'pdflayout\');"' : ''); ?>>
<?php
        foreach ($cfg['PDFPageSizes'] AS $key => $val) {
            echo '<option value="' . $val . '"';
            if ($val == $cfg['PDFDefaultPageSize']) {
                echo ' selected="selected"';
            }
            echo ' >' . $val . '</option>' . "\n";
        }
?>
    </select><br />
    &nbsp;&nbsp;<input type="submit" value="<?php echo $strGo; ?>" />
</form>
<?php
        if ((isset($showwysiwyg) && $showwysiwyg == '1')) {
?>
<script type="text/javascript">
//<![CDATA[
ToggleDragDrop('pdflayout');
//]]>
</script>
<?php
        }
    } // end if
} // end if ($cfgRelation['pdfwork'])


/**
 * Displays the footer
 */
echo "\n";
require_once './libraries/footer.inc.php';
?>
