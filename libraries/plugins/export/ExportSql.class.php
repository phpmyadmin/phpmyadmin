<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Set of functions used to build SQL dumps of tables
 *
 * @package    PhpMyAdmin-Export
 * @subpackage SQL
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* Get the export interface */
require_once 'libraries/plugins/ExportPlugin.class.php';

/**
 * Handles the export for the SQL class
 *
 * @package    PhpMyAdmin-Export
 * @subpackage SQL
 */
class ExportSql extends ExportPlugin
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->setProperties();

        // Avoids undefined variables, use NULL so isset() returns false
        if (! isset($GLOBALS['sql_backquotes'])) {
            $GLOBALS['sql_backquotes'] = null;
        }
    }

    /**
     * Sets the export SQL properties
     *
     * @return void
     */
    protected function setProperties()
    {
        global $plugin_param;

        $hide_sql = false;
        $hide_structure = false;
        if ($plugin_param['export_type'] == 'table'
            && ! $plugin_param['single_table']
        ) {
            $hide_structure = true;
            $hide_sql = true;
        }

        if (! $hide_sql) {
            $props = 'libraries/properties/';
            include_once "$props/plugins/ExportPluginProperties.class.php";
            include_once "$props/options/groups/OptionsPropertyRootGroup.class.php";
            include_once "$props/options/groups/OptionsPropertyMainGroup.class.php";
            include_once "$props/options/groups/OptionsPropertySubgroup.class.php";
            include_once "$props/options/items/BoolPropertyItem.class.php";
            include_once "$props/options/items/MessageOnlyPropertyItem.class.php";
            include_once "$props/options/items/RadioPropertyItem.class.php";
            include_once "$props/options/items/SelectPropertyItem.class.php";
            include_once "$props/options/items/TextPropertyItem.class.php";
            include_once "$props/options/items/NumberPropertyItem.class.php";

            $exportPluginProperties = new ExportPluginProperties();
            $exportPluginProperties->setText('SQL');
            $exportPluginProperties->setExtension('sql');
            $exportPluginProperties->setMimeType('text/x-sql');
            $exportPluginProperties->setOptionsText(__('Options'));

            // create the root group that will be the options field for
            // $exportPluginProperties
            // this will be shown as "Format specific options"
            $exportSpecificOptions = new OptionsPropertyRootGroup();
            $exportSpecificOptions->setName("Format Specific Options");

            // general options main group
            $generalOptions = new OptionsPropertyMainGroup();
            $generalOptions->setName("general_opts");

            // comments
            $subgroup = new OptionsPropertySubgroup();
            $subgroup->setName("include_comments");
            $leaf = new BoolPropertyItem();
            $leaf->setName('include_comments');
            $leaf->setText(
                __(
                    'Display comments <i>(includes info such as export'
                    . ' timestamp, PHP version, and server version)</i>'
                )
            );
            $subgroup->setSubgroupHeader($leaf);

            $leaf = new TextPropertyItem();
            $leaf->setName('header_comment');
            $leaf->setText(
                __('Additional custom header comment (\n splits lines):')
            );
            $subgroup->addProperty($leaf);
            $leaf = new BoolPropertyItem();
            $leaf->setName('dates');
            $leaf->setText(
                __(
                    'Include a timestamp of when databases were created, last'
                    . ' updated, and last checked'
                )
            );
            $subgroup->addProperty($leaf);
            if (! empty($GLOBALS['cfgRelation']['relation'])) {
                $leaf = new BoolPropertyItem();
                $leaf->setName('relation');
                $leaf->setText(__('Display foreign key relationships'));
                $subgroup->addProperty($leaf);
            }
            if (! empty($GLOBALS['cfgRelation']['mimework'])) {
                $leaf = new BoolPropertyItem();
                $leaf->setName('mime');
                $leaf->setText(__('Display MIME types'));
                $subgroup->addProperty($leaf);
            }
            $generalOptions->addProperty($subgroup);

            // enclose in a transaction
            $leaf = new BoolPropertyItem();
            $leaf->setName("use_transaction");
            $leaf->setText(__('Enclose export in a transaction'));
            $leaf->setDoc(
                array(
                    'programs',
                    'mysqldump',
                    'option_mysqldump_single-transaction'
                )
            );
            $generalOptions->addProperty($leaf);

            // disable foreign key checks
            $leaf = new BoolPropertyItem();
            $leaf->setName("disable_fk");
            $leaf->setText(__('Disable foreign key checks'));
            $leaf->setDoc(
                array(
                    'manual_MySQL_Database_Administration',
                    'server-system-variables',
                    'sysvar_foreign_key_checks'
                )
            );
            $generalOptions->addProperty($leaf);

            // export views as tables
            $leaf = new BoolPropertyItem();
            $leaf->setName("views_as_tables");
            $leaf->setText(__('Export views as tables'));
            $generalOptions->addProperty($leaf);

            // compatibility maximization
            $compats = $GLOBALS['dbi']->getCompatibilities();
            if (count($compats) > 0) {
                $values = array();
                foreach ($compats as $val) {
                    $values[$val] = $val;
                }

                $leaf = new SelectPropertyItem();
                $leaf->setName("compatibility");
                $leaf->setText(
                    __(
                        'Database system or older MySQL server to maximize output'
                        . ' compatibility with:'
                    )
                );
                $leaf->setValues($values);
                $leaf->setDoc(
                    array(
                        'manual_MySQL_Database_Administration',
                        'Server_SQL_mode'
                    )
                );
                $generalOptions->addProperty($leaf);

                unset($values);
            }

            // server export options
            if ($plugin_param['export_type'] == 'server') {
                $leaf = new BoolPropertyItem();
                $leaf->setName("drop_database");
                $leaf->setText(
                    sprintf(__('Add %s statement'), '<code>DROP DATABASE</code>')
                );
                $generalOptions->addProperty($leaf);
            }

            // what to dump (structure/data/both)
            $subgroup = new OptionsPropertySubgroup();
            $subgroup->setName("dump_table");
            $subgroup->setText("Dump table");
            $leaf = new RadioPropertyItem();
            $leaf->setName('structure_or_data');
            $leaf->setValues(
                array(
                    'structure' => __('structure'),
                    'data' => __('data'),
                    'structure_and_data' => __('structure and data')
                )
            );
            $subgroup->setSubgroupHeader($leaf);
            $generalOptions->addProperty($subgroup);

            // add the main group to the root group
            $exportSpecificOptions->addProperty($generalOptions);


            // structure options main group
            if (! $hide_structure) {
                $structureOptions = new OptionsPropertyMainGroup();
                $structureOptions->setName("structure");
                $structureOptions->setText(__('Object creation options'));
                $structureOptions->setForce('data');

                // begin SQL Statements
                $subgroup = new OptionsPropertySubgroup();
                $leaf = new MessageOnlyPropertyItem();
                $leaf->setName('add_statements');
                $leaf->setText(__('Add statements:'));
                $subgroup->setSubgroupHeader($leaf);

                if ($plugin_param['export_type'] != 'table') {
                    $leaf = new BoolPropertyItem();
                    $leaf->setName('create_database');
                    $create_clause = '<code>CREATE DATABASE / USE</code>';
                    $leaf->setText(sprintf(__('Add %s statement'), $create_clause));
                    $subgroup->addProperty($leaf);
                }

                if ($plugin_param['export_type'] == 'table') {
                    if (PMA_Table::isView($GLOBALS['db'], $GLOBALS['table'])) {
                        $drop_clause = '<code>DROP VIEW</code>';
                    } else {
                        $drop_clause = '<code>DROP TABLE</code>';
                    }
                } else {
                    if (PMA_DRIZZLE) {
                        $drop_clause = '<code>DROP TABLE</code>';
                    } else {
                        $drop_clause = '<code>DROP TABLE / VIEW / PROCEDURE'
                            . ' / FUNCTION</code>';
                        if (PMA_MYSQL_INT_VERSION > 50100) {
                            $drop_clause .= '<code> / EVENT</code>';
                        }
                    }
                }

                $drop_clause .= '<code> / TRIGGER</code>';

                $leaf = new BoolPropertyItem();
                $leaf->setName('drop_table');
                $leaf->setText(sprintf(__('Add %s statement'), $drop_clause));
                $subgroup->addProperty($leaf);

                // Add table structure option
                $leaf = new BoolPropertyItem();
                $leaf->setName('create_table');
                $leaf->setText(
                    sprintf(__('Add %s statement'), '<code>CREATE TABLE</code>')
                );
                $subgroup->addProperty($leaf);

                // Add view option
                $leaf = new BoolPropertyItem();
                $leaf->setName('create_view');
                $leaf->setText(
                    sprintf(__('Add %s statement'), '<code>CREATE VIEW</code>')
                );
                $subgroup->addProperty($leaf);

                // Drizzle doesn't support procedures and functions
                if (! PMA_DRIZZLE) {
                    $leaf = new BoolPropertyItem();
                    $leaf->setName('procedure_function');
                    $leaf->setText(
                        sprintf(
                            __('Add %s statement'),
                            '<code>CREATE PROCEDURE / FUNCTION'
                            . (PMA_MYSQL_INT_VERSION > 50100
                            ? ' / EVENT</code>' : '</code>')
                        )
                    );
                    $subgroup->addProperty($leaf);
                }

                // Add triggers option
                $leaf = new BoolPropertyItem();
                $leaf->setName('create_trigger');
                $leaf->setText(
                    sprintf(__('Add %s statement'), '<code>CREATE TRIGGER</code>')
                );
                $subgroup->addProperty($leaf);

                // begin CREATE TABLE statements
                $subgroup_create_table = new OptionsPropertySubgroup();
                $leaf = new BoolPropertyItem();
                $leaf->setName('create_table_statements');
                $leaf->setText(__('<code>CREATE TABLE</code> options:'));
                $subgroup_create_table->setSubgroupHeader($leaf);
                $leaf = new BoolPropertyItem();
                $leaf->setName('if_not_exists');
                $leaf->setText('<code>IF NOT EXISTS</code>');
                $subgroup_create_table->addProperty($leaf);
                $leaf = new BoolPropertyItem();
                $leaf->setName('auto_increment');
                $leaf->setText('<code>AUTO_INCREMENT</code>');
                $subgroup_create_table->addProperty($leaf);
                $subgroup->addProperty($subgroup_create_table);
                $structureOptions->addProperty($subgroup);

                $leaf = new BoolPropertyItem();
                $leaf->setName("backquotes");
                $leaf->setText(
                    __(
                        'Enclose table and column names with backquotes '
                        . '<i>(Protects column and table names formed with'
                        . ' special characters or keywords)</i>'
                    )
                );

                $structureOptions->addProperty($leaf);

                // add the main group to the root group
                $exportSpecificOptions->addProperty($structureOptions);
            }


            // begin Data options
            $dataOptions = new OptionsPropertyMainGroup();
            $dataOptions->setName("data");
            $dataOptions->setText(__('Data creation options'));
            $dataOptions->setForce('structure');
            $leaf = new BoolPropertyItem();
            $leaf->setName("truncate");
            $leaf->setText(__('Truncate table before insert'));
            $dataOptions->addProperty($leaf);

            // begin SQL Statements
            $subgroup = new OptionsPropertySubgroup();
            $leaf = new MessageOnlyPropertyItem();
            $leaf->setText(__('Instead of <code>INSERT</code> statements, use:'));
            $subgroup->setSubgroupHeader($leaf);
            // Not supported in Drizzle
            if (! PMA_DRIZZLE) {
                $leaf = new BoolPropertyItem();
                $leaf->setName("delayed");
                $leaf->setText(__('<code>INSERT DELAYED</code> statements'));
                $leaf->setDoc(
                    array(
                        'manual_MySQL_Database_Administration',
                        'insert_delayed'
                    )
                );
                $subgroup->addProperty($leaf);
            }
            $leaf = new BoolPropertyItem();
            $leaf->setName("ignore");
            $leaf->setText(__('<code>INSERT IGNORE</code> statements'));
            $leaf->setDoc(
                array(
                        'manual_MySQL_Database_Administration',
                        'insert'
                )
            );
            $subgroup->addProperty($leaf);
            $dataOptions->addProperty($subgroup);

            // Function to use when dumping dat
            $leaf = new SelectPropertyItem();
            $leaf->setName("type");
            $leaf->setText(__('Function to use when dumping data:'));
            $leaf->setValues(
                array(
                    'INSERT' => 'INSERT',
                    'UPDATE' => 'UPDATE',
                    'REPLACE' => 'REPLACE'
                )
            );
            $dataOptions->addProperty($leaf);

            /* Syntax to use when inserting data */
            $subgroup = new OptionsPropertySubgroup();
            $leaf = new MessageOnlyPropertyItem();
            $leaf->setText(__('Syntax to use when inserting data:'));
            $subgroup->setSubgroupHeader($leaf);
            $leaf = new RadioPropertyItem();
            $leaf->setName("insert_syntax");
            $leaf->setText(__('<code>INSERT IGNORE</code> statements'));
            $leaf->setValues(
                array(
                    'complete' => __(
                        'include column names in every <code>INSERT</code> statement'
                        . ' <br /> &nbsp; &nbsp; &nbsp; Example: <code>INSERT INTO'
                        . ' tbl_name (col_A,col_B,col_C) VALUES (1,2,3)</code>'
                    ),
                    'extended' => __(
                        'insert multiple rows in every <code>INSERT</code> statement'
                        . '<br /> &nbsp; &nbsp; &nbsp; Example: <code>INSERT INTO'
                        . ' tbl_name VALUES (1,2,3), (4,5,6), (7,8,9)</code>'
                    ),
                    'both' => __(
                        'both of the above<br /> &nbsp; &nbsp; &nbsp; Example:'
                        . ' <code>INSERT INTO tbl_name (col_A,col_B) VALUES (1,2,3),'
                        . ' (4,5,6), (7,8,9)</code>'
                    ),
                    'none' => __(
                        'neither of the above<br /> &nbsp; &nbsp; &nbsp; Example:'
                        . ' <code>INSERT INTO tbl_name VALUES (1,2,3)</code>'
                    )
                )
            );
            $subgroup->addProperty($leaf);
            $dataOptions->addProperty($subgroup);

            // Max length of query
            $leaf = new NumberPropertyItem();
            $leaf->setName("max_query_size");
            $leaf->setText(__('Maximal length of created query'));
            $dataOptions->addProperty($leaf);

            // Dump binary columns in hexadecimal
            $leaf = new BoolPropertyItem();
            $leaf->setName("hex_for_blob");
            $leaf->setText(
                __(
                    'Dump binary columns in hexadecimal notation'
                    . ' <i>(for example, "abc" becomes 0x616263)</i>'
                )
            );
            $dataOptions->addProperty($leaf);

            // Drizzle works only with UTC timezone
            if (! PMA_DRIZZLE) {
                // Dump time in UTC
                $leaf = new BoolPropertyItem();
                $leaf->setName("utc_time");
                $leaf->setText(
                    __(
                        'Dump TIMESTAMP columns in UTC <i>(enables TIMESTAMP columns'
                        . ' to be dumped and reloaded between servers in different'
                        . ' time zones)</i>'
                    )
                );
                $dataOptions->addProperty($leaf);
            }

            // add the main group to the root group
            $exportSpecificOptions->addProperty($dataOptions);

            // set the options for the export plugin property item
            $exportPluginProperties->setOptions($exportSpecificOptions);
            $this->properties = $exportPluginProperties;
        }
    }

    /**
     * This method is called when any PluginManager to which the observer
     * is attached calls PluginManager::notify()
     *
     * @param SplSubject $subject The PluginManager notifying the observer
     *                            of an update.
     *
     * @return void
     */
    public function update (SplSubject $subject)
    {
    }

    /**
     * Exports routines (procedures and functions)
     *
     * @param string $db Database
     *
     * @return bool Whether it succeeded
     */
    public function exportRoutines($db)
    {
        global $crlf;

        $text = '';
        $delimiter = '$$';

        $procedure_names = $GLOBALS['dbi']
            ->getProceduresOrFunctions($db, 'PROCEDURE');
        $function_names = $GLOBALS['dbi']->getProceduresOrFunctions($db, 'FUNCTION');

        if ($procedure_names || $function_names) {
            $text .= $crlf
                . 'DELIMITER ' . $delimiter . $crlf;
        }

        if ($procedure_names) {
            $text .=
                $this->_exportComment()
              . $this->_exportComment(__('Procedures'))
              . $this->_exportComment();

            foreach ($procedure_names as $procedure_name) {
                if (! empty($GLOBALS['sql_drop_table'])) {
                    $text .= 'DROP PROCEDURE IF EXISTS '
                        . PMA_Util::backquote($procedure_name)
                        . $delimiter . $crlf;
                }
                $text .= $GLOBALS['dbi']
                    ->getDefinition($db, 'PROCEDURE', $procedure_name)
                    . $delimiter . $crlf . $crlf;
            }
        }

        if ($function_names) {
            $text .=
                $this->_exportComment()
              . $this->_exportComment(__('Functions'))
              . $this->_exportComment();

            foreach ($function_names as $function_name) {
                if (! empty($GLOBALS['sql_drop_table'])) {
                    $text .= 'DROP FUNCTION IF EXISTS '
                        . PMA_Util::backquote($function_name)
                        . $delimiter . $crlf;
                }
                $text .= $GLOBALS['dbi']
                    ->getDefinition($db, 'FUNCTION', $function_name)
                    . $delimiter . $crlf . $crlf;
            }
        }

        if ($procedure_names || $function_names) {
            $text .= 'DELIMITER ;' . $crlf;
        }

        if (! empty($text)) {
            return PMA_exportOutputHandler($text);
        } else {
            return false;
        }
    }

    /**
     * Possibly outputs comment
     *
     * @param string $text Text of comment
     *
     * @return string The formatted comment
     */
    private function _exportComment($text = '')
    {
        if (isset($GLOBALS['sql_include_comments'])
            && $GLOBALS['sql_include_comments']
        ) {
            // see http://dev.mysql.com/doc/refman/5.0/en/ansi-diff-comments.html
            return '--' . (empty($text) ? '' : ' ') . $text . $GLOBALS['crlf'];
        } else {
            return '';
        }
    }

    /**
     * Possibly outputs CRLF
     *
     * @return string $crlf or nothing
     */
    private function _possibleCRLF()
    {
        if (isset($GLOBALS['sql_include_comments'])
            && $GLOBALS['sql_include_comments']
        ) {
            return $GLOBALS['crlf'];
        } else {
            return '';
        }
    }

    /**
     * Outputs export footer
     *
     * @return bool Whether it succeeded
     */
    public function exportFooter()
    {
        global $crlf, $mysql_charset_map;

        $foot = '';

        if (isset($GLOBALS['sql_disable_fk'])) {
            $foot .=  'SET FOREIGN_KEY_CHECKS=1;' . $crlf;
        }

        if (isset($GLOBALS['sql_use_transaction'])) {
            $foot .=  'COMMIT;' . $crlf;
        }

        // restore connection settings
        $charset_of_file = isset($GLOBALS['charset_of_file'])
            ? $GLOBALS['charset_of_file'] : '';
        if (! empty($GLOBALS['asfile'])
            && isset($mysql_charset_map[$charset_of_file])
            && ! PMA_DRIZZLE
        ) {
            $foot .=  $crlf
                . '/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;'
                . $crlf
                . '/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;'
                . $crlf
                . '/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;'
                . $crlf;
        }

        /* Restore timezone */
        if (isset($GLOBALS['sql_utc_time']) && $GLOBALS['sql_utc_time']) {
            $GLOBALS['dbi']->query('SET time_zone = "' . $GLOBALS['old_tz'] . '"');
        }

        return PMA_exportOutputHandler($foot);
    }

    /**
     * Outputs export header. It is the first method to be called, so all
     * the required variables are initialized here.
     *
     * @return bool Whether it succeeded
     */
    public function exportHeader()
    {
        global $crlf, $cfg;
        global $mysql_charset_map;

        if (isset($GLOBALS['sql_compatibility'])) {
            $tmp_compat = $GLOBALS['sql_compatibility'];
            if ($tmp_compat == 'NONE') {
                $tmp_compat = '';
            }
            $GLOBALS['dbi']->tryQuery('SET SQL_MODE="' . $tmp_compat . '"');
            unset($tmp_compat);
        }
        $head  =  $this->_exportComment('phpMyAdmin SQL Dump')
               .  $this->_exportComment('version ' . PMA_VERSION)
               .  $this->_exportComment('http://www.phpmyadmin.net')
               .  $this->_exportComment();
        $host_string = __('Host:') . ' ' .  $cfg['Server']['host'];
        if (! empty($cfg['Server']['port'])) {
            $host_string .= ':' . $cfg['Server']['port'];
        }
        $head .= $this->_exportComment($host_string);
        $head .=
            $this->_exportComment(
                __('Generation Time:') . ' '
                .  PMA_Util::localisedDate()
            )
            .  $this->_exportComment(
                __('Server version:') . ' ' . PMA_MYSQL_STR_VERSION
            )
            .  $this->_exportComment(__('PHP Version:') . ' ' . phpversion())
            .  $this->_possibleCRLF();

        if (isset($GLOBALS['sql_header_comment'])
            && ! empty($GLOBALS['sql_header_comment'])
        ) {
            // '\n' is not a newline (like "\n" would be), it's the characters
            // backslash and n, as explained on the export interface
            $lines = explode('\n', $GLOBALS['sql_header_comment']);
            $head .= $this->_exportComment();
            foreach ($lines as $one_line) {
                $head .= $this->_exportComment($one_line);
            }
            $head .= $this->_exportComment();
        }

        if (isset($GLOBALS['sql_disable_fk'])) {
            $head .= 'SET FOREIGN_KEY_CHECKS=0;' . $crlf;
        }

        // We want exported AUTO_INCREMENT columns to have still same value,
        // do this only for recent MySQL exports
        if ((! isset($GLOBALS['sql_compatibility'])
            || $GLOBALS['sql_compatibility'] == 'NONE')
            && ! PMA_DRIZZLE
        ) {
            $head .= 'SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";' . $crlf;
        }

        if (isset($GLOBALS['sql_use_transaction'])) {
            $head .= 'SET AUTOCOMMIT = 0;' . $crlf
                   . 'START TRANSACTION;' . $crlf;
        }

        /* Change timezone if we should export timestamps in UTC */
        if (isset($GLOBALS['sql_utc_time']) && $GLOBALS['sql_utc_time']) {
            $head .= 'SET time_zone = "+00:00";' . $crlf;
            $GLOBALS['old_tz'] = $GLOBALS['dbi']
                ->fetchValue('SELECT @@session.time_zone');
            $GLOBALS['dbi']->query('SET time_zone = "+00:00"');
        }

        $head .= $this->_possibleCRLF();

        if (! empty($GLOBALS['asfile']) && ! PMA_DRIZZLE) {
            // we are saving as file, therefore we provide charset information
            // so that a utility like the mysql client can interpret
            // the file correctly
            if (isset($GLOBALS['charset_of_file'])
                && isset($mysql_charset_map[$GLOBALS['charset_of_file']])
            ) {
                // we got a charset from the export dialog
                $set_names = $mysql_charset_map[$GLOBALS['charset_of_file']];
            } else {
                // by default we use the connection charset
                $set_names = $mysql_charset_map['utf-8'];
            }
            $head .=  $crlf
                . '/*!40101 SET @OLD_CHARACTER_SET_CLIENT='
                . '@@CHARACTER_SET_CLIENT */;' . $crlf
                . '/*!40101 SET @OLD_CHARACTER_SET_RESULTS='
                . '@@CHARACTER_SET_RESULTS */;' . $crlf
                . '/*!40101 SET @OLD_COLLATION_CONNECTION='
                . '@@COLLATION_CONNECTION */;' . $crlf
                . '/*!40101 SET NAMES ' . $set_names . ' */;' . $crlf . $crlf;
        }

        return PMA_exportOutputHandler($head);
    }

    /**
     * Outputs CREATE DATABASE statement
     *
     * @param string $db Database name
     *
     * @return bool Whether it succeeded
     */
    public function exportDBCreate($db)
    {
        global $crlf;

        if (isset($GLOBALS['sql_compatibility'])) {
            $compat = $GLOBALS['sql_compatibility'];
        } else {
            $compat = 'NONE';
        }
        if (isset($GLOBALS['sql_drop_database'])) {
            if (! PMA_exportOutputHandler(
                'DROP DATABASE '
                . (isset($GLOBALS['sql_backquotes'])
                ? PMA_Util::backquoteCompat($db, $compat) : $db)
                . ';' . $crlf
            )) {
                return false;
            }
        }
        if (isset($GLOBALS['sql_create_database'])) {
            $create_query = 'CREATE DATABASE IF NOT EXISTS '
                . (isset($GLOBALS['sql_backquotes'])
                ? PMA_Util::backquoteCompat($db, $compat) : $db);
            $collation = PMA_getDbCollation($db);
            if (PMA_DRIZZLE) {
                $create_query .= ' COLLATE ' . $collation;
            } else {
                if (strpos($collation, '_')) {
                    $create_query .= ' DEFAULT CHARACTER SET '
                        . substr($collation, 0, strpos($collation, '_'))
                        . ' COLLATE ' . $collation;
                } else {
                    $create_query .= ' DEFAULT CHARACTER SET ' . $collation;
                }
            }
            $create_query .= ';' . $crlf;
            if (! PMA_exportOutputHandler($create_query)) {
                return false;
            }
            if (isset($GLOBALS['sql_backquotes'])
                && ((isset($GLOBALS['sql_compatibility'])
                && $GLOBALS['sql_compatibility'] == 'NONE')
                || PMA_DRIZZLE)
            ) {
                $result = PMA_exportOutputHandler(
                    'USE ' . PMA_Util::backquoteCompat($db, $compat)
                    . ';' . $crlf
                );
            } else {
                $result = PMA_exportOutputHandler('USE ' . $db . ';' . $crlf);
            }
            return $result;
        } else {
            return true;
        }
    }

    /**
     * Outputs database header
     *
     * @param string $db Database name
     *
     * @return bool Whether it succeeded
     */
    public function exportDBHeader($db)
    {
        if (isset($GLOBALS['sql_compatibility'])) {
            $compat = $GLOBALS['sql_compatibility'];
        } else {
            $compat = 'NONE';
        }
        $head = $this->_exportComment()
            . $this->_exportComment(
                __('Database:') . ' '
                . (isset($GLOBALS['sql_backquotes'])
                ? PMA_Util::backquoteCompat($db, $compat)
                : '\'' . $db . '\'')
            )
            . $this->_exportComment();
        return PMA_exportOutputHandler($head);
    }

    /**
     * Outputs database footer
     *
     * @param string $db Database name
     *
     * @return bool Whether it succeeded
     */
    public function exportDBFooter($db)
    {
        global $crlf;

        $result = true;

        //add indexes to the sql dump file
        if (isset($GLOBALS['sql_indexes'])) {
            $result = PMA_exportOutputHandler($GLOBALS['sql_indexes']);
            unset($GLOBALS['sql_indexes']);
        }
        //add auto increments to the sql dump file
        if (isset($GLOBALS['sql_auto_increments'])) {
            $result = PMA_exportOutputHandler($GLOBALS['sql_auto_increments']);
            unset($GLOBALS['sql_auto_increments']);
        }
        //add constraints to the sql dump file
        if (isset($GLOBALS['sql_constraints'])) {
            $result = PMA_exportOutputHandler($GLOBALS['sql_constraints']);
            unset($GLOBALS['sql_constraints']);
        }

        if (($GLOBALS['sql_structure_or_data'] == 'structure'
            || $GLOBALS['sql_structure_or_data'] == 'structure_and_data')
            && isset($GLOBALS['sql_procedure_function'])
        ) {
            $text = '';
            $delimiter = '$$';

            if (PMA_MYSQL_INT_VERSION > 50100) {
                $event_names = $GLOBALS['dbi']->fetchResult(
                    'SELECT EVENT_NAME FROM information_schema.EVENTS WHERE'
                    . ' EVENT_SCHEMA= \''
                    . PMA_Util::sqlAddSlashes($db, true)
                    . '\';'
                );
            } else {
                $event_names = array();
            }

            if ($event_names) {
                $text .= $crlf
                  . 'DELIMITER ' . $delimiter . $crlf;

                $text .=
                    $this->_exportComment()
                    . $this->_exportComment(__('Events'))
                    . $this->_exportComment();

                foreach ($event_names as $event_name) {
                    if (! empty($GLOBALS['sql_drop_table'])) {
                        $text .= 'DROP EVENT '
                            . PMA_Util::backquote($event_name)
                            . $delimiter . $crlf;
                    }
                    $text .= $GLOBALS['dbi']
                        ->getDefinition($db, 'EVENT', $event_name)
                        . $delimiter . $crlf . $crlf;
                }

                $text .= 'DELIMITER ;' . $crlf;
            }

            if (! empty($text)) {
                $result = PMA_exportOutputHandler($text);
            }
        }
        return $result;
    }

    /**
     * Returns a stand-in CREATE definition to resolve view dependencies
     *
     * @param string $db   the database name
     * @param string $view the view name
     * @param string $crlf the end of line sequence
     *
     * @return string resulting definition
     */
    public function getTableDefStandIn($db, $view, $crlf)
    {
        $create_query = '';
        if (! empty($GLOBALS['sql_drop_table'])) {
            $create_query .= 'DROP VIEW IF EXISTS '
                . PMA_Util::backquote($view)
                . ';' . $crlf;
        }

        $create_query .= 'CREATE TABLE ';

        if (isset($GLOBALS['sql_if_not_exists'])
            && $GLOBALS['sql_if_not_exists']
        ) {
            $create_query .= 'IF NOT EXISTS ';
        }
        $create_query .= PMA_Util::backquote($view) . ' (' . $crlf;
        $tmp = array();
        $columns = $GLOBALS['dbi']->getColumnsFull($db, $view);
        foreach ($columns as $column_name => $definition) {
            $tmp[] = PMA_Util::backquote($column_name) . ' ' .
                $definition['Type'] . $crlf;
        }
        $create_query .= implode(',', $tmp) . ');';
        return($create_query);
    }

    /**
     * Returns CREATE definition that matches $view's structure
     *
     * @param string $db            the database name
     * @param string $view          the view name
     * @param string $crlf          the end of line sequence
     * @param bool   $add_semicolon whether to add semicolon and end-of-line at
     *                              the end
     *
     * @return string resulting schema
     */
    private function _getTableDefForView(
        $db,
        $view,
        $crlf,
        $add_semicolon = true
    ) {
        $create_query = "CREATE TABLE";
        if (isset($GLOBALS['sql_if_not_exists'])) {
            $create_query .= " IF NOT EXISTS ";
        }
        $create_query .= PMA_Util::backquote($view) . "(" . $crlf;

        $columns = $GLOBALS['dbi']->getColumns($db, $view, null, true);

        $firstCol = true;
        foreach ($columns as $column) {
            $extracted_columnspec = PMA_Util::extractColumnSpec($column['Type']);

            if (! $firstCol) {
                $create_query .= "," . $crlf;
            }
            $create_query .= "    " . PMA_Util::backquote($column['Field']);
            $create_query .= " " . $column['Type'];
            if ($extracted_columnspec['can_contain_collation']
                && ! empty($column['Collation'])
            ) {
                $create_query .= " COLLATE " . $column['Collation'];
            }
            if ($column['Null'] == 'NO') {
                $create_query .= " NOT NULL";
            }
            if (isset($column['Default'])) {
                 $create_query .= " DEFAULT '"
                     . PMA_Util::sqlAddSlashes($column['Default']) . "'";
            } else if ($column['Null'] == 'YES') {
                 $create_query .= " DEFAULT NULL";
            }
            if (! empty($column['Comment'])) {
                $create_query .= " COMMENT '"
                    . PMA_Util::sqlAddSlashes($column['Comment']) . "'";
            }
            $firstCol = false;
        }
        $create_query .= $crlf . ")" . ($add_semicolon ? ';' : '') . $crlf;

        if (isset($GLOBALS['sql_compatibility'])) {
            $compat = $GLOBALS['sql_compatibility'];
        } else {
            $compat = 'NONE';
        }
        if ($compat == 'MSSQL') {
            $create_query = $this->_makeCreateTableMSSQLCompatible(
                $create_query
            );
        }
        return $create_query;
    }

    /**
     * Returns $table's CREATE definition
     *
     * @param string $db                        the database name
     * @param string $table                     the table name
     * @param string $crlf                      the end of line sequence
     * @param string $error_url                 the url to go back in case
     *                                          of error
     * @param bool   $show_dates                whether to include creation/
     *                                          update/check dates
     * @param bool   $add_semicolon             whether to add semicolon and 
     *                                          end-of-line at the end
     * @param bool   $view                      whether we're handling a view
     * @param bool   $update_indexes_increments whether we need to update
     *                                          two global variables 
     *
     * @return string resulting schema
     */
    public function getTableDef(
        $db,
        $table,
        $crlf,
        $error_url,
        $show_dates = false,
        $add_semicolon = true,
        $view = false,
        $update_indexes_increments = true
    ) {
        global $sql_drop_table, $sql_backquotes, $sql_constraints,
            $sql_constraints_query, $sql_indexes, $sql_indexes_query,
            $sql_auto_increments,$sql_drop_foreign_keys;

        $schema_create = '';
        $auto_increment = '';
        $new_crlf = $crlf;

        if (isset($GLOBALS['sql_compatibility'])) {
            $compat = $GLOBALS['sql_compatibility'];
        } else {
            $compat = 'NONE';
        }

        // need to use PMA_DatabaseInterface::QUERY_STORE
        // with $GLOBALS['dbi']->numRows() in mysqli
        $result = $GLOBALS['dbi']->query(
            'SHOW TABLE STATUS FROM ' . PMA_Util::backquote($db)
            . ' LIKE \'' . PMA_Util::sqlAddSlashes($table, true) . '\'',
            null,
            PMA_DatabaseInterface::QUERY_STORE
        );
        if ($result != false) {
            if ($GLOBALS['dbi']->numRows($result) > 0) {
                $tmpres = $GLOBALS['dbi']->fetchAssoc($result);
                if (PMA_DRIZZLE && $show_dates) {
                    // Drizzle doesn't give Create_time and Update_time in
                    // SHOW TABLE STATUS, add it
                    $sql ="SELECT
                            TABLE_CREATION_TIME AS Create_time,
                            TABLE_UPDATE_TIME AS Update_time
                        FROM data_dictionary.TABLES
                        WHERE TABLE_SCHEMA = '"
                        . PMA_Util::sqlAddSlashes($db) . "'
                          AND TABLE_NAME = '"
                        . PMA_Util::sqlAddSlashes($table) . "'";
                    $tmpres = array_merge(
                        $GLOBALS['dbi']->fetchSingleRow($sql), $tmpres
                    );
                }
                // Here we optionally add the AUTO_INCREMENT next value,
                // but starting with MySQL 5.0.24, the clause is already included
                // in SHOW CREATE TABLE so we'll remove it below
                // It's required for Drizzle because SHOW CREATE TABLE uses
                // the value from table's creation time
                if (isset($GLOBALS['sql_auto_increment'])
                    && ! empty($tmpres['Auto_increment'])
                ) {
                    $auto_increment .= ' AUTO_INCREMENT='
                        . $tmpres['Auto_increment'] . ' ';
                }

                if ($show_dates
                    && isset($tmpres['Create_time'])
                    && ! empty($tmpres['Create_time'])
                ) {
                    $schema_create .= $this->_exportComment(
                        __('Creation:') . ' '
                        . PMA_Util::localisedDate(
                            strtotime($tmpres['Create_time'])
                        )
                    );
                    $new_crlf = $this->_exportComment() . $crlf;
                }

                if ($show_dates
                    && isset($tmpres['Update_time'])
                    && ! empty($tmpres['Update_time'])
                ) {
                    $schema_create .= $this->_exportComment(
                        __('Last update:') . ' '
                        . PMA_Util::localisedDate(
                            strtotime($tmpres['Update_time'])
                        )
                    );
                    $new_crlf = $this->_exportComment() . $crlf;
                }

                if ($show_dates
                    && isset($tmpres['Check_time'])
                    && ! empty($tmpres['Check_time'])
                ) {
                    $schema_create .= $this->_exportComment(
                        __('Last check:') . ' '
                        . PMA_Util::localisedDate(
                            strtotime($tmpres['Check_time'])
                        )
                    );
                    $new_crlf = $this->_exportComment() . $crlf;
                }
            }
            $GLOBALS['dbi']->freeResult($result);
        }

        $schema_create .= $new_crlf;

        // no need to generate a DROP VIEW here, it was done earlier
        if (! empty($sql_drop_table) && ! PMA_Table::isView($db, $table)) {
            $schema_create .= 'DROP TABLE IF EXISTS '
                . PMA_Util::backquote($table, $sql_backquotes) . ';'
                . $crlf;
        }

        // Complete table dump,
        // Whether to quote table and column names or not
        // Drizzle always quotes names
        if (! PMA_DRIZZLE) {
            if ($sql_backquotes) {
                $GLOBALS['dbi']->query('SET SQL_QUOTE_SHOW_CREATE = 1');
            } else {
                $GLOBALS['dbi']->query('SET SQL_QUOTE_SHOW_CREATE = 0');
            }
        }

        // I don't see the reason why this unbuffered query could cause problems,
        // because SHOW CREATE TABLE returns only one row, and we free the
        // results below. Nonetheless, we got 2 user reports about this
        // (see bug 1562533) so I removed the unbuffered mode.
        // $result = $GLOBALS['dbi']->query('SHOW CREATE TABLE ' . backquote($db)
        // . '.' . backquote($table), null, PMA_DatabaseInterface::QUERY_UNBUFFERED);
        //
        // Note: SHOW CREATE TABLE, at least in MySQL 5.1.23, does not
        // produce a displayable result for the default value of a BIT
        // column, nor does the mysqldump command. See MySQL bug 35796
        $result = $GLOBALS['dbi']->tryQuery(
            'SHOW CREATE TABLE ' . PMA_Util::backquote($db) . '.'
            . PMA_Util::backquote($table)
        );
        // an error can happen, for example the table is crashed
        $tmp_error = $GLOBALS['dbi']->getError();
        if ($tmp_error) {
            return $this->_exportComment(__('in use') . '(' . $tmp_error . ')');
        }

        if ($result != false && ($row = $GLOBALS['dbi']->fetchRow($result))) {
            $create_query = $row[1];
            unset($row);

            // Convert end of line chars to one that we want (note that MySQL
            // doesn't return query it will accept in all cases)
            if (strpos($create_query, "(\r\n ")) {
                $create_query = str_replace("\r\n", $crlf, $create_query);
            } elseif (strpos($create_query, "(\n ")) {
                $create_query = str_replace("\n", $crlf, $create_query);
            } elseif (strpos($create_query, "(\r ")) {
                $create_query = str_replace("\r", $crlf, $create_query);
            }

            /*
             * Drop database name from VIEW creation.
             *
             * This is a bit tricky, but we need to issue SHOW CREATE TABLE with
             * database name, but we don't want name to show up in CREATE VIEW
             * statement.
             */
            if ($view) {
                $create_query = preg_replace(
                    '/' . preg_quote(PMA_Util::backquote($db)) . '\./',
                    '',
                    $create_query
                );
            }

            // Should we use IF NOT EXISTS?
            if (isset($GLOBALS['sql_if_not_exists'])) {
                $create_query = preg_replace(
                    '/^CREATE TABLE/',
                    'CREATE TABLE IF NOT EXISTS',
                    $create_query
                );
            }

            if ($compat == 'MSSQL') {
                $create_query = $this->_makeCreateTableMSSQLCompatible(
                    $create_query
                );
            }

            // Drizzle (checked on 2011.03.13) returns ROW_FORMAT surrounded
            // with quotes, which is not accepted by parser
            if (PMA_DRIZZLE) {
                $create_query = preg_replace(
                    '/ROW_FORMAT=\'(\S+)\'/',
                    'ROW_FORMAT=$1',
                    $create_query
                );
            }

            //are there any constraints to cut out?
            if (preg_match('@CONSTRAINT|KEY@', $create_query)) {
                $has_constraints = 0;
                $has_indexes = 0;

                //if there are constraints
                if (preg_match(
                    '@CONSTRAINT@',
                    $create_query
                )) {
                    $has_constraints = 1;
                    // comments -> constraints for dumped tables
                    if (! isset($sql_constraints)) {
                        if (isset($GLOBALS['no_constraints_comments'])) {
                            $sql_constraints = '';
                        } else {
                            $sql_constraints = $crlf
                                . $this->_exportComment()
                                . $this->_exportComment(
                                    __('Constraints for dumped tables')
                                )
                                . $this->_exportComment();
                        }
                    }
                        // comments for current table
                    if (! isset($GLOBALS['no_constraints_comments'])) {
                        $sql_constraints .= $crlf
                        . $this->_exportComment()
                        . $this->_exportComment(
                            __('Constraints for table')
                            . ' '
                            . PMA_Util::backquoteCompat($table, $compat)
                        )
                        . $this->_exportComment();
                    }
                    $sql_constraints_query .= 'ALTER TABLE '
                    . PMA_Util::backquoteCompat($table, $compat)
                    . $crlf;
                    $sql_constraints .= 'ALTER TABLE '
                    . PMA_Util::backquoteCompat($table,  $compat)
                    . $crlf;
                    $sql_drop_foreign_keys .= 'ALTER TABLE '
                    . PMA_Util::backquoteCompat($db, $compat) . '.'
                    . PMA_Util::backquoteCompat($table, $compat)
                    . $crlf;
                }
                //if there are indexes
                // (look for KEY followed by whitespace to avoid matching
                //  keyworks like PACK_KEYS)
                if ($update_indexes_increments && preg_match(
                    '@KEY[\s]+@',
                    $create_query
                )) {
                    $has_indexes = 1;

                    // comments -> indexes for dumped tables
                    if (! isset($sql_indexes)) {
                        if (isset($GLOBALS['no_constraints_comments'])) {
                            $sql_indexes = '';
                        } else {
                            $sql_indexes = $crlf
                                . $this->_exportComment()
                                . $this->_exportComment(
                                    __('Indexes for dumped tables')
                                )
                                . $this->_exportComment();
                        }
                    }
                    // comments for current table
                    if (! isset($GLOBALS['no_constraints_comments'])) {
                        $sql_indexes .= $crlf
                        . $this->_exportComment()
                        . $this->_exportComment(
                            __('Indexes for table')
                            . ' '
                            . PMA_Util::backquoteCompat($table, $compat)
                        )
                        . $this->_exportComment();
                    }
                    $sql_indexes_query .= 'ALTER TABLE '
                    . PMA_Util::backquoteCompat($table, $compat)
                    . $crlf;

                    $sql_indexes .= 'ALTER TABLE '
                    . PMA_Util::backquoteCompat($table,  $compat)
                    . $crlf;
                }
                if ($update_indexes_increments && preg_match(
                    '@AUTO_INCREMENT@',
                    $create_query
                )) {
                    // comments -> auto increments for dumped tables
                    if (! isset($sql_auto_increments)) {
                        if (isset($GLOBALS['no_constraints_comments'])) {
                            $sql_auto_increments = '';
                        } else {
                            $sql_auto_increments = $crlf
                                . $this->_exportComment()
                                . $this->_exportComment(
                                    __('AUTO_INCREMENT for dumped tables')
                                )
                                . $this->_exportComment();
                        }
                    }
                    // comments for current table
                    if (! isset($GLOBALS['no_constraints_comments'])) {
                        $sql_auto_increments .= $crlf
                        . $this->_exportComment()
                        . $this->_exportComment(
                            __('AUTO_INCREMENT for table')
                            . ' '
                            . PMA_Util::backquoteCompat($table, $compat)
                        )
                        . $this->_exportComment();
                    }
                    $sql_auto_increments .= 'ALTER TABLE '
                    . PMA_Util::backquoteCompat($table, $compat)
                    . $crlf;
                }

                // Split the query into lines, so we can easily handle it.
                // We know lines are separated by $crlf (done few lines above).
                $sql_lines = explode($crlf, $create_query);
                $sql_count = count($sql_lines);

                // lets find first line with constraints
                $first_occur = -1;
                for ($i = 0; $i < $sql_count; $i++) {
                    if (preg_match(
                        '@[\s]+(CONSTRAINT|KEY)@',
                        $sql_lines[$i]
                    ) && $first_occur == -1) {
                        $first_occur = $i;
                    }
                }

                for ($k = 0; $k < $sql_count; $k++) {
                    if ($update_indexes_increments && preg_match(
                        '( AUTO_INCREMENT | AUTO_INCREMENT,| AUTO_INCREMENT$)',
                        $sql_lines[$k]
                    )) {
                        //removes extra space at the beginning, if there is
                        $sql_lines[$k] = ltrim($sql_lines[$k], ' ');
                        //creates auto increment code
                        $sql_auto_increments .= "MODIFY " . $sql_lines[$k];
                        //removes auto increment code from table definition
                        $sql_lines[$k] = str_replace(
                            " AUTO_INCREMENT", "", $sql_lines[$k]
                        );
                    }
                    if ($update_indexes_increments && preg_match(
                        '@[\s]+(AUTO_INCREMENT=)@',
                        $sql_lines[$k]
                    )) {
                        //adds auto increment value
                        $increment_value = substr(
                            $sql_lines[$k],
                            strpos($sql_lines[$k], "AUTO_INCREMENT")
                        );
                        $increment_value_array = explode(' ', $increment_value);
                        $sql_auto_increments .= $increment_value_array[0] . ";";

                    }
                }

                if ($sql_auto_increments != '') {
                    $sql_auto_increments = substr(
                        $sql_auto_increments, 0, -1
                    ) . ';';
                }
                // If we really found a constraint
                if ($first_occur != $sql_count) {
                    // lets find first line
                    $sql_lines[$first_occur - 1] = preg_replace(
                        '@,$@',
                        '',
                        $sql_lines[$first_occur - 1]
                    );

                    $first = true;
                    for ($j = $first_occur; $j < $sql_count; $j++) {
                        //removes extra space at the beginning, if there is
                        $sql_lines[$j]=ltrim($sql_lines[$j], ' ');

                        //if it's a constraint
                        if (preg_match(
                            '@CONSTRAINT|FOREIGN[\s]+KEY@',
                            $sql_lines[$j]
                        )) {
                            if (! $first) {
                                $sql_constraints .= $crlf;
                            }
                            if (strpos($sql_lines[$j], 'CONSTRAINT') === false) {
                                $tmp_str = preg_replace(
                                    '/(FOREIGN[\s]+KEY)/',
                                    'ADD \1',
                                    $sql_lines[$j]
                                );

                                $sql_constraints_query .= $tmp_str;
                                $sql_constraints .= $tmp_str;

                            } else {
                                $tmp_str = preg_replace(
                                    '/(CONSTRAINT)/',
                                    'ADD \1',
                                    $sql_lines[$j]
                                );

                                $sql_constraints_query .= $tmp_str;
                                $sql_constraints .= $tmp_str;
                                preg_match(
                                    '/(CONSTRAINT)([\s])([\S]*)([\s])/',
                                    $sql_lines[$j],
                                    $matches
                                );
                                if (! $first) {
                                    $sql_drop_foreign_keys .= ', ';
                                }
                                $sql_drop_foreign_keys .= 'DROP FOREIGN KEY '
                                    . $matches[3];
                            }
                            $first = false;
                        } else if ($update_indexes_increments && preg_match(
                            '@KEY[\s]+@',
                            $sql_lines[$j]
                        )) {
                            //if it's a index
                            $tmp_str = " ADD " . $sql_lines[$j];
                            $sql_indexes_query .= $tmp_str;
                            $sql_indexes .= $tmp_str;
                        } else {
                            break;
                        }
                    }
                    //removes superfluous comma at the end
                    $sql_indexes = rtrim($sql_indexes, ',');
                    $sql_indexes_query = rtrim($sql_indexes_query, ',');
                    //removes superfluous semicolon at the end
                    if ($has_constraints == 1) {
                        $sql_constraints .= ';' . $crlf;
                        $sql_constraints_query .= ';';
                    }
                    if ($has_indexes == 1) {
                        $sql_indexes .= ';' . $crlf;
                        $sql_indexes_query .= ';';
                    }
                    //remove indexes and constraints from the $create_query
                    $create_query = implode(
                        $crlf,
                        array_slice($sql_lines, 0, $first_occur)
                    )
                    . $crlf
                    . implode(
                        $crlf,
                        array_slice($sql_lines, $j, $sql_count - 1)
                    );
                    unset($sql_lines);
                }
            }
            $schema_create .= $create_query;
        }

        // remove a possible "AUTO_INCREMENT = value" clause
        // that could be there starting with MySQL 5.0.24
        // in Drizzle it's useless as it contains the value given at table
        // creation time
        $schema_create = preg_replace(
            '/AUTO_INCREMENT\s*=\s*([0-9])+/',
            '',
            $schema_create
        );

        $schema_create .= ($compat != 'MSSQL') ? $auto_increment : '';

        $GLOBALS['dbi']->freeResult($result);
        return $schema_create . ($add_semicolon ? ';' . $crlf : '');
    } // end of the 'getTableDef()' function

    /**
     * Returns $table's comments, relations etc.
     *
     * @param string $db          database name
     * @param string $table       table name
     * @param string $crlf        end of line sequence
     * @param bool   $do_relation whether to include relation comments
     * @param bool   $do_mime     whether to include mime comments
     *
     * @return string resulting comments
     */
    private function _getTableComments(
        $db,
        $table,
        $crlf,
        $do_relation = false,
        $do_mime = false
    ) {
        global $cfgRelation, $sql_backquotes;

        $schema_create = '';

        // Check if we can use Relations
        if ($do_relation && ! empty($cfgRelation['relation'])) {
            // Find which tables are related with the current one and write it in
            // an array
            $res_rel = PMA_getForeigners($db, $table);

            if ($res_rel && count($res_rel) > 0) {
                $have_rel = true;
            } else {
                $have_rel = false;
            }
        } else {
               $have_rel = false;
        } // end if

        if ($do_mime && $cfgRelation['mimework']) {
            if (! ($mime_map = PMA_getMIME($db, $table, true))) {
                unset($mime_map);
            }
        }

        if (isset($mime_map) && count($mime_map) > 0) {
            $schema_create .= $this->_possibleCRLF()
            . $this->_exportComment()
            . $this->_exportComment(
                __('MIME TYPES FOR TABLE') . ' '
                . PMA_Util::backquote($table, $sql_backquotes) . ':'
            );
            @reset($mime_map);
            foreach ($mime_map as $mime_field => $mime) {
                $schema_create .=
                    $this->_exportComment(
                        '  '
                        . PMA_Util::backquote($mime_field, $sql_backquotes)
                    )
                    . $this->_exportComment(
                        '      '
                        . PMA_Util::backquote(
                            $mime['mimetype'],
                            $sql_backquotes
                        )
                    );
            }
            $schema_create .= $this->_exportComment();
        }

        if ($have_rel) {
            $schema_create .= $this->_possibleCRLF()
                . $this->_exportComment()
                . $this->_exportComment(
                    __('RELATIONS FOR TABLE') . ' '
                    . PMA_Util::backquote($table, $sql_backquotes)
                    . ':'
                );
            foreach ($res_rel as $rel_field => $rel) {
                $schema_create .=
                    $this->_exportComment(
                        '  '
                        . PMA_Util::backquote($rel_field, $sql_backquotes)
                    )
                    . $this->_exportComment(
                        '      '
                        . PMA_Util::backquote(
                            $rel['foreign_table'],
                            $sql_backquotes
                        )
                        . ' -> '
                        . PMA_Util::backquote(
                            $rel['foreign_field'],
                            $sql_backquotes
                        )
                    );
            }
            $schema_create .= $this->_exportComment();
        }

        return $schema_create;

    } // end of the '_getTableComments()' function

    /**
     * Outputs table's structure
     *
     * @param string $db          database name
     * @param string $table       table name
     * @param string $crlf        the end of line sequence
     * @param string $error_url   the url to go back in case of error
     * @param string $export_mode 'create_table','triggers','create_view',
     *                            'stand_in'
     * @param string $export_type 'server', 'database', 'table'
     * @param bool   $relation    whether to include relation comments
     * @param bool   $comments    whether to include the pmadb-style column
     *                            comments as comments in the structure; this is
     *                            deprecated but the parameter is left here
     *                            because export.php calls exportStructure()
     *                            also for other export types which use this
     *                            parameter
     * @param bool   $mime        whether to include mime comments
     * @param bool   $dates       whether to include creation/update/check dates
     *
     * @return bool Whether it succeeded
     */
    public function exportStructure(
        $db,
        $table,
        $crlf,
        $error_url,
        $export_mode,
        $export_type,
        $relation = false,
        $comments = false,
        $mime = false,
        $dates = false
    ) {
        if (isset($GLOBALS['sql_compatibility'])) {
            $compat = $GLOBALS['sql_compatibility'];
        } else {
            $compat = 'NONE';
        }

        $formatted_table_name = (isset($GLOBALS['sql_backquotes']))
            ? PMA_Util::backquoteCompat($table, $compat)
            : '\'' . $table . '\'';
        $dump = $this->_possibleCRLF()
            . $this->_exportComment(str_repeat('-', 56))
            . $this->_possibleCRLF()
            . $this->_exportComment();

        switch($export_mode) {
        case 'create_table':
            $dump .= $this->_exportComment(
                __('Table structure for table') . ' ' . $formatted_table_name
            );
            $dump .= $this->_exportComment();
            $dump .= $this->getTableDef($db, $table, $crlf, $error_url, $dates);
            $dump .= $this->_getTableComments($db, $table, $crlf, $relation, $mime);
            break;
        case 'triggers':
            $dump = '';
            $triggers = $GLOBALS['dbi']->getTriggers($db, $table);
            if ($triggers) {
                $dump .=  $this->_possibleCRLF()
                    . $this->_exportComment()
                    . $this->_exportComment(
                        __('Triggers') . ' ' . $formatted_table_name
                    )
                    . $this->_exportComment();
                $delimiter = '//';
                foreach ($triggers as $trigger) {
                    if (! empty($GLOBALS['sql_drop_table'])) {
                        $dump .= $trigger['drop'] . ';' . $crlf;
                    }
                    $dump .= 'DELIMITER ' . $delimiter . $crlf;
                    $dump .= $trigger['create'];
                    $dump .= 'DELIMITER ;' . $crlf;
                }
            }
            break;
        case 'create_view':
            if (empty($GLOBALS['sql_views_as_tables'])) {
                $dump .=
                    $this->_exportComment(
                        __('Structure for view')
                        . ' '
                        . $formatted_table_name
                    )
                    . $this->_exportComment();
                // delete the stand-in table previously created (if any)
                if ($export_type != 'table') {
                    $dump .= 'DROP TABLE IF EXISTS '
                        . PMA_Util::backquote($table) . ';' . $crlf;
                }
                $dump .= $this->getTableDef(
                    $db, $table, $crlf, $error_url, $dates, true, true
                );
            } else {
                $dump .=
                $this->_exportComment(
                    sprintf(
                        __('Structure for view %s exported as a table'),
                        $formatted_table_name
                    )
                )
                . $this->_exportComment();
                // delete the stand-in table previously created (if any)
                if ($export_type != 'table') {
                    $dump .= 'DROP TABLE IF EXISTS '
                        . PMA_Util::backquote($table) . ';' . $crlf;
                }
                $dump .= $this->_getTableDefForView(
                    $db, $table, $crlf, true
                );
            }
            break;
        case 'stand_in':
            $dump .=
                $this->_exportComment(
                    __('Stand-in structure for view') . ' ' . $formatted_table_name
                )
                . $this->_exportComment();
            // export a stand-in definition to resolve view dependencies
            $dump .= $this->getTableDefStandIn($db, $table, $crlf);
        } // end switch

        // this one is built by getTableDef() to use in table copy/move
        // but not in the case of export
        unset($GLOBALS['sql_constraints_query']);

        return PMA_exportOutputHandler($dump);
    }

    /**
     * Outputs the content of a table in SQL format
     *
     * @param string $db        database name
     * @param string $table     table name
     * @param string $crlf      the end of line sequence
     * @param string $error_url the url to go back in case of error
     * @param string $sql_query SQL query for obtaining data
     *
     * @return bool Whether it succeeded
     */
    public function exportData($db, $table, $crlf, $error_url, $sql_query)
    {
        global $current_row, $sql_backquotes;

        if (isset($GLOBALS['sql_compatibility'])) {
            $compat = $GLOBALS['sql_compatibility'];
        } else {
            $compat = 'NONE';
        }

        $formatted_table_name = (isset($GLOBALS['sql_backquotes']))
            ? PMA_Util::backquoteCompat($table, $compat)
            : '\'' . $table . '\'';

        // Do not export data for a VIEW, unless asked to export the view as a table
        // (For a VIEW, this is called only when exporting a single VIEW)
        if (PMA_Table::isView($db, $table)
            && empty($GLOBALS['sql_views_as_tables'])
        ) {
            $head = $this->_possibleCRLF()
              . $this->_exportComment()
              . $this->_exportComment('VIEW ' . ' ' . $formatted_table_name)
              . $this->_exportComment(__('Data:') . ' ' . __('None'))
              . $this->_exportComment()
              . $this->_possibleCRLF();

            if (! PMA_exportOutputHandler($head)) {
                return false;
            }
            return true;
        }

        // analyze the query to get the true column names, not the aliases
        // (this fixes an undefined index, also if Complete inserts
        //  are used, we did not get the true column name in case of aliases)
        $analyzed_sql = PMA_SQP_analyze(PMA_SQP_parse($sql_query));

        $result = $GLOBALS['dbi']->tryQuery(
            $sql_query, null, PMA_DatabaseInterface::QUERY_UNBUFFERED
        );
        // a possible error: the table has crashed
        $tmp_error = $GLOBALS['dbi']->getError();
        if ($tmp_error) {
            return PMA_exportOutputHandler(
                $this->_exportComment(
                    __('Error reading data:') . ' (' . $tmp_error . ')'
                )
            );
        }

        if ($result != false) {
            $fields_cnt = $GLOBALS['dbi']->numFields($result);

            // Get field information
            $fields_meta = $GLOBALS['dbi']->getFieldsMeta($result);
            $field_flags = array();
            for ($j = 0; $j < $fields_cnt; $j++) {
                $field_flags[$j] = $GLOBALS['dbi']->fieldFlags($result, $j);
            }

            $field_set = array();
            for ($j = 0; $j < $fields_cnt; $j++) {
                if (isset($analyzed_sql[0]['select_expr'][$j]['column'])) {
                    $field_set[$j] = PMA_Util::backquoteCompat(
                        $analyzed_sql[0]['select_expr'][$j]['column'],
                        $compat,
                        $sql_backquotes
                    );
                } else {
                    $field_set[$j] = PMA_Util::backquoteCompat(
                        $fields_meta[$j]->name,
                        $compat,
                        $sql_backquotes
                    );
                }
            }

            if (isset($GLOBALS['sql_type'])
                && $GLOBALS['sql_type'] == 'UPDATE'
            ) {
                // update
                $schema_insert  = 'UPDATE ';
                if (isset($GLOBALS['sql_ignore'])) {
                    $schema_insert .= 'IGNORE ';
                }
                // avoid EOL blank
                $schema_insert .= PMA_Util::backquoteCompat(
                    $table,
                    $compat,
                    $sql_backquotes
                ) . ' SET';
            } else {
                // insert or replace
                if (isset($GLOBALS['sql_type'])
                    && $GLOBALS['sql_type'] == 'REPLACE'
                ) {
                    $sql_command = 'REPLACE';
                } else {
                    $sql_command = 'INSERT';
                }

                // delayed inserts?
                if (isset($GLOBALS['sql_delayed'])) {
                    $insert_delayed = ' DELAYED';
                } else {
                    $insert_delayed = '';
                }

                // insert ignore?
                if (isset($GLOBALS['sql_type'])
                    && $GLOBALS['sql_type'] == 'INSERT'
                    && isset($GLOBALS['sql_ignore'])
                ) {
                    $insert_delayed .= ' IGNORE';
                }
                //truncate table before insert
                if (isset($GLOBALS['sql_truncate'])
                    && $GLOBALS['sql_truncate']
                    && $sql_command == 'INSERT'
                ) {
                    $truncate = 'TRUNCATE TABLE '
                        . PMA_Util::backquoteCompat(
                            $table,
                            $compat,
                            $sql_backquotes
                        ) . ";";
                    $truncatehead = $this->_possibleCRLF()
                        . $this->_exportComment()
                        . $this->_exportComment(
                            __('Truncate table before insert') . ' '
                            . $formatted_table_name
                        )
                        . $this->_exportComment()
                        . $crlf;
                    PMA_exportOutputHandler($truncatehead);
                    PMA_exportOutputHandler($truncate);
                } else {
                    $truncate = '';
                }

                // scheme for inserting fields
                if ($GLOBALS['sql_insert_syntax'] == 'complete'
                    || $GLOBALS['sql_insert_syntax'] == 'both'
                ) {
                    $fields        = implode(', ', $field_set);
                    $schema_insert = $sql_command . $insert_delayed . ' INTO '
                        . PMA_Util::backquoteCompat(
                            $table,
                            $compat,
                            $sql_backquotes
                        )
                        // avoid EOL blank
                        . ' (' . $fields . ') VALUES';
                } else {
                    $schema_insert = $sql_command . $insert_delayed . ' INTO '
                        . PMA_Util::backquoteCompat(
                            $table,
                            $compat,
                            $sql_backquotes
                        )
                        . ' VALUES';
                }
            }

            //\x08\\x09, not required
            $search      = array("\x00", "\x0a", "\x0d", "\x1a");
            $replace     = array('\0', '\n', '\r', '\Z');
            $current_row = 0;
            $query_size  = 0;
            if (($GLOBALS['sql_insert_syntax'] == 'extended'
                || $GLOBALS['sql_insert_syntax'] == 'both')
                && (! isset($GLOBALS['sql_type'])
                || $GLOBALS['sql_type'] != 'UPDATE')
            ) {
                $separator      = ',';
                $schema_insert .= $crlf;
            } else {
                $separator      = ';';
            }

            while ($row = $GLOBALS['dbi']->fetchRow($result)) {
                if ($current_row == 0) {
                    $head = $this->_possibleCRLF()
                        . $this->_exportComment()
                        . $this->_exportComment(
                            __('Dumping data for table') . ' '
                            . $formatted_table_name
                        )
                        . $this->_exportComment()
                        . $crlf;
                    if (! PMA_exportOutputHandler($head)) {
                        return false;
                    }
                }
                 // We need to SET IDENTITY_INSERT ON for MSSQL
                if (isset($GLOBALS['sql_compatibility'])
                    && $GLOBALS['sql_compatibility'] == 'MSSQL'
                    && $current_row == 0
                ) {
                    if (! PMA_exportOutputHandler(
                        'SET IDENTITY_INSERT '
                        . PMA_Util::backquoteCompat(
                            $table,
                            $compat
                        )
                        . ' ON ;' . $crlf
                    )) {
                        return false;
                    }
                }
                $current_row++;
                $values = array();
                for ($j = 0; $j < $fields_cnt; $j++) {
                    // NULL
                    if (! isset($row[$j]) || is_null($row[$j])) {
                        $values[] = 'NULL';
                    } elseif ($fields_meta[$j]->numeric
                        && $fields_meta[$j]->type != 'timestamp'
                        && ! $fields_meta[$j]->blob
                    ) {
                        // a number
                        // timestamp is numeric on some MySQL 4.1, BLOBs are
                        // sometimes numeric
                        $values[] = $row[$j];
                    } elseif (stristr($field_flags[$j], 'BINARY')
                        && $fields_meta[$j]->blob
                        && isset($GLOBALS['sql_hex_for_blob'])
                    ) {
                        // a true BLOB
                        // - mysqldump only generates hex data when the --hex-blob
                        //   option is used, for fields having the binary attribute
                        //   no hex is generated
                        // - a TEXT field returns type blob but a real blob
                        //   returns also the 'binary' flag

                        // empty blobs need to be different, but '0' is also empty
                        // :-(
                        if (empty($row[$j]) && $row[$j] != '0') {
                            $values[] = '\'\'';
                        } else {
                            $values[] = '0x' . bin2hex($row[$j]);
                        }
                    } elseif ($fields_meta[$j]->type == 'bit') {
                        // detection of 'bit' works only on mysqli extension
                        $values[] = "b'" . PMA_Util::sqlAddSlashes(
                            PMA_Util::printableBitValue(
                                $row[$j], $fields_meta[$j]->length
                            )
                        )
                            . "'";
                    } else {
                        // something else -> treat as a string
                        $values[] = '\''
                            . str_replace(
                                $search, $replace,
                                PMA_Util::sqlAddSlashes($row[$j])
                            )
                            . '\'';
                    } // end if
                } // end for

                // should we make update?
                if (isset($GLOBALS['sql_type'])
                    && $GLOBALS['sql_type'] == 'UPDATE'
                ) {

                    $insert_line = $schema_insert;
                    for ($i = 0; $i < $fields_cnt; $i++) {
                        if (0 == $i) {
                            $insert_line .= ' ';
                        }
                        if ($i > 0) {
                            // avoid EOL blank
                            $insert_line .= ',';
                        }
                        $insert_line .= $field_set[$i] . ' = ' . $values[$i];
                    }

                    list($tmp_unique_condition, $tmp_clause_is_unique)
                        = PMA_Util::getUniqueCondition(
                            $result,
                            $fields_cnt,
                            $fields_meta,
                            $row
                        );
                    $insert_line .= ' WHERE ' . $tmp_unique_condition;
                    unset($tmp_unique_condition, $tmp_clause_is_unique);

                } else {

                    // Extended inserts case
                    if ($GLOBALS['sql_insert_syntax'] == 'extended'
                        || $GLOBALS['sql_insert_syntax'] == 'both'
                    ) {
                        if ($current_row == 1) {
                            $insert_line  = $schema_insert . '('
                                . implode(', ', $values) . ')';
                        } else {
                            $insert_line  = '(' . implode(', ', $values) . ')';
                            $sql_max_size = $GLOBALS['sql_max_query_size'];
                            if (isset($sql_max_size)
                                && $sql_max_size > 0
                                && $query_size + strlen($insert_line) > $sql_max_size
                            ) {
                                if (! PMA_exportOutputHandler(';' . $crlf)) {
                                    return false;
                                }
                                $query_size  = 0;
                                $current_row = 1;
                                $insert_line = $schema_insert . $insert_line;
                            }
                        }
                        $query_size += strlen($insert_line);
                        // Other inserts case
                    } else {
                        $insert_line = $schema_insert
                            . '('
                            . implode(', ', $values)
                            . ')';
                    }
                }
                unset($values);

                if (! PMA_exportOutputHandler(
                    ($current_row == 1 ? '' : $separator . $crlf)
                    . $insert_line
                )) {
                    return false;
                }

            } // end while

            if ($current_row > 0) {
                if (! PMA_exportOutputHandler(';' . $crlf)) {
                    return false;
                }
            }

            // We need to SET IDENTITY_INSERT OFF for MSSQL
            if (isset($GLOBALS['sql_compatibility'])
                && $GLOBALS['sql_compatibility'] == 'MSSQL'
                && $current_row > 0
            ) {
                $outputSucceeded = PMA_exportOutputHandler(
                    $crlf . 'SET IDENTITY_INSERT '
                    . PMA_Util::backquoteCompat($table, $compat)
                    . ' OFF;' . $crlf
                );
                if (! $outputSucceeded) {
                    return false;
                }
            }
        } // end if ($result != false)
        $GLOBALS['dbi']->freeResult($result);

        return true;
    } // end of the 'exportData()' function

    /**
     * Make a create table statement compatible with MSSQL
     *
     * @param string $create_query MySQL create table statement
     *
     * @return string MSSQL compatible create table statement
     */
    private function _makeCreateTableMSSQLCompatible($create_query)
    {
        // In MSSQL
        // 1. No 'IF NOT EXISTS' in CREATE TABLE
        // 2. DATE field doesn't exists, we will use DATETIME instead
        // 3. UNSIGNED attribute doesn't exist
        // 4. No length on INT, TINYINT, SMALLINT, BIGINT and no precision on
        //    FLOAT fields
        // 5. No KEY and INDEX inside CREATE TABLE
        // 6. DOUBLE field doesn't exists, we will use FLOAT instead

        $create_query = preg_replace(
            "/^CREATE TABLE IF NOT EXISTS/",
            'CREATE TABLE',
            $create_query
        );
        // first we need  to replace all lines ended with '" DATE ...,\n'
        // last preg_replace preserve us from situation with date text
        // inside DEFAULT field value
        $create_query = preg_replace(
            "/\" date DEFAULT NULL(,)?\n/",
            '" datetime DEFAULT NULL$1' . "\n",
            $create_query
        );
        $create_query = preg_replace(
            "/\" date NOT NULL(,)?\n/",
            '" datetime NOT NULL$1' . "\n",
            $create_query
        );
        $create_query = preg_replace(
            '/" date NOT NULL DEFAULT \'([^\'])/',
            '" datetime NOT NULL DEFAULT \'$1',
            $create_query
        );

        // next we need to replace all lines ended with ') UNSIGNED ...,'
        // last preg_replace preserve us from situation with unsigned text
        // inside DEFAULT field value
        $create_query = preg_replace(
            "/\) unsigned NOT NULL(,)?\n/",
            ') NOT NULL$1' . "\n",
            $create_query
        );
        $create_query = preg_replace(
            "/\) unsigned DEFAULT NULL(,)?\n/",
            ') DEFAULT NULL$1' . "\n",
            $create_query
        );
        $create_query = preg_replace(
            '/\) unsigned NOT NULL DEFAULT \'([^\'])/',
            ') NOT NULL DEFAULT \'$1',
            $create_query
        );

        // we need to replace all lines ended with
        // '" INT|TINYINT([0-9]{1,}) ...,' last preg_replace preserve us
        // from situation with int([0-9]{1,}) text inside DEFAULT field
        // value
        $create_query = preg_replace(
            '/" (int|tinyint|smallint|bigint)\([0-9]+\) DEFAULT NULL(,)?\n/',
            '" $1 DEFAULT NULL$2' . "\n",
            $create_query
        );
        $create_query = preg_replace(
            '/" (int|tinyint|smallint|bigint)\([0-9]+\) NOT NULL(,)?\n/',
            '" $1 NOT NULL$2' . "\n",
            $create_query
        );
        $create_query = preg_replace(
            '/" (int|tinyint|smallint|bigint)\([0-9]+\) NOT NULL DEFAULT \'([^\'])/',
            '" $1 NOT NULL DEFAULT \'$2',
            $create_query
        );

        // we need to replace all lines ended with
        // '" FLOAT|DOUBLE([0-9,]{1,}) ...,'
        // last preg_replace preserve us from situation with
        // float([0-9,]{1,}) text inside DEFAULT field value
        $create_query = preg_replace(
            '/" (float|double)(\([0-9]+,[0-9,]+\))? DEFAULT NULL(,)?\n/',
            '" float DEFAULT NULL$3' . "\n",
            $create_query
        );
        $create_query = preg_replace(
            '/" (float|double)(\([0-9,]+,[0-9,]+\))? NOT NULL(,)?\n/',
            '" float NOT NULL$3' . "\n",
            $create_query
        );
        $create_query = preg_replace(
            '/" (float|double)(\([0-9,]+,[0-9,]+\))? NOT NULL DEFAULT \'([^\'])/',
            '" float NOT NULL DEFAULT \'$3',
            $create_query
        );

        // @todo remove indexes from CREATE TABLE

        return $create_query;
    }
}
