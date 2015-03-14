<?php

/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * set of functions with the operations section in pma
 *
 * @package PhpMyAdmin
 */

if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Get HTML output for database comment
 *
 * @param string $db database name
 *
 * @return string $html_output
 */
function PMA_getHtmlForDatabaseComment($db)
{
    $html_output = '<div class="operations_half_width">'
        . '<form method="post" action="db_operations.php" id="formDatabaseComment">'
        . PMA_URL_getHiddenInputs($db)
        . '<fieldset>'
        . '<legend>';
    if (PMA_Util::showIcons('ActionLinksMode')) {
        $html_output .= '<img class="icon ic_b_comment" '
            . 'src="themes/dot.gif" alt="" />';
    }
    $html_output .=  __('Database comment:');
    $html_output .= '</legend>';
    $html_output .= '<input type="text" name="comment" '
        . 'class="textfield" size="30"'
        . 'value="' . htmlspecialchars(PMA_getDBComment($db)) . '" />'
        . '</fieldset>';
    $html_output .= '<fieldset class="tblFooters">'
        . '<input type="submit" value="' . __('Go') . '" />'
        . '</fieldset>'
        . '</form>'
        . '</div>';

    return $html_output;
}

/**
 * Get HTML output for rename database
 *
 * @param string $db database name
 *
 * @return string $html_output
 */
function PMA_getHtmlForRenameDatabase($db)
{
    $html_output = '<div class="operations_half_width">'
        . '<form id="rename_db_form" '
        . 'class="ajax" '
        . 'method="post" action="db_operations.php" '
        . 'onsubmit="return emptyCheckTheField(this, \'newname\')">';
    if (isset($_REQUEST['db_collation'])) {
        $html_output .= '<input type="hidden" name="db_collation" '
            . 'value="' . $_REQUEST['db_collation']
            . '" />' . "\n";
    }
    $html_output .= '<input type="hidden" name="what" value="data" />'
        . '<input type="hidden" name="db_rename" value="true" />'
        . PMA_URL_getHiddenInputs($db)
        . '<fieldset>'
        . '<legend>';

    if (PMA_Util::showIcons('ActionLinksMode')) {
        $html_output .= PMA_Util::getImage('b_edit.png');
    }
    $html_output .= __('Rename database to:')
        . '</legend>';

    $html_output .= '<input id="new_db_name" type="text" name="newname" '
        . 'size="30" class="textfield" value="" required="required" />'
        . '</fieldset>'
        . '<fieldset class="tblFooters">'
        . '<input id="rename_db_input" type="submit" value="' . __('Go') . '" />'
        . '</fieldset>'
        . '</form>'
        . '</div>';

    return $html_output;
}

/**
 * Get HTML for database drop link
 *
 * @param string $db database name
 *
 * @return string $html_output
 */
function PMA_getHtmlForDropDatabaseLink($db)
{
    $this_sql_query = 'DROP DATABASE ' . PMA_Util::backquote($db);
    $this_url_params = array(
        'sql_query' => $this_sql_query,
        'back' => 'db_operations.php',
        'goto' => 'index.php',
        'reload' => '1',
        'purge' => '1',
        'message_to_show' => sprintf(
            __('Database %s has been dropped.'),
            htmlspecialchars(PMA_Util::backquote($db))
        ),
        'db' => null,
    );

    $html_output = '<div class="operations_half_width">'
        . '<fieldset class="caution">';
    $html_output .= '<legend>';
    if (PMA_Util::showIcons('ActionLinksMode')) {
        $html_output .= PMA_Util::getImage('b_deltbl.png');
    }
    $html_output .= __('Remove database')
        . '</legend>';
    $html_output .= '<ul>';
    $html_output .= PMA_getDeleteDataOrTablelink(
        $this_url_params,
        'DROP_DATABASE',
        __('Drop the database (DROP)'),
        'drop_db_anchor'
    );
    $html_output .= '</ul></fieldset>'
        . '</div>';

    return $html_output;
}

/**
 * Get HTML snippet for copy database
 *
 * @param string $db database name
 *
 * @return string $html_output
 */
function PMA_getHtmlForCopyDatabase($db)
{
    $drop_clause = 'DROP TABLE / DROP VIEW';
    $choices = array(
        'structure' => __('Structure only'),
        'data'      => __('Structure and data'),
        'dataonly'  => __('Data only')
    );

    if (isset($_COOKIE)
        && isset($_COOKIE['pma_switch_to_new'])
        && $_COOKIE['pma_switch_to_new'] == 'true'
    ) {
        $pma_switch_to_new = 'true';
    }

    $html_output = '<div class="operations_half_width clearfloat">';
    $html_output .= '<form id="copy_db_form" '
        . 'class="ajax" '
        . 'method="post" action="db_operations.php" '
        . 'onsubmit="return emptyCheckTheField(this, \'newname\')">';

    if (isset($_REQUEST['db_collation'])) {
        $html_output .= '<input type="hidden" name="db_collation" '
        . 'value="' . $_REQUEST['db_collation'] . '" />' . "\n";
    }
    $html_output .= '<input type="hidden" name="db_copy" value="true" />' . "\n"
        . PMA_URL_getHiddenInputs($db);
    $html_output .= '<fieldset>'
        . '<legend>';

    if (PMA_Util::showIcons('ActionLinksMode')) {
        $html_output .= PMA_Util::getImage('b_edit.png');
    }
    $html_output .= __('Copy database to:')
        . '</legend>'
        . '<input type="text" name="newname" size="30" '
        . 'class="textfield" value="" required="required" /><br />'
        . PMA_Util::getRadioFields(
            'what', $choices, 'data', true
        );
    $html_output .= '<input type="checkbox" name="create_database_before_copying" '
        . 'value="1" id="checkbox_create_database_before_copying"'
        . 'checked="checked" />';
    $html_output .= '<label for="checkbox_create_database_before_copying">'
        . __('CREATE DATABASE before copying') . '</label><br />';
    $html_output .= '<input type="checkbox" name="drop_if_exists" value="true"'
        . 'id="checkbox_drop" />';
    $html_output .= '<label for="checkbox_drop">'
        . sprintf(__('Add %s'), $drop_clause)
        . '</label><br />';
    $html_output .= '<input type="checkbox" name="sql_auto_increment" value="1" '
        . 'checked="checked" id="checkbox_auto_increment" />';
    $html_output .= '<label for="checkbox_auto_increment">'
        . __('Add AUTO_INCREMENT value') . '</label><br />';
    $html_output .= '<input type="checkbox" name="add_constraints" value="1"'
        . 'id="checkbox_constraints" />';
    $html_output .= '<label for="checkbox_constraints">'
        . __('Add constraints') . '</label><br />';
    $html_output .= '<input type="checkbox" name="switch_to_new" value="true"'
        . 'id="checkbox_switch"'
        . ((isset($pma_switch_to_new) && $pma_switch_to_new == 'true')
            ? ' checked="checked"'
            : '')
        . '/>';
    $html_output .= '<label for="checkbox_switch">'
        . __('Switch to copied database') . '</label>'
        . '</fieldset>';
    $html_output .= '<fieldset class="tblFooters">'
        . '<input type="submit" name="submit_copy" value="' . __('Go') . '" />'
        . '</fieldset>'
        . '</form>'
        . '</div>';

    return $html_output;
}

/**
 * Get HTML snippet for change database charset
 *
 * @param string $db    database name
 * @param string $table table name
 *
 * @return string $html_output
 */
function PMA_getHtmlForChangeDatabaseCharset($db, $table)
{
    $html_output = '<div class="operations_half_width">'
        . '<form id="change_db_charset_form" ';
    $html_output .= 'class="ajax" ';
    $html_output .= 'method="post" action="db_operations.php">';

    $html_output .= PMA_URL_getHiddenInputs($db, $table);

    $html_output .= '<fieldset>' . "\n"
       . '    <legend>';
    if (PMA_Util::showIcons('ActionLinksMode')) {
        $html_output .= PMA_Util::getImage('s_asci.png');
    }
    $html_output .= '<label for="select_db_collation">' . __('Collation')
        . ':</label>' . "\n"
        . '</legend>' . "\n"
        . PMA_generateCharsetDropdownBox(
            PMA_CSDROPDOWN_COLLATION,
            'db_collation',
            'select_db_collation',
            isset($_REQUEST['db_collation']) ? $_REQUEST['db_collation'] : '',
            false
        )
        . '</fieldset>'
        . '<fieldset class="tblFooters">'
        . '<input type="submit" name="submitcollation"'
        . ' value="' . __('Go') . '" />' . "\n"
        . '</fieldset>' . "\n"
        . '</form></div>' . "\n";

    return $html_output;
}

/**
 * Run the Procedure definitions and function definitions
 *
 * to avoid selecting alternatively the current and new db
 * we would need to modify the CREATE definitions to qualify
 * the db name
 *
 * @param string $db database name
 *
 * @return void
 */
function PMA_runProcedureAndFunctionDefinitions($db)
{
    $procedure_names = $GLOBALS['dbi']->getProceduresOrFunctions($db, 'PROCEDURE');
    if ($procedure_names) {
        foreach ($procedure_names as $procedure_name) {
            $GLOBALS['dbi']->selectDb($db);
            $tmp_query = $GLOBALS['dbi']->getDefinition(
                $db, 'PROCEDURE', $procedure_name
            );
            // collect for later display
            $GLOBALS['sql_query'] .= "\n" . $tmp_query;
            $GLOBALS['dbi']->selectDb($_REQUEST['newname']);
            $GLOBALS['dbi']->query($tmp_query);
        }
    }

    $function_names = $GLOBALS['dbi']->getProceduresOrFunctions($db, 'FUNCTION');
    if ($function_names) {
        foreach ($function_names as $function_name) {
            $GLOBALS['dbi']->selectDb($db);
            $tmp_query = $GLOBALS['dbi']->getDefinition(
                $db, 'FUNCTION', $function_name
            );
            // collect for later display
            $GLOBALS['sql_query'] .= "\n" . $tmp_query;
            $GLOBALS['dbi']->selectDb($_REQUEST['newname']);
            $GLOBALS['dbi']->query($tmp_query);
        }
    }
}

/**
 * Get sql query and create database before copy
 *
 * @return string $sql_query
 */
function PMA_getSqlQueryAndCreateDbBeforeCopy()
{
    // lower_case_table_names=1 `DB` becomes `db`
    if (! PMA_DRIZZLE) {
        $lowerCaseTableNames = $GLOBALS['dbi']->fetchValue(
            'SHOW VARIABLES LIKE "lower_case_table_names"', 0, 1
        );
        if ($lowerCaseTableNames === '1') {
            $_REQUEST['newname'] = /*overload*/mb_strtolower(
                $_REQUEST['newname']
            );
        }
    }

    $local_query = 'CREATE DATABASE IF NOT EXISTS '
        . PMA_Util::backquote($_REQUEST['newname']);
    if (isset($_REQUEST['db_collation'])) {
        $local_query .= ' DEFAULT'
            . PMA_generateCharsetQueryPart($_REQUEST['db_collation']);
    }
    $local_query .= ';';
    $sql_query = $local_query;
    // save the original db name because Tracker.class.php which
    // may be called under $GLOBALS['dbi']->query() changes $GLOBALS['db']
    // for some statements, one of which being CREATE DATABASE
    $original_db = $GLOBALS['db'];
    $GLOBALS['dbi']->query($local_query);
    $GLOBALS['db'] = $original_db;

    // Set the SQL mode to NO_AUTO_VALUE_ON_ZERO to prevent MySQL from creating
    // export statements it cannot import
    $sql_set_mode = "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO'";
    $GLOBALS['dbi']->query($sql_set_mode);

    // rebuild the database list because PMA_Table::moveCopy
    // checks in this list if the target db exists
    $GLOBALS['pma']->databases->build();

    return $sql_query;
}

/**
 * remove all foreign key constraints and return
 * sql constraints query for full database
 *
 * @param array     $tables_full       array of all tables in given db or dbs
 * @param ExportSql $export_sql_plugin export plugin instance
 * @param boolean   $move              whether database name is empty or not
 * @param string    $db                database name
 *
 * @return string sql constraints query for full databases
 */
function PMA_getSqlConstraintsQueryForFullDb(
    $tables_full, $export_sql_plugin, $move, $db
) {
    global $sql_constraints, $sql_drop_foreign_keys;
    $sql_constraints_query_full_db = array();
    foreach ($tables_full as $each_table => $tmp) {
        /* Following globals are set in getTableDef */
        $sql_constraints = '';
        $sql_drop_foreign_keys = '';
        $export_sql_plugin->getTableDef(
            $db, $each_table, "\n", '', false, false, false, false
        );
        if ($move && ! empty($sql_drop_foreign_keys)) {
            $GLOBALS['dbi']->query($sql_drop_foreign_keys);
        }
        // keep the constraint we just dropped
        if (! empty($sql_constraints)) {
            $sql_constraints_query_full_db[] = $sql_constraints;
        }
    }
    return $sql_constraints_query_full_db;
}

/**
 * Get views as an array and create SQL view stand-in
 *
 * @param array     $tables_full       array of all tables in given db or dbs
 * @param ExportSql $export_sql_plugin export plugin instance
 * @param string    $db                database name
 *
 * @return array $views
 */
function PMA_getViewsAndCreateSqlViewStandIn(
    $tables_full, $export_sql_plugin, $db
) {
    $views = array();
    foreach ($tables_full as $each_table => $tmp) {
        // to be able to rename a db containing views,
        // first all the views are collected and a stand-in is created
        // the real views are created after the tables
        if (PMA_Table::isView($db, $each_table)) {

            // If view exists, and 'add drop view' is selected: Drop it!
            if ($_REQUEST['what'] != 'nocopy'
                && isset($_REQUEST['drop_if_exists'])
                && $_REQUEST['drop_if_exists'] == 'true'
            ) {
                $drop_query = 'DROP VIEW IF EXISTS '
                    . PMA_Util::backquote($_REQUEST['newname']) . '.'
                    . PMA_Util::backquote($each_table);
                $GLOBALS['dbi']->query($drop_query);

                $GLOBALS['sql_query'] .= "\n" . $drop_query . ';';
            }

            $views[] = $each_table;
            // Create stand-in definition to resolve view dependencies
            $sql_view_standin = $export_sql_plugin->getTableDefStandIn(
                $db, $each_table, "\n"
            );
            $GLOBALS['dbi']->selectDb($_REQUEST['newname']);
            $GLOBALS['dbi']->query($sql_view_standin);
            $GLOBALS['sql_query'] .= "\n" . $sql_view_standin;
        }
    }
    return $views;
}

/**
 * Get sql query for copy/rename table and boolean for whether copy/rename or not
 *
 * @param array   $tables_full array of all tables in given db or dbs
 * @param string  $sql_query   sql query for all operations
 * @param boolean $move        whether database name is empty or not
 * @param string  $db          database name
 *
 * @return array ($sql_query, $error)
 */
function PMA_getSqlQueryForCopyTable($tables_full, $sql_query, $move, $db)
{
    $error = false;
    foreach ($tables_full as $each_table => $tmp) {
        // skip the views; we have created stand-in definitions
        if (PMA_Table::isView($db, $each_table)) {
            continue;
        }
        $back = $sql_query;
        $sql_query = '';

        // value of $what for this table only
        $this_what = $_REQUEST['what'];

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
            $triggers = $GLOBALS['dbi']->getTriggers($db, $each_table, '');

            if (! PMA_Table::moveCopy(
                $db, $each_table, $_REQUEST['newname'], $each_table,
                (isset($this_what) ? $this_what : 'data'),
                $move, 'db_copy'
            )) {
                $error = true;
                // $sql_query is filled by PMA_Table::moveCopy()
                $sql_query = $back . $sql_query;
                break;
            }
            // apply the triggers to the destination db+table
            if ($triggers) {
                $GLOBALS['dbi']->selectDb($_REQUEST['newname']);
                foreach ($triggers as $trigger) {
                    $GLOBALS['dbi']->query($trigger['create']);
                    $GLOBALS['sql_query'] .= "\n" . $trigger['create'] . ';';
                }
            }

            // this does not apply to a rename operation
            if (isset($_REQUEST['add_constraints'])
                && ! empty($GLOBALS['sql_constraints_query'])
            ) {
                $GLOBALS['sql_constraints_query_full_db'][]
                    = $GLOBALS['sql_constraints_query'];
                unset($GLOBALS['sql_constraints_query']);
            }
        }
        // $sql_query is filled by PMA_Table::moveCopy()
        $sql_query = $back . $sql_query;
    }
    return array($sql_query, $error);
}

/**
 * Run the EVENT definition for selected database
 *
 * to avoid selecting alternatively the current and new db
 * we would need to modify the CREATE definitions to qualify
 * the db name
 *
 * @param string $db database name
 *
 * @return void
 */
function PMA_runEventDefinitionsForDb($db)
{
    $event_names = $GLOBALS['dbi']->fetchResult(
        'SELECT EVENT_NAME FROM information_schema.EVENTS WHERE EVENT_SCHEMA= \''
        . PMA_Util::sqlAddSlashes($db, true) . '\';'
    );
    if ($event_names) {
        foreach ($event_names as $event_name) {
            $GLOBALS['dbi']->selectDb($db);
            $tmp_query = $GLOBALS['dbi']->getDefinition($db, 'EVENT', $event_name);
            // collect for later display
            $GLOBALS['sql_query'] .= "\n" . $tmp_query;
            $GLOBALS['dbi']->selectDb($_REQUEST['newname']);
            $GLOBALS['dbi']->query($tmp_query);
        }
    }
}

/**
 * Handle the views, return the boolean value whether table rename/copy or not
 *
 * @param array   $views views as an array
 * @param boolean $move  whether database name is empty or not
 * @param string  $db    database name
 *
 * @return boolean $_error whether table rename/copy or not
 */
function PMA_handleTheViews($views, $move, $db)
{
    $_error = false;
    // temporarily force to add DROP IF EXIST to CREATE VIEW query,
    // to remove stand-in VIEW that was created earlier
    // ( $_REQUEST['drop_if_exists'] is used in moveCopy() )
    if (isset($_REQUEST['drop_if_exists'])) {
        $temp_drop_if_exists = $_REQUEST['drop_if_exists'];
    }
    $_REQUEST['drop_if_exists'] = 'true';

    foreach ($views as $view) {
        $copying_succeeded = PMA_Table::moveCopy(
            $db, $view, $_REQUEST['newname'], $view, 'structure', $move, 'db_copy'
        );
        if (! $copying_succeeded) {
            $_error = true;
            break;
        }
    }
    unset($_REQUEST['drop_if_exists']);
    if (isset($temp_drop_if_exists)) {
        // restore previous value
        $_REQUEST['drop_if_exists'] = $temp_drop_if_exists;
    }
    return $_error;
}

/**
 * Create all accumulated constraints
 *
 * @return void
 */
function PMA_createAllAccumulatedConstraints()
{
    $GLOBALS['dbi']->selectDb($_REQUEST['newname']);
    foreach ($GLOBALS['sql_constraints_query_full_db'] as $one_query) {
        $GLOBALS['dbi']->query($one_query);
        // and prepare to display them
        $GLOBALS['sql_query'] .= "\n" . $one_query;
    }
    unset($GLOBALS['sql_constraints_query_full_db']);
}

/**
 * Duplicate the bookmarks for the db (done once for each db)
 *
 * @param boolean $_error whether table rename/copy or not
 * @param string  $db     database name
 *
 * @return void
 */
function PMA_duplicateBookmarks($_error, $db)
{
    if (! $_error && $db != $_REQUEST['newname']) {
        $get_fields = array('user', 'label', 'query');
        $where_fields = array('dbase' => $db);
        $new_fields = array('dbase' => $_REQUEST['newname']);
        PMA_Table::duplicateInfo(
            'bookmarkwork', 'bookmark', $get_fields,
            $where_fields, $new_fields
        );
    }
}

/**
 * Get the HTML snippet for order the table
 *
 * @param array $columns columns array
 *
 * @return string $html_out
 */
function PMA_getHtmlForOrderTheTable($columns)
{
    $html_output = '<div class="operations_half_width">';
    $html_output .= '<form method="post" id="alterTableOrderby" '
        . 'action="tbl_operations.php">';
    $html_output .= PMA_URL_getHiddenInputs(
        $GLOBALS['db'], $GLOBALS['table']
    );
    $html_output .= '<fieldset id="fieldset_table_order">'
        . '<legend>' . __('Alter table order by') . '</legend>'
        . '<select name="order_field">';

    foreach ($columns as $fieldname) {
        $html_output .= '<option '
            . 'value="' . htmlspecialchars($fieldname['Field']) . '">'
            . htmlspecialchars($fieldname['Field']) . '</option>' . "\n";
    }
    $html_output .= '</select> ' . __('(singly)') . ' '
        . '<br />'
        . '<input id="order_order_asc" name="order_order"'
        . ' type="radio" value="asc" checked="checked" />'
        . '<label for="order_order_asc">' . __('Ascending') . '</label>'
        . '<input id="order_order_desc" name="order_order"'
        . ' type="radio" value="desc" />'
        . '<label for="order_order_desc">' . __('Descending') . '</label>'
        . '</fieldset>'
        . '<fieldset class="tblFooters">'
        . '<input type="hidden" name="submitorderby" value="1" />'
        . '<input type="submit" value="' . __('Go') . '" />'
        . '</fieldset>'
        . '</form>'
        . '</div>';

     return $html_output;
}

/**
 * Get the HTML snippet for move table
 *
 * @return string $html_output
 */
function PMA_getHtmlForMoveTable()
{
    $html_output = '<div class="operations_half_width">';
    $html_output .= '<form method="post" action="tbl_operations.php"'
        . ' id="moveTableForm" class="ajax"'
        . ' onsubmit="return emptyCheckTheField(this, \'new_name\')">'
        . PMA_URL_getHiddenInputs($GLOBALS['db'], $GLOBALS['table']);

    $html_output .= '<input type="hidden" name="reload" value="1" />'
        . '<input type="hidden" name="what" value="data" />'
        . '<fieldset id="fieldset_table_rename">';

    $html_output .= '<legend>' . __('Move table to (database<b>.</b>table):')
        . '</legend>';

    if (count($GLOBALS['pma']->databases) > $GLOBALS['cfg']['MaxDbList']) {
        $html_output .= '<input type="text" maxlength="100" size="30" '
            . 'name="target_db" value="' . htmlspecialchars($GLOBALS['db'])
            . '"/>';
    } else {
        $html_output .= '<select class="halfWidth" name="target_db">'
            . $GLOBALS['pma']->databases->getHtmlOptions(true, false)
            . '</select>';
    }
    $html_output .= '&nbsp;<strong>.</strong>&nbsp;';
    $html_output .= '<input class="halfWidth" type="text" size="20" name="new_name"'
        . ' required="required" '
        . 'value="' . htmlspecialchars($GLOBALS['table']) . '" /><br />';

    // starting with MySQL 5.0.24, SHOW CREATE TABLE includes the AUTO_INCREMENT
    // next value but users can decide if they want it or not for the operation

    $html_output .= '<input type="checkbox" name="sql_auto_increment" '
        . 'value="1" id="checkbox_auto_increment_mv" checked="checked" />'
        . '<label for="checkbox_auto_increment_mv">'
        . __('Add AUTO_INCREMENT value')
        . '</label><br />'
        . '</fieldset>';

    $html_output .= '<fieldset class="tblFooters">'
        . '<input type="submit" name="submit_move" value="' . __('Go') . '" />'
        . '</fieldset>'
        . '</form>'
        . '</div>';

    return $html_output;
}

/**
 * Get the HTML div for Table option
 *
 * @param string  $comment            Comment
 * @param array   $tbl_collation      table collation
 * @param string  $tbl_storage_engine table storage engine
 * @param boolean $is_myisam_or_aria  whether MYISAM | ARIA or not
 * @param boolean $is_isam            whether ISAM or not
 * @param array   $pack_keys          pack keys
 * @param string  $auto_increment     value of auto increment
 * @param string  $delay_key_write    delay key write
 * @param string  $transactional      value of transactional
 * @param string  $page_checksum      value of page checksum
 * @param boolean $is_innodb          whether INNODB or not
 * @param boolean $is_pbxt            whether PBXT or not
 * @param boolean $is_aria            whether ARIA or not
 * @param string  $checksum           the checksum
 *
 * @return string $html_output
 */
function PMA_getTableOptionDiv($comment, $tbl_collation, $tbl_storage_engine,
    $is_myisam_or_aria, $is_isam, $pack_keys, $auto_increment, $delay_key_write,
    $transactional, $page_checksum, $is_innodb, $is_pbxt, $is_aria, $checksum
) {
    $html_output = '<div class="operations_half_width clearfloat">';
    $html_output .= '<form method="post" action="tbl_operations.php"';
    $html_output .= ' id="tableOptionsForm" class="ajax">';
    $html_output .= PMA_URL_getHiddenInputs(
        $GLOBALS['db'], $GLOBALS['table']
    );
    $html_output .= '<input type="hidden" name="reload" value="1" />';

    $html_output .= PMA_getTableOptionFieldset(
        $comment, $tbl_collation,
        $tbl_storage_engine, $is_myisam_or_aria, $is_isam, $pack_keys,
        $delay_key_write, $auto_increment, $transactional, $page_checksum,
        $is_innodb, $is_pbxt, $is_aria, $checksum
    );

    $html_output .= '<fieldset class="tblFooters">'
        . '<input type="hidden" name="submitoptions" value="1" />'
        . '<input type="submit" value="' . __('Go') . '" />'
        . '</fieldset>'
        . '</form>'
        . '</div>';

    return $html_output;
}

/**
 * Get HTML fieldset for Table option, it contains HTML table for options
 *
 * @param string  $comment            Comment
 * @param array   $tbl_collation      table collation
 * @param string  $tbl_storage_engine table storage engine
 * @param boolean $is_myisam_or_aria  whether MYISAM | ARIA or not
 * @param boolean $is_isam            whether ISAM or not
 * @param array   $pack_keys          pack keys
 * @param string  $delay_key_write    delay key write
 * @param string  $auto_increment     value of auto increment
 * @param string  $transactional      value of transactional
 * @param string  $page_checksum      value of page checksum
 * @param boolean $is_innodb          whether INNODB or not
 * @param boolean $is_pbxt            whether PBXT or not
 * @param boolean $is_aria            whether ARIA or not
 * @param string  $checksum           the checksum
 *
 * @return string $html_output
 */
function PMA_getTableOptionFieldset($comment, $tbl_collation,
    $tbl_storage_engine, $is_myisam_or_aria, $is_isam, $pack_keys,
    $delay_key_write, $auto_increment, $transactional,
    $page_checksum, $is_innodb, $is_pbxt, $is_aria, $checksum
) {
    $html_output = '<fieldset>'
        . '<legend>' . __('Table options') . '</legend>';

    $html_output .= '<table>';
    //Change table name
    $html_output .= '<tr><td>' . __('Rename table to') . '</td>'
        . '<td>'
        . '<input type="text" size="20" name="new_name" '
        . 'value="' . htmlspecialchars($GLOBALS['table'])
        . '" required="required" />'
        . '</td>'
        . '</tr>';

    //Table comments
    $html_output .= '<tr><td>' . __('Table comments') . '</td>'
        . '<td><input type="text" name="comment" maxlength="60" size="30"'
        . 'value="' . htmlspecialchars($comment) . '" />'
        . '<input type="hidden" name="prev_comment" value="'
        . htmlspecialchars($comment) . '" />'
        . '</td>'
        . '</tr>';

    //Storage engine
    $html_output .= '<tr><td>' . __('Storage Engine')
        . PMA_Util::showMySQLDocu('Storage_engines')
        . '</td>'
        . '<td>'
        . PMA_StorageEngine::getHtmlSelect(
            'new_tbl_storage_engine', null, $tbl_storage_engine
        )
        . '</td>'
        . '</tr>';

    //Table character set
    $html_output .= '<tr><td>' . __('Collation') . '</td>'
        . '<td>'
        . PMA_generateCharsetDropdownBox(
            PMA_CSDROPDOWN_COLLATION,
            'tbl_collation', null, $tbl_collation, false
        )
        . '</td>'
        . '</tr>';

    if ($is_myisam_or_aria || $is_isam) {
        $html_output .= '<tr>'
            . '<td><label for="new_pack_keys">PACK_KEYS</label></td>'
            . '<td><select name="new_pack_keys" id="new_pack_keys">';

        $html_output .= '<option value="DEFAULT"';
        if ($pack_keys == 'DEFAULT') {
            $html_output .= 'selected="selected"';
        }
        $html_output .= '>DEFAULT</option>
                <option value="0"';
        if ($pack_keys == '0') {
            $html_output .= 'selected="selected"';
        }
        $html_output .= '>0</option>
                <option value="1" ';
        if ($pack_keys == '1') {
            $html_output .= 'selected="selected"';
        }
        $html_output .= '>1</option>'
            . '</select>'
            . '</td>'
            . '</tr>';
    } // end if (MYISAM|ISAM)

    if ($is_myisam_or_aria) {
        $html_output .= PMA_getHtmlForTableRow(
            'new_checksum',
            'CHECKSUM',
            $checksum
        );

        $html_output .= PMA_getHtmlForTableRow(
            'new_delay_key_write',
            'DELAY_KEY_WRITE',
            $delay_key_write
        );
    } // end if (MYISAM)

    if ($is_aria) {
        $html_output .= PMA_getHtmlForTableRow(
            'new_transactional',
            'TRANSACTIONAL',
            $transactional
        );

        $html_output .= PMA_getHtmlForTableRow(
            'new_page_checksum',
            'PAGE_CHECKSUM',
            $page_checksum
        );
    } // end if (ARIA)

    if (/*overload*/mb_strlen($auto_increment) > 0
        && ($is_myisam_or_aria || $is_innodb || $is_pbxt)
    ) {
        $html_output .= '<tr><td>'
            . '<label for="auto_increment_opt">AUTO_INCREMENT</label></td>'
            . '<td><input type="number" name="new_auto_increment" '
            . 'id="auto_increment_opt"'
            . 'value="' . $auto_increment . '" /></td>'
            . '</tr> ';
    } // end if (MYISAM|INNODB)

    $possible_row_formats = PMA_getPossibleRowFormat();

    // for MYISAM there is also COMPRESSED but it can be set only by the
    // myisampack utility, so don't offer here the choice because if we
    // try it inside an ALTER TABLE, MySQL (at least in 5.1.23-maria)
    // does not return a warning
    // (if the table was compressed, it can be seen on the Structure page)

    if (isset($possible_row_formats[$tbl_storage_engine])) {
        $current_row_format
            = /*overload*/mb_strtoupper($GLOBALS['showtable']['Row_format']);
        $html_output .= '<tr><td>'
            . '<label for="new_row_format">ROW_FORMAT</label></td>'
            . '<td>';
        $html_output .= PMA_Util::getDropdown(
            'new_row_format', $possible_row_formats[$tbl_storage_engine],
            $current_row_format, 'new_row_format'
        );
        $html_output .= '</td></tr>';
    }
    $html_output .= '</table>'
        . '</fieldset>';

    return $html_output;
}

/**
 * Get the common HTML table row (tr) for new_checksum, new_delay_key_write,
 * new_transactional and new_page_checksum
 *
 * @param string $attribute class, name and id attribute
 * @param string $label     label value
 * @param string $val       checksum, delay_key_write, transactional, page_checksum
 *
 * @return string $html_output
 */
function PMA_getHtmlForTableRow($attribute, $label, $val)
{
    return '<tr>'
        . '<td><label for="' . $attribute . '">' . $label . '</label></td>'
        . '<td><input type="checkbox" name="' . $attribute . '"'
        . ' id="' . $attribute . '"'
        . ' value="1"'
        . ((!empty($val) && $val == 1) ? ' checked="checked"' : '') . '/></td>'
        . '</tr>';
}

/**
 * Get array of possible row formats
 *
 * @return array $possible_row_formats
 */
function PMA_getPossibleRowFormat()
{
    // the outer array is for engines, the inner array contains the dropdown
    // option values as keys then the dropdown option labels

    $possible_row_formats = array(
        'ARIA'  => array(
            'FIXED'     => 'FIXED',
            'DYNAMIC'   => 'DYNAMIC',
            'PAGE'      => 'PAGE'
        ),
        'MARIA'  => array(
            'FIXED'     => 'FIXED',
            'DYNAMIC'   => 'DYNAMIC',
            'PAGE'      => 'PAGE'
        ),
        'MYISAM' => array(
             'FIXED'    => 'FIXED',
             'DYNAMIC'  => 'DYNAMIC'
        ),
        'PBXT'   => array(
             'FIXED'    => 'FIXED',
             'DYNAMIC'  => 'DYNAMIC'
        ),
        'INNODB' => array(
             'COMPACT'  => 'COMPACT',
             'REDUNDANT' => 'REDUNDANT'
        )
    );

    $innodbEnginePlugin = PMA_StorageEngine::getEngine('innodb');
    $innodbPluginVersion = $innodbEnginePlugin->getInnodbPluginVersion();
    if (!empty($innodbPluginVersion)) {
        $innodb_file_format = $innodbEnginePlugin->getInnodbFileFormat();
    } else {
        $innodb_file_format = '';
    }
    if ('Barracuda' == $innodb_file_format
        && $innodbEnginePlugin->supportsFilePerTable()
    ) {
        $possible_row_formats['INNODB']['DYNAMIC'] = 'DYNAMIC';
        $possible_row_formats['INNODB']['COMPRESSED'] = 'COMPRESSED';
    }

    return $possible_row_formats;
}

/**
 * Get HTML div for copy table
 *
 * @return string $html_output
 */
function PMA_getHtmlForCopytable()
{
    $html_output = '<div class="operations_half_width">';
    $html_output .= '<form method="post" action="tbl_operations.php" '
        . 'name="copyTable" '
        . 'id="copyTable" '
        . ' class="ajax" '
        . 'onsubmit="return emptyCheckTheField(this, \'new_name\')">'
        . PMA_URL_getHiddenInputs($GLOBALS['db'], $GLOBALS['table'])
        . '<input type="hidden" name="reload" value="1" />';

    $html_output .= '<fieldset>';
    $html_output .= '<legend>'
        . __('Copy table to (database<b>.</b>table):') . '</legend>';

    if (count($GLOBALS['pma']->databases) > $GLOBALS['cfg']['MaxDbList']) {
        $html_output .= '<input class="halfWidth" type="text" maxlength="100" '
            . 'size="30" name="target_db" '
            . 'value="' . htmlspecialchars($GLOBALS['db']) . '"/>';
    } else {
        $html_output .= '<select class="halfWidth" name="target_db">'
            . $GLOBALS['pma']->databases->getHtmlOptions(true, false)
            . '</select>';
    }
    $html_output .= '&nbsp;<strong>.</strong>&nbsp;';
    $html_output .= '<input class="halfWidth" type="text" required="required" '
        . 'size="20" name="new_name" '
        . 'value="' . htmlspecialchars($GLOBALS['table']) . '"/><br />';

    $choices = array(
            'structure' => __('Structure only'),
            'data'      => __('Structure and data'),
            'dataonly'  => __('Data only'));

    $html_output .= PMA_Util::getRadioFields(
        'what', $choices, 'data', true
    );

    $html_output .= '<input type="checkbox" name="drop_if_exists" '
        . 'value="true" id="checkbox_drop" />'
        . '<label for="checkbox_drop">'
        . sprintf(__('Add %s'), 'DROP TABLE') . '</label><br />'
        . '<input type="checkbox" name="sql_auto_increment" '
        . 'value="1" id="checkbox_auto_increment_cp" />'
        . '<label for="checkbox_auto_increment_cp">'
        . __('Add AUTO_INCREMENT value') . '</label><br />';

    // display "Add constraints" choice only if there are
    // foreign keys
    if (PMA_getForeigners($GLOBALS['db'], $GLOBALS['table'], '', 'foreign')) {
        $html_output .= '<input type="checkbox" name="add_constraints" '
            . 'value="1" id="checkbox_constraints" />';
        $html_output .= '<label for="checkbox_constraints">'
            . __('Add constraints') . '</label><br />';
    } // endif

    if (isset($_COOKIE['pma_switch_to_new'])
        && $_COOKIE['pma_switch_to_new'] == 'true'
    ) {
        $pma_switch_to_new = 'true';
    }

    $html_output .= '<input type="checkbox" name="switch_to_new" value="true"'
        . 'id="checkbox_switch"'
        . ((isset($pma_switch_to_new) && $pma_switch_to_new == 'true')
            ? ' checked="checked"'
            : '' . '/>');
    $html_output .= '<label for="checkbox_switch">'
        . __('Switch to copied table') . '</label>'
        . '</fieldset>';

    $html_output .= '<fieldset class="tblFooters">'
        . '<input type="submit" name="submit_copy" value="' . __('Go') . '" />'
        . '</fieldset>'
        . '</form>'
        . '</div>';

    return $html_output;
}

/**
 * Get HTML snippet for table maintenance
 *
 * @param boolean $is_myisam_or_aria whether MYISAM | ARIA or not
 * @param boolean $is_innodb         whether innodb or not
 * @param boolean $is_berkeleydb     whether  berkeleydb or not
 * @param array   $url_params        array of URL parameters
 *
 * @return string $html_output
 */
function PMA_getHtmlForTableMaintenance(
    $is_myisam_or_aria, $is_innodb, $is_berkeleydb, $url_params
) {
    $html_output = '<div class="operations_half_width">';
    $html_output .= '<fieldset>'
        . '<legend>' . __('Table maintenance') . '</legend>';
    $html_output .= '<ul id="tbl_maintenance">';

    // Note: BERKELEY (BDB) is no longer supported, starting with MySQL 5.1
    $html_output .= PMA_getListofMaintainActionLink(
        $is_myisam_or_aria, $is_innodb, $url_params, $is_berkeleydb
    );

    $html_output .= '</ul>'
        . '</fieldset>'
        . '</div>';

    return $html_output;
}

/**
 * Get HTML 'li' having a link of maintain action
 *
 * @param boolean $is_myisam_or_aria whether MYISAM | ARIA or not
 * @param boolean $is_innodb         whether innodb or not
 * @param array   $url_params        array of URL parameters
 * @param boolean $is_berkeleydb     whether  berkeleydb or not
 *
 * @return string $html_output
 */
function PMA_getListofMaintainActionLink($is_myisam_or_aria,
    $is_innodb, $url_params, $is_berkeleydb
) {
    $html_output = '';

    if ($is_myisam_or_aria || $is_innodb || $is_berkeleydb) {
        if ($is_myisam_or_aria || $is_innodb) {
            $params = array(
                'sql_query' => 'CHECK TABLE '
                    . PMA_Util::backquote($GLOBALS['table']),
                'table_maintenance' => 'Go',
            );
            $html_output .= PMA_getMaintainActionlink(
                __('Check table'),
                $params,
                $url_params,
                'CHECK_TABLE'
            );
        }
        if ($is_innodb) {
            $params = array(
                'sql_query' => 'ALTER TABLE '
                . PMA_Util::backquote($GLOBALS['table'])
                . ' ENGINE = InnoDB;'
            );
            $html_output .= PMA_getMaintainActionlink(
                __('Defragment table'),
                $params,
                $url_params,
                'InnoDB_File_Defragmenting'
            );
        }
        if ($is_innodb || $is_myisam_or_aria || $is_berkeleydb) {
            $params = array(
                'sql_query' => 'ANALYZE TABLE '
                    . PMA_Util::backquote($GLOBALS['table']),
                'table_maintenance' => 'Go',
            );
            $html_output .= PMA_getMaintainActionlink(
                __('Analyze table'),
                $params,
                $url_params,
                'ANALYZE_TABLE'
            );
        }
        if ($is_myisam_or_aria && !PMA_DRIZZLE) {
            $params = array(
                'sql_query' => 'REPAIR TABLE '
                    . PMA_Util::backquote($GLOBALS['table']),
                'table_maintenance' => 'Go',
            );
            $html_output .= PMA_getMaintainActionlink(
                __('Repair table'),
                $params,
                $url_params,
                'REPAIR_TABLE'
            );
        }
        if (($is_myisam_or_aria || $is_innodb || $is_berkeleydb)
            && !PMA_DRIZZLE
        ) {
            $params = array(
                'sql_query' => 'OPTIMIZE TABLE '
                    . PMA_Util::backquote($GLOBALS['table']),
                'table_maintenance' => 'Go',
            );
            $html_output .= PMA_getMaintainActionlink(
                __('Optimize table'),
                $params,
                $url_params,
                'OPTIMIZE_TABLE'
            );
        }
    } // end MYISAM or BERKELEYDB case

    $params = array(
        'sql_query' => 'FLUSH TABLE '
            . PMA_Util::backquote($GLOBALS['table']),
        'message_to_show' => sprintf(
            __('Table %s has been flushed.'),
            htmlspecialchars($GLOBALS['table'])
        ),
        'reload' => 1,
    );

    $html_output .= PMA_getMaintainActionlink(
        __('Flush the table (FLUSH)'),
        $params,
        $url_params,
        'FLUSH'
    );

    return $html_output;
}

/**
 * Get maintain action HTML link
 *
 * @param string $action_message action message
 * @param array  $params         url parameters array
 * @param array  $url_params     additional url parameters
 * @param string $link           contains name of page/anchor that is being linked
 *
 * @return string $html_output
 */
function PMA_getMaintainActionlink($action_message, $params, $url_params, $link)
{
    return '<li>'
        . '<a class="maintain_action ajax" '
        . 'href="sql.php'
        . PMA_URL_getCommon(array_merge($url_params, $params)) . '">'
        . $action_message
        . '</a>'
        . PMA_Util::showMySQLDocu($link)
        . '</li>';
}

/**
 * Get HTML for Delete data or table (truncate table, drop table)
 *
 * @param array $truncate_table_url_params url parameter array for truncate table
 * @param array $dropTableUrlParams        url parameter array for drop table
 *
 * @return string $html_output
 */
function PMA_getHtmlForDeleteDataOrTable(
    $truncate_table_url_params,
    $dropTableUrlParams
) {
    $html_output = '<div class="operations_half_width">'
        . '<fieldset class="caution">'
        . '<legend>' . __('Delete data or table') . '</legend>';

    $html_output .= '<ul>';

    if (! empty($truncate_table_url_params)) {
        $html_output .= PMA_getDeleteDataOrTablelink(
            $truncate_table_url_params,
            'TRUNCATE_TABLE',
            __('Empty the table (TRUNCATE)'),
            'truncate_tbl_anchor'
        );
    }
    if (!empty ($dropTableUrlParams)) {
        $html_output .= PMA_getDeleteDataOrTablelink(
            $dropTableUrlParams,
            'DROP_TABLE',
            __('Delete the table (DROP)'),
            'drop_tbl_anchor'
        );
    }
    $html_output .= '</ul></fieldset></div>';

    return $html_output;
}

/**
 * Get the HTML link for Truncate table, Drop table and Drop db
 *
 * @param array  $url_params url parameter array for delete data or table
 * @param string $syntax     TRUNCATE_TABLE or DROP_TABLE or DROP_DATABASE
 * @param string $link       link to be shown
 * @param string $htmlId     id of the link
 *
 * @return String html output
 */
function PMA_getDeleteDataOrTablelink($url_params, $syntax, $link, $htmlId)
{
    return  '<li><a '
        . 'href="sql.php' . PMA_URL_getCommon($url_params) . '"'
        . ' id="' . $htmlId . '" class="ajax">'
        . $link . '</a>'
        . PMA_Util::showMySQLDocu($syntax)
        . '</li>';
}

/**
 * Get HTML snippet for partition maintenance
 *
 * @param array $partition_names array of partition names for a specific db/table
 * @param array $url_params      url parameters
 *
 * @return string $html_output
 */
function PMA_getHtmlForPartitionMaintenance($partition_names, $url_params)
{
    $choices = array(
        'ANALYZE' => __('Analyze'),
        'CHECK' => __('Check'),
        'OPTIMIZE' => __('Optimize'),
        'REBUILD' => __('Rebuild'),
        'REPAIR' => __('Repair')
    );

    $html_output = '<div class="operations_half_width">'
        . '<form method="post" action="tbl_operations.php">'
        . PMA_URL_getHiddenInputs($GLOBALS['db'], $GLOBALS['table'])
        . '<fieldset>'
        . '<legend>' . __('Partition maintenance') . '</legend>';

    $html_select = '<select name="partition_name">' . "\n";
    foreach ($partition_names as $one_partition) {
        $one_partition = htmlspecialchars($one_partition);
        $html_select .= '<option value="' . $one_partition . '">'
            . $one_partition . '</option>' . "\n";
    }
    $html_select .= '</select>' . "\n";
    $html_output .= sprintf(__('Partition %s'), $html_select);

    $html_output .= PMA_Util::getRadioFields(
        'partition_operation', $choices, '', false
    );
    $html_output .= PMA_Util::showMySQLDocu('partitioning_maintenance');
    $this_url_params = array_merge(
        $url_params,
        array(
            'sql_query' => 'ALTER TABLE '
            . PMA_Util::backquote($GLOBALS['table'])
            . ' REMOVE PARTITIONING;'
        )
    );
    $html_output .= '<br /><a href="sql.php'
        . PMA_URL_getCommon($this_url_params) . '">'
        . __('Remove partitioning') . '</a>';

    $html_output .= '</fieldset>'
        . '<fieldset class="tblFooters">'
        . '<input type="hidden" name="submit_partition" value="1">'
        . '<input type="submit" value="' . __('Go') . '" />'
        . '</fieldset>'
        . '</form>'
        . '</div>';

    return $html_output;
}

/**
 * Get the HTML for Referential Integrity check
 *
 * @param array $foreign    all Relations to foreign tables for a given table
 *                          or optionally a given column in a table
 * @param array $url_params array of url parameters
 *
 * @return string $html_output
 */
function PMA_getHtmlForReferentialIntegrityCheck($foreign, $url_params)
{
    $html_output = '<div class="operations_half_width">'
        . '<fieldset>'
        . '<legend>' . __('Check referential integrity:') . '</legend>';

    $html_output .= '<ul>';

    foreach ($foreign as $master => $arr) {
        $join_query  = 'SELECT '
            . PMA_Util::backquote($GLOBALS['table']) . '.*'
            . ' FROM ' . PMA_Util::backquote($GLOBALS['table'])
            . ' LEFT JOIN '
            . PMA_Util::backquote($arr['foreign_db'])
            . '.'
            . PMA_Util::backquote($arr['foreign_table']);
        if ($arr['foreign_table'] == $GLOBALS['table']) {
            $foreign_table = $GLOBALS['table'] . '1';
            $join_query .= ' AS ' . PMA_Util::backquote($foreign_table);
        } else {
            $foreign_table = $arr['foreign_table'];
        }
        $join_query .= ' ON '
            . PMA_Util::backquote($GLOBALS['table']) . '.'
            . PMA_Util::backquote($master)
            . ' = '
            . PMA_Util::backquote($arr['foreign_db'])
            . '.'
            . PMA_Util::backquote($foreign_table) . '.'
            . PMA_Util::backquote($arr['foreign_field'])
            . ' WHERE '
            . PMA_Util::backquote($arr['foreign_db'])
            . '.'
            . PMA_Util::backquote($foreign_table) . '.'
            . PMA_Util::backquote($arr['foreign_field'])
            . ' IS NULL AND '
            . PMA_Util::backquote($GLOBALS['table']) . '.'
            . PMA_Util::backquote($master)
            . ' IS NOT NULL';
        $this_url_params = array_merge(
            $url_params,
            array('sql_query' => $join_query)
        );

        $html_output .= '<li>'
            . '<a href="sql.php'
            . PMA_URL_getCommon($this_url_params)
            . '">'
            . $master . '&nbsp;->&nbsp;' . $arr['foreign_db'] . '.'
            . $arr['foreign_table'] . '.' . $arr['foreign_field']
            . '</a></li>' . "\n";
    } //  foreach $foreign
    $html_output .= '</ul></fieldset></div>';

    return $html_output;
}

/**
 * Reorder table based on request params
 *
 * @return array SQL query and result
 */
function PMA_getQueryAndResultForReorderingTable()
{
    $sql_query = 'ALTER TABLE '
        . PMA_Util::backquote($GLOBALS['table'])
        . ' ORDER BY '
        . PMA_Util::backquote(urldecode($_REQUEST['order_field']));
    if (isset($_REQUEST['order_order'])
        && $_REQUEST['order_order'] === 'desc'
    ) {
        $sql_query .= ' DESC';
    }
    $sql_query .= ';';
    $result = $GLOBALS['dbi']->query($sql_query);

    return array($sql_query, $result);
}

/**
 * Get table alters array
 *
 * @param boolean $is_myisam_or_aria   whether MYISAM | ARIA or not
 * @param boolean $is_isam             whether ISAM or not
 * @param string  $pack_keys           pack keys
 * @param string  $checksum            value of checksum
 * @param boolean $is_aria             whether ARIA or not
 * @param string  $page_checksum       value of page checksum
 * @param string  $delay_key_write     delay key write
 * @param boolean $is_innodb           whether INNODB or not
 * @param boolean $is_pbxt             whether PBXT or not
 * @param string  $row_format          row format
 * @param string  $newTblStorageEngine table storage engine
 * @param string  $transactional       value of transactional
 * @param string  $tbl_collation       collation of the table
 *
 * @return array  $table_alters
 */
function PMA_getTableAltersArray($is_myisam_or_aria, $is_isam, $pack_keys,
    $checksum, $is_aria, $page_checksum, $delay_key_write, $is_innodb,
    $is_pbxt, $row_format, $newTblStorageEngine, $transactional, $tbl_collation
) {
    global $auto_increment;

    $table_alters = array();

    if (isset($_REQUEST['comment'])
        && urldecode($_REQUEST['prev_comment']) !== $_REQUEST['comment']
    ) {
        $table_alters[] = 'COMMENT = \''
            . PMA_Util::sqlAddSlashes($_REQUEST['comment']) . '\'';
    }

    if (! empty($newTblStorageEngine)
        && /*overload*/mb_strtolower($newTblStorageEngine) !== /*overload*/mb_strtolower($GLOBALS['tbl_storage_engine'])
    ) {
        $table_alters[] = 'ENGINE = ' . $newTblStorageEngine;
    }
    if (! empty($_REQUEST['tbl_collation'])
        && $_REQUEST['tbl_collation'] !== $tbl_collation
    ) {
        $table_alters[] = 'DEFAULT '
            . PMA_generateCharsetQueryPart($_REQUEST['tbl_collation']);
    }

    if (($is_myisam_or_aria || $is_isam)
        && isset($_REQUEST['new_pack_keys'])
        && $_REQUEST['new_pack_keys'] != (string)$pack_keys
    ) {
        $table_alters[] = 'pack_keys = ' . $_REQUEST['new_pack_keys'];
    }

    $_REQUEST['new_checksum'] = empty($_REQUEST['new_checksum']) ? '0' : '1';
    if ($is_myisam_or_aria
        && $_REQUEST['new_checksum'] !== $checksum
    ) {
        $table_alters[] = 'checksum = ' . $_REQUEST['new_checksum'];
    }

    $_REQUEST['new_transactional']
        = empty($_REQUEST['new_transactional']) ? '0' : '1';
    if ($is_aria
        && $_REQUEST['new_transactional'] !== $transactional
    ) {
        $table_alters[] = 'TRANSACTIONAL = ' . $_REQUEST['new_transactional'];
    }

    $_REQUEST['new_page_checksum']
        = empty($_REQUEST['new_page_checksum']) ? '0' : '1';
    if ($is_aria
        && $_REQUEST['new_page_checksum'] !== $page_checksum
    ) {
        $table_alters[] = 'PAGE_CHECKSUM = ' . $_REQUEST['new_page_checksum'];
    }

    $_REQUEST['new_delay_key_write']
        = empty($_REQUEST['new_delay_key_write']) ? '0' : '1';
    if ($is_myisam_or_aria
        && $_REQUEST['new_delay_key_write'] !== $delay_key_write
    ) {
        $table_alters[] = 'delay_key_write = ' . $_REQUEST['new_delay_key_write'];
    }

    if (($is_myisam_or_aria || $is_innodb || $is_pbxt)
        && ! empty($_REQUEST['new_auto_increment'])
        && (! isset($auto_increment)
        || $_REQUEST['new_auto_increment'] !== $auto_increment)
    ) {
        $table_alters[] = 'auto_increment = '
            . PMA_Util::sqlAddSlashes($_REQUEST['new_auto_increment']);
    }

    $newRowFormat = $_REQUEST['new_row_format'];
    $newRowFormatLower = /*overload*/mb_strtolower($newRowFormat);
    if (($is_myisam_or_aria || $is_innodb || $is_pbxt)
        &&  ! empty($newRowFormat)
        && (!/*overload*/mb_strlen($row_format)
        || $newRowFormatLower !== /*overload*/mb_strtolower($row_format))
    ) {
        $table_alters[] = 'ROW_FORMAT = ' . PMA_Util::sqlAddSlashes($newRowFormat);
    }

    return $table_alters;
}

/**
 * set initial value of the set of variables, based on the current table engine
 *
 * @param string $tbl_storage_engine table storage engine
 *
 * @return array ($is_myisam_or_aria, $is_innodb, $is_isam,
 *                $is_berkeleydb, $is_aria, $is_pbxt)
 */
function PMA_setGlobalVariablesForEngine($tbl_storage_engine)
{
    $upperTblStorEngine = /*overload*/mb_strtoupper($tbl_storage_engine);

    //Options that apply to MYISAM usually apply to ARIA
    $is_myisam_or_aria = ($upperTblStorEngine == 'MYISAM'
        || $upperTblStorEngine == 'ARIA'
        || $upperTblStorEngine == 'MARIA'
    );
    $is_aria = ($upperTblStorEngine == 'ARIA');

    $is_isam = ($upperTblStorEngine == 'ISAM');
    $is_innodb = ($upperTblStorEngine == 'INNODB');
    $is_berkeleydb = ($upperTblStorEngine == 'BERKELEYDB');
    $is_pbxt = ($upperTblStorEngine == 'PBXT');

    return array(
        $is_myisam_or_aria, $is_innodb, $is_isam,
        $is_berkeleydb, $is_aria, $is_pbxt
    );
}

/**
 * Get warning messages array
 *
 * @return array  $warning_messages
 */
function PMA_getWarningMessagesArray()
{
    $warning_messages = array();
    foreach ($GLOBALS['dbi']->getWarnings() as $warning) {
        // In MariaDB 5.1.44, when altering a table from Maria to MyISAM
        // and if TRANSACTIONAL was set, the system reports an error;
        // I discussed with a Maria developer and he agrees that this
        // should not be reported with a Level of Error, so here
        // I just ignore it. But there are other 1478 messages
        // that it's better to show.
        if (! ($_REQUEST['new_tbl_storage_engine'] == 'MyISAM'
            && $warning['Code'] == '1478'
            && $warning['Level'] == 'Error')
        ) {
            $warning_messages[] = $warning['Level'] . ': #' . $warning['Code']
                . ' ' . $warning['Message'];
        }
    }
    return $warning_messages;
}

/**
 * Get SQL query and result after ran this SQL query for a partition operation
 * has been requested by the user
 *
 * @return array $sql_query, $result
 */
function PMA_getQueryAndResultForPartition()
{
    $sql_query = 'ALTER TABLE '
        . PMA_Util::backquote($GLOBALS['table']) . ' '
        . $_REQUEST['partition_operation']
        . ' PARTITION '
        . $_REQUEST['partition_name'] . ';';
    $result = $GLOBALS['dbi']->query($sql_query);

    return array($sql_query, $result);
}


/**
 * Move or copy a table
 *
 * @param string $db    current database name
 * @param string $table current table name
 *
 * @return void
 */
function PMA_moveOrCopyTable($db, $table)
{
    /**
     * Selects the database to work with
     */
    $GLOBALS['dbi']->selectDb($db);

    /**
     * $_REQUEST['target_db'] could be empty in case we came from an input field
     * (when there are many databases, no drop-down)
     */
    if (empty($_REQUEST['target_db'])) {
        $_REQUEST['target_db'] = $db;
    }

    /**
     * A target table name has been sent to this script -> do the work
     */
    if (PMA_isValid($_REQUEST['new_name'])) {
        if ($db == $_REQUEST['target_db'] && $table == $_REQUEST['new_name']) {
            if (isset($_REQUEST['submit_move'])) {
                $message = PMA_Message::error(__('Can\'t move table to same one!'));
            } else {
                $message = PMA_Message::error(__('Can\'t copy table to same one!'));
            }
        } else {
            PMA_Table::moveCopy(
                $db, $table, $_REQUEST['target_db'], $_REQUEST['new_name'],
                $_REQUEST['what'], isset($_REQUEST['submit_move']), 'one_table'
            );

            if (isset($_REQUEST['submit_move'])) {
                $message = PMA_Message::success(
                    __('Table %s has been moved to %s.')
                );
            } else {
                $message = PMA_Message::success(
                    __('Table %s has been copied to %s.')
                );
            }
            $old = PMA_Util::backquote($db) . '.'
                . PMA_Util::backquote($table);
            $message->addParam($old);
            $new = PMA_Util::backquote($_REQUEST['target_db']) . '.'
                . PMA_Util::backquote($_REQUEST['new_name']);
            $message->addParam($new);

            /* Check: Work on new table or on old table? */
            if (isset($_REQUEST['submit_move'])
                || PMA_isValid($_REQUEST['switch_to_new'])
            ) {
            }
        }
    } else {
        /**
         * No new name for the table!
         */
        $message = PMA_Message::error(__('The table name is empty!'));
    }

    if ($GLOBALS['is_ajax_request'] == true) {
        $response = PMA_Response::getInstance();
        $response->addJSON('message', $message);
        if ($message->isSuccess()) {
            $response->addJSON('db', $GLOBALS['db']);
        } else {
            $response->isSuccess(false);
        }
        exit;
    }
}
?>
