<?php

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Export;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Plugins\ExportPlugin;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Options\Items\HiddenPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use PhpMyAdmin\Util;
use PhpMyAdmin\Version;

use function __;
use function count;
use function htmlspecialchars;
use function is_array;
use function mb_substr;
use function rtrim;
use function str_replace;
use function strlen;

use const PHP_VERSION;

/**
 * Used to build XML dumps of tables
 */
class ExportXml extends ExportPlugin
{
    /**
     * Table name
     *
     * @var string
     */
    private $table;
    /**
     * Table names
     *
     * @var array
     */
    private $tables = [];

    /**
     * @psalm-return non-empty-lowercase-string
     */
    public function getName(): string
    {
        return 'xml';
    }

    /**
     * Initialize the local variables that are used for export XML
     */
    private function initSpecificVariables(): void
    {
        $GLOBALS['tables'] = $GLOBALS['tables'] ?? null;

        $this->setTable($GLOBALS['table']);
        if (! is_array($GLOBALS['tables'])) {
            return;
        }

        $this->setTables($GLOBALS['tables']);
    }

    protected function setProperties(): ExportPluginProperties
    {
        // create the export plugin property item
        $exportPluginProperties = new ExportPluginProperties();
        $exportPluginProperties->setText('XML');
        $exportPluginProperties->setExtension('xml');
        $exportPluginProperties->setMimeType('text/xml');
        $exportPluginProperties->setOptionsText(__('Options'));

        // create the root group that will be the options field for
        // $exportPluginProperties
        // this will be shown as "Format specific options"
        $exportSpecificOptions = new OptionsPropertyRootGroup('Format Specific Options');

        // general options main group
        $generalOptions = new OptionsPropertyMainGroup('general_opts');
        // create primary items and add them to the group
        $leaf = new HiddenPropertyItem('structure_or_data');
        $generalOptions->addProperty($leaf);
        // add the main group to the root group
        $exportSpecificOptions->addProperty($generalOptions);

        // export structure main group
        $structure = new OptionsPropertyMainGroup(
            'structure',
            __('Object creation options (all are recommended)')
        );

        // create primary items and add them to the group
        $leaf = new BoolPropertyItem(
            'export_events',
            __('Events')
        );
        $structure->addProperty($leaf);
        $leaf = new BoolPropertyItem(
            'export_functions',
            __('Functions')
        );
        $structure->addProperty($leaf);
        $leaf = new BoolPropertyItem(
            'export_procedures',
            __('Procedures')
        );
        $structure->addProperty($leaf);
        $leaf = new BoolPropertyItem(
            'export_tables',
            __('Tables')
        );
        $structure->addProperty($leaf);
        $leaf = new BoolPropertyItem(
            'export_triggers',
            __('Triggers')
        );
        $structure->addProperty($leaf);
        $leaf = new BoolPropertyItem(
            'export_views',
            __('Views')
        );
        $structure->addProperty($leaf);
        $exportSpecificOptions->addProperty($structure);

        // data main group
        $data = new OptionsPropertyMainGroup(
            'data',
            __('Data dump options')
        );
        // create primary items and add them to the group
        $leaf = new BoolPropertyItem(
            'export_contents',
            __('Export contents')
        );
        $data->addProperty($leaf);
        $exportSpecificOptions->addProperty($data);

        // set the options for the export plugin property item
        $exportPluginProperties->setOptions($exportSpecificOptions);

        return $exportPluginProperties;
    }

    /**
     * Generates output for SQL defintions of routines
     *
     * @param string $db      Database name
     * @param string $type    Item type to be used in XML output
     * @param string $dbitype Item type used in DBI queries
     *
     * @return string XML with definitions
     */
    private function exportRoutinesDefinition($db, $type, $dbitype)
    {
        // Export routines
        $routines = $GLOBALS['dbi']->getProceduresOrFunctions($db, $dbitype);

        return $this->exportDefinitions($db, $type, $dbitype, $routines);
    }

    /**
     * Generates output for SQL defintions
     *
     * @param string $db      Database name
     * @param string $type    Item type to be used in XML output
     * @param string $dbitype Item type used in DBI queries
     * @param array  $names   Names of items to export
     *
     * @return string XML with definitions
     */
    private function exportDefinitions($db, $type, $dbitype, array $names)
    {
        $GLOBALS['crlf'] = $GLOBALS['crlf'] ?? null;

        $head = '';

        if ($names) {
            foreach ($names as $name) {
                $head .= '            <pma:' . $type . ' name="'
                    . htmlspecialchars($name) . '">' . $GLOBALS['crlf'];

                // Do some formatting
                $sql = $GLOBALS['dbi']->getDefinition($db, $dbitype, $name);
                $sql = htmlspecialchars(rtrim($sql));
                $sql = str_replace("\n", "\n                ", $sql);

                $head .= '                ' . $sql . $GLOBALS['crlf'];
                $head .= '            </pma:' . $type . '>' . $GLOBALS['crlf'];
            }
        }

        return $head;
    }

    /**
     * Outputs export header. It is the first method to be called, so all
     * the required variables are initialized here.
     */
    public function exportHeader(): bool
    {
        $this->initSpecificVariables();

        $GLOBALS['crlf'] = $GLOBALS['crlf'] ?? null;
        $table = $this->getTable();
        $tables = $this->getTables();

        $export_struct = isset($GLOBALS['xml_export_functions'])
            || isset($GLOBALS['xml_export_procedures'])
            || isset($GLOBALS['xml_export_tables'])
            || isset($GLOBALS['xml_export_triggers'])
            || isset($GLOBALS['xml_export_views']);
        $export_data = isset($GLOBALS['xml_export_contents']);

        if ($GLOBALS['output_charset_conversion']) {
            $charset = $GLOBALS['charset'];
        } else {
            $charset = 'utf-8';
        }

        $head = '<?xml version="1.0" encoding="' . $charset . '"?>' . $GLOBALS['crlf']
            . '<!--' . $GLOBALS['crlf']
            . '- phpMyAdmin XML Dump' . $GLOBALS['crlf']
            . '- version ' . Version::VERSION . $GLOBALS['crlf']
            . '- https://www.phpmyadmin.net' . $GLOBALS['crlf']
            . '-' . $GLOBALS['crlf']
            . '- ' . __('Host:') . ' ' . htmlspecialchars($GLOBALS['cfg']['Server']['host']);
        if (! empty($GLOBALS['cfg']['Server']['port'])) {
            $head .= ':' . $GLOBALS['cfg']['Server']['port'];
        }

        $head .= $GLOBALS['crlf']
            . '- ' . __('Generation Time:') . ' '
            . Util::localisedDate() . $GLOBALS['crlf']
            . '- ' . __('Server version:') . ' ' . $GLOBALS['dbi']->getVersionString() . $GLOBALS['crlf']
            . '- ' . __('PHP Version:') . ' ' . PHP_VERSION . $GLOBALS['crlf']
            . '-->' . $GLOBALS['crlf'] . $GLOBALS['crlf'];

        $head .= '<pma_xml_export version="1.0"'
            . ($export_struct
                ? ' xmlns:pma="https://www.phpmyadmin.net/some_doc_url/"'
                : '')
            . '>' . $GLOBALS['crlf'];

        if ($export_struct) {
            $result = $GLOBALS['dbi']->fetchResult(
                'SELECT `DEFAULT_CHARACTER_SET_NAME`, `DEFAULT_COLLATION_NAME`'
                . ' FROM `information_schema`.`SCHEMATA` WHERE `SCHEMA_NAME`'
                . ' = \'' . $GLOBALS['dbi']->escapeString($GLOBALS['db']) . '\' LIMIT 1'
            );
            $db_collation = $result[0]['DEFAULT_COLLATION_NAME'];
            $db_charset = $result[0]['DEFAULT_CHARACTER_SET_NAME'];

            $head .= '    <!--' . $GLOBALS['crlf'];
            $head .= '    - Structure schemas' . $GLOBALS['crlf'];
            $head .= '    -->' . $GLOBALS['crlf'];
            $head .= '    <pma:structure_schemas>' . $GLOBALS['crlf'];
            $head .= '        <pma:database name="' . htmlspecialchars($GLOBALS['db'])
                . '" collation="' . htmlspecialchars($db_collation) . '" charset="' . htmlspecialchars($db_charset)
                . '">' . $GLOBALS['crlf'];

            if (count($tables) === 0) {
                $tables[] = $table;
            }

            foreach ($tables as $table) {
                // Export tables and views
                $result = $GLOBALS['dbi']->fetchResult(
                    'SHOW CREATE TABLE ' . Util::backquote($GLOBALS['db']) . '.'
                    . Util::backquote($table),
                    0
                );
                $tbl = (string) $result[$table][1];

                $is_view = $GLOBALS['dbi']->getTable($GLOBALS['db'], $table)
                    ->isView();

                if ($is_view) {
                    $type = 'view';
                } else {
                    $type = 'table';
                }

                if ($is_view && ! isset($GLOBALS['xml_export_views'])) {
                    continue;
                }

                if (! $is_view && ! isset($GLOBALS['xml_export_tables'])) {
                    continue;
                }

                $head .= '            <pma:' . $type . ' name="' . htmlspecialchars($table) . '">'
                    . $GLOBALS['crlf'];

                $tbl = '                ' . htmlspecialchars($tbl);
                $tbl = str_replace("\n", "\n                ", $tbl);

                $head .= $tbl . ';' . $GLOBALS['crlf'];
                $head .= '            </pma:' . $type . '>' . $GLOBALS['crlf'];

                if (! isset($GLOBALS['xml_export_triggers']) || ! $GLOBALS['xml_export_triggers']) {
                    continue;
                }

                // Export triggers
                $triggers = $GLOBALS['dbi']->getTriggers($GLOBALS['db'], $table);
                if (! $triggers) {
                    continue;
                }

                foreach ($triggers as $trigger) {
                    $code = $trigger['create'];
                    $head .= '            <pma:trigger name="'
                        . htmlspecialchars($trigger['name']) . '">' . $GLOBALS['crlf'];

                    // Do some formatting
                    $code = mb_substr(rtrim($code), 0, -3);
                    $code = '                ' . htmlspecialchars($code);
                    $code = str_replace("\n", "\n                ", $code);

                    $head .= $code . $GLOBALS['crlf'];
                    $head .= '            </pma:trigger>' . $GLOBALS['crlf'];
                }

                unset($trigger, $triggers);
            }

            if (isset($GLOBALS['xml_export_functions']) && $GLOBALS['xml_export_functions']) {
                $head .= $this->exportRoutinesDefinition($GLOBALS['db'], 'function', 'FUNCTION');
            }

            if (isset($GLOBALS['xml_export_procedures']) && $GLOBALS['xml_export_procedures']) {
                $head .= $this->exportRoutinesDefinition($GLOBALS['db'], 'procedure', 'PROCEDURE');
            }

            if (isset($GLOBALS['xml_export_events']) && $GLOBALS['xml_export_events']) {
                // Export events
                $events = $GLOBALS['dbi']->fetchResult(
                    'SELECT EVENT_NAME FROM information_schema.EVENTS '
                    . "WHERE EVENT_SCHEMA='" . $GLOBALS['dbi']->escapeString($GLOBALS['db'])
                    . "'"
                );
                $head .= $this->exportDefinitions($GLOBALS['db'], 'event', 'EVENT', $events);
            }

            unset($result);

            $head .= '        </pma:database>' . $GLOBALS['crlf'];
            $head .= '    </pma:structure_schemas>' . $GLOBALS['crlf'];

            if ($export_data) {
                $head .= $GLOBALS['crlf'];
            }
        }

        return $this->export->outputHandler($head);
    }

    /**
     * Outputs export footer
     */
    public function exportFooter(): bool
    {
        $foot = '</pma_xml_export>';

        return $this->export->outputHandler($foot);
    }

    /**
     * Outputs database header
     *
     * @param string $db      Database name
     * @param string $dbAlias Aliases of db
     */
    public function exportDBHeader($db, $dbAlias = ''): bool
    {
        $GLOBALS['crlf'] = $GLOBALS['crlf'] ?? null;

        if (empty($dbAlias)) {
            $dbAlias = $db;
        }

        if (isset($GLOBALS['xml_export_contents']) && $GLOBALS['xml_export_contents']) {
            $head = '    <!--' . $GLOBALS['crlf']
                . '    - ' . __('Database:') . ' \''
                . htmlspecialchars($dbAlias) . '\'' . $GLOBALS['crlf']
                . '    -->' . $GLOBALS['crlf'] . '    <database name="'
                . htmlspecialchars($dbAlias) . '">' . $GLOBALS['crlf'];

            return $this->export->outputHandler($head);
        }

        return true;
    }

    /**
     * Outputs database footer
     *
     * @param string $db Database name
     */
    public function exportDBFooter($db): bool
    {
        $GLOBALS['crlf'] = $GLOBALS['crlf'] ?? null;

        if (isset($GLOBALS['xml_export_contents']) && $GLOBALS['xml_export_contents']) {
            return $this->export->outputHandler('    </database>' . $GLOBALS['crlf']);
        }

        return true;
    }

    /**
     * Outputs CREATE DATABASE statement
     *
     * @param string $db         Database name
     * @param string $exportType 'server', 'database', 'table'
     * @param string $dbAlias    Aliases of db
     */
    public function exportDBCreate($db, $exportType, $dbAlias = ''): bool
    {
        return true;
    }

    /**
     * Outputs the content of a table in XML format
     *
     * @param string $db       database name
     * @param string $table    table name
     * @param string $crlf     the end of line sequence
     * @param string $errorUrl the url to go back in case of error
     * @param string $sqlQuery SQL query for obtaining data
     * @param array  $aliases  Aliases of db/table/columns
     */
    public function exportData(
        $db,
        $table,
        $crlf,
        $errorUrl,
        $sqlQuery,
        array $aliases = []
    ): bool {
        // Do not export data for merge tables
        if ($GLOBALS['dbi']->getTable($db, $table)->isMerge()) {
            return true;
        }

        $db_alias = $db;
        $table_alias = $table;
        $this->initAlias($aliases, $db_alias, $table_alias);
        if (isset($GLOBALS['xml_export_contents']) && $GLOBALS['xml_export_contents']) {
            $result = $GLOBALS['dbi']->query(
                $sqlQuery,
                DatabaseInterface::CONNECT_USER,
                DatabaseInterface::QUERY_UNBUFFERED
            );

            $columns_cnt = $result->numFields();
            $columns = $result->getFieldNames();

            $buffer = '        <!-- ' . __('Table') . ' '
                . htmlspecialchars($table_alias) . ' -->' . $crlf;
            if (! $this->export->outputHandler($buffer)) {
                return false;
            }

            while ($record = $result->fetchRow()) {
                $buffer = '        <table name="'
                    . htmlspecialchars($table_alias) . '">' . $crlf;
                for ($i = 0; $i < $columns_cnt; $i++) {
                    $col_as = $columns[$i];
                    if (! empty($aliases[$db]['tables'][$table]['columns'][$col_as])) {
                        $col_as = $aliases[$db]['tables'][$table]['columns'][$col_as];
                    }

                    // If a cell is NULL, still export it to preserve
                    // the XML structure
                    if (! isset($record[$i])) {
                        $record[$i] = 'NULL';
                    }

                    $buffer .= '            <column name="'
                        . htmlspecialchars($col_as) . '">'
                        . htmlspecialchars((string) $record[$i])
                        . '</column>' . $crlf;
                }

                $buffer .= '        </table>' . $crlf;

                if (! $this->export->outputHandler($buffer)) {
                    return false;
                }
            }
        }

        return true;
    }

    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */

    /**
     * Gets the table name
     *
     * @return string
     */
    private function getTable()
    {
        return $this->table;
    }

    /**
     * Sets the table name
     *
     * @param string $table table name
     */
    private function setTable($table): void
    {
        $this->table = $table;
    }

    /**
     * Gets the table names
     *
     * @return array
     */
    private function getTables()
    {
        return $this->tables;
    }

    /**
     * Sets the table names
     *
     * @param array $tables table names
     */
    private function setTables(array $tables): void
    {
        $this->tables = $tables;
    }

    public static function isAvailable(): bool
    {
        // Can't do server export.
        return isset($GLOBALS['db']) && strlen($GLOBALS['db']) > 0;
    }
}
