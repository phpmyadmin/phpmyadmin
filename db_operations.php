<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

require_once('./libraries/common.lib.php');
require_once('./libraries/mysql_charsets.lib.php');
/**
 * Rename database or Copy database
 */
if (isset($db) &&
    ((isset($db_rename) && $db_rename == 'true') ||
    (isset($db_copy) && $db_copy == 'true'))) {

    require_once('./libraries/tbl_move_copy.php');

    if (isset($db_rename) && $db_rename == 'true') {
        $move = TRUE;
    } else {
        $move = FALSE;
    }

    if (!isset($newname) || empty($newname)) {
        $message = $strDatabaseEmpty;
    } else {
        if ($move ||
           (isset($create_database_before_copying) && $create_database_before_copying)) {
            $local_query = 'CREATE DATABASE ' . PMA_backquote($newname);
            if (isset($db_collation)) {
                $local_query .= ' DEFAULT' . PMA_generateCharsetQueryPart($db_collation);
            }
            $local_query .= ';';
            $sql_query = $local_query;
            PMA_DBI_query($local_query);
        }

        $tables_full = PMA_DBI_get_tables_full($db);
        foreach ($tables_full as $table => $tmp) {
            $back = $sql_query;
            $sql_query = '';

            // value of $what for this table only
            $this_what = $what;

            if (!isset($tables_full[$table]['Engine'])) {
                $tables_full[$table]['Engine'] = $tables_full[$table]['Type'];
            }
            // do not copy the data from a Merge table
            // note: on the calling FORM, 'data' means 'structure and data'
            if ($tables_full[$table]['Engine'] == 'MRG_MyISAM') {
                if ($this_what == 'data') {
                    $this_what = 'structure';
                }
                if ($this_what == 'dataonly') {
                    $this_what = 'nocopy';
                }
            }

            if ($this_what != 'nocopy') {
                PMA_table_move_copy($db, $table, $newname, $table, isset($this_what) ? $this_what : 'data', $move);
            }

            $sql_query = $back . $sql_query;
        }
        unset($table);

        // Duplicate the bookmarks for this db (done once for each db)
        if ($db != $newname) {
            $get_fields = array('user','label','query');
            $where_fields = array('dbase' => $db);
            $new_fields = array('dbase' => $newname);
            PMA_duplicate_table_info('bookmarkwork', 'bookmark', $get_fields, $where_fields, $new_fields);
        }

        if ($move) {
            // cleanup pmadb stuff for this db
            require_once('./libraries/relation_cleanup.lib.php');
            PMA_relationsCleanupDatabase($db);

            $local_query = 'DROP DATABASE ' . PMA_backquote($db) . ';';
            $sql_query .= "\n" . $local_query;
            PMA_DBI_query($local_query);
            $message    = sprintf($strRenameDatabaseOK, htmlspecialchars($db), htmlspecialchars($newname));
        } else {
            $message    = sprintf($strCopyDatabaseOK, htmlspecialchars($db), htmlspecialchars($newname));
        }
        $reload     = TRUE;

        /* Change database to be used */
        if ($move) {
            $db         = $newname;
        } else {
            $pma_uri_parts = parse_url($cfg['PmaAbsoluteUri']);
            if (isset($switch_to_new) && $switch_to_new == 'true') {
                setcookie('pma_switch_to_new', 'true', 0, $GLOBALS['cookie_path'], '' , $GLOBALS['is_https']);
                $db         = $newname;
            } else {
                setcookie('pma_switch_to_new', '', 0, $GLOBALS['cookie_path'], '' , $GLOBALS['is_https']);
            }
        }
    }
}
/**
 * Settings for relations stuff
 */

require_once('./libraries/relation.lib.php');
$cfgRelation = PMA_getRelationsParam();

/**
 * Check if comments were updated
 * (must be done before displaying the menu tabs)
 */
if ($cfgRelation['commwork'] && isset($db_comment) && $db_comment == 'true') {
    PMA_SetComment($db, '', '(db_comment)', $comment);
}

/**
 * Prepares the tables list if the user where not redirected to this script
 * because there is no table in the database ($is_info is TRUE)
 */
if (empty($is_info)) {
    require('./libraries/db_details_common.inc.php');
    $url_query .= '&amp;goto=db_operations.php';

    // Gets the database structure
    $sub_part = '_structure';
    require('./libraries/db_details_db_info.inc.php');
    echo "\n";
}

if (PMA_MYSQL_INT_VERSION >= 40101) {
    $db_collation = PMA_getDbCollation($db);
}
if (PMA_MYSQL_INT_VERSION < 50002 || (PMA_MYSQL_INT_VERSION >= 50002 && $db != 'information_schema')) {
    $is_information_schema = FALSE;
} else {
    $is_information_schema = TRUE;
}

if (!$is_information_schema) {

    require('./libraries/display_create_table.lib.php');

    echo '<table border="0" cellpadding="2" cellspacing="0">';
    if ($cfgRelation['commwork']) {
?>
    <!-- Alter/Enter db-comment -->
        <tr><td colspan="3"><img src="<?php echo $GLOBALS['pmaThemeImage'] . 'spacer.png'; ?>" width="1" height="1" border="0" alt="" /></td></tr>

        <tr>
        <td colspan="3" class="tblHeaders"><?php
        if ($cfg['PropertiesIconic']) {
            echo '<img src="' . $pmaThemeImage . 'b_comment.png" border="0" width="16" height="16" hspace="2" align="middle" />';
        }
        echo $strDBComment;
        $comment = PMA_getComments($db);
        ?></td></tr>
                                <form method="post" action="db_operations.php">
        <tr bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
                                  <td colspan="2" nowrap="nowrap">
            <input type="hidden" name="db_comment" value="true" />
            <?php echo PMA_generate_common_hidden_inputs($db); ?>
            <input type="text" name="comment" class="textfield" size="30" value="<?php echo (isset($comment) && is_array($comment) ? htmlspecialchars(implode(' ', $comment)) : ''); ?>" /></td><td align="right">
            <input type="submit" value="<?php echo $strGo; ?>" />
         </td></tr>
        </form>
<?php
    }
?>
    <!-- Rename database -->
        <tr><td colspan="3"><img src="<?php echo $GLOBALS['pmaThemeImage'] . 'spacer.png'; ?>" width="1" height="1" border="0" alt="" /></td></tr>
        <tr><td colspan="3" class="tblHeaders"><?php
          if ($cfg['PropertiesIconic']) {
              echo '<img src="' . $pmaThemeImage . 'b_edit.png" border="0" width="16" height="16" hspace="2" align="middle" />';
          }
          echo $strDBRename.':&nbsp;';
          ?></td></tr>
        <form method="post" action="db_operations.php"
            onsubmit="return emptyFormElements(this, 'newname')">
                                        <tr bgcolor="<?php echo $cfg['BgcolorOne']; ?>"><td colspan="2"><?php
          echo '<input type="hidden" name="what" value="data" />';
          echo '<input type="hidden" name="db_rename" value="true" />'
             . PMA_generate_common_hidden_inputs($db);
          ?><input type="text" name="newname" size="30" class="textfield" value="" /></td>
            <td align="right"><input type="submit" value="<?php echo $strGo; ?>" /></td>
        </form></tr>

    <!-- Copy database -->
        <tr><td colspan="3"><img src="<?php echo $GLOBALS['pmaThemeImage'] . 'spacer.png'; ?>" width="1" height="1" border="0" alt="" /></td></tr>
        <tr><td colspan="3" class="tblHeaders"><?php
          if ($cfg['PropertiesIconic']) {
              echo '<img src="' . $pmaThemeImage . 'b_edit.png" border="0" width="16" height="16" hspace="2" align="middle" />';
          }
          echo $strDBCopy.':&nbsp;';
          ?></td></tr>
        <form method="post" action="db_operations.php"
            onsubmit="return emptyFormElements(this, 'newname')">
        <tr bgcolor="<?php echo $cfg['BgcolorOne']; ?>"><td colspan="3">
<?php
          if (isset($db_collation)) {
              echo '<input type="hidden" name="db_collation" value="' . $db_collation .'" />' . "\n";
          }
          echo '<input type="hidden" name="db_copy" value="true" />' . "\n"
             . PMA_generate_common_hidden_inputs($db);
          ?><input type="text" name="newname" size="30" class="textfield" value="" /></td>
        </tr><tr>
            <td nowrap="nowrap" bgcolor="<?php echo $cfg['BgcolorOne']; ?>" colspan="2">
                <input type="radio" name="what" value="structure" id="radio_copy_structure" style="vertical-align: middle" /><label for="radio_copy_structure"><?php echo $strStrucOnly; ?></label>&nbsp;&nbsp;<br />
                <input type="radio" name="what" value="data" id="radio_copy_data" checked="checked" style="vertical-align: middle" /><label for="radio_copy_data"><?php echo $strStrucData; ?></label>&nbsp;&nbsp;<br />
                <input type="radio" name="what" value="dataonly" id="radio_copy_dataonly" style="vertical-align: middle" /><label for="radio_copy_dataonly"><?php echo $strDataOnly; ?></label>&nbsp;&nbsp;<br />
                

                <input type="checkbox" name="create_database_before_copying" value="1" id="checkbox_create_database_before_copying" style="vertical-align: middle" checked="checked" /><label for="checkbox_create_database_before_copying"><?php echo $strCreateDatabaseBeforeCopying; ?></label><br />
                <input type="checkbox" name="drop_if_exists" value="true" id="checkbox_drop" style="vertical-align: middle" /><label for="checkbox_drop"><?php echo $strStrucDrop; ?></label>&nbsp;&nbsp;<br />
                <input type="checkbox" name="sql_auto_increment" value="1" id="checkbox_auto_increment" style="vertical-align: middle" /><label for="checkbox_auto_increment"><?php echo $strAddAutoIncrement; ?></label><br />
                <input type="checkbox" name="constraints" value="1" id="checkbox_constraints" style="vertical-align: middle" /><label for="checkbox_constraints"><?php echo $strAddConstraints; ?></label><br />
                <?php
                    if (isset($_COOKIE) && isset($_COOKIE['pma_switch_to_new']) && $_COOKIE['pma_switch_to_new'] == 'true') {
                        $pma_switch_to_new = 'true';
                    }
                ?>
                <input type="checkbox" name="switch_to_new" value="true" id="checkbox_switch"<?php echo ((isset($pma_switch_to_new) && $pma_switch_to_new == 'true') ? ' checked="checked"' : ''); ?> style="vertical-align: middle" /><label for="checkbox_switch"><?php echo $strSwitchToDatabase; ?></label>&nbsp;&nbsp;
            </td>
            <td align="<?php echo $cell_align_right; ?>" valign="bottom" bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
                <input type="submit" name="submit_copy" value="<?php echo $strGo; ?>" />
            </td>
        </tr>
    </form>

<?php

    if (PMA_MYSQL_INT_VERSION >= 40101) {
    // MySQL supports setting default charsets / collations for databases since
    // version 4.1.1.
        echo '    <!-- Change database charset -->' . "\n"
           . '    <tr><td colspan="3"><img src="' . $GLOBALS['pmaThemeImage'] . 'spacer.png' . '" width="1" height="1" border="0" alt="" /></td></tr>' . "\n"
           . '    <tr><td colspan="3" class="tblHeaders">';
        if ($cfg['PropertiesIconic']) {
            echo '<img src="' . $pmaThemeImage . 's_asci.png" border="0" width="16" height="16" hspace="2" align="middle" />';
        }
        echo '      <label for="select_db_collation">' . $strCollation . '</label>:&nbsp;' . "\n"
           . '    </td></tr>' . "\n"
           . '        <form method="post" action="./db_operations.php">' . "\n"
           . '    <tr bgcolor="' . $cfg['BgcolorOne'] . '"><td colspan="2" nowrap="nowrap">'
           . PMA_generate_common_hidden_inputs($db, $table, 3)
           . PMA_generateCharsetDropdownBox(PMA_CSDROPDOWN_COLLATION, 'db_collation', 'select_db_collation', $db_collation, FALSE, 3)
           . '    </td><td align="right">'
           . '            <input type="submit" name="submitcollation" value="' . $strGo . '" style="vertical-align: middle" />' . "\n"
           . '    </td></tr>' . "\n"
           . '        </form>' . "\n"
           . '         ' . "\n\n";
    }
    
    echo '</table>';

    if ( $num_tables > 0
        && !$cfgRelation['allworks'] && $cfg['PmaNoRelation_DisableWarning'] == FALSE) {
        echo '<div class="error"><h1>' . $strError . '</h1>'
            . sprintf( $strRelationNotWorking, '<a href="' . $cfg['PmaAbsoluteUri'] . 'chk_rel.php?' . $url_query . '">',  '</a>')
            . '</div>';
    } // end if
} // end if (!$is_information_schema)
// not sure about leaving the PDF dialog for information_schema

?>
<form method="post" action="pdf_schema.php">
<?php

if ($num_tables > 0) {
    $takeaway = $url_query . '&amp;table=' . urlencode($table);
}

if (($cfgRelation['pdfwork'] && $num_tables > 0) ||
    ($num_tables > 0
     && $cfgRelation['relwork'] && $cfgRelation['commwork']
     && isset($cfg['docSQLDir']) && !empty($cfg['docSQLDir'])
    )) { ?>
<hr /><table border="0" cellpadding="2" cellspacing="0">
<?php
}

if ($cfgRelation['pdfwork'] && $num_tables > 0) { ?>
    <!-- Work on PDF Pages -->
    <tr>
        <td colspan="3" class="tblHeaders">
    <?php
    if ($cfg['PropertiesIconic']) {
        echo '<img src="' . $pmaThemeImage . 'b_pdfdoc.png" border="0" width="16" height="16" hspace="2" align="middle" />';
    } ?>PDF</td>
    </tr>

    <tr bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
        <td colspan="3">
        <?php
            echo '<a href="pdf_pages.php?' . $takeaway . '">';
            if ($cfg['PropertiesIconic']) {
                echo '<img src="' . $pmaThemeImage . 'b_edit.png" border="0" width="16" height="16" hspace="2" align="middle" />';
            }
            echo ''. $strEditPDFPages . '</a>';
        ?>
        </td>
    </tr>

    <!-- PDF schema -->
    <?php
    // We only show this if we find something in the new pdf_pages table

    $test_query = 'SELECT * FROM ' . PMA_backquote($cfgRelation['pdf_pages'])
                . ' WHERE db_name = \'' . PMA_sqlAddslashes($db) . '\'';
    $test_rs    = PMA_query_as_cu($test_query, NULL, PMA_DBI_QUERY_STORE);

    if ($test_rs && PMA_DBI_num_rows($test_rs) > 0) { ?>
    <tr bgcolor="<?php echo $cfg['BgcolorTwo']; ?>">
        <td colspan="3">
        <?php
         echo PMA_generate_common_hidden_inputs($db);
         if ($cfg['PropertiesIconic']) {
             echo '<img src="' . $pmaThemeImage . 'b_view.png" border="0" width="16" height="16" hspace="2" align="middle" />';
         }
         echo $strDisplayPDF; ?>:&nbsp;
        </td>
    </tr>

    <tr bgcolor="<?php echo $cfg['BgcolorTwo']; ?>">
        <td width="20">&nbsp;</td>
        <td colspan="2">
            <label for="pdf_page_number_opt"><?php echo $strPageNumber; ?></label>
            <select name="pdf_page_number" id="pdf_page_number_opt">
            <?php
            while ($pages = @PMA_DBI_fetch_assoc($test_rs)) {
                echo '                <option value="' . $pages['page_nr'] . '">' . $pages['page_nr'] . ': ' . $pages['page_descr'] . '</option>' . "\n";
            } // end while
            PMA_DBI_free_result($test_rs);
            unset($test_rs);
            ?>
            </select><br />

            <input type="checkbox" name="show_grid" id="show_grid_opt" /><label for="show_grid_opt"><?php echo $strShowGrid; ?></label><br />
            <input type="checkbox" name="show_color" id="show_color_opt" checked="checked" /><label for="show_color_opt"><?php echo $strShowColor; ?></label><br />
            <input type="checkbox" name="show_table_dimension" id="show_table_dim_opt" /><label for="show_table_dim_opt"><?php echo $strShowTableDimension; ?></label><br />
            <input type="checkbox" name="all_tab_same_wide" id="all_tab_same_wide" /><label for="all_tab_same_wide"><?php echo $strAllTableSameWidth; ?></label><br />
            <input type="checkbox" name="with_doc" id="with_doc" checked="checked" /><label for="with_doc"><?php echo $strDataDict; ?></label><br />

            <label for="orientation_opt"><?php echo $strShowDatadictAs; ?></label>
            <select name="orientation" id="orientation_opt">
                <option value="L"><?php echo $strLandscape;?></option>
                <option value="P"><?php echo $strPortrait;?></option>
            </select><br />

            <label for="paper_opt"><?php echo $strPaperSize; ?></label>
            <select name="paper" id="paper_opt">
            <?php
                foreach ($cfg['PDFPageSizes'] AS $key => $val) {
                    echo '<option value="' . $val . '"';
                    if ($val == $cfg['PDFDefaultPageSize']) {
                        echo ' selected="selected"';
                    }
                    echo ' >' . $val . '</option>' . "\n";
                }
            ?>
            </select>
        </td>
    </tr>

    <tr bgcolor="<?php echo $cfg['BgcolorTwo']; ?>">
        <td width="20">&nbsp;</td>
        <td colspan="3" align="right">&nbsp;&nbsp;<input type="submit" value="<?php echo $strGo; ?>" /></td>
    </tr>
    <tr>
        <td colspan="3"><img src="<?php echo $GLOBALS['pmaThemeImage'] . 'spacer.png'; ?>" width="1" height="1" border="0" alt="" /></td>
    </tr>
        <?php
    }   // end if
} // end if

if ($num_tables > 0
    && $cfgRelation['relwork'] && $cfgRelation['commwork']
    && isset($cfg['docSQLDir']) && !empty($cfg['docSQLDir'])
    ) {
?>
    <!-- import docSQL files -->
    <tr bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
        <td colspan="3">
        <?php
        echo '<a href="db_details_importdocsql.php?' . $takeaway . '">';
        if ($cfg['PropertiesIconic']) {
            echo '<img src="' . $pmaThemeImage . 'b_docsql.png" border="0" width="16" height="16" hspace="2" align="middle" />';
        }
        echo $strImportDocSQL . '</a>';
        ?>
        </td>
    </tr>
    <?php
}
echo "\n";
if (($cfgRelation['pdfwork'] && $num_tables > 0) ||
    ($num_tables > 0
     && $cfgRelation['relwork'] && $cfgRelation['commwork']
     && isset($cfg['docSQLDir']) && !empty($cfg['docSQLDir'])
    )) { ?>
</table>
</form>
<?php
}

/**
 * Displays the footer
 */
echo "\n";
require_once('./footer.inc.php');
?>
