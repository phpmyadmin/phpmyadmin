<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

require_once('./libraries/grab_globals.lib.php');
require_once('./libraries/common.lib.php');
require_once('./libraries/mysql_charsets.lib.php');

/**
 * Rename database
 */
if (isset($db) &&
    ((isset($db_rename) && $db_rename == 'true') ||
    (isset($db_copy) && $db_copy == 'true'))) {

    require_once('./libraries/tbl_move_copy.php');

    if (isset($db_rename) && $db_rename == 'true') $move = TRUE;
    else $move = FALSE;

    if (!isset($newname) || empty($newname)) {
        $message = $strDatabaseEmpty;
    } else {
        $local_query = 'CREATE DATABASE ' . PMA_backquote($newname) . ';';
        $sql_query = $local_query;
        PMA_DBI_query($local_query);
        $tables = PMA_DBI_get_tables($db);
        foreach ($tables as $table) {
            $back = $sql_query;
            $sql_query = '';
            PMA_table_move_copy($db, $table, $newname, $table, isset($what) ? $what : 'data', $move);
            $sql_query = $back . $sql_query;
        }
        if ($move) {
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
                setcookie('pma_switch_to_new', 'true', 0, substr($pma_uri_parts['path'], 0, strrpos($pma_uri_parts['path'], '/')), '', ($pma_uri_parts['scheme'] == 'https'));
                $db         = $newname;
            } else {
                setcookie('pma_switch_to_new', '', 0, substr($pma_uri_parts['path'], 0, strrpos($pma_uri_parts['path'], '/')), '', ($pma_uri_parts['scheme'] == 'https'));
            }
        }
    }
}
/**
 * Prepares the tables list if the user where not redirected to this script
 * because there is no table in the database ($is_info is TRUE)
 */
if (empty($is_info)) {
    require('./db_details_common.php');
    $url_query .= '&amp;goto=db_operations.php';

    // Gets the database structure
    $sub_part = '_structure';
    require('./db_details_db_info.php');
    echo "\n";
}

if (PMA_MYSQL_INT_VERSION >= 40101) {
    $db_collation = PMA_getDbCollation($db);
}
?>

<table border="0" cellpadding="2" cellspacing="0">
    <!-- Create a new table -->
        <form method="post" action="tbl_create.php" onsubmit="return (emptyFormElements(this, 'table') && checkFormElementInRange(this, 'num_fields', 1))">
     <tr>
     <td class="tblHeaders" colspan="3" nowrap="nowrap"><?php
        echo PMA_generate_common_hidden_inputs($db);
        if($cfg['PropertiesIconic']){ echo '<img src="' . $pmaThemeImage . 'b_newtbl.png" border="0" width="16" height="16" hspace="2" align="middle" />'; }
        // if you want navigation:
        $strDBLink = '<a href="' . $GLOBALS['cfg']['DefaultTabDatabase'] . '?' . PMA_generate_common_url() . '&amp;db=' . urlencode($GLOBALS['db']) . '">'
                   . htmlspecialchars($GLOBALS['db']) . '</a>';
        // else use
        // $strDBLink = htmlspecialchars($db);
echo '             ' . sprintf($strCreateNewTable, $strDBLink) . ':&nbsp;' . "\n";
echo '     </td></tr>';
echo '     <tr bgcolor="'.$cfg['BgcolorOne'].'"><td nowrap="nowrap">';
echo '             ' . $strName . ':&nbsp;' . "\n";
echo '     </td>';
echo '     <td nowrap="nowrap">';
echo '             ' . '<input type="text" name="table" maxlength="64" size="30" class="textfield" />';
echo '     </td><td>&nbsp;</td></tr>';
echo '     <tr bgcolor="'.$cfg['BgcolorOne'].'"><td nowrap="nowrap">';
echo '             ' . $strFields . ':&nbsp;' . "\n";
echo '     </td>';
echo '     <td nowrap="nowrap">';
echo '             ' . '<input type="text" name="num_fields" size="2" class="textfield" />' . "\n";
echo '     </td>';
echo '     <td align="right">';
echo '             ' . '&nbsp;<input type="submit" value="' . $strGo . '" />' . "\n";
echo '     </td> </tr>';
echo '        </form>';

/**
 * Settings for relations stuff
 */
require_once('./libraries/relation.lib.php');
$cfgRelation = PMA_getRelationsParam();

/**
 * Check if comments were updated
 */
if ($cfgRelation['commwork'] && isset($db_comment) && $db_comment == 'true') {
    PMA_SetComment($db, '', '(db_comment)', $comment);
}

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
                                        <tr bgcolor="<?php echo $cfg['BgcolorOne']; ?>"><td colspan="3"><?php
          echo '<input type="hidden" name="db_copy" value="true" />'
             . PMA_generate_common_hidden_inputs($db);
          ?><input type="text" name="newname" size="30" class="textfield" value="" /></td>
        </tr><tr>
            <td nowrap="nowrap" bgcolor="<?php echo $cfg['BgcolorOne']; ?>" colspan="2">
                <input type="radio" name="what" value="structure" id="radio_copy_structure" style="vertical-align: middle" /><label for="radio_copy_structure"><?php echo $strStrucOnly; ?></label>&nbsp;&nbsp;<br />
                <input type="radio" name="what" value="data" id="radio_copy_data" checked="checked" style="vertical-align: middle" /><label for="radio_copy_data"><?php echo $strStrucData; ?></label>&nbsp;&nbsp;<br />
                <input type="radio" name="what" value="dataonly" id="radio_copy_dataonly" style="vertical-align: middle" /><label for="radio_copy_dataonly"><?php echo $strDataOnly; ?></label>&nbsp;&nbsp;<br />

                <input type="checkbox" name="auto_increment" value="1" id="checkbox_auto_increment" style="vertical-align: middle" /><label for="checkbox_auto_increment"><?php echo $strAddAutoIncrement; ?></label><br />
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

if ($num_tables > 0
    && !$cfgRelation['allworks'] && $cfg['PmaNoRelation_DisableWarning'] == FALSE) {
    echo '<tr><td colspan="3"><img src="' . $GLOBALS['pmaThemeImage'] . 'spacer.png' . '" width="1" height="1" border="0" alt="" /></td></tr>'
        . '<tr><th colspan="3" class="tblHeadError"><div class="errorhead">' . $strError . '</div></th></tr>'
        . '<tr><td colspan="3" class="tblError">'
        . sprintf(wordwrap($strRelationNotWorking,65,'<br />'), '<a href="' . $cfg['PmaAbsoluteUri'] . 'chk_rel.php?' . $url_query . '">',  '</a>')
        . '</td></tr>';
} // end if
?>
</table>

<form method="post" action="pdf_schema.php">
<?php
// is this OK to check for 'class' support?
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
