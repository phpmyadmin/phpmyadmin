<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * handles miscellaneous db operations:
 *  - move/rename
 *  - copy
 *  - changing collation
 *  - changing comment
 *  - adding tables
 *  - viewing PDF schemas
 *
 * @version $Id$
 */

/**
 * requirements
 */
require_once './libraries/common.inc.php';
require_once './libraries/Table.class.php';
require_once './libraries/mysql_charsets.lib.php';

/**
 * Rename/move or copy database
 */
if (strlen($db) &&
    ((isset($db_rename) && $db_rename == 'true') ||
    (isset($db_copy) && $db_copy == 'true'))) {

    if (isset($db_rename) && $db_rename == 'true') {
        $move = true;
    } else {
        $move = false;
    }

    if (!isset($newname) || !strlen($newname)) {
        $message = $strDatabaseEmpty;
    } else {
        $sql_query = ''; // in case target db exists
        if ($move ||
           (isset($create_database_before_copying) && $create_database_before_copying)) {
            // lower_case_table_names=1 `DB` becomes `db`
            $lower_case_table_names = PMA_DBI_fetch_value('SHOW VARIABLES LIKE "lower_case_table_names"', 0, 1);
            if ($lower_case_table_names === '1') {
                $newname = strtolower($newname);
            }
            
            $local_query = 'CREATE DATABASE ' . PMA_backquote($newname);
            if (isset($db_collation)) {
                $local_query .= ' DEFAULT' . PMA_generateCharsetQueryPart($db_collation);
            }
            $local_query .= ';';
            $sql_query = $local_query;
            PMA_DBI_query($local_query);
            // rebuild the database list because PMA_Table::moveCopy
            // checks in this list if the target db exists
            $GLOBALS['PMA_List_Database']->build();
        }

        if (isset($GLOBALS['add_constraints'])) {
            $GLOBALS['sql_constraints_query_full_db'] = '';
        }

        $tables_full = PMA_DBI_get_tables_full($db);
        $views = array();
        foreach ($tables_full as $each_table => $tmp) {
            // to be able to rename a db containing views, we
            // first collect in $views all the views we find and we
            // will handle them after the tables
            /**
             * @todo support a view of a view
             * @todo support triggers 
             */
            if (PMA_Table::isView($db, $each_table)) {
                $views[] = $each_table;
                continue;
            }

            $back = $sql_query;
            $sql_query = '';

            // value of $what for this table only
            $this_what = $what;

            if (!isset($tables_full[$each_table]['Engine'])) {
                $tables_full[$each_table]['Engine'] = $tables_full[$each_table]['Type'];
            }
            // do not copy the data from a Merge table
            // note: on the calling FORM, 'data' means 'structure and data'
            if ($tables_full[$each_table]['Engine'] == 'MRG_MyISAM') {
                if ($this_what == 'data') {
                    $this_what = 'structure';
                }
                if ($this_what == 'dataonly') {
                    $this_what = 'nocopy';
                }
            }

            if ($this_what != 'nocopy') {
                PMA_Table::moveCopy($db, $each_table, $newname, $each_table,
                    isset($this_what) ? $this_what : 'data', $move, 'db_copy');
                if (isset($GLOBALS['add_constraints'])) {
                    $GLOBALS['sql_constraints_query_full_db'] .= $GLOBALS['sql_constraints_query'];
                    unset($GLOBALS['sql_constraints_query']);
                }
            }

            $sql_query = $back . $sql_query;
        } // end (foreach)
        unset($each_table);

        // handle the views
        foreach ($views as $view) {
            PMA_Table::moveCopy($db, $view, $newname, $view,
                'structure', $move, 'db_copy');
        }
        unset($view, $views);

        // now that all tables exist, create all the accumulated constraints
        if (isset($GLOBALS['add_constraints'])) {
            /**
             * @todo this works with mysqli but not with mysql, because
             * mysql extension does not accept more than one statement; maybe
             * interface with the sql import plugin that handles statement delimiter
             */
            PMA_DBI_query($GLOBALS['sql_constraints_query_full_db']);

            // and prepare to display them
            $GLOBALS['sql_query'] .= "\n" . $GLOBALS['sql_constraints_query_full_db'];
            unset($GLOBALS['sql_constraints_query_full_db']);
        }

        if (PMA_MYSQL_INT_VERSION >= 50000) {
            // here I don't use DELIMITER because it's not part of the
            // language; I have to send each statement one by one

            // to avoid selecting alternatively the current and new db
            // we would need to modify the CREATE definitions to qualify
            // the db name
            $procedure_names = PMA_DBI_get_procedures_or_functions($db, 'PROCEDURE');
            if ($procedure_names) {
                foreach($procedure_names as $procedure_name) {
                    PMA_DBI_select_db($db);
                    $tmp_query = PMA_DBI_get_procedure_or_function_def($db, 'PROCEDURE', $procedure_name);
                    // collect for later display
                    $GLOBALS['sql_query'] .= "\n" . $tmp_query;
                    PMA_DBI_select_db($newname);
                    PMA_DBI_query($tmp_query);
                }
            }

            $function_names = PMA_DBI_get_procedures_or_functions($db, 'FUNCTION');
            if ($function_names) {
                foreach($function_names as $function_name) {
                    PMA_DBI_select_db($db);
                    $tmp_query = PMA_DBI_get_procedure_or_function_def($db, 'FUNCTION', $function_name);
                    // collect for later display
                    $GLOBALS['sql_query'] .= "\n" . $tmp_query;
                    PMA_DBI_select_db($newname);
                    PMA_DBI_query($tmp_query);
                }
            }
        }
        // go back to current db, just in case
        PMA_DBI_select_db($db);

        // Duplicate the bookmarks for this db (done once for each db)
        if ($db != $newname) {
            $get_fields = array('user', 'label', 'query');
            $where_fields = array('dbase' => $db);
            $new_fields = array('dbase' => $newname);
            PMA_Table::duplicateInfo('bookmarkwork', 'bookmark', $get_fields,
                $where_fields, $new_fields);
        }

        if ($move) {
            // cleanup pmadb stuff for this db
            require_once './libraries/relation_cleanup.lib.php';
            PMA_relationsCleanupDatabase($db);

            // if someday the RENAME DATABASE reappears, do not DROP
            $local_query = 'DROP DATABASE ' . PMA_backquote($db) . ';';
            $sql_query .= "\n" . $local_query;
            PMA_DBI_query($local_query);

            $message    = sprintf($strRenameDatabaseOK, htmlspecialchars($db),
                htmlspecialchars($newname));
        } else {
            $message    = sprintf($strCopyDatabaseOK, htmlspecialchars($db),
                htmlspecialchars($newname));
        }
        $reload     = true;

        /* Change database to be used */
        if ($move) {
            $db         = $newname;
        } else {
            if (isset($switch_to_new) && $switch_to_new == 'true') {
                PMA_setCookie('pma_switch_to_new', 'true');
                $db         = $newname;
            } else {
                PMA_setCookie('pma_switch_to_new', '');
            }
        }
    }
}
/**
 * Settings for relations stuff
 */

require_once './libraries/relation.lib.php';
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
 * because there is no table in the database ($is_info is true)
 */
if (empty($is_info)) {
    require './libraries/db_common.inc.php';
    $url_query .= '&amp;goto=db_operations.php';

    // Gets the database structure
    $sub_part = '_structure';
    require './libraries/db_info.inc.php';
    echo "\n";
}

if (PMA_MYSQL_INT_VERSION >= 40101) {
    $db_collation = PMA_getDbCollation($db);
}
if (PMA_MYSQL_INT_VERSION < 50002
  || (PMA_MYSQL_INT_VERSION >= 50002 && $db != 'information_schema')) {
    $is_information_schema = false;
} else {
    $is_information_schema = true;
}

if (!$is_information_schema) {

    require './libraries/display_create_table.lib.php';

    if ($cfgRelation['commwork']) {
        /**
         * database comment
         */
        ?>
    <form method="post" action="db_operations.php">
    <?php echo PMA_generate_common_hidden_inputs($db); ?>
    <input type="hidden" name="db_comment" value="true" />
    <fieldset>
        <legend>
        <?php
        if ($cfg['PropertiesIconic']) {
            echo '<img class="icon" src="' . $pmaThemeImage . 'b_comment.png"'
                .' alt="" border="0" width="16" height="16" hspace="2" align="middle" />';
        }
        echo $strDBComment;
        $comment = PMA_getComments($db);
        ?>
        </legend>
        <input type="text" name="comment" class="textfield" size="30"
            value="<?php
            echo (isset($comment) && is_array($comment)
                ? htmlspecialchars(implode(' ', $comment))
                : ''); ?>" />
        <input type="submit" value="<?php echo $strGo; ?>" />
    </fieldset>
    </form>
        <?php
    }
    /**
     * rename database
     */
    ?>
    <form method="post" action="db_operations.php"
        onsubmit="return emptyFormElements(this, 'newname')">
        <?php
    if (isset($db_collation)) {
        echo '<input type="hidden" name="db_collation" value="' . $db_collation
             .'" />' . "\n";
    }
         ?>
    <input type="hidden" name="what" value="data" />
    <input type="hidden" name="db_rename" value="true" />
    <?php echo PMA_generate_common_hidden_inputs($db); ?>
    <fieldset>
        <legend>
    <?php
    if ($cfg['PropertiesIconic']) {
        echo '<img class="icon" src="' . $pmaThemeImage . 'b_edit.png"'
            .' alt="" width="16" height="16" />';
    }
    echo $strDBRename . ':';
    ?>
        </legend>
        <input type="text" name="newname" size="30" class="textfield" value="" />
        <?php
    echo '(' . $strCommand . ': ';
    /**
     * @todo (see explanations above in a previous todo) 
     */
    //if (PMA_MYSQL_INT_VERSION >= XYYZZ) {
    //    echo 'RENAME DATABASE';
    //} else {
        echo 'INSERT INTO ... SELECT';
    //}
    echo ')'; ?>
        <input type="submit" value="<?php echo $strGo; ?>" onclick="return confirmLink(this, 'CREATE DATABASE ... <?php echo $strAndThen; ?> DROP DATABASE <?php echo PMA_jsFormat($db); ?>')" />
    </fieldset>
    </form>

    <?php
    /**
     * Copy database
     */
    ?>
    <form method="post" action="db_operations.php"
        onsubmit="return emptyFormElements(this, 'newname')">
    <?php
    if (isset($db_collation)) {
        echo '<input type="hidden" name="db_collation" value="' . $db_collation
            .'" />' . "\n";
    }
    echo '<input type="hidden" name="db_copy" value="true" />' . "\n";
    echo PMA_generate_common_hidden_inputs($db);
    ?>
    <fieldset>
        <legend>
    <?php
    if ($cfg['PropertiesIconic']) {
        echo '<img class="icon" src="' . $pmaThemeImage . 'b_edit.png"'
            .' alt="" width="16" height="16" />';
    }
    echo $strDBCopy . ':';
    if (PMA_MYSQL_INT_VERSION >= 50000) {
        $drop_clause = 'DROP TABLE / DROP VIEW';
    } else {
        $drop_clause = 'DROP TABLE';
    }
    ?>
        </legend>
        <input type="text" name="newname" size="30" class="textfield" value="" /><br />
        <input type="radio" name="what" value="structure"
            id="radio_copy_structure" style="vertical-align: middle" />
        <label for="radio_copy_structure"><?php echo $strStrucOnly; ?></label><br />
        <input type="radio" name="what" value="data" id="radio_copy_data"
            checked="checked" style="vertical-align: middle" />
        <label for="radio_copy_data"><?php echo $strStrucData; ?></label><br />
        <input type="radio" name="what" value="dataonly"
            id="radio_copy_dataonly" style="vertical-align: middle" />
        <label for="radio_copy_dataonly"><?php echo $strDataOnly; ?></label><br />

        <input type="checkbox" name="create_database_before_copying" value="1"
            id="checkbox_create_database_before_copying"
            style="vertical-align: middle" checked="checked" />
        <label for="checkbox_create_database_before_copying">
            <?php echo $strCreateDatabaseBeforeCopying; ?></label><br />
        <input type="checkbox" name="drop_if_exists" value="true"
            id="checkbox_drop" style="vertical-align: middle" />
        <label for="checkbox_drop"><?php echo sprintf($strAddClause, $drop_clause); ?></label><br />
        <input type="checkbox" name="sql_auto_increment" value="1"
            id="checkbox_auto_increment" style="vertical-align: middle" />
        <label for="checkbox_auto_increment">
            <?php echo $strAddAutoIncrement; ?></label><br />
        <input type="checkbox" name="add_constraints" value="1"
            id="checkbox_constraints" style="vertical-align: middle" />
        <label for="checkbox_constraints">
            <?php echo $strAddConstraints; ?></label><br />
    <?php
    unset($drop_clause);

    if (isset($_COOKIE) && isset($_COOKIE['pma_switch_to_new'])
      && $_COOKIE['pma_switch_to_new'] == 'true') {
        $pma_switch_to_new = 'true';
    }
    ?>
        <input type="checkbox" name="switch_to_new" value="true"
            id="checkbox_switch"
            <?php echo ((isset($pma_switch_to_new) && $pma_switch_to_new == 'true') ? ' checked="checked"' : ''); ?>
            style="vertical-align: middle" />
        <label for="checkbox_switch"><?php echo $strSwitchToDatabase; ?></label>
    </fieldset>
    <fieldset class="tblFooters">
        <input type="submit" name="submit_copy" value="<?php echo $strGo; ?>" />
    </fieldset>
    </form>

    <?php
    /**
     * Change database charset
     */
    if (PMA_MYSQL_INT_VERSION >= 40101) {
    // MySQL supports setting default charsets / collations for databases since
    // version 4.1.1.
        echo '<form method="post" action="./db_operations.php">' . "\n"
           . PMA_generate_common_hidden_inputs($db, $table)
           . '<fieldset>' . "\n"
           . '    <legend>';
        if ($cfg['PropertiesIconic']) {
            echo '<img class="icon" src="' . $pmaThemeImage . 's_asci.png"'
                .' alt="" width="16" height="16" />';
        }
        echo '    <label for="select_db_collation">' . $strCollation . ':</label>' . "\n"
           . '    </legend>' . "\n"
           . PMA_generateCharsetDropdownBox(PMA_CSDROPDOWN_COLLATION,
                'db_collation', 'select_db_collation', $db_collation, false, 3)
           . '    <input type="submit" name="submitcollation"'
           . ' value="' . $strGo . '" style="vertical-align: middle" />' . "\n"
           . '</fieldset>' . "\n"
           . '</form>' . "\n";
    }

    if ($num_tables > 0
      && !$cfgRelation['allworks'] && $cfg['PmaNoRelation_DisableWarning'] == false) {
        /* Show error if user has configured something, notice elsewhere */
        if (!empty($cfg['Servers'][$server]['pmadb'])) {
            echo '<div class="error"><h1>' . $strError . '</h1>';
        } else {
            echo '<div class="notice">';
        }
        printf($strRelationNotWorking, '<a href="' . $cfg['PmaAbsoluteUri'] . 'chk_rel.php?' . $url_query . '">', '</a>');
        echo '</div>';
    } // end if
} // end if (!$is_information_schema)


// not sure about displaying the PDF dialog in case db is information_schema
if ($cfgRelation['pdfwork'] && $num_tables > 0) { ?>
    <!-- Work on PDF Pages -->

    <?php
    // We only show this if we find something in the new pdf_pages table

    $test_query = '
         SELECT *
           FROM ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['pdf_pages']) . '
          WHERE db_name = \'' . PMA_sqlAddslashes($db) . '\'';
    $test_rs    = PMA_query_as_cu($test_query, null, PMA_DBI_QUERY_STORE);

    if ($test_rs && PMA_DBI_num_rows($test_rs) > 0) { ?>
    <!-- PDF schema -->
    <form method="post" action="pdf_schema.php">
    <fieldset>
        <legend>
        <?php
        echo PMA_generate_common_hidden_inputs($db);
        if ($cfg['PropertiesIconic']) {
            echo '<img class="icon" src="' . $pmaThemeImage . 'b_view.png"'
                .' alt="" width="16" height="16" />';
        }
        echo $strDisplayPDF;
        ?>:
        </legend>
        <label for="pdf_page_number_opt"><?php echo $strPageNumber; ?></label>
        <select name="pdf_page_number" id="pdf_page_number_opt">
        <?php
        while ($pages = @PMA_DBI_fetch_assoc($test_rs)) {
            echo '                <option value="' . $pages['page_nr'] . '">'
                . $pages['page_nr'] . ': ' . htmlspecialchars($pages['page_descr']) . '</option>' . "\n";
        } // end while
        PMA_DBI_free_result($test_rs);
        unset($test_rs);
        ?>
        </select><br />

        <input type="checkbox" name="show_grid" id="show_grid_opt" />
        <label for="show_grid_opt"><?php echo $strShowGrid; ?></label><br />
        <input type="checkbox" name="show_color" id="show_color_opt"
            checked="checked" />
        <label for="show_color_opt"><?php echo $strShowColor; ?></label><br />
        <input type="checkbox" name="show_table_dimension" id="show_table_dim_opt" />
        <label for="show_table_dim_opt"><?php echo $strShowTableDimension; ?>
            </label><br />
        <input type="checkbox" name="all_tab_same_wide" id="all_tab_same_wide" />
        <label for="all_tab_same_wide"><?php echo $strAllTableSameWidth; ?>
            </label><br />
        <input type="checkbox" name="with_doc" id="with_doc" checked="checked" />
        <label for="with_doc"><?php echo $strDataDict; ?></label><br />

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
    </fieldset>
    <fieldset class="tblFooters">
        <input type="submit" value="<?php echo $strGo; ?>" />
    </fieldset>
    </form>
        <?php
    }   // end if
    echo '<br /><a href="pdf_pages.php?' . $url_query . '">';
    if ($cfg['PropertiesIconic']) {
        echo '<img class="icon" src="' . $pmaThemeImage . 'b_edit.png"'
            .' alt="" width="16" height="16" />';
    }
    echo $strEditPDFPages . '</a>';
} // end if

/**
 * Displays the footer
 */
require_once './libraries/footer.inc.php';
?>
