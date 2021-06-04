<?php
/**
 * Set of functions used to build dumps of tables as PHP Arrays
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Export;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Plugins\ExportPlugin;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\HiddenPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use PhpMyAdmin\Util;
use function preg_match;
use function preg_replace;
use function stripslashes;
use function strtr;
use function var_export;

/**
 * Handles the export for the PHP Array class
 */
class ExportPhparray extends ExportPlugin
{
    public function __construct()
    {
        parent::__construct();
        $this->setProperties();
    }

    /**
     * Sets the export PHP Array properties
     *
     * @return void
     */
    protected function setProperties()
    {
        $exportPluginProperties = new ExportPluginProperties();
        $exportPluginProperties->setText('PHP array');
        $exportPluginProperties->setExtension('php');
        $exportPluginProperties->setMimeType('text/plain');
        $exportPluginProperties->setOptionsText(__('Options'));

        // create the root group that will be the options field for
        // $exportPluginProperties
        // this will be shown as "Format specific options"
        $exportSpecificOptions = new OptionsPropertyRootGroup(
            'Format Specific Options'
        );

        // general options main group
        $generalOptions = new OptionsPropertyMainGroup('general_opts');
        // create primary items and add them to the group
        $leaf = new HiddenPropertyItem('structure_or_data');
        $generalOptions->addProperty($leaf);
        // add the main group to the root group
        $exportSpecificOptions->addProperty($generalOptions);

        // set the options for the export plugin property item
        $exportPluginProperties->setOptions($exportSpecificOptions);
        $this->properties = $exportPluginProperties;
    }

    /**
     * Removes end of comment from a string
     *
     * @param string $string String to replace
     *
     * @return string
     */
    public function commentString($string)
    {
        return strtr($string, '*/', '-');
    }

    /**
     * Outputs export header
     *
     * @return bool Whether it succeeded
     */
    public function exportHeader()
    {
        $this->export->outputHandler(
            '<?php' . $GLOBALS['crlf']
            . '/**' . $GLOBALS['crlf']
            . ' * Export to PHP Array plugin for PHPMyAdmin' . $GLOBALS['crlf']
            . ' * @version ' . PMA_VERSION . $GLOBALS['crlf']
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
        $this->export->outputHandler(
            '/**' . $GLOBALS['crlf']
            . ' * Database ' . $this->commentString(Util::backquote($db_alias))
            . $GLOBALS['crlf'] . ' */' . $GLOBALS['crlf']
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
        $db,
        $table,
        $crlf,
        $error_url,
        $sql_query,
        array $aliases = []
    ) {
        global $dbi;

        $db_alias = $db;
        $table_alias = $table;
        $this->initAlias($aliases, $db_alias, $table_alias);

        $result = $dbi->query(
            $sql_query,
            DatabaseInterface::CONNECT_USER,
            DatabaseInterface::QUERY_UNBUFFERED
        );

        $columns_cnt = $dbi->numFields($result);
        $columns = [];
        for ($i = 0; $i < $columns_cnt; $i++) {
            $col_as = $dbi->fieldName($result, $i);
            if (! empty($aliases[$db]['tables'][$table]['columns'][$col_as])) {
                $col_as = $aliases[$db]['tables'][$table]['columns'][$col_as];
            }
            $columns[$i] = stripslashes($col_as);
        }

        // fix variable names (based on
        // https://www.php.net/manual/en/language.variables.basics.php)
        if (! preg_match(
            '/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/',
            $table_alias
        )
        ) {
            // fix invalid characters in variable names by replacing them with
            // underscores
            $tablefixed = preg_replace(
                '/[^a-zA-Z0-9_\x7f-\xff]/',
                '_',
                $table_alias
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
        $buffer .= $crlf . '/* '
            . $this->commentString(Util::backquote($db_alias)) . '.'
            . $this->commentString(Util::backquote($table_alias)) . ' */' . $crlf;
        $buffer .= '$' . $tablefixed . ' = array(';
        if (! $this->export->outputHandler($buffer)) {
            return false;
        }
        // Reset the buffer
        $buffer = '';
        while ($record = $dbi->fetchRow($result)) {
            $record_cnt++;

            if ($record_cnt == 1) {
                $buffer .= $crlf . '  array(';
            } else {
                $buffer .= ',' . $crlf . '  array(';
            }

            for ($i = 0; $i < $columns_cnt; $i++) {
                $buffer .= var_export($columns[$i], true)
                    . ' => ' . var_export($record[$i], true)
                    . ($i + 1 >= $columns_cnt ? '' : ',');
            }

            $buffer .= ')';
            if (! $this->export->outputHandler($buffer)) {
                return false;
            }
            // Reset the buffer
            $buffer = '';
        }

        $buffer .= $crlf . ');' . $crlf;
        if (! $this->export->outputHandler($buffer)) {
            return false;
        }

        $dbi->freeResult($result);

        return true;
    }

    /**
     * Outputs result of raw query as PHP array
     *
     * @param string $err_url   the url to go back in case of error
     * @param string $sql_query the rawquery to output
     * @param string $crlf      the end of line sequence
     *
     * @return bool if succeeded
     */
    public function exportRawQuery(string $err_url, string $sql_query, string $crlf): bool
    {
        return $this->exportData('', '', $crlf, $err_url, $sql_query);
    }
}
