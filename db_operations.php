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
 * @package PhpMyAdmin
 */

/**
 * requirements
 */
require_once './libraries/common.inc.php';
require_once './libraries/mysql_charsets.lib.php';

// add blobstreaming library functions
require_once "./libraries/blobstreaming.lib.php";

// add a javascript file for jQuery functions to handle Ajax actions
// also add jQueryUI
$GLOBALS['js_include'][] = 'jquery/jquery-ui-1.8.16.custom.js';
$GLOBALS['js_include'][] = 'db_operations.js';

/**
 * Rename/move or copy database
 */
if (strlen($db) && (! empty($db_rename) || ! empty($db_copy))) {

    if (! empty($db_rename)) {
        $move = true;
    } else {
        $move = false;
    }

    if (! isset($newname) || ! strlen($newname)) {
        $message = PMA_Message::error(__('The database name is empty!'));
    } else {
        $sql_query = ''; // in case target db exists
        $_error = false;
        if ($move || (isset($create_database_before_copying) && $create_database_before_copying)) {
            // lower_case_table_names=1 `DB` becomes `db`
            if (!PMA_DRIZZLE) {
                $lower_case_table_names = PMA_DBI_fetch_value('SHOW VARIABLES LIKE "lower_case_table_names"', 0, 1);
                if ($lower_case_table_names === '1') {
                    $newname = PMA_strtolower($newname);
                }
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

        // here I don't use DELIMITER because it's not part of the
        // language; I have to send each statement one by one

        // to avoid selecting alternatively the current and new db
        // we would need to modify the CREATE definitions to qualify
        // the db name
        $procedure_names = PMA_DBI_get_procedures_or_functions($db, 'PROCEDURE');
        if ($procedure_names) {
            foreach ($procedure_names as $procedure_name) {
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
            foreach ($function_names as $function_name) {
                PMA_DBI_select_db($db);
                $tmp_query = PMA_DBI_get_definition($db, 'FUNCTION', $function_name);
                // collect for later display
                $GLOBALS['sql_query'] .= "\n" . $tmp_query;
                PMA_DBI_select_db($newname);
                PMA_DBI_query($tmp_query);
            }
        }

        // go back to current db, just in case
        PMA_DBI_select_db($db);

        $GLOBALS['sql_constraints_query_full_db'] = array();

        $tables_full = PMA_DBI_get_tables_full($db);
        $views = array();

        // remove all foreign key constraints, otherwise we can get errors
        include_once './libraries/export/sql.php';
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
            // to be able to rename a db containing views,
            // first all the views are collected and a stand-in is created
            // the real views are created after the tables
            if (PMA_Table::isView($db, $each_table)) {
                $views[] = $each_table;
                // Create stand-in definition to resolve view dependencies
                $sql_view_standin = PMA_getTableDefStandIn($db, $each_table, "\n");
                PMA_DBI_select_db($newname);
                PMA_DBI_query($sql_view_standin);
                $GLOBALS['sql_query'] .= "\n" . $sql_view_standin;
            }
        }

        foreach ($tables_full as $each_table => $tmp) {
            // skip the views; we have creted stand-in definitions
            if (PMA_Table::isView($db, $each_table)) {
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

                if (! PMA_Table::moveCopy(
                    $db, $each_table, $newname, $each_table,
                    isset($this_what) ? $this_what : 'data', $move, 'db_copy')
                ) {
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
                        $GLOBALS['sql_query'] .= "\n" . $trigger['create'] . ';';
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
            // temporarily force to add DROP IF EXIST to CREATE VIEW query,
            // to remove stand-in VIEW that was created earlier
            if (isset($GLOBALS['drop_if_exists'])) {
                $temp_drop_if_exists = $GLOBALS['drop_if_exists'];
            }
            $GLOBALS['drop_if_exists'] = 'true';

            foreach ($views as $view) {
                if (! PMA_Table::moveCopy($db, $view, $newname, $view, 'structure', $move, 'db_copy')) {
                    $_error = true;
                    break;
                }
            }
            unset($GLOBALS['drop_if_exists']);
            if (isset($temp_drop_if_exists)) {
                // restore previous value
                $GLOBALS['drop_if_exists'] = $temp_drop_if_exists;
                unset($temp_drop_if_exists);
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

        if (!PMA_DRIZZLE && PMA_MYSQL_INT_VERSION >= 50100) {
            // here DELIMITER is not used because it's not part of the
            // language; each statement is sent one by one

            // to avoid selecting alternatively the current and new db
            // we would need to modify the CREATE definitions to qualify
            // the db name
            $event_names = PMA_DBI_fetch_result('SELECT EVENT_NAME FROM information_schema.EVENTS WHERE EVENT_SCHEMA= \'' . PMA_sqlAddSlashes($db, true) . '\';');
            if ($event_names) {
                foreach ($event_names as $event_name) {
                    PMA_DBI_select_db($db);
                    $tmp_query = PMA_DBI_get_definition($db, 'EVENT', $event_name);
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
            include_once './libraries/relation_cleanup.lib.php';
            PMA_relationsCleanupDatabase($db);

            // if someday the RENAME DATABASE reappears, do not DROP
            $local_query = 'DROP DATABASE ' . PMA_backquote($db) . ';';
            $sql_query .= "\n" . $local_query;
            PMA_DBI_query($local_query);

            $message = PMA_Message::success(__('Database %s has been renamed to %s'));
            $message->addParam($db);
            $message->addParam($newname);
        } elseif (! $_error) {
            $message = PMA_Message::success(__('Database %s has been copied to %s'));
            $message->addParam($db);
            $message->addParam($newname);
        }
        $reload     = true;

        /* Change database to be used */
        if (! $_error && $move) {
            $db = $newname;
        } elseif (! $_error) {
            if (isset($switch_to_new) && $switch_to_new == 'true') {
                $GLOBALS['PMA_Config']->setCookie('pma_switch_to_new', 'true');
                $db = $newname;
            } else {
                $GLOBALS['PMA_Config']->setCookie('pma_switch_to_new', '');
            }
        }

        if ($_error && ! isset($message)) {
            $message = PMA_Message::error();
        }
    }

    /**
     * Database has been successfully renamed/moved.  If in an Ajax request,
     * generate the output with {@link PMA_ajaxResponse} and exit
     */
    if ( $GLOBALS['is_ajax_request'] == true) {
        $extra_data['newname'] = $newname;
        $extra_data['sql_query'] = PMA_showMessage(null, $sql_query);
        PMA_ajaxResponse($message, $message->isSuccess(), $extra_data);
    };
}


/**
 * Settings for relations stuff
 */

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
    include './libraries/db_common.inc.php';
    $url_query .= '&amp;goto=db_operations.php';

    // Gets the database structure
    $sub_part = '_structure';
    include './libraries/db_info.inc.php';
    echo "\n";

    if (isset($message)) {
        PMA_showMessage($message, $sql_query);
        unset($message);
    }
}

$db_collation = PMA_getDbCollation($db);
$is_information_schema = PMA_is_system_schema($db);

if (!$is_information_schema) {
    if ($cfgRelation['commwork']) {
        /**
         * database comment
         */
        ?>
    <div class="operations_half_width">
    <form method="post" action="db_operations.php">
    <?php echo PMA_generate_common_hidden_inputs($db); ?>
    <fieldset>
        <legend>
        <?php
        if ($cfg['PropertiesIconic']) {
            echo '<img class="icon ic_b_comment" src="themes/dot.gif" alt="" />';
        }
        echo __('Database comment: ');
        ?>
        </legend>
        <input type="text" name="comment" class="textfield" size="30"
            value="<?php
            echo htmlspecialchars(PMA_getDBComment($db)); ?>" />
    </fieldset>
    <fieldset class="tblFooters">
        <input type="submit" value="<?php echo __('Go'); ?>" />
    </fieldset>
    </form>
    </div>
        <?php
    }
    ?>
    <div class="operations_half_width">
    <?php include './libraries/display_create_table.lib.php'; ?>
    </div>
    <?php
    /**
     * rename database
     */
if ($db != 'mysql') {
    ?>
        <div class="operations_half_width">
        <form id="rename_db_form" <?php echo ($GLOBALS['cfg']['AjaxEnable'] ? ' class="ajax" ' : ''); ?>method="post" action="db_operations.php"
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
        echo PMA_getImage('b_edit.png');
    }
    echo __('Rename database to') . ':';
    ?>
        </legend>
        <input id="new_db_name" type="text" name="newname" size="30" class="textfield" value="" />
    </fieldset>
    <fieldset class="tblFooters">
        <input id="rename_db_input" type="submit" value="<?php echo __('Go'); ?>" />
    </fieldset>
    </form>
    </div>
<?php
} // end if

// Drop link if allowed
// Don't even try to drop information_schema. You won't be able to. Believe me. You won't.
// Don't allow to easily drop mysql database, RFE #1327514.
if (($is_superuser || $GLOBALS['cfg']['AllowUserDropDatabase'])
        && !$db_is_information_schema
        && (PMA_DRIZZLE || $db != 'mysql')) {
?>
<div class="operations_half_width">
<fieldset class="caution">
 <legend><?php
if ($cfg['PropertiesIconic']) {
    echo PMA_getImage('b_deltbl.png');
}
echo __('Remove database');
?></legend>

<ul>
<?php
    $this_sql_query = 'DROP DATABASE ' . PMA_backquote($GLOBALS['db']);
    $this_url_params = array(
            'sql_query' => $this_sql_query,
            'back' => 'db_operations.php',
            'goto' => 'main.php',
            'reload' => '1',
            'purge' => '1',
            'message_to_show' => sprintf(__('Database %s has been dropped.'), htmlspecialchars(PMA_backquote($db))),
            'db' => null,
        );
    ?>
        <li><a href="sql.php<?php echo PMA_generate_common_url($this_url_params); ?>" <?php echo ($GLOBALS['cfg']['AjaxEnable'] ? 'id="drop_db_anchor"' : ''); ?>>
            <?php echo __('Drop the database (DROP)'); ?></a>
        <?php echo PMA_showMySQLDocu('SQL-Syntax', 'DROP_DATABASE'); ?>
    </li>
</ul>
</fieldset>
</div>
<?php } ?>
    <?php
    /**
     * Copy database
     */
    ?>
        <div class="operations_half_width clearfloat">
        <form id="copy_db_form" <?php echo ($GLOBALS['cfg']['AjaxEnable'] ? ' class="ajax" ' : ''); ?>method="post" action="db_operations.php"
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
        echo PMA_getImage('b_edit.png');
    }
    echo __('Copy database to') . ':';
    $drop_clause = 'DROP TABLE / DROP VIEW';
    ?>
        </legend>
        <input type="text" name="newname" size="30" class="textfield" value="" /><br />
<?php
        $choices = array(
            'structure' => __('Structure only'),
            'data'      => __('Structure and data'),
            'dataonly'  => __('Data only'));
        PMA_display_html_radio('what', $choices, 'data', true);
        unset($choices);
?>
        <input type="checkbox" name="create_database_before_copying" value="1"
            id="checkbox_create_database_before_copying"
            checked="checked" />
        <label for="checkbox_create_database_before_copying">
            <?php echo __('CREATE DATABASE before copying'); ?></label><br />
        <input type="checkbox" name="drop_if_exists" value="true"
            id="checkbox_drop" />
        <label for="checkbox_drop"><?php echo sprintf(__('Add %s'), $drop_clause); ?></label><br />
        <input type="checkbox" name="sql_auto_increment" value="1" checked="checked"
            id="checkbox_auto_increment" />
        <label for="checkbox_auto_increment">
            <?php echo __('Add AUTO_INCREMENT value'); ?></label><br />
        <input type="checkbox" name="add_constraints" value="1"
            id="checkbox_constraints" />
        <label for="checkbox_constraints">
            <?php echo __('Add constraints'); ?></label><br />
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
            />
        <label for="checkbox_switch"><?php echo __('Switch to copied database'); ?></label>
    </fieldset>
    <fieldset class="tblFooters">
        <input type="submit" name="submit_copy" value="<?php echo __('Go'); ?>" />
    </fieldset>
    </form>
    </div>
    <?php

    /**
     * Change database charset
     */
    echo '<div class="operations_half_width"><form id="change_db_charset_form" ';
    if ($GLOBALS['cfg']['AjaxEnable']) {
        echo ' class="ajax" ';
    }
    echo 'method="post" action="./db_operations.php">'
       . PMA_generate_common_hidden_inputs($db, $table)
       . '<fieldset>' . "\n"
       . '    <legend>';
    if ($cfg['PropertiesIconic']) {
        echo PMA_getImage('s_asci.png');
    }
    echo '    <label for="select_db_collation">' . __('Collation') . ':</label>' . "\n"
       . '    </legend>' . "\n"
       . PMA_generateCharsetDropdownBox(PMA_CSDROPDOWN_COLLATION,
            'db_collation', 'select_db_collation', $db_collation, false, 3)
       . '</fieldset>'
       . '<fieldset class="tblFooters">'
       . '    <input type="submit" name="submitcollation"'
       . ' value="' . __('Go') . '" />' . "\n"
       . '</fieldset>' . "\n"
       . '</form></div>' . "\n";

    if ($num_tables > 0
      && ! $cfgRelation['allworks'] && $cfg['PmaNoRelation_DisableWarning'] == false) {
        $message = PMA_Message::notice(__('The phpMyAdmin configuration storage has been deactivated. To find out why click %shere%s.'));
        $message->addParam('<a href="' . $cfg['PmaAbsoluteUri'] . 'chk_rel.php?' . $url_query . '">', false);
        $message->addParam('</a>', false);
        /* Show error if user has configured something, notice elsewhere */
        if (!empty($cfg['Servers'][$server]['pmadb'])) {
            $message->isError(true);
        }
        echo '<div class="operations_full_width">';
        $message->display();
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
          WHERE db_name = \'' . PMA_sqlAddSlashes($db) . '\'';
    $test_rs    = PMA_query_as_controluser($test_query, null, PMA_DBI_QUERY_STORE);

    /*
     * Export Relational Schema View
     */
    echo '<div class="operations_full_width"><fieldset><a href="schema_edit.php?' . $url_query . '">';
    if ($cfg['PropertiesIconic']) {
        echo PMA_getImage('b_edit.png');
    }
    echo __('Edit or export relational schema') . '</a></fieldset></div>';
} // end if

/**
 * Displays the footer
 */
require './libraries/footer.inc.php';
?>
