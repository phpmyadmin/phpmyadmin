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
            case 'createpage':
                if (!isset($newpage) || $newpage == '') {
                    $newpage = $strNoDescription;
                }
                $ins_query   = 'INSERT INTO ' . PMA_backquote($cfgRelation['pdf_pages'])
                             . ' (db_name, page_descr)'
                             . ' VALUES (\'' . PMA_sqlAddslashes($db) . '\', \'' . PMA_sqlAddslashes($newpage) . '\')';
                PMA_query_as_cu($ins_query);
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
        } // end switch
    } // end if (isset($do))

    // We will need an array of all tables in this db
    $selectboxall[] = '--';
    $alltab_qry     = 'SHOW TABLES FROM ' . PMA_backquote($db);
    $alltab_rs      = @PMA_mysql_query($alltab_qry) or PMA_mysqlDie('', $alltab_qry, '', $err_url_0);
    while (list($table) = @PMA_mysql_fetch_array($alltab_rs)) {
        $selectboxall[] = $table;
    }

    // Now first show some possibility to choose a page for the pdf
    $page_query = 'SELECT * FROM ' . PMA_backquote($cfgRelation['pdf_pages'])
                . ' WHERE db_name = \'' . PMA_sqlAddslashes($db) . '\'';
    $page_rs    = PMA_query_as_cu($page_query);
    if ($page_rs && mysql_num_rows($page_rs) > 0) {
        ?>
<form method="post" action="pdf_pages.php3" name="selpage">
    <?php echo $strChoosePage . "\n"; ?>
    <input type="hidden" name="db" value="<?php echo htmlspecialchars($db); ?>" />
    <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
    <input type="hidden" name="convcharset" value="<?php echo $convcharset; ?>" />
    <input type="hidden" name="server" value="<?php echo $server; ?>" />
    <input type="hidden" name="table" value="<?php echo htmlspecialchars($table); ?>" />
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
    <input type="submit" value="<?php echo $strGo; ?>" />
</form>
        <?php
    }
    echo "\n";

    // Possibility to create a new page:
    ?>
<form method="post" action="pdf_pages.php3" name="crpage">
    <?php echo $strCreatePage . "\n"; ?>
    <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
    <input type="hidden" name="convcharset" value="<?php echo $convcharset; ?>" />
    <input type="hidden" name="server" value="<?php echo $server; ?>" />
    <input type="hidden" name="db" value="<?php echo htmlspecialchars($db); ?>" />
    <input type="hidden" name="table" value="<?php echo htmlspecialchars($table); ?>" />
    <input type="hidden" name="do" value="createpage" />
    <input type="text" name="newpage" size="20" maxlength="50" />
    <input type="submit" value="<?php echo $strGo; ?>" />
</form>
    <?php
    // Now if we allready have choosen a page number then we should show the
    // tables involved
    if (isset($chpage) && $chpage > 0) {
        echo "\n";
        ?>
<hr />

<h2><?php echo $strSelectTables ;?></h2>
<form method="post" action="pdf_pages.php3" name="edcoord">
    <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
    <input type="hidden" name="convcharset" value="<?php echo $convcharset; ?>" />
    <input type="hidden" name="server" value="<?php echo $server; ?>" />
    <input type="hidden" name="db" value="<?php echo htmlspecialchars($db); ?>" />
    <input type="hidden" name="table" value="<?php echo htmlspecialchars($table); ?>" />
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

    if (isset($do) && ($do == 'edcoord' || $do == 'choosepage')) {
        ?>
<form method="post" action="pdf_schema.php3">
    <input type="hidden" name="server" value="<?php echo $server; ?>" />
    <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
    <input type="hidden" name="convcharset" value="<?php echo $convcharset; ?>" />
    <input type="hidden" name="db" value="<?php echo htmlspecialchars($db); ?>" />
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
    <label for="with_doc"><?php echo $strDataDict; ?></label>
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
