<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

require_once('./libraries/grab_globals.lib.php');
require_once('./libraries/common.lib.php');
require_once('./libraries/mysql_charsets.lib.php');

/**
 * Rename database
 */
if (isset($db) && isset($db_rename) && $db_rename == 'true') {
    if (!isset($newname) || empty($newname)) {
        $message = $strDatabaseEmpty;
    } else {
        $local_query = 'CREATE DATABASE ' . PMA_backquote($newname) . ';';
        $sql_query = $local_query;
        PMA_DBI_query($local_query);
        $tables = PMA_DBI_get_tables($db);
        foreach ($tables as $table) {
            $local_query = 'RENAME TABLE '
                . PMA_backquote($db) . '.' . PMA_backquote($table)
                . ' TO '
                . PMA_backquote($newname) . '.' . PMA_backquote($table)
                . ';';
            $sql_query .= "\n" . $local_query;
            PMA_DBI_query($local_query);
        }
        $local_query = 'DROP DATABASE ' . PMA_backquote($db) . ';';
        $sql_query .= "\n" . $local_query;
        PMA_DBI_query($local_query);
        $reload     = TRUE;
        $message    = sprintf($strRenameDatabaseOK, htmlspecialchars($db), htmlspecialchars($newname));

        /* Update relations */
        require_once('./libraries/relation.lib.php');
        $cfgRelation = PMA_getRelationsParam();

        if ($cfgRelation['commwork']) {
            PMA_query_as_cu('UPDATE ' . PMA_backquote($cfgRelation['column_info'])
                          . ' SET db_name    = \'' . PMA_sqlAddslashes($newname) . '\''
                          . ' WHERE db_name  = \'' . PMA_sqlAddslashes($db) . '\'');
        }
        if ($cfgRelation['bookmarkwork']) {
            PMA_query_as_cu('UPDATE ' . PMA_backquote($cfgRelation['bookmark'])
                          . ' SET dbase    = \'' . PMA_sqlAddslashes($newname) . '\''
                          . ' WHERE dbase  = \'' . PMA_sqlAddslashes($db) . '\'');
        }
        if ($cfgRelation['displaywork']) {
            PMA_query_as_cu('UPDATE ' . PMA_backquote($cfgRelation['table_info'])
                          . ' SET db_name    = \'' . PMA_sqlAddslashes($newname) . '\''
                          . ' WHERE db_name  = \'' . PMA_sqlAddslashes($db) . '\'');
        }

        if ($cfgRelation['relwork']) {
            PMA_query_as_cu('UPDATE ' . PMA_backquote($cfgRelation['relation'])
                          . ' SET foreign_db    = \'' . PMA_sqlAddslashes($newname) . '\''
                          . ' WHERE foreign_db  = \'' . PMA_sqlAddslashes($db) . '\'');
            PMA_query_as_cu('UPDATE ' . PMA_backquote($cfgRelation['relation'])
                          . ' SET master_db    = \'' . PMA_sqlAddslashes($newname) . '\''
                          . ' WHERE master_db  = \'' . PMA_sqlAddslashes($db) . '\'');
        }
        if ($cfgRelation['historywork']) {
            PMA_query_as_cu('UPDATE ' . PMA_backquote($cfgRelation['history'])
                          . ' SET db    = \'' . PMA_sqlAddslashes($newname) . '\''
                          . ' WHERE db  = \'' . PMA_sqlAddslashes($db) . '\'');
        }
        if ($cfgRelation['pdfwork']) {
            PMA_query_as_cu('UPDATE ' . PMA_backquote($cfgRelation['table_info'])
                          . ' SET db_name    = \'' . PMA_sqlAddslashes($newname) . '\''
                          . ' WHERE db_name  = \'' . PMA_sqlAddslashes($db) . '\'');
        }

        /* Change database to be used */
        $db         = $newname;
    }
}
/**
 * Prepares the tables list if the user where not redirected to this script
 * because there is no table in the database ($is_info is TRUE)
 */
if (empty($is_info)) {
    require('./db_details_common.php');
    $url_query .= '&amp;goto=db_details_structure.php';

    // Gets the database structure
    $sub_part = '_structure';
    require('./db_details_db_info.php');
    echo "\n";
}

if (PMA_MYSQL_INT_VERSION >= 40101) {
    $db_collation = PMA_getDbCollation($db);
}


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
        <form method="post" action="db_details_structure.php"
            onsubmit="return emptyFormElements(this, 'newname')">
                                        <tr bgcolor="<?php echo $cfg['BgcolorOne']; ?>"><td colspan="2"><?php
          echo '<input type="hidden" name="db_rename" value="true" />'
             . PMA_generate_common_hidden_inputs($db);
          ?><input type="text" name="newname" size="30" class="textfield" value="" /></td>
            <td align="right"><input type="submit" value="<?php echo $strGo; ?>" /></td>
        </form></tr>

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
       . '        <form method="post" action="./db_details_structure.php">' . "\n"
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
