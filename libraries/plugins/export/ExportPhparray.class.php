<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Set of functions used to build dumps of tables as PHP Arrays
 *
 * @package    PhpMyAdmin-Export
 * @subpackage PHP
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* Get the export interface */
require_once 'libraries/plugins/ExportPlugin.class.php';

/**
 * Handles the export for the PHP Array class
 *
 * @package    PhpMyAdmin-Export
 * @subpackage PHP
 */
class ExportPhparray extends ExportPlugin
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->setProperties();
    }

    /**
     * Sets the export PHP Array properties
     *
     * @return void
     */
    protected function setProperties()
    {
        $props = 'libraries/properties/';
        include_once "$props/plugins/ExportPluginProperties.class.php";
        include_once "$props/options/groups/OptionsPropertyRootGroup.class.php";
        include_once "$props/options/groups/OptionsPropertyMainGroup.class.php";
        include_once "$props/options/items/HiddenPropertyItem.class.php";

        $exportPluginProperties = new ExportPluginProperties();
        $exportPluginProperties->setText('PHP array');
        $exportPluginProperties->setExtension('php');
        $exportPluginProperties->setMimeType('text/plain');
        $exportPluginProperties->setOptionsText(__('Options'));

        // create the root group that will be the options field for
        // $exportPluginProperties
        // this will be shown as "Format specific options"
        $exportSpecificOptions = new OptionsPropertyRootGroup();
        $exportSpecificOptions->setName("Format Specific Options");

        // general options main group
        $generalOptions = new OptionsPropertyMainGroup();
        $generalOptions->setName("general_opts");
        // create primary items and add them to the group
        $leaf = new HiddenPropertyItem();
        $leaf->setName("structure_or_data");
        $generalOptions->addProperty($leaf);
        // add the main group to the root group
        $exportSpecificOptions->addProperty($generalOptions);

        // set the options for the export plugin property item
        $exportPluginProperties->setOptions($exportSpecificOptions);
        $this->properties = $exportPluginProperties;
    }

    /**
     * Outputs export header
     *
     * @return bool Whether it succeeded
     */
    public function exportHeader()
    {
        PMA_exportOutputHandler(
            '<?php' . $GLOBALS['crlf']
            . '/**' . $GLOBALS['crlf']
            . ' * Export to PHP Array plugin for PHPMyAdmin' . $GLOBALS['crlf']
            . ' * @version 0.2b' . $GLOBALS['crlf']
            . ' */' . $GLOBALS['crlf'] . $GLOBALS['crlf']
        );
        return true;
    }

    /**
     * Outputs export footer
     *
     * @return bool Whether it succeeded
     */
    public function exportFooter()
    {
        return true;
    }

    /**
     * Outputs database header
     *
     * @param string $db       Database name
     * @param string $db_alias Aliases of db
     *
     * @return bool Whether it succeeded
     */
    public function exportDBHeader($db, $db_alias = '')
    {
        if (empty($db_alias)) {
            $db_alias = $db;
        }
        PMA_exportOutputHandler(
            '//' . $GLOBALS['crlf']
            . '// Database ' . PMA_Util::backquote($db_alias)
            . $GLOBALS['crlf'] . '//' . $GLOBALS['crlf']
        );
        return true;
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
        return true;
    }

    /**
     * Outputs CREATE DATABASE statement
     *
     * @param string $db          Database name
     * @param string $export_type 'server', 'database', 'table'
     * @param string $db_alias    Aliases of db
     *
     * @return bool Whether it succeeded
     */
    public function exportDBCreate($db, $export_type, $db_alias = '')
    {
        return true;
    }

    /**
     * Outputs the content of a table in PHP array format
     *
     * @param string $db        database name
     * @param string $table     table name
     * @param string $crlf      the end of line sequence
     * @param string $error_url the url to go back in case of error
     * @param string $sql_query SQL query for obtaining data
     * @param array  $aliases   Aliases of db/table/columns
     *
     * @return bool Whether it succeeded
     */
    public function exportData(
        $db, $table, $crlf, $error_url, $sql_query, $aliases = array()
    ) {
        $db_alias = $db;
        $table_alias = $table;
        $this->initAlias($aliases, $db_alias, $table_alias);

        $result = $GLOBALS['dbi']->query(
            $sql_query, null, PMA_DatabaseInterface::QUERY_UNBUFFERED
        );

        $columns_cnt = $GLOBALS['dbi']->numFields($result);
        $columns = array();
        for ($i = 0; $i < $columns_cnt; $i++) {
            $col_as = $GLOBALS['dbi']->fieldName($result, $i);
            if (!empty($aliases[$db]['tables'][$table]['columns'][$col_as])) {
                $col_as = $aliases[$db]['tables'][$table]['columns'][$col_as];
            }
            $columns[$i] = stripslashes($col_as);
        }

        // fix variable names (based on
        // http://www.php.net/manual/language.variables.basics.php)
        if (! preg_match(
            '/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/',
            $table_alias
        )) {
            // fix invalid characters in variable names by replacing them with
            // underscores
            $tablefixed = preg_replace(
                '/[^a-zA-Z0-9_\x7f-\xff]/', '_', $table_alias
            );

            // variable name must not start with a number or dash...
            if (preg_match('/^[a-zA-Z_\x7f-\xff]/', $tablefixed) === 0) {
                $tablefixed = '_' . $tablefixed;
            }
        } else {
            $tablefixed = $table;
        }

        $buffer = '';
        $record_cnt = 0;
        // Output table name as comment
        $buffer .= $crlf . '// '
            . PMA_Util::backquote($db_alias) . '.'
            . PMA_Util::backquote($table_alias) . $crlf;
        $buffer .= '$' . $tablefixed . ' = array(';

        while ($record = $GLOBALS['dbi']->fetchRow($result)) {
            $record_cnt++;

            if ($record_cnt == 1) {
                $buffer .= $crlf . '  array(';
            } else {
                $buffer .= ',' . $crlf . '  array(';
            }

            for ($i = 0; $i < $columns_cnt; $i++) {
                $buffer .= var_export($columns[$i], true)
                    . " => " . var_export($record[$i], true)
                    . (($i + 1 >= $columns_cnt) ? '' : ',');
            }

            $buffer .= ')';
        }

        $buffer .= $crlf . ');' . $crlf;
        if (! PMA_exportOutputHandler($buffer)) {
            return false;
        }

        $GLOBALS['dbi']->freeResult($result);
        return true;
    }
}
