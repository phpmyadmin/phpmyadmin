<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


/**
 * Gets some core libraries
 */
require('./libraries/grab_globals.lib.php3');
require('./libraries/common.lib.php3');
require('./db_details_common.php3');


/**
 * Settings for relation stuff
 */
require('./libraries/relation.lib.php3');
$cfgRelation = PMA_getRelationsParam();


/**
 * Now in ./libraries/relation.lib.php3 we check for all tables
 * that we need, but if we don't find them we are quiet about it
 * so people can work without.
 * This page is absolutely useless if you didn't set up your tables
 * correctly, so it is a good place to see which tables we can and
 * complain ;-)
 */
if (!$cfgRelation['relwork']) {
    echo sprintf($strNotSet, 'relation', 'config.inc.php3') . '<br />' . "\n"
         . '<a href="./Documentation.html#relation" target="documentation">' . $strDocu . '</a>' . "\n";
    exit();
}

if (!$cfgRelation['displaywork']) {
    echo sprintf($strNotSet, 'table_info', 'config.inc.php3') . '<br />' . "\n"
         . '<a href="./Documentation.html#table_info" target="documentation">' . $strDocu . '</a>' . "\n";
    exit();
}

if (!isset($cfgRelation['table_coords'])){
    echo sprintf($strNotSet, 'table_coords', 'config.inc.php3') . '<br />' . "\n"
         . '<a href="./Documentation.html#table_coords" target="documentation">' . $strDocu . '</a>' . "\n";
    exit();
}
if (!isset($cfgRelation['pdf_pages'])) {
    echo sprintf($strNotSet, 'pdf_page', 'config.inc.php3') . '<br />' . "\n"
         . '<a href="./Documentation.html#pdf_pages" target="documentation">' . $strDocu . '</a>' . "\n";
    exit();
}

if ($cfgRelation['pdfwork']) {
    // Now is the time to work on all changes
    if (isset($do)) {
        switch ($do) {
            case 'choosepage':
                if ($action_choose=="1") {
                    $ch_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['table_coords'])
                              .   ' WHERE db_name = \'' . PMA_sqlAddslashes($db) . '\''
                              .   ' AND   pdf_page_number = ' . $chpage;
                    PMA_query_as_cu($ch_query);
                
                    $ch_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['pdf_pages'])
                              .   ' WHERE db_name = \'' . PMA_sqlAddslashes($db) . '\''
                              .   ' AND   page_nr = ' . $chpage;
                    PMA_query_as_cu($ch_query);
                }
                break;
            case 'createpage':
                if (!isset($newpage) || $newpage == '') {
                    $newpage = $strNoDescription;
                }
                $ins_query   = 'INSERT INTO ' . PMA_backquote($cfgRelation['pdf_pages'])
                             . ' (db_name, page_descr)'
                             . ' VALUES (\'' . PMA_sqlAddslashes($db) . '\', \'' . PMA_sqlAddslashes($newpage) . '\')';
                PMA_query_as_cu($ins_query);

                // A u t o m a t i c    l a y o u t

                if (isset($autolayout)) {
                    // save the page number
                    $pdf_page_number = mysql_insert_id((isset($dbh)?$dbh:''));

                    // get the tables that have relations, by descending 
                    // number of links
                    $master_tables = 'SELECT COUNT(master_table), master_table'
                                . ' FROM ' . PMA_backquote($cfgRelation['relation'])
                                . ' WHERE master_db = \'' . $db . '\''
                                . ' GROUP BY master_table'
                                . ' ORDER BY ' . PMA_backquote('COUNT(master_table)') . ' DESC ';
                    $master_tables_rs = PMA_query_as_cu($master_tables);
                    if ($master_tables_rs && mysql_num_rows($master_tables_rs) > 0) {
                        // first put all the master tables at beginning
                        // of the list, so they are near the center of
                        // the schema
                        while (list(,$master_table) = mysql_fetch_row($master_tables_rs)) {
                            $all_tables[] = $master_table;
                        }

                        // then for each master, add its foreigns into an array
                        // of foreign tables, if not already there
                        // (a foreign might be foreign for more than
                        // one table, and might be a master itself)

                        $foreign_tables = array();
                        while (list(,$master_table) = each($all_tables)) {
                            $foreigners = PMA_getForeigners($db, $master_table);
                            while (list(, $foreigner) = each($foreigners)) {
                                if (!in_array($foreigner['foreign_table'], $foreign_tables)) {
                                    $foreign_tables[] = $foreigner['foreign_table'];
                                } 
                            } 
                        } 

                        // then merge the arrays

                        while (list(,$foreign_table) = each($foreign_tables)) {
                            if (!in_array($foreign_table, $all_tables)) {
                                $all_tables[] = $foreign_table;
                            } 

                        }
                        // now generate the coordinates for the schema,
                        // in a clockwise spiral
                       
                        $pos_x = 500;
                        $pos_y = 500;
                        $delta = 50;
                        $delta_mult = 1.34;
                        $direction = "right";
                        reset($all_tables);

                        while (list(,$current_table) = each($all_tables)) {

                            // save current table's coordinates
                            $insert_query = 'INSERT INTO ' . PMA_backquote($cfgRelation['table_coords']) . ' '
                                          . '(db_name, table_name, pdf_page_number, x, y) '
                                          . 'VALUES (\'' . PMA_sqlAddslashes($db) . '\', \'' . PMA_sqlAddslashes($current_table) . '\',' . $pdf_page_number . ',' . $pos_x . ',' . $pos_y . ')';
                            PMA_query_as_cu($insert_query);


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
                        } // end while
                    } // end if there are master tables

                    $chpage = $pdf_page_number;
                } // end if isset autolayout

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
                        $test_query = 'SELECT * FROM ' . PMA_backquote($cfgRelation['table_coords'])
                                    .   ' WHERE db_name = \'' .  PMA_sqlAddslashes($db) . '\''
                                    .   ' AND   table_name = \'' . PMA_sqlAddslashes($arrvalue['name']) . '\''
                                    .   ' AND   pdf_page_number = ' . $chpage;
                        $test_rs    = PMA_query_as_cu($test_query);
                        if ($test_rs && mysql_num_rows($test_rs) > 0) {
                            if (isset($arrvalue['delete']) && $arrvalue['delete'] == 'y') {
                                $ch_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['table_coords'])
                                          .   ' WHERE db_name = \'' . PMA_sqlAddslashes($db) . '\''
                                          .   ' AND   table_name = \'' . PMA_sqlAddslashes($arrvalue['name']) . '\''
                                          .   ' AND   pdf_page_number = ' . $chpage;
                            } else {
                                $ch_query = 'UPDATE ' . PMA_backquote($cfgRelation['table_coords']) . ' '
                                          . 'SET x = ' . $arrvalue['x'] . ', y= ' . $arrvalue['y']
                                          .   ' WHERE db_name = \'' . PMA_sqlAddslashes($db) . '\''
                                          .   ' AND   table_name = \'' . PMA_sqlAddslashes($arrvalue['name']) . '\''
                                          .   ' AND   pdf_page_number = ' . $chpage;
                            }
                        } else {
                            $ch_query     = 'INSERT INTO ' . PMA_backquote($cfgRelation['table_coords']) . ' '
                                          . '(db_name, table_name, pdf_page_number, x, y) '
                                          . 'VALUES (\'' . PMA_sqlAddslashes($db) . '\', \'' . PMA_sqlAddslashes($arrvalue['name']) . '\',' . $chpage . ',' . $arrvalue['x'] . ',' . $arrvalue['y'] . ')';
                        }
                        PMA_query_as_cu($ch_query);
                    } // end if
                } // end for
                break;
            case 'deleteCrap':
                while (list(,$current_row) = each($delrow)) {
                    $d_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['table_coords']) . ' ' . "\n"
                             .   ' WHERE db_name = \'' . PMA_sqlAddslashes($db) . '\'' . "\n"
                             .   ' AND   table_name = \'' . PMA_sqlAddslashes($current_row) . '\'' . "\n"
                             .   ' AND   pdf_page_number = ' . $chpage;
                    PMA_query_as_cu($d_query);
                }
                break;
        } // end switch
    } // end if (isset($do))

    // We will need an array of all tables in this db
    $selectboxall = array('--');
    $alltab_qry     = 'SHOW TABLES FROM ' . PMA_backquote($db);
    $alltab_rs      = @PMA_mysql_query($alltab_qry) or PMA_mysqlDie('', $alltab_qry, '', $err_url_0);
    while ($val = @PMA_mysql_fetch_array($alltab_rs)) {
        $selectboxall[] = $table[0];
    }


    // Now first show some possibility to choose a page for the pdf
    $page_query = 'SELECT * FROM ' . PMA_backquote($cfgRelation['pdf_pages'])
                . ' WHERE db_name = \'' . PMA_sqlAddslashes($db) . '\'';
    $page_rs    = PMA_query_as_cu($page_query);
    if ($page_rs && mysql_num_rows($page_rs) > 0) {
        ?>
<form method="post" action="pdf_pages.php3" name="selpage">
    <?php echo $strChoosePage . "\n"; ?>
    <?php echo PMA_generate_common_hidden_inputs($db, $table); ?>
    <input type="hidden" name="do" value="choosepage" />
    <select name="chpage" onchange="this.form.submit()">
        <?php
        while ($curr_page = @PMA_mysql_fetch_array($page_rs)) {
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
    <input type="radio" name="action_choose" value="0" id="radio_choose0" checked="checked" style="vertical-align: middle" /> <label for="radio_choose0">
<?php echo $strEdit; ?> </label>
    <input type="radio" name="action_choose" value="1" id="radio_choose1"  style="vertical-align: middle" /> <label for="radio_choose1">
<?php echo $strDelete; ?> </label>

    <input type="submit" value="<?php echo $strGo; ?>" />
</form>
        <?php
    }
    echo "\n";

    // Possibility to create a new page:
    ?>
<form method="post" action="pdf_pages.php3" name="crpage">
    <?php echo $strCreatePage . "\n"; ?>
    <?php echo PMA_generate_common_hidden_inputs($db, $table); ?>
    <input type="hidden" name="do" value="createpage" />
    <input type="text" name="newpage" size="20" maxlength="50" />
    <input type="checkbox" name="autolayout" />
    <?php echo '(' . $strAutomaticLayout . ')' . "\n"; ?>
    <input type="submit" value="<?php echo $strGo; ?>" />
</form>
    <?php
    // Now if we already have chosen a page number then we should show the
    // tables involved
    if (isset($chpage) && $chpage > 0) {
        echo "\n";
        ?>
<hr />

<h2><?php echo $strSelectTables ;?></h2>
<form method="post" action="pdf_pages.php3" name="edcoord">
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

        $page_query = 'SELECT * FROM ' . PMA_backquote($cfgRelation['table_coords'])
                    . ' WHERE db_name = \'' . PMA_sqlAddslashes($db) . '\''
                    . ' AND pdf_page_number = ' . $chpage;
        $page_rs    = PMA_query_as_cu($page_query);

        $i = 0;
        while ($sh_page = @PMA_mysql_fetch_array($page_rs)) {
            $_mtab       = $sh_page['table_name'];
            $tabExist[$_mtab] = FALSE;
            echo "\n" . '    <tr ';
            if ($i % 2 == 0) {
                echo 'bgcolor="' . $cfg['BgcolorOne'] . '"';
            } else {
                echo 'bgcolor="' . $cfg['BgcolorTwo'] . '"';
            }
            echo '>';
            echo "\n" . '        <td>'
                 . "\n" . '            <select name="c_table_' . $i . '[name]">';
            reset($selectboxall);
            while (list($key, $value) = each($selectboxall)) {
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
                 . "\n" . '            <input type="text" name="c_table_' . $i . '[x]" value="' . $sh_page['x'] . '" />';
            echo "\n" . '        </td>';
            echo "\n" . '        <td>'
                 . "\n" . '            <input type="text" name="c_table_' . $i . '[y]" value="' . $sh_page['y'] . '" />';
            echo "\n" . '        </td>';
            echo "\n" . '    </tr>';
            $i++;
        } // end while
        // Do one more empty row
        echo "\n" . '    <tr ';
        if ($i % 2 == 0) {
            echo 'bgcolor="' . $cfg['BgcolorOne'] . '"';
        } else {
            echo 'bgcolor="' . $cfg['BgcolorTwo'] . '"';
        }
        echo '>';
        echo "\n" . '        <td>'
             . "\n" . '            <select name="c_table_' . $i . '[name]">';
        reset($selectboxall);
        while (list($key, $value) = each($selectboxall)) {
            echo "\n" . '                <option value="' . $value . '">' . $value . '</option>';
        }
        echo "\n" . '            </select>'
             . "\n" . '        </td>';
        echo "\n" . '        <td>'
             . "\n" . '            <input type="checkbox" name="c_table_' . $i . '[delete]" value="y" />' . $strDelete;
        echo "\n" . '        </td>';
        echo "\n" . '        <td>'
             . "\n" . '            <input type="text" name="c_table_' . $i . '[x]" value="' . $sh_page['x'] . '" />';
        echo "\n" . '        </td>';
        echo "\n" . '        <td>'
             . "\n" . '            <input type="text" name="c_table_' . $i . '[y]" value="' . $sh_page['y'] . '" />';
        echo "\n" . '        </td>';
        echo "\n" . '    </tr>';
        echo "\n" . '    </table>' . "\n";

        echo "\n" . '    <input type="hidden" name="c_table_rows" value="' . ($i + 1) . '" />';
        echo "\n" . '    <input type="submit" value="' . $strGo . '" />';
        echo "\n" . '</form>' . "\n\n";
    } // end if

    //  Check if there are tables that need to be deleted,
    //  if there are, ask the user for allowance
    $_strtrans  = '';
    $_strname   = '';
    $shoot      = FALSE;
    if (!empty($tabExist) && is_array($tabExist)) {
        while (list($key, $value) = each($tabExist)) {
            if (!$value) {
                $_strtrans  .= '<input type="hidden" name="delrow[]" value="' . $key . '">' . "\n";
                $_strname   .= '<li>' . $key . '</li>' . "\n";
                $shoot       = TRUE;
            }
        }
        if ($shoot) {
            echo '<FORM action="pdf_pages.php3" method="post">' . "\n"
               . PMA_generate_common_hidden_inputs($db, $table)
               . '<input type="hidden" name="do" value="deleteCrap">' . "\n"
               . '<input type="hidden" name="chpage" value="' . $chpage . '">' . "\n"
               . $strDelOld
               . '<ul>' . "\n"
               . $_strname
               . '</ul>' . "\n"
               . $_strtrans
               . '<input type="submit" value="' . $strGo . '">' . "\n"
               . '</FORM>';
        }
    }
    //    ------------------------------------
    //    d i s p l a y   p d f    s c h e m a
    //    ------------------------------------

    if (isset($do) 
    && ($do == 'edcoord' 
       || $do == 'choosepage' 
       || ($do == 'createpage' && isset($chpage)))) {
        ?>
<form method="post" action="pdf_schema.php3">
    <?php echo PMA_generate_common_hidden_inputs($db); ?>
    <input type="hidden" name="pdf_page_number" value="<?php echo $chpage; ?>" />
    <?php echo $strDisplayPDF; ?>&nbsp;:<br />
    <input type="checkbox" name="show_grid" id="show_grid_opt" />
    <label for="show_grid_opt"><?php echo $strShowGrid; ?></label><br />
    <input type="checkbox" name="show_color" id="show_color_opt" checked="checked" />
    <label for="show_color_opt"><?php echo $strShowColor; ?></label><br />
    <input type="checkbox" name="show_table_dimension" id="show_table_dim_opt" />
    <label for="show_table_dim_opt"><?php echo $strShowTableDimension; ?></label><br />
    <input type="checkbox" name="all_tab_same_wide" id="all_tab_same_wide" />
    <label for="all_tab_same_wide"><?php echo $strAllTableSameWidth; ?></label><br />
    <input type="checkbox" name="with_doc" id="with_doc" checked="checked" />
    <label for="with_doc"><?php echo $strDataDict; ?></label> <br />
    <?php echo $strShowDatadictAs; ?>
    <select name="orientation">
        <option value="L"><?php echo $strLandscape;?></option>
        <option value="P"><?php echo $strPortrait;?></option>
    </select>
    &nbsp;&nbsp;<input type="submit" value="<?php echo $strGo; ?>" />
</form>
        <?php
    } // end if
} // end if ($cfgRelation['pdfwork'])


/**
 * Displays the footer
 */
echo "\n";
require('./footer.inc.php3');
?>
