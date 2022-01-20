<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PhpMyAdmin\Operations class
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin;

use PhpMyAdmin\Charsets;
use PhpMyAdmin\Core;
use PhpMyAdmin\Engines\Innodb;
use PhpMyAdmin\Message;
use PhpMyAdmin\Partition;
use PhpMyAdmin\Plugins\Export\ExportSql;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Response;
use PhpMyAdmin\StorageEngine;
use PhpMyAdmin\Table;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

/**
 * Set of functions with the operations section in phpMyAdmin
 *
 * @package PhpMyAdmin
 */
class Operations
{
    /**
     * @var Relation $relation
     */
    private $relation;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->relation = new Relation();
    }

    /**
     * Get HTML output for database comment
     *
     * @param string $db database name
     *
     * @return string $html_output
     */
    public function getHtmlForDatabaseComment($db)
    {
        $html_output = '<div>'
            . '<form method="post" action="db_operations.php" id="formDatabaseComment">'
            . Url::getHiddenInputs($db)
            . '<fieldset>'
            . '<legend>';
        if (Util::showIcons('ActionLinksMode')) {
            $html_output .= Util::getImage('b_comment') . '&nbsp;';
        }
        $html_output .=  __('Database comment');
        $html_output .= '</legend>';
        $html_output .= '<input type="text" name="comment" '
            . 'class="textfield"'
            . 'value="' . htmlspecialchars($this->relation->getDbComment($db)) . '" />'
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
     * @param string $db           database name
     * @param string $db_collation dataset collation
     *
     * @return string $html_output
     */
    public function getHtmlForRenameDatabase($db, $db_collation)
    {
        $html_output = '<div>'
            . '<form id="rename_db_form" '
            . 'class="ajax" '
            . 'method="post" action="db_operations.php" '
            . 'onsubmit="return emptyCheckTheField(this, \'newname\')">';
        if (isset($db_collation)) {
            $html_output .= '<input type="hidden" name="db_collation" '
                . 'value="' . $db_collation
                . '" />' . "\n";
        }
        $html_output .= '<input type="hidden" name="what" value="data" />'
            . '<input type="hidden" name="db_rename" value="true" />'
            . Url::getHiddenInputs($db)
            . '<fieldset>'
            . '<legend>';

        if (Util::showIcons('ActionLinksMode')) {
            $html_output .= Util::getImage('b_edit') . '&nbsp;';
        }
        $html_output .= __('Rename database to')
            . '</legend>';

        $html_output .= '<input id="new_db_name" type="text" name="newname" '
            . 'maxlength="64" class="textfield" required="required"/>';
        $html_output .= '<br />';

        if ($GLOBALS['db_priv'] && $GLOBALS['table_priv']
            && $GLOBALS['col_priv'] && $GLOBALS['proc_priv']
            && $GLOBALS['is_reload_priv']
        ) {
            $html_output .= '<input type="checkbox" name="adjust_privileges" '
                . 'value="1" id="checkbox_adjust_privileges" checked="checked" />';
        } else {
            $html_output .= '<input type="checkbox" name="adjust_privileges" '
                . 'value="1" id="checkbox_adjust_privileges" title="' . __(
                    'You don\'t have sufficient privileges to perform this '
                    . 'operation; Please refer to the documentation for more details'
                )
                . '" disabled/>';
        }

        $html_output .= '<label for="checkbox_adjust_privileges">'
                . __('Adjust privileges') . Util::showDocu('faq', 'faq6-39')
                . '</label><br />';

        $html_output .= ''
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
    public function getHtmlForDropDatabaseLink($db)
    {
        $this_sql_query = 'DROP DATABASE ' . Util::backquote($db);
        $this_url_params = array(
            'sql_query' => $this_sql_query,
            'back' => 'db_operations.php',
            'goto' => 'index.php',
            'reload' => '1',
            'purge' => '1',
            'message_to_show' => sprintf(
                __('Database %s has been dropped.'),
                htmlspecialchars(Util::backquote($db))
            ),
            'db' => null,
        );

        $html_output = '<div>'
            . '<fieldset class="caution">';
        $html_output .= '<legend>';
        if (Util::showIcons('ActionLinksMode')) {
            $html_output .= Util::getImage('b_deltbl') . '&nbsp';
        }
        $html_output .= __('Remove database')
            . '</legend>';
        $html_output .= '<ul>';
        $html_output .= $this->getDeleteDataOrTablelink(
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
     * @param string $db           database name
     * @param string $db_collation dataset collation
     *
     * @return string $html_output
     */
    public function getHtmlForCopyDatabase($db, $db_collation)
    {
        $drop_clause = 'DROP TABLE / DROP VIEW';
        $choices = array(
            'structure' => __('Structure only'),
            'data'      => __('Structure and data'),
            'dataonly'  => __('Data only')
        );

        $pma_switch_to_new = isset($_SESSION['pma_switch_to_new']) && $_SESSION['pma_switch_to_new'];

        $html_output = '<div>';
        $html_output .= '<form id="copy_db_form" '
            . 'class="ajax" '
            . 'method="post" action="db_operations.php" '
            . 'onsubmit="return emptyCheckTheField(this, \'newname\')">';

        if (isset($db_collation)) {
            $html_output .= '<input type="hidden" name="db_collation" '
            . 'value="' . $db_collation . '" />' . "\n";
        }
        $html_output .= '<input type="hidden" name="db_copy" value="true" />' . "\n"
            . Url::getHiddenInputs($db);
        $html_output .= '<fieldset>'
            . '<legend>';

        if (Util::showIcons('ActionLinksMode')) {
            $html_output .= Util::getImage('b_edit') . '&nbsp';
        }
        $html_output .= __('Copy database to')
            . '</legend>'
            . '<input type="text" maxlength="64" name="newname" '
            . 'class="textfield" required="required" /><br />'
            . Util::getRadioFields(
                'what', $choices, 'data', true
            );
        $html_output .= '<br />';
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
            . 'id="checkbox_constraints" checked="checked"/>';
        $html_output .= '<label for="checkbox_constraints">'
            . __('Add constraints') . '</label><br />';
        $html_output .= '<br />';

        if ($GLOBALS['db_priv'] && $GLOBALS['table_priv']
            && $GLOBALS['col_priv'] && $GLOBALS['proc_priv']
            && $GLOBALS['is_reload_priv']
        ) {
            $html_output .= '<input type="checkbox" name="adjust_privileges" '
                . 'value="1" id="checkbox_privileges" checked="checked" />';
        } else {
            $html_output .= '<input type="checkbox" name="adjust_privileges" '
                . 'value="1" id="checkbox_privileges" title="' . __(
                    'You don\'t have sufficient privileges to perform this '
                    . 'operation; Please refer to the documentation for more details'
                )
                . '" disabled/>';
        }
        $html_output .= '<label for="checkbox_privileges">'
            . __('Adjust privileges') . Util::showDocu('faq', 'faq6-39')
            . '</label><br />';

        $html_output .= '<input type="checkbox" name="switch_to_new" value="true"'
            . 'id="checkbox_switch"'
            . ($pma_switch_to_new ? ' checked="checked"' : '') . '/>';
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
     * @param string $db           database name
     * @param string $db_collation dataset collation
     *
     * @return string $html_output
     */
    public function getHtmlForChangeDatabaseCharset($db, $db_collation)
    {
        $html_output = '<div>'
            . '<form id="change_db_charset_form" ';
        $html_output .= 'class="ajax" ';
        $html_output .= 'method="post" action="db_operations.php">';

        $html_output .= Url::getHiddenInputs($db);

        $html_output .= '<fieldset>' . "\n"
           . '    <legend>';
        if (Util::showIcons('ActionLinksMode')) {
            $html_output .= Util::getImage('s_asci') . '&nbsp';
        }
        $html_output .= '<label for="select_db_collation">' . __('Collation')
            . '</label>' . "\n"
            . '</legend>' . "\n"
            . Charsets::getCollationDropdownBox(
                $GLOBALS['dbi'],
                $GLOBALS['cfg']['Server']['DisableIS'],
                'db_collation',
                'select_db_collation',
                isset($db_collation) ? $db_collation : '',
                false
            )
            . '<br />'
            . '<input type="checkbox" name="change_all_tables_collations"'
            . 'id="checkbox_change_all_tables_collations" />'
            . '<label for="checkbox_change_all_tables_collations">'
            . __('Change all tables collations')
            . '</label>'
            . '<br />'
            . '<span id="span_change_all_tables_columns_collations"><input type="checkbox" name="change_all_tables_columns_collations"'
            . 'id="checkbox_change_all_tables_columns_collations" />'
            . '<label for="checkbox_change_all_tables_columns_collations">'
            . __('Change all tables columns collations')
            . '</label></span>'
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
    public function runProcedureAndFunctionDefinitions($db)
    {
        $procedure_names = $GLOBALS['dbi']->getProceduresOrFunctions($db, 'PROCEDURE');
        if ($procedure_names) {
            foreach ($procedure_names as $procedure_name) {
                $GLOBALS['dbi']->selectDb($db);
                $tmp_query = $GLOBALS['dbi']->getDefinition(
                    $db, 'PROCEDURE', $procedure_name
                );
                if ($tmp_query !== false) {
                    // collect for later display
                    $GLOBALS['sql_query'] .= "\n" . $tmp_query;
                    $GLOBALS['dbi']->selectDb($_POST['newname']);
                    $GLOBALS['dbi']->query($tmp_query);
                }
            }
        }

        $function_names = $GLOBALS['dbi']->getProceduresOrFunctions($db, 'FUNCTION');
        if ($function_names) {
            foreach ($function_names as $function_name) {
                $GLOBALS['dbi']->selectDb($db);
                $tmp_query = $GLOBALS['dbi']->getDefinition(
                    $db, 'FUNCTION', $function_name
                );
                if ($tmp_query !== false) {
                    // collect for later display
                    $GLOBALS['sql_query'] .= "\n" . $tmp_query;
                    $GLOBALS['dbi']->selectDb($_POST['newname']);
                    $GLOBALS['dbi']->query($tmp_query);
                }
            }
        }
    }

    /**
     * Create database before copy
     *
     * @return void
     */
    public function createDbBeforeCopy()
    {
        $local_query = 'CREATE DATABASE IF NOT EXISTS '
            . Util::backquote($_POST['newname']);
        if (isset($_POST['db_collation'])) {
            $local_query .= ' DEFAULT'
                . Util::getCharsetQueryPart($_POST['db_collation']);
        }
        $local_query .= ';';
        $GLOBALS['sql_query'] .= $local_query;

        // save the original db name because Tracker.php which
        // may be called under $GLOBALS['dbi']->query() changes $GLOBALS['db']
        // for some statements, one of which being CREATE DATABASE
        $original_db = $GLOBALS['db'];
        $GLOBALS['dbi']->query($local_query);
        $GLOBALS['db'] = $original_db;

        // Set the SQL mode to NO_AUTO_VALUE_ON_ZERO to prevent MySQL from creating
        // export statements it cannot import
        $sql_set_mode = "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO'";
        $GLOBALS['dbi']->query($sql_set_mode);

        // rebuild the database list because Table::moveCopy
        // checks in this list if the target db exists
        $GLOBALS['dblist']->databases->build();
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
    public function getViewsAndCreateSqlViewStandIn(
        array $tables_full, $export_sql_plugin, $db
    ) {
        $views = array();
        foreach ($tables_full as $each_table => $tmp) {
            // to be able to rename a db containing views,
            // first all the views are collected and a stand-in is created
            // the real views are created after the tables
            if ($GLOBALS['dbi']->getTable($db, $each_table)->isView()) {

                // If view exists, and 'add drop view' is selected: Drop it!
                if ($_POST['what'] != 'nocopy'
                    && isset($_POST['drop_if_exists'])
                    && $_POST['drop_if_exists'] == 'true'
                ) {
                    $drop_query = 'DROP VIEW IF EXISTS '
                        . Util::backquote($_POST['newname']) . '.'
                        . Util::backquote($each_table);
                    $GLOBALS['dbi']->query($drop_query);

                    $GLOBALS['sql_query'] .= "\n" . $drop_query . ';';
                }

                $views[] = $each_table;
                // Create stand-in definition to resolve view dependencies
                $sql_view_standin = $export_sql_plugin->getTableDefStandIn(
                    $db, $each_table, "\n"
                );
                $GLOBALS['dbi']->selectDb($_POST['newname']);
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
     * @param boolean $move        whether database name is empty or not
     * @param string  $db          database name
     *
     * @return array SQL queries for the constraints
     */
    public function copyTables(array $tables_full, $move, $db)
    {
        $sqlContraints = array();
        foreach ($tables_full as $each_table => $tmp) {
            // skip the views; we have created stand-in definitions
            if ($GLOBALS['dbi']->getTable($db, $each_table)->isView()) {
                continue;
            }

            // value of $what for this table only
            $this_what = $_POST['what'];

            // do not copy the data from a Merge table
            // note: on the calling FORM, 'data' means 'structure and data'
            if ($GLOBALS['dbi']->getTable($db, $each_table)->isMerge()) {
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

                if (! Table::moveCopy(
                    $db, $each_table, $_POST['newname'], $each_table,
                    (isset($this_what) ? $this_what : 'data'),
                    $move, 'db_copy'
                )) {
                    $GLOBALS['_error'] = true;
                    break;
                }
                // apply the triggers to the destination db+table
                if ($triggers) {
                    $GLOBALS['dbi']->selectDb($_POST['newname']);
                    foreach ($triggers as $trigger) {
                        $GLOBALS['dbi']->query($trigger['create']);
                        $GLOBALS['sql_query'] .= "\n" . $trigger['create'] . ';';
                    }
                }

                // this does not apply to a rename operation
                if (isset($_POST['add_constraints'])
                    && ! empty($GLOBALS['sql_constraints_query'])
                ) {
                    $sqlContraints[] = $GLOBALS['sql_constraints_query'];
                    unset($GLOBALS['sql_constraints_query']);
                }
            }
        }
        return $sqlContraints;
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
    public function runEventDefinitionsForDb($db)
    {
        $event_names = $GLOBALS['dbi']->fetchResult(
            'SELECT EVENT_NAME FROM information_schema.EVENTS WHERE EVENT_SCHEMA= \''
            . $GLOBALS['dbi']->escapeString($db) . '\';'
        );
        if ($event_names) {
            foreach ($event_names as $event_name) {
                $GLOBALS['dbi']->selectDb($db);
                $tmp_query = $GLOBALS['dbi']->getDefinition($db, 'EVENT', $event_name);
                // collect for later display
                $GLOBALS['sql_query'] .= "\n" . $tmp_query;
                $GLOBALS['dbi']->selectDb($_POST['newname']);
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
     * @return void
     */
    public function handleTheViews(array $views, $move, $db)
    {
        // temporarily force to add DROP IF EXIST to CREATE VIEW query,
        // to remove stand-in VIEW that was created earlier
        // ( $_POST['drop_if_exists'] is used in moveCopy() )
        if (isset($_POST['drop_if_exists'])) {
            $temp_drop_if_exists = $_POST['drop_if_exists'];
        }

        $_POST['drop_if_exists'] = 'true';
        foreach ($views as $view) {
            $copying_succeeded = Table::moveCopy(
                $db, $view, $_POST['newname'], $view, 'structure', $move, 'db_copy'
            );
            if (! $copying_succeeded) {
                $GLOBALS['_error'] = true;
                break;
            }
        }
        unset($_POST['drop_if_exists']);

        if (isset($temp_drop_if_exists)) {
            // restore previous value
            $_POST['drop_if_exists'] = $temp_drop_if_exists;
        }
    }

    /**
     * Adjust the privileges after Renaming the db
     *
     * @param string $oldDb   Database name before renaming
     * @param string $newname New Database name requested
     *
     * @return void
     */
    public function adjustPrivilegesMoveDb($oldDb, $newname)
    {
        if ($GLOBALS['db_priv'] && $GLOBALS['table_priv']
            && $GLOBALS['col_priv'] && $GLOBALS['proc_priv']
            && $GLOBALS['is_reload_priv']
        ) {
            $GLOBALS['dbi']->selectDb('mysql');
            $newname = str_replace("_", "\_", $newname);
            $oldDb = str_replace("_", "\_", $oldDb);

            // For Db specific privileges
            $query_db_specific = 'UPDATE ' . Util::backquote('db')
                . 'SET Db = \'' . $GLOBALS['dbi']->escapeString($newname)
                . '\' where Db = \'' . $GLOBALS['dbi']->escapeString($oldDb) . '\';';
            $GLOBALS['dbi']->query($query_db_specific);

            // For table specific privileges
            $query_table_specific = 'UPDATE ' . Util::backquote('tables_priv')
                . 'SET Db = \'' . $GLOBALS['dbi']->escapeString($newname)
                . '\' where Db = \'' . $GLOBALS['dbi']->escapeString($oldDb) . '\';';
            $GLOBALS['dbi']->query($query_table_specific);

            // For column specific privileges
            $query_col_specific = 'UPDATE ' . Util::backquote('columns_priv')
                . 'SET Db = \'' . $GLOBALS['dbi']->escapeString($newname)
                . '\' where Db = \'' . $GLOBALS['dbi']->escapeString($oldDb) . '\';';
            $GLOBALS['dbi']->query($query_col_specific);

            // For procedures specific privileges
            $query_proc_specific = 'UPDATE ' . Util::backquote('procs_priv')
                . 'SET Db = \'' . $GLOBALS['dbi']->escapeString($newname)
                . '\' where Db = \'' . $GLOBALS['dbi']->escapeString($oldDb) . '\';';
            $GLOBALS['dbi']->query($query_proc_specific);

            // Finally FLUSH the new privileges
            $flush_query = "FLUSH PRIVILEGES;";
            $GLOBALS['dbi']->query($flush_query);
        }
    }

    /**
     * Adjust the privileges after Copying the db
     *
     * @param string $oldDb   Database name before copying
     * @param string $newname New Database name requested
     *
     * @return void
     */
    public function adjustPrivilegesCopyDb($oldDb, $newname)
    {
        if ($GLOBALS['db_priv'] && $GLOBALS['table_priv']
            && $GLOBALS['col_priv'] && $GLOBALS['proc_priv']
            && $GLOBALS['is_reload_priv']
        ) {
            $GLOBALS['dbi']->selectDb('mysql');
            $newname = str_replace("_", "\_", $newname);
            $oldDb = str_replace("_", "\_", $oldDb);

            $query_db_specific_old = 'SELECT * FROM '
                . Util::backquote('db') . ' WHERE '
                . 'Db = "' . $oldDb . '";';

            $old_privs_db = $GLOBALS['dbi']->fetchResult($query_db_specific_old, 0);

            foreach ($old_privs_db as $old_priv) {
                $newDb_db_privs_query = 'INSERT INTO ' . Util::backquote('db')
                    . ' VALUES("' . $old_priv[0] . '", "' . $newname . '"';
                for ($i = 2; $i < count($old_priv); $i++) {
                    $newDb_db_privs_query .= ', "' . $old_priv[$i] . '"';
                }
                    $newDb_db_privs_query .= ')';

                $GLOBALS['dbi']->query($newDb_db_privs_query);
            }

            // For Table Specific privileges
            $query_table_specific_old = 'SELECT * FROM '
                . Util::backquote('tables_priv') . ' WHERE '
                . 'Db = "' . $oldDb . '";';

            $old_privs_table = $GLOBALS['dbi']->fetchResult(
                $query_table_specific_old,
                0
            );

            foreach ($old_privs_table as $old_priv) {
                $newDb_table_privs_query = 'INSERT INTO ' . Util::backquote(
                    'tables_priv'
                ) . ' VALUES("' . $old_priv[0] . '", "' . $newname . '", "'
                . $old_priv[2] . '", "' . $old_priv[3] . '", "' . $old_priv[4]
                . '", "' . $old_priv[5] . '", "' . $old_priv[6] . '", "'
                . $old_priv[7] . '");';

                $GLOBALS['dbi']->query($newDb_table_privs_query);
            }

            // For Column Specific privileges
            $query_col_specific_old = 'SELECT * FROM '
                . Util::backquote('columns_priv') . ' WHERE '
                . 'Db = "' . $oldDb . '";';

            $old_privs_col = $GLOBALS['dbi']->fetchResult(
                $query_col_specific_old,
                0
            );

            foreach ($old_privs_col as $old_priv) {
                $newDb_col_privs_query = 'INSERT INTO ' . Util::backquote(
                    'columns_priv'
                ) . ' VALUES("' . $old_priv[0] . '", "' . $newname . '", "'
                . $old_priv[2] . '", "' . $old_priv[3] . '", "' . $old_priv[4]
                . '", "' . $old_priv[5] . '", "' . $old_priv[6] . '");';

                $GLOBALS['dbi']->query($newDb_col_privs_query);
            }

            // For Procedure Specific privileges
            $query_proc_specific_old = 'SELECT * FROM '
                . Util::backquote('procs_priv') . ' WHERE '
                . 'Db = "' . $oldDb . '";';

            $old_privs_proc = $GLOBALS['dbi']->fetchResult(
                $query_proc_specific_old,
                0
            );

            foreach ($old_privs_proc as $old_priv) {
                $newDb_proc_privs_query = 'INSERT INTO ' . Util::backquote(
                    'procs_priv'
                ) . ' VALUES("' . $old_priv[0] . '", "' . $newname . '", "'
                . $old_priv[2] . '", "' . $old_priv[3] . '", "' . $old_priv[4]
                . '", "' . $old_priv[5] . '", "' . $old_priv[6] . '", "'
                . $old_priv[7] . '");';

                $GLOBALS['dbi']->query($newDb_proc_privs_query);
            }

            // Finally FLUSH the new privileges
            $flush_query = "FLUSH PRIVILEGES;";
            $GLOBALS['dbi']->query($flush_query);
        }
    }

    /**
     * Create all accumulated constraints
     *
     * @param array $sqlConstratints array of sql constraints for the database
     *
     * @return void
     */
    public function createAllAccumulatedConstraints(array $sqlConstratints)
    {
        $GLOBALS['dbi']->selectDb($_POST['newname']);
        foreach ($sqlConstratints as $one_query) {
            $GLOBALS['dbi']->query($one_query);
            // and prepare to display them
            $GLOBALS['sql_query'] .= "\n" . $one_query;
        }
    }

    /**
     * Duplicate the bookmarks for the db (done once for each db)
     *
     * @param boolean $_error whether table rename/copy or not
     * @param string  $db     database name
     *
     * @return void
     */
    public function duplicateBookmarks($_error, $db)
    {
        if (! $_error && $db != $_POST['newname']) {
            $get_fields = array('user', 'label', 'query');
            $where_fields = array('dbase' => $db);
            $new_fields = array('dbase' => $_POST['newname']);
            Table::duplicateInfo(
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
    public function getHtmlForOrderTheTable(array $columns)
    {
        $html_output = '<div>';
        $html_output .= '<form method="post" id="alterTableOrderby" '
            . 'action="tbl_operations.php">';
        $html_output .= Url::getHiddenInputs(
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
    public function getHtmlForMoveTable()
    {
        $html_output = '<div>';
        $html_output .= '<form method="post" action="tbl_operations.php"'
            . ' id="moveTableForm" class="ajax"'
            . ' onsubmit="return emptyCheckTheField(this, \'new_name\')">'
            . Url::getHiddenInputs($GLOBALS['db'], $GLOBALS['table']);

        $html_output .= '<input type="hidden" name="reload" value="1" />'
            . '<input type="hidden" name="what" value="data" />'
            . '<fieldset id="fieldset_table_rename">';

        $html_output .= '<legend>' . __('Move table to (database<b>.</b>table)')
            . '</legend>';

        if (count($GLOBALS['dblist']->databases) > $GLOBALS['cfg']['MaxDbList']) {
            $html_output .= '<input type="text" maxlength="100" '
                . 'name="target_db" value="' . htmlspecialchars($GLOBALS['db'])
                . '"/>';
        } else {
            $html_output .= '<select class="halfWidth" name="target_db">'
                . $GLOBALS['dblist']->databases->getHtmlOptions(true, false)
                . '</select>';
        }
        $html_output .= '&nbsp;<strong>.</strong>&nbsp;';
        $html_output .= '<input class="halfWidth" type="text" name="new_name"'
            . ' maxlength="64" required="required" '
            . 'value="' . htmlspecialchars($GLOBALS['table']) . '" /><br />';

        // starting with MySQL 5.0.24, SHOW CREATE TABLE includes the AUTO_INCREMENT
        // next value but users can decide if they want it or not for the operation

        $html_output .= '<input type="checkbox" name="sql_auto_increment" '
            . 'value="1" id="checkbox_auto_increment_mv" checked="checked" />'
            . '<label for="checkbox_auto_increment_mv">'
            . __('Add AUTO_INCREMENT value')
            . '</label><br />';

        if ($GLOBALS['table_priv'] && $GLOBALS['col_priv']
            && $GLOBALS['is_reload_priv']
        ) {
            $html_output .= '<input type="checkbox" name="adjust_privileges" '
                . 'value="1" id="checkbox_privileges_tables_move" '
                . 'checked="checked" />';
        } else {
            $html_output .= '<input type="checkbox" name="adjust_privileges" '
                . 'value="1" id="checkbox_privileges_tables_move" title="' . __(
                    'You don\'t have sufficient privileges to perform this '
                    . 'operation; Please refer to the documentation for more details'
                )
                . '" disabled/>';
        }
        $html_output .= '<label for="checkbox_privileges_tables_move">'
            . __('Adjust privileges') . Util::showDocu('faq', 'faq6-39')
            . '</label><br />';

        $html_output .= '</fieldset><fieldset class="tblFooters">'
            . '<input type="submit" name="submit_move" value="' . __('Go') . '" />'
            . '</fieldset>'
            . '</form>'
            . '</div>';

        return $html_output;
    }

    /**
     * Get the HTML div for Table option
     *
     * @param Table  $pma_table          Table object
     * @param string $comment            Comment
     * @param array  $tbl_collation      table collation
     * @param string $tbl_storage_engine table storage engine
     * @param string $pack_keys          pack keys
     * @param string $auto_increment     value of auto increment
     * @param string $delay_key_write    delay key write
     * @param string $transactional      value of transactional
     * @param string $page_checksum      value of page checksum
     * @param string $checksum           the checksum
     *
     * @return string $html_output
     */
    public function getTableOptionDiv($pma_table, $comment, $tbl_collation, $tbl_storage_engine,
        $pack_keys, $auto_increment, $delay_key_write,
        $transactional, $page_checksum, $checksum
    ) {
        $html_output = '<div>';
        $html_output .= '<form method="post" action="tbl_operations.php"';
        $html_output .= ' id="tableOptionsForm" class="ajax">';
        $html_output .= Url::getHiddenInputs(
            $GLOBALS['db'], $GLOBALS['table']
        );
        $html_output .= '<input type="hidden" name="reload" value="1" />';

        $html_output .= $this->getTableOptionFieldset(
            $pma_table, $comment, $tbl_collation,
            $tbl_storage_engine, $pack_keys,
            $delay_key_write, $auto_increment, $transactional, $page_checksum,
            $checksum
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
     * Get HTML for the rename table part of table options
     *
     * @return string $html_output
     */
    private function getHtmlForRenameTable()
    {
        $html_output = '<tr><td class="vmiddle">' . __('Rename table to') . '</td>'
            . '<td>'
            . '<input type="text" name="new_name" maxlength="64" '
            . 'value="' . htmlspecialchars($GLOBALS['table'])
            . '" required="required" />'
            . '</td></tr>'
            . '<tr><td></td><td>';

        if ($GLOBALS['table_priv'] && $GLOBALS['col_priv']
            && $GLOBALS['is_reload_priv']
        ) {
            $html_output .= '<input type="checkbox" name="adjust_privileges" '
                . 'value="1" id="checkbox_privileges_table_options" '
                . 'checked="checked" />';
        } else {
            $html_output .= '<input type="checkbox" name="adjust_privileges" '
                . 'value="1" id="checkbox_privileges_table_options" title="' . __(
                    'You don\'t have sufficient privileges to perform this '
                    . 'operation; Please refer to the documentation for more details'
                )
                . '" disabled/>';
        }
        $html_output .= '<label for="checkbox_privileges_table_options">'
            . __('Adjust privileges') . '&nbsp;'
            . Util::showDocu('faq', 'faq6-39') . '</label>';

        $html_output .= '</td></tr>';
        return $html_output;
    }

    /**
     * Get HTML for the table comments part of table options
     *
     * @param string $current_value of the table comments
     *
     * @return string $html_output
     */
    private function getHtmlForTableComments($current_value)
    {
        $commentLength = $GLOBALS['dbi']->getVersion() >= 50503 ? 2048 : 60;
        $html_output = '<tr><td class="vmiddle">' . __('Table comments') . '</td>'
            . '<td><input type="text" name="comment" '
            . 'maxlength="' . $commentLength . '"'
            . 'value="' . htmlspecialchars($current_value) . '" />'
            . '<input type="hidden" name="prev_comment" value="'
            . htmlspecialchars($current_value) . '" />'
            . '</td>'
            . '</tr>';

        return $html_output;
    }

    /**
     * Get HTML for the PACK KEYS part of table options
     *
     * @param string $current_value of the pack keys option
     *
     * @return string $html_output
     */
    private function getHtmlForPackKeys($current_value)
    {
        $html_output = '<tr>'
            . '<td class="vmiddle"><label for="new_pack_keys">PACK_KEYS</label></td>'
            . '<td><select name="new_pack_keys" id="new_pack_keys">';

        $html_output .= '<option value="DEFAULT"';
        if ($current_value == 'DEFAULT') {
            $html_output .= 'selected="selected"';
        }
        $html_output .= '>DEFAULT</option>
                <option value="0"';
        if ($current_value == '0') {
            $html_output .= 'selected="selected"';
        }
        $html_output .= '>0</option>
                <option value="1" ';
        if ($current_value == '1') {
            $html_output .= 'selected="selected"';
        }
        $html_output .= '>1</option>'
            . '</select>'
            . '</td>'
            . '</tr>';

        return $html_output;
    }

    /**
     * Get HTML fieldset for Table option, it contains HTML table for options
     *
     * @param Table  $pma_table          Table object
     * @param string $comment            Comment
     * @param array  $tbl_collation      table collation
     * @param string $tbl_storage_engine table storage engine
     * @param string $pack_keys          pack keys
     * @param string $delay_key_write    delay key write
     * @param string $auto_increment     value of auto increment
     * @param string $transactional      value of transactional
     * @param string $page_checksum      value of page checksum
     * @param string $checksum           the checksum
     *
     * @return string $html_output
     */
    private function getTableOptionFieldset($pma_table, $comment, $tbl_collation,
        $tbl_storage_engine, $pack_keys,
        $delay_key_write, $auto_increment, $transactional,
        $page_checksum, $checksum
    ) {
        $html_output = '<fieldset>'
            . '<legend>' . __('Table options') . '</legend>';

        $html_output .= '<table>';
        $html_output .= $this->getHtmlForRenameTable();
        $html_output .= $this->getHtmlForTableComments($comment);

        //Storage engine
        $html_output .= '<tr><td class="vmiddle">' . __('Storage Engine')
            . '&nbsp;' . Util::showMySQLDocu('Storage_engines')
            . '</td>'
            . '<td>'
            . StorageEngine::getHtmlSelect(
                'new_tbl_storage_engine', null, $tbl_storage_engine
            )
            . '</td>'
            . '</tr>';

        //Table character set
        $html_output .= '<tr><td class="vmiddle">' . __('Collation') . '</td>'
            . '<td>'
            . Charsets::getCollationDropdownBox(
                $GLOBALS['dbi'],
                $GLOBALS['cfg']['Server']['DisableIS'],
                'tbl_collation',
                null,
                $tbl_collation,
                false
            )
            . '</td>'
            . '</tr>';

        // Change all Column collations
        $html_output .= '<tr><td></td><td>'
            . '<input type="checkbox" name="change_all_collations" value="1" '
            . 'id="checkbox_change_all_collations" />'
            . '<label for="checkbox_change_all_collations">'
            . __('Change all column collations')
            . '</label>'
            . '</td></tr>';

        if ($pma_table->isEngine(array('MYISAM', 'ARIA', 'ISAM'))) {
            $html_output .= $this->getHtmlForPackKeys($pack_keys);
        } // end if (MYISAM|ISAM)

        if ($pma_table->isEngine(array('MYISAM', 'ARIA'))) {
            $html_output .= $this->getHtmlForTableRow(
                'new_checksum',
                'CHECKSUM',
                $checksum
            );

            $html_output .= $this->getHtmlForTableRow(
                'new_delay_key_write',
                'DELAY_KEY_WRITE',
                $delay_key_write
            );
        } // end if (MYISAM)

        if ($pma_table->isEngine('ARIA')) {
            $html_output .= $this->getHtmlForTableRow(
                'new_transactional',
                'TRANSACTIONAL',
                $transactional
            );

            $html_output .= $this->getHtmlForTableRow(
                'new_page_checksum',
                'PAGE_CHECKSUM',
                $page_checksum
            );
        } // end if (ARIA)

        if (strlen($auto_increment) > 0
            && $pma_table->isEngine(array('MYISAM', 'ARIA', 'INNODB', 'PBXT', 'ROCKSDB'))
        ) {
            $html_output .= '<tr><td class="vmiddle">'
                . '<label for="auto_increment_opt">AUTO_INCREMENT</label></td>'
                . '<td><input type="number" name="new_auto_increment" '
                . 'id="auto_increment_opt"'
                . 'value="' . $auto_increment . '" /></td>'
                . '</tr> ';
        } // end if (MYISAM|INNODB)

        $possible_row_formats = $this->getPossibleRowFormat();

        // for MYISAM there is also COMPRESSED but it can be set only by the
        // myisampack utility, so don't offer here the choice because if we
        // try it inside an ALTER TABLE, MySQL (at least in 5.1.23-maria)
        // does not return a warning
        // (if the table was compressed, it can be seen on the Structure page)

        if (isset($possible_row_formats[$tbl_storage_engine])) {
            $current_row_format
                = mb_strtoupper($GLOBALS['showtable']['Row_format']);
            $html_output .= '<tr><td class="vmiddle">'
                . '<label for="new_row_format">ROW_FORMAT</label></td>'
                . '<td>';
            $html_output .= Util::getDropdown(
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
    private function getHtmlForTableRow($attribute, $label, $val)
    {
        return '<tr>'
            . '<td class="vmiddle">'
            . '<label for="' . $attribute . '">' . $label . '</label>'
            . '</td>'
            . '<td>'
            . '<input type="checkbox" name="' . $attribute . '" id="' . $attribute . '"'
            . ' value="1"' . ((!empty($val) && $val == 1) ? ' checked="checked"' : '')
            . '/>'
            . '</td>'
            . '</tr>';
    }

    /**
     * Get array of possible row formats
     *
     * @return array $possible_row_formats
     */
    private function getPossibleRowFormat()
    {
        // the outer array is for engines, the inner array contains the dropdown
        // option values as keys then the dropdown option labels

        $possible_row_formats = array(
            'ARCHIVE' => array(
                'COMPRESSED' => 'COMPRESSED',
            ),
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

        /** @var Innodb $innodbEnginePlugin */
        $innodbEnginePlugin = StorageEngine::getEngine('Innodb');
        $innodbPluginVersion = $innodbEnginePlugin->getInnodbPluginVersion();
        if (!empty($innodbPluginVersion)) {
            $innodb_file_format = $innodbEnginePlugin->getInnodbFileFormat();
        } else {
            $innodb_file_format = '';
        }
        /**
         * Newer MySQL/MariaDB always return empty a.k.a '' on $innodb_file_format otherwise
         * old versions of MySQL/MariaDB must be returning something or not empty.
         * This patch is to support newer MySQL/MariaDB while also for backward compatibilities.
         */
        if (( ('Barracuda' == $innodb_file_format) || ($innodb_file_format == '') )
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
    public function getHtmlForCopytable()
    {
        $html_output = '<div>';
        $html_output .= '<form method="post" action="tbl_operations.php" '
            . 'name="copyTable" '
            . 'id="copyTable" '
            . ' class="ajax" '
            . 'onsubmit="return emptyCheckTheField(this, \'new_name\')">'
            . Url::getHiddenInputs($GLOBALS['db'], $GLOBALS['table'])
            . '<input type="hidden" name="reload" value="1" />';

        $html_output .= '<fieldset>';
        $html_output .= '<legend>'
            . __('Copy table to (database<b>.</b>table)') . '</legend>';

        if (count($GLOBALS['dblist']->databases) > $GLOBALS['cfg']['MaxDbList']) {
            $html_output .= '<input class="halfWidth" type="text" maxlength="100" '
                . 'name="target_db" '
                . 'value="' . htmlspecialchars($GLOBALS['db']) . '"/>';
        } else {
            $html_output .= '<select class="halfWidth" name="target_db">'
                . $GLOBALS['dblist']->databases->getHtmlOptions(true, false)
                . '</select>';
        }
        $html_output .= '&nbsp;<strong>.</strong>&nbsp;';
        $html_output .= '<input class="halfWidth" type="text" required="required" '
            . 'name="new_name" maxlength="64" '
            . 'value="' . htmlspecialchars($GLOBALS['table']) . '"/><br />';

        $choices = array(
            'structure' => __('Structure only'),
            'data'      => __('Structure and data'),
            'dataonly'  => __('Data only')
        );

        $html_output .= Util::getRadioFields(
            'what', $choices, 'data', true
        );
        $html_output .= '<br />';

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
        if ($this->relation->getForeigners($GLOBALS['db'], $GLOBALS['table'], '', 'foreign')) {
            $html_output .= '<input type="checkbox" name="add_constraints" '
                . 'value="1" id="checkbox_constraints" checked="checked"/>';
            $html_output .= '<label for="checkbox_constraints">'
                . __('Add constraints') . '</label><br />';
        } // endif

        $html_output .= '<br />';

        if ($GLOBALS['table_priv'] && $GLOBALS['col_priv']
            && $GLOBALS['is_reload_priv']
        ) {
            $html_output .= '<input type="checkbox" name="adjust_privileges" '
                . 'value="1" id="checkbox_adjust_privileges" checked="checked" />';
        } else {
            $html_output .= '<input type="checkbox" name="adjust_privileges" '
                . 'value="1" id="checkbox_adjust_privileges" title="' . __(
                    'You don\'t have sufficient privileges to perform this '
                    . 'operation; Please refer to the documentation for more details'
                )
                . '" disabled/>';
        }
        $html_output .= '<label for="checkbox_adjust_privileges">'
            . __('Adjust privileges') . Util::showDocu('faq', 'faq6-39')
            . '</label><br />';

        $pma_switch_to_new = isset($_SESSION['pma_switch_to_new']) && $_SESSION['pma_switch_to_new'];

        $html_output .= '<input type="checkbox" name="switch_to_new" value="true"'
            . 'id="checkbox_switch"'
            . ($pma_switch_to_new ? ' checked="checked"' : '') . '/>';
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
     * @param Table $pma_table  Table object
     * @param array $url_params array of URL parameters
     *
     * @return string $html_output
     */
    public function getHtmlForTableMaintenance($pma_table, array $url_params)
    {
        $html_output = '<div>';
        $html_output .= '<fieldset>'
            . '<legend>' . __('Table maintenance') . '</legend>';
        $html_output .= '<ul id="tbl_maintenance">';

        // Note: BERKELEY (BDB) is no longer supported, starting with MySQL 5.1
        $html_output .= $this->getListofMaintainActionLink($pma_table, $url_params);

        $html_output .= '</ul>'
            . '</fieldset>'
            . '</div>';

        return $html_output;
    }

    /**
     * Get HTML 'li' having a link of maintain action
     *
     * @param Table $pma_table  Table object
     * @param array $url_params Array of URL parameters
     *
     * @return string $html_output
     */
    private function getListofMaintainActionLink($pma_table, array $url_params)
    {
        $html_output = '';

        // analyze table
        if ($pma_table->isEngine(array('MYISAM', 'ARIA', 'INNODB', 'BERKELEYDB', 'TOKUDB'))) {
            $params = array(
                'sql_query' => 'ANALYZE TABLE '
                    . Util::backquote($GLOBALS['table']),
                'table_maintenance' => 'Go',
            );
            $html_output .= $this->getMaintainActionlink(
                __('Analyze table'),
                $params,
                $url_params,
                'ANALYZE_TABLE'
            );
        }

        // check table
        if ($pma_table->isEngine(array('MYISAM', 'ARIA', 'INNODB', 'TOKUDB'))) {
            $params = array(
                'sql_query' => 'CHECK TABLE '
                    . Util::backquote($GLOBALS['table']),
                'table_maintenance' => 'Go',
            );
            $html_output .= $this->getMaintainActionlink(
                __('Check table'),
                $params,
                $url_params,
                'CHECK_TABLE'
            );
        }

        // checksum table
        $params = array(
            'sql_query' => 'CHECKSUM TABLE '
                . Util::backquote($GLOBALS['table']),
            'table_maintenance' => 'Go',
        );
        $html_output .= $this->getMaintainActionlink(
            __('Checksum table'),
            $params,
            $url_params,
            'CHECKSUM_TABLE'
        );

        // defragment table
        if ($pma_table->isEngine(array('INNODB'))) {
            $params = array(
                'sql_query' => 'ALTER TABLE '
                . Util::backquote($GLOBALS['table'])
                . ' ENGINE = InnoDB;'
            );
            $html_output .= $this->getMaintainActionlink(
                __('Defragment table'),
                $params,
                $url_params,
                'InnoDB_File_Defragmenting'
            );
        }

        // flush table
        $params = array(
            'sql_query' => 'FLUSH TABLE '
                . Util::backquote($GLOBALS['table']),
            'message_to_show' => sprintf(
                __('Table %s has been flushed.'),
                htmlspecialchars($GLOBALS['table'])
            ),
            'reload' => 1,
        );
        $html_output .= $this->getMaintainActionlink(
            __('Flush the table (FLUSH)'),
            $params,
            $url_params,
            'FLUSH'
        );

        // optimize table
        if ($pma_table->isEngine(array('MYISAM', 'ARIA', 'INNODB', 'BERKELEYDB', 'TOKUDB'))) {
            $params = array(
                'sql_query' => 'OPTIMIZE TABLE '
                    . Util::backquote($GLOBALS['table']),
                'table_maintenance' => 'Go',
            );
            $html_output .= $this->getMaintainActionlink(
                __('Optimize table'),
                $params,
                $url_params,
                'OPTIMIZE_TABLE'
            );
        }

        // repair table
        if ($pma_table->isEngine(array('MYISAM', 'ARIA'))) {
            $params = array(
                'sql_query' => 'REPAIR TABLE '
                    . Util::backquote($GLOBALS['table']),
                'table_maintenance' => 'Go',
            );
            $html_output .= $this->getMaintainActionlink(
                __('Repair table'),
                $params,
                $url_params,
                'REPAIR_TABLE'
            );
        }

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
    private function getMaintainActionlink($action_message, array $params, array $url_params, $link)
    {
        return '<li>'
            . Util::linkOrButton(
                'sql.php',
                array_merge($url_params, $params),
                $action_message,
                ['class' => 'maintain_action ajax']
            )
            . Util::showMySQLDocu($link)
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
    public function getHtmlForDeleteDataOrTable(
        array $truncate_table_url_params,
        array $dropTableUrlParams
    ) {
        $html_output = '<div>'
            . '<fieldset class="caution">'
            . '<legend>' . __('Delete data or table') . '</legend>';

        $html_output .= '<ul>';

        if (! empty($truncate_table_url_params)) {
            $html_output .= $this->getDeleteDataOrTablelink(
                $truncate_table_url_params,
                'TRUNCATE_TABLE',
                __('Empty the table (TRUNCATE)'),
                'truncate_tbl_anchor'
            );
        }
        if (!empty($dropTableUrlParams)) {
            $html_output .= $this->getDeleteDataOrTablelink(
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
     * @return string html output
     */
    public function getDeleteDataOrTablelink(array $url_params, $syntax, $link, $htmlId)
    {
        return '<li>' . Util::linkOrButton(
                'sql.php',
                $url_params,
                $link,
                array('id' => $htmlId, 'class' => 'ajax')
            )
            . Util::showMySQLDocu($syntax)
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
    public function getHtmlForPartitionMaintenance(array $partition_names, array $url_params)
    {
        $choices = array(
            'ANALYZE' => __('Analyze'),
            'CHECK' => __('Check'),
            'OPTIMIZE' => __('Optimize'),
            'REBUILD' => __('Rebuild'),
            'REPAIR' => __('Repair'),
            'TRUNCATE' => __('Truncate')
        );

        $partition_method = Partition::getPartitionMethod(
            $GLOBALS['db'], $GLOBALS['table']
        );
        // add COALESCE or DROP option to choices array depeding on Partition method
        if ($partition_method == 'RANGE'
            || $partition_method == 'RANGE COLUMNS'
            || $partition_method == 'LIST'
            || $partition_method == 'LIST COLUMNS'
        ) {
            $choices['DROP'] = __('Drop');
        } else {
            $choices['COALESCE'] = __('Coalesce');
        }

        $html_output = '<div>'
            . '<form id="partitionsForm" class="ajax" '
            . 'method="post" action="tbl_operations.php" >'
            . Url::getHiddenInputs($GLOBALS['db'], $GLOBALS['table'])
            . '<fieldset>'
            . '<legend>'
            . __('Partition maintenance')
            . Util::showMySQLDocu('partitioning_maintenance')
            . '</legend>';

        $html_select = '<select id="partition_name" name="partition_name[]"'
            . ' multiple="multiple" required="required">' . "\n";
        $first = true;
        foreach ($partition_names as $one_partition) {
            $one_partition = htmlspecialchars($one_partition);
            $html_select .= '<option value="' . $one_partition . '"';
            if ($first) {
                $html_select .= ' selected="selected"';
                $first = false;
            }
            $html_select .=  '>' . $one_partition . '</option>' . "\n";
        }
        $html_select .= '</select>' . "\n";
        $html_output .= sprintf(__('Partition %s'), $html_select);

        $html_output .= '<div class="clearfloat" />';
        $html_output .= Util::getRadioFields(
            'partition_operation', $choices, 'ANALYZE', false, true, 'floatleft'
        );
        $this_url_params = array_merge(
            $url_params,
            array(
                'sql_query' => 'ALTER TABLE '
                . Util::backquote($GLOBALS['table'])
                . ' REMOVE PARTITIONING;'
            )
        );
        $html_output .= '<div class="clearfloat" /><br />';

        $html_output .= '<a href="sql.php'
            . Url::getCommon($this_url_params) . '">'
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
    public function getHtmlForReferentialIntegrityCheck(array $foreign, array $url_params)
    {
        $html_output = '<div>'
            . '<fieldset>'
            . '<legend>' . __('Check referential integrity:') . '</legend>';

        $html_output .= '<ul>';

        foreach ($foreign as $master => $arr) {
            $join_query  = 'SELECT '
                . Util::backquote($GLOBALS['table']) . '.*'
                . ' FROM ' . Util::backquote($GLOBALS['table'])
                . ' LEFT JOIN '
                . Util::backquote($arr['foreign_db'])
                . '.'
                . Util::backquote($arr['foreign_table']);
            if ($arr['foreign_table'] == $GLOBALS['table']) {
                $foreign_table = $GLOBALS['table'] . '1';
                $join_query .= ' AS ' . Util::backquote($foreign_table);
            } else {
                $foreign_table = $arr['foreign_table'];
            }
            $join_query .= ' ON '
                . Util::backquote($GLOBALS['table']) . '.'
                . Util::backquote($master)
                . ' = '
                . Util::backquote($arr['foreign_db'])
                . '.'
                . Util::backquote($foreign_table) . '.'
                . Util::backquote($arr['foreign_field'])
                . ' WHERE '
                . Util::backquote($arr['foreign_db'])
                . '.'
                . Util::backquote($foreign_table) . '.'
                . Util::backquote($arr['foreign_field'])
                . ' IS NULL AND '
                . Util::backquote($GLOBALS['table']) . '.'
                . Util::backquote($master)
                . ' IS NOT NULL';
            $this_url_params = array_merge(
                $url_params,
                [
                   'sql_query' => $join_query,
                   'sql_signature' => Core::signSqlQuery($join_query),
                ]
            );

            $html_output .= '<li>'
                . '<a href="sql.php'
                . Url::getCommon($this_url_params)
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
    public function getQueryAndResultForReorderingTable()
    {
        $sql_query = 'ALTER TABLE '
            . Util::backquote($GLOBALS['table'])
            . ' ORDER BY '
            . Util::backquote(urldecode($_POST['order_field']));
        if (isset($_POST['order_order'])
            && $_POST['order_order'] === 'desc'
        ) {
            $sql_query .= ' DESC';
        } else {
            $sql_query .= ' ASC';
        }
        $sql_query .= ';';
        $result = $GLOBALS['dbi']->query($sql_query);

        return array($sql_query, $result);
    }

    /**
     * Get table alters array
     *
     * @param Table  $pma_table           The Table object
     * @param string $pack_keys           pack keys
     * @param string $checksum            value of checksum
     * @param string $page_checksum       value of page checksum
     * @param string $delay_key_write     delay key write
     * @param string $row_format          row format
     * @param string $newTblStorageEngine table storage engine
     * @param string $transactional       value of transactional
     * @param string $tbl_collation       collation of the table
     *
     * @return array  $table_alters
     */
    public function getTableAltersArray($pma_table, $pack_keys,
        $checksum, $page_checksum, $delay_key_write,
        $row_format, $newTblStorageEngine, $transactional, $tbl_collation
    ) {
        global $auto_increment;

        $table_alters = array();

        if (isset($_POST['comment'])
            && urldecode($_POST['prev_comment']) !== $_POST['comment']
        ) {
            $table_alters[] = 'COMMENT = \''
                . $GLOBALS['dbi']->escapeString($_POST['comment']) . '\'';
        }

        if (! empty($newTblStorageEngine)
            && mb_strtolower($newTblStorageEngine) !== mb_strtolower($GLOBALS['tbl_storage_engine'])
        ) {
            $table_alters[] = 'ENGINE = ' . $newTblStorageEngine;
        }
        if (! empty($_POST['tbl_collation'])
            && $_POST['tbl_collation'] !== $tbl_collation
        ) {
            $table_alters[] = 'DEFAULT '
                . Util::getCharsetQueryPart($_POST['tbl_collation']);
        }

        if ($pma_table->isEngine(array('MYISAM', 'ARIA', 'ISAM'))
            && isset($_POST['new_pack_keys'])
            && $_POST['new_pack_keys'] != (string)$pack_keys
        ) {
            $table_alters[] = 'pack_keys = ' . $_POST['new_pack_keys'];
        }

        $_POST['new_checksum'] = empty($_POST['new_checksum']) ? '0' : '1';
        if ($pma_table->isEngine(array('MYISAM', 'ARIA'))
            && $_POST['new_checksum'] !== $checksum
        ) {
            $table_alters[] = 'checksum = ' . $_POST['new_checksum'];
        }

        $_POST['new_transactional']
            = empty($_POST['new_transactional']) ? '0' : '1';
        if ($pma_table->isEngine('ARIA')
            && $_POST['new_transactional'] !== $transactional
        ) {
            $table_alters[] = 'TRANSACTIONAL = ' . $_POST['new_transactional'];
        }

        $_POST['new_page_checksum']
            = empty($_POST['new_page_checksum']) ? '0' : '1';
        if ($pma_table->isEngine('ARIA')
            && $_POST['new_page_checksum'] !== $page_checksum
        ) {
            $table_alters[] = 'PAGE_CHECKSUM = ' . $_POST['new_page_checksum'];
        }

        $_POST['new_delay_key_write']
            = empty($_POST['new_delay_key_write']) ? '0' : '1';
        if ($pma_table->isEngine(array('MYISAM', 'ARIA'))
            && $_POST['new_delay_key_write'] !== $delay_key_write
        ) {
            $table_alters[] = 'delay_key_write = ' . $_POST['new_delay_key_write'];
        }

        if ($pma_table->isEngine(array('MYISAM', 'ARIA', 'INNODB', 'PBXT', 'ROCKSDB'))
            && ! empty($_POST['new_auto_increment'])
            && (! isset($auto_increment)
            || $_POST['new_auto_increment'] !== $auto_increment)
        ) {
            $table_alters[] = 'auto_increment = '
                . $GLOBALS['dbi']->escapeString($_POST['new_auto_increment']);
        }

        if (! empty($_POST['new_row_format'])) {
            $newRowFormat = $_POST['new_row_format'];
            $newRowFormatLower = mb_strtolower($newRowFormat);
            if ($pma_table->isEngine(array('MYISAM', 'ARIA', 'INNODB', 'PBXT'))
                && (strlen($row_format) === 0
                || $newRowFormatLower !== mb_strtolower($row_format))
            ) {
                $table_alters[] = 'ROW_FORMAT = '
                    . $GLOBALS['dbi']->escapeString($newRowFormat);
            }
        }

        return $table_alters;
    }

    /**
     * Get warning messages array
     *
     * @return array  $warning_messages
     */
    public function getWarningMessagesArray()
    {
        $warning_messages = array();
        foreach ($GLOBALS['dbi']->getWarnings() as $warning) {
            // In MariaDB 5.1.44, when altering a table from Maria to MyISAM
            // and if TRANSACTIONAL was set, the system reports an error;
            // I discussed with a Maria developer and he agrees that this
            // should not be reported with a Level of Error, so here
            // I just ignore it. But there are other 1478 messages
            // that it's better to show.
            if (! (isset($_POST['new_tbl_storage_engine'])
                && $_POST['new_tbl_storage_engine'] == 'MyISAM'
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
    public function getQueryAndResultForPartition()
    {
        $sql_query = 'ALTER TABLE '
            . Util::backquote($GLOBALS['table']) . ' '
            . $_POST['partition_operation']
            . ' PARTITION ';

        if ($_POST['partition_operation'] == 'COALESCE') {
            $sql_query .= count($_POST['partition_name']);
        } else {
            $sql_query .= implode(', ', $_POST['partition_name']) . ';';
        }

        $result = $GLOBALS['dbi']->query($sql_query);

        return array($sql_query, $result);
    }

    /**
     * Adjust the privileges after renaming/moving a table
     *
     * @param string $oldDb    Database name before table renaming/moving table
     * @param string $oldTable Table name before table renaming/moving table
     * @param string $newDb    Database name after table renaming/ moving table
     * @param string $newTable Table name after table renaming/moving table
     *
     * @return void
     */
    public function adjustPrivilegesRenameOrMoveTable($oldDb, $oldTable, $newDb, $newTable)
    {
        if ($GLOBALS['table_priv'] && $GLOBALS['col_priv']
            && $GLOBALS['is_reload_priv']
        ) {
            $GLOBALS['dbi']->selectDb('mysql');

            // For table specific privileges
            $query_table_specific = 'UPDATE ' . Util::backquote('tables_priv')
                . 'SET Db = \'' . $GLOBALS['dbi']->escapeString($newDb) . '\', Table_name = \'' . $GLOBALS['dbi']->escapeString($newTable)
                . '\' where Db = \'' . $GLOBALS['dbi']->escapeString($oldDb) . '\' AND Table_name = \'' . $GLOBALS['dbi']->escapeString($oldTable)
                . '\';';
            $GLOBALS['dbi']->query($query_table_specific);

            // For column specific privileges
            $query_col_specific = 'UPDATE ' . Util::backquote('columns_priv')
                . 'SET Db = \'' . $GLOBALS['dbi']->escapeString($newDb) . '\', Table_name = \'' . $GLOBALS['dbi']->escapeString($newTable)
                . '\' where Db = \'' . $GLOBALS['dbi']->escapeString($oldDb) . '\' AND Table_name = \'' . $GLOBALS['dbi']->escapeString($oldTable)
                . '\';';
            $GLOBALS['dbi']->query($query_col_specific);

            // Finally FLUSH the new privileges
            $flush_query = "FLUSH PRIVILEGES;";
            $GLOBALS['dbi']->query($flush_query);
        }
    }

    /**
     * Adjust the privileges after copying a table
     *
     * @param string $oldDb    Database name before table copying
     * @param string $oldTable Table name before table copying
     * @param string $newDb    Database name after table copying
     * @param string $newTable Table name after table copying
     *
     * @return void
     */
    public function adjustPrivilegesCopyTable($oldDb, $oldTable, $newDb, $newTable)
    {
        if ($GLOBALS['table_priv'] && $GLOBALS['col_priv']
            && $GLOBALS['is_reload_priv']
        ) {
            $GLOBALS['dbi']->selectDb('mysql');

            // For Table Specific privileges
            $query_table_specific_old = 'SELECT * FROM '
                . Util::backquote('tables_priv') . ' where '
                . 'Db = "' . $oldDb . '" AND Table_name = "' . $oldTable . '";';

            $old_privs_table = $GLOBALS['dbi']->fetchResult(
                $query_table_specific_old,
                0
            );

            foreach ($old_privs_table as $old_priv) {
                $newDb_table_privs_query = 'INSERT INTO '
                    . Util::backquote('tables_priv') . ' VALUES("'
                    . $old_priv[0] . '", "' . $newDb . '", "' . $old_priv[2] . '", "'
                    . $newTable . '", "' . $old_priv[4] . '", "' . $old_priv[5]
                    . '", "' . $old_priv[6] . '", "' . $old_priv[7] . '");';

                $GLOBALS['dbi']->query($newDb_table_privs_query);
            }

            // For Column Specific privileges
            $query_col_specific_old = 'SELECT * FROM '
                . Util::backquote('columns_priv') . ' WHERE '
                . 'Db = "' . $oldDb . '" AND Table_name = "' . $oldTable . '";';

            $old_privs_col = $GLOBALS['dbi']->fetchResult(
                $query_col_specific_old,
                0
            );

            foreach ($old_privs_col as $old_priv) {
                $newDb_col_privs_query = 'INSERT INTO '
                    . Util::backquote('columns_priv') . ' VALUES("'
                    . $old_priv[0] . '", "' . $newDb . '", "' . $old_priv[2] . '", "'
                    . $newTable . '", "' . $old_priv[4] . '", "' . $old_priv[5]
                    . '", "' . $old_priv[6] . '");';

                $GLOBALS['dbi']->query($newDb_col_privs_query);
            }

            // Finally FLUSH the new privileges
            $flush_query = "FLUSH PRIVILEGES;";
            $GLOBALS['dbi']->query($flush_query);
        }
    }

    /**
     * Change all collations and character sets of all columns in table
     *
     * @param string $db            Database name
     * @param string $table         Table name
     * @param string $tbl_collation Collation Name
     *
     * @return void
     */
    public function changeAllColumnsCollation($db, $table, $tbl_collation)
    {
        $GLOBALS['dbi']->selectDb($db);

        $change_all_collations_query = 'ALTER TABLE '
            . Util::backquote($table)
            . ' CONVERT TO';

        list($charset) = explode('_', $tbl_collation);

        $change_all_collations_query .= ' CHARACTER SET ' . $charset
            . ($charset == $tbl_collation ? '' : ' COLLATE ' . $tbl_collation);

        $GLOBALS['dbi']->query($change_all_collations_query);
    }

    /**
     * Move or copy a table
     *
     * @param string $db    current database name
     * @param string $table current table name
     *
     * @return void
     */
    public function moveOrCopyTable($db, $table)
    {
        /**
         * Selects the database to work with
         */
        $GLOBALS['dbi']->selectDb($db);

        /**
         * $_POST['target_db'] could be empty in case we came from an input field
         * (when there are many databases, no drop-down)
         */
        if (empty($_POST['target_db'])) {
            $_POST['target_db'] = $db;
        }

        /**
         * A target table name has been sent to this script -> do the work
         */
        if (Core::isValid($_POST['new_name'])) {
            if ($db == $_POST['target_db'] && $table == $_POST['new_name']) {
                if (isset($_POST['submit_move'])) {
                    $message = Message::error(__('Can\'t move table to same one!'));
                } else {
                    $message = Message::error(__('Can\'t copy table to same one!'));
                }
            } else {
                Table::moveCopy(
                    $db, $table, $_POST['target_db'], $_POST['new_name'],
                    $_POST['what'], isset($_POST['submit_move']), 'one_table'
                );

                if (isset($_POST['adjust_privileges'])
                    && ! empty($_POST['adjust_privileges'])
                ) {
                    if (isset($_POST['submit_move'])) {
                        $this->adjustPrivilegesRenameOrMoveTable(
                            $db, $table, $_POST['target_db'], $_POST['new_name']
                        );
                    } else {
                        $this->adjustPrivilegesCopyTable(
                            $db, $table, $_POST['target_db'], $_POST['new_name']
                        );
                    }

                    if (isset($_POST['submit_move'])) {
                        $message = Message::success(
                            __(
                                'Table %s has been moved to %s. Privileges have been '
                                . 'adjusted.'
                            )
                        );
                    } else {
                        $message = Message::success(
                            __(
                                'Table %s has been copied to %s. Privileges have been '
                                . 'adjusted.'
                            )
                        );
                    }

                } else {
                    if (isset($_POST['submit_move'])) {
                        $message = Message::success(
                            __('Table %s has been moved to %s.')
                        );
                    } else {
                        $message = Message::success(
                            __('Table %s has been copied to %s.')
                        );
                    }
                }

                $old = Util::backquote($db) . '.'
                    . Util::backquote($table);
                $message->addParam($old);

                $new_name = $_POST['new_name'];
                if ($GLOBALS['dbi']->getLowerCaseNames() === '1') {
                    $new_name = strtolower($new_name);
                }

                $GLOBALS['table'] = $new_name;

                $new = Util::backquote($_POST['target_db']) . '.'
                    . Util::backquote($new_name);
                $message->addParam($new);

                /* Check: Work on new table or on old table? */
                if (isset($_POST['submit_move'])
                    || Core::isValid($_POST['switch_to_new'])
                ) {
                }
            }
        } else {
            /**
             * No new name for the table!
             */
            $message = Message::error(__('The table name is empty!'));
        }

        $response = Response::getInstance();
        if ($response->isAjax()) {
            $response->addJSON('message', $message);
            if ($message->isSuccess()) {
                $response->addJSON('db', $GLOBALS['db']);
            } else {
                $response->setRequestStatus(false);
            }
            exit;
        }
    }
}
