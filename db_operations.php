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
 * @package phpMyAdmin
 */

/**
 * requirements
 */
require_once './libraries/common.inc.php';
require_once './libraries/Table.class.php';
require_once './libraries/mysql_charsets.lib.php';

// add blobstreaming library functions
require_once "./libraries/blobstreaming.lib.php";

/**
 * Rename/move or copy database
 */
if (strlen($db) && (! empty($db_rename) || ! empty($db_copy))) {

    if (! empty($db_rename)) {
        $move = true;
    } else {
        $move = false;
    }

    if (!isset($newname) || !strlen($newname)) {
        $message = PMA_Message::error('strDatabaseEmpty');
    } else {
        $sql_query = ''; // in case target db exists
        $_error = false;
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
            // save the original db name because Tracker.class.php which
            // may be called under PMA_DBI_query() changes $GLOBALS['db']
            // for some statements, one of which being CREATE DATABASE
            $original_db = $db;
            PMA_DBI_query($local_query);
            $db = $original_db;
            unset($original_db);

            // rebuild the database list because PMA_Table::moveCopy
            // checks in this list if the target db exists
            $GLOBALS['pma']->databases->build();
        }

        if (isset($GLOBALS['add_constraints']) || $move) {
            $GLOBALS['sql_constraints_query_full_db'] = array();
        }

        $tables_full = PMA_DBI_get_tables_full($db);
        $views = array();

        // remove all foreign key constraints, otherwise we can get errors
        require_once './libraries/export/sql.php';
        foreach ($tables_full as $each_table => $tmp) {
            $sql_constraints = '';
            $sql_drop_foreign_keys = '';
            $sql_structure = PMA_getTableDef($db, $each_table, "\n", '', false, false);
            if ($move && ! empty($sql_drop_foreign_keys)) {
                PMA_DBI_query($sql_drop_foreign_keys);
            }
            // keep the constraint we just dropped
            if (! empty($sql_constraints)) {
                $GLOBALS['sql_constraints_query_full_db'][] = $sql_constraints;
            }
        }
        unset($sql_constraints, $sql_drop_foreign_keys, $sql_structure);


        foreach ($tables_full as $each_table => $tmp) {
            // to be able to rename a db containing views, we
            // first collect in $views all the views we find and we
            // will handle them after the tables
            /**
             * @todo support a view of a view
             */
            if (PMA_Table::isView($db, $each_table)) {
                $views[] = $each_table;
                continue;
            }

            $back = $sql_query;
            $sql_query = '';

            // value of $what for this table only
            $this_what = $what;

            // do not copy the data from a Merge table
            // note: on the calling FORM, 'data' means 'structure and data'
            if (PMA_Table::isMerge($db, $each_table)) {
                if ($this_what == 'data') {
                    $this_what = 'structure';
                }
                if ($this_what == 'dataonly') {
                    $this_what = 'nocopy';
                }
            }

            if ($this_what != 'nocopy') {
                // keep the triggers from the original db+table
                // (third param is empty because delimiters are only intended 
                //  for importing via the mysql client or our Import feature)
                $triggers = PMA_DBI_get_triggers($db, $each_table, '');

                if (! PMA_Table::moveCopy($db, $each_table, $newname, $each_table,
                    isset($this_what) ? $this_what : 'data', $move, 'db_copy'))
                {
                    $_error = true;
                    // $sql_query is filled by PMA_Table::moveCopy()
                    $sql_query = $back . $sql_query;
                    break;
                }
                // apply the triggers to the destination db+table
                if ($triggers) {
                    PMA_DBI_select_db($newname);
                    foreach ($triggers as $trigger) {
                        PMA_DBI_query($trigger['create']);
                    }
                    unset($trigger);
                }
                unset($triggers); 

                // this does not apply to a rename operation
                if (isset($GLOBALS['add_constraints']) && !empty($GLOBALS['sql_constraints_query'])) {
                    $GLOBALS['sql_constraints_query_full_db'][] = $GLOBALS['sql_constraints_query'];
                    unset($GLOBALS['sql_constraints_query']);
                }
            }
            // $sql_query is filled by PMA_Table::moveCopy()
            $sql_query = $back . $sql_query;
        } // end (foreach)
        unset($each_table);

        // handle the views
        if (! $_error) {
            foreach ($views as $view) {
                if (! PMA_Table::moveCopy($db, $view, $newname, $view,
                 'structure', $move, 'db_copy')) {
                    $_error = true;
                    break;
                }
            }
        }
        unset($view, $views);

        // now that all tables exist, create all the accumulated constraints
        if (! $_error && count($GLOBALS['sql_constraints_query_full_db']) > 0) {
            PMA_DBI_select_db($newname);
            foreach ($GLOBALS['sql_constraints_query_full_db'] as $one_query) {
                PMA_DBI_query($one_query);
            // and prepare to display them
                $GLOBALS['sql_query'] .= "\n" . $one_query;
            }

            unset($GLOBALS['sql_constraints_query_full_db'], $one_query);
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
                    $tmp_query = PMA_DBI_get_definition($db, 'PROCEDURE', $procedure_name);
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
                    $tmp_query = PMA_DBI_get_definition($db, 'FUNCTION', $function_name);
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
        if (! $_error && $db != $newname) {
            $get_fields = array('user', 'label', 'query');
            $where_fields = array('dbase' => $db);
            $new_fields = array('dbase' => $newname);
            PMA_Table::duplicateInfo('bookmarkwork', 'bookmark', $get_fields,
                $where_fields, $new_fields);
        }

        if (! $_error && $move) {
            /**
             * cleanup pmadb stuff for this db
             */
            require_once './libraries/relation_cleanup.lib.php';
            PMA_relationsCleanupDatabase($db);

            // if someday the RENAME DATABASE reappears, do not DROP
            $local_query = 'DROP DATABASE ' . PMA_backquote($db) . ';';
            $sql_query .= "\n" . $local_query;
            PMA_DBI_query($local_query);

            $message = PMA_Message::success('strRenameDatabaseOK');
            $message->addParam($db);
            $message->addParam($newname);
        } elseif (! $_error)  {
            $message = PMA_Message::success('strCopyDatabaseOK');
            $message->addParam($db);
            $message->addParam($newname);
        }
        $reload     = true;

        /* Change database to be used */
        if (! $_error && $move) {
            $db = $newname;
        } elseif (! $_error) {
            if (isset($switch_to_new) && $switch_to_new == 'true') {
                PMA_setCookie('pma_switch_to_new', 'true');
                $db = $newname;
            } else {
                PMA_setCookie('pma_switch_to_new', '');
            }
        }

        if ($_error && ! isset($message)) {
            $message = PMA_Message::error();
        }
    }
}

/*
 * Enable/Disable/Repair BLOB Repository Monitoring for current database
*/
if (strlen($db) > 0 && !empty($db_blob_streaming_op))
{
    // load PMA_Config
    $PMA_Config = $_SESSION['PMA_Config'];

    if (!empty($PMA_Config))
    {
        if ($PMA_Config->get('PBXT_NAME') !== strtolower($db))
        {
            // if Blobstreaming plugins exist, begin checking for Blobstreaming tables
            if ($PMA_Config->get('BLOBSTREAMING_PLUGINS_EXIST'))
            {
                $bs_tables = $PMA_Config->get('BLOBSTREAMABLE_DATABASES');
                $bs_tables = $bs_tables[$db];

                $oneBSTableExists = FALSE;

                // check if at least one blobstreaming table exists
                foreach ($bs_tables as $table_key=>$tbl)
                    if ($bs_tables[$table_key]['Exists'])
                    {
                        $oneBSTableExists = TRUE;
                        break;
                    }

                switch ($db_blob_streaming_op)
                {
                    // enable BLOB repository monitoring
                    case "enable":
                        // if blobstreaming tables do not exist, create them
                        if (!$oneBSTableExists)
                            PMA_BS_CreateTables($db);
                    break;
                    // disable BLOB repository monitoring
                    case "disable":
                        // if at least one blobstreaming table exists, execute drop
                        if ($oneBSTableExists)
                            PMA_BS_DropTables($db);
                    break;
                    // repair BLOB repository
                    case "repair":
                        // check if a blobstreaming table is missing
                        foreach ($bs_tables as $table_key=>$tbl)
                        if (!$bs_tables[$table_key]['Exists'])
                        {
                            PMA_DBI_select_db($db);
                            PMA_DBI_query(PMA_BS_GetTableStruct($table_key));
                        }
                }

                // refresh side menu
                PMA_sendHeaderLocation($cfg['PmaAbsoluteUri'] . 'db_operations.php?' . PMA_generate_common_url ('','', '&') . (isset($db) ? '&db=' . urlencode($db) : '') . (isset($token) ? '&token=' . urlencode($token) : '') . (isset($goto) ? '&goto=' . urlencode($goto) : '') . 'reload=1&purge=1');
            }   // end  if ($PMA_Config->get('BLOBSTREAMING_PLUGINS_EXIST'))
        }   // end if ($PMA_Config->get('PBXT_NAME') !== strtolower($db))
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
if (isset($_REQUEST['comment'])) {
    PMA_setDbComment($db, $comment);
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

    if (isset($message)) {
        PMA_showMessage($message, $sql_query);
        unset($message);
    }
}

$db_collation = PMA_getDbCollation($db);
if ($db == 'information_schema') {
    $is_information_schema = true;
} else {
    $is_information_schema = false;
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
    <fieldset>
        <legend>
        <?php echo PMA_getIcon('b_comment.png', $strDBComment, false, true); ?>
        </legend>
        <input type="text" name="comment" class="textfield" size="30"
            value="<?php
            echo htmlspecialchars(PMA_getDBComment($db)); ?>" />
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
    $drop_clause = 'DROP TABLE / DROP VIEW';
    ?>
        </legend>
        <input type="text" name="newname" size="30" class="textfield" value="" /><br />
<?php
        $choices = array(
            'structure' => $strStrucOnly,
            'data'      => $strStrucData,
            'dataonly'  => $strDataOnly);
        PMA_display_html_radio('what', $choices, 'data', true);
        unset($choices);
?>
        <input type="checkbox" name="create_database_before_copying" value="1"
            id="checkbox_create_database_before_copying"
            style="vertical-align: middle" checked="checked" />
        <label for="checkbox_create_database_before_copying">
            <?php echo $strCreateDatabaseBeforeCopying; ?></label><br />
        <input type="checkbox" name="drop_if_exists" value="true"
            id="checkbox_drop" style="vertical-align: middle" />
        <label for="checkbox_drop"><?php echo sprintf($strAddClause, $drop_clause); ?></label><br />
        <input type="checkbox" name="sql_auto_increment" value="1" checked="checked"
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
    /*
     * BLOB streaming support
    */

    // load PMA_Config
    $PMA_Config = $_SESSION['PMA_Config'];

    // if all blobstreaming plugins exist, begin checking for blobstreaming tables
    if (!empty($PMA_Config))
    {
        if ($PMA_Config->get('PBXT_NAME') !== strtolower($db))
        {
            if ($PMA_Config->get('BLOBSTREAMING_PLUGINS_EXIST'))
            {
                $bs_tables = $PMA_Config->get('BLOBSTREAMABLE_DATABASES');
                $bs_tables = $bs_tables[$db];

                $oneBSTableExists = FALSE;
                $allBSTablesExist = TRUE;

                // first check that all blobstreaming tables do not exist
                foreach ($bs_tables as $table_key=>$tbl)
                    if ($bs_tables[$table_key]['Exists'])
                        $oneBSTableExists = TRUE;
                    else
                        $allBSTablesExist = FALSE;

                ?>

                    <form method="post" action="./db_operations.php">
                    <?php echo PMA_generate_common_hidden_inputs($db); ?>
                    <fieldset>
                    <legend>
                    <?php echo PMA_getIcon('b_edit.png', $strBLOBRepository, false, true); ?>
                    </legend>

                    <?php echo $strStatus; ?>:

                    <?php

                    // if the blobstreaming tables exist, provide option to disable the BLOB repository
                    if ($allBSTablesExist)
                    {
                        ?>
                            <?php echo $strBLOBRepositoryEnabled; ?>
                            </fieldset>
                            <fieldset class="tblFooters">
                            <input type="hidden" name="db_blob_streaming_op" value="disable" />
                            <input type="submit" onclick="return confirmDisableRepository('<?php echo $db; ?>');" value="<?php echo $strBLOBRepositoryDisable; ?>" />
                            </fieldset>
                            <?php
                    }
                    else
                    {
                        // if any of the blobstreaming tables are missing, provide option to repair the BLOB repository
                        if ($oneBSTableExists && !$allBSTablesExist)
                        {
                            ?>
                                <?php echo $strBLOBRepositoryDamaged; ?>
                                </fieldset>
                                <fieldset class="tblFooters">
                                <input type="hidden" name="db_blob_streaming_op" value="repair" />
                                <input type="submit" value="<?php echo $strBLOBRepositoryRepair; ?>" />
                                </fieldset>
                                <?php
                        }
                        // if none of the blobstreaming tables exist, provide option to enable BLOB repository
                        else
                        {
                            ?>
                                <?php echo $strBLOBRepositoryDisabled; ?>
                                </fieldset>
                                <fieldset class="tblFooters">
                                <input type="hidden" name="db_blob_streaming_op" value="enable" />
                                <input type="submit" value="<?php echo $strBLOBRepositoryEnable; ?>" />
                                </fieldset>
                                <?php
                        }
                    }   // end if ($allBSTablesExist)

                ?>
                    </form>
                <?php
            }   // end  if ($PMA_Config->get('BLOBSTREAMING_PLUGINS_EXIST'))
        }   // end if ($PMA_Config->get('PBXT_NAME') !== strtolower($db))
    }

    /**
     * Change database charset
     */
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

    if ($num_tables > 0
      && !$cfgRelation['allworks'] && $cfg['PmaNoRelation_DisableWarning'] == false) {
        $message = PMA_Message::notice('strRelationNotWorking');
        $message->addParam('<a href="' . $cfg['PmaAbsoluteUri'] . 'chk_rel.php?' . $url_query . '">', false);
        $message->addParam('</a>', false);
        /* Show error if user has configured something, notice elsewhere */
        if (!empty($cfg['Servers'][$server]['pmadb'])) {
            $message->isError(true);
        }
        $message->display();
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
    $test_rs    = PMA_query_as_controluser($test_query, null, PMA_DBI_QUERY_STORE);

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
		<input type="checkbox" name="show_keys" id="show_keys" />
        <label for="show_keys"><?php echo $strShowKeys; ?></label><br />

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
