<?php

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Export;

use DateTimeImmutable;
use PhpMyAdmin\Config;
use PhpMyAdmin\Current;
use PhpMyAdmin\Database\Events;
use PhpMyAdmin\Database\Routines;
use PhpMyAdmin\Database\RoutineType;
use PhpMyAdmin\Dbal\ConnectionType;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Export\Export;
use PhpMyAdmin\Export\StructureOrData;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Plugins\ExportPlugin;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Options\Items\HiddenPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use PhpMyAdmin\Triggers\Triggers;
use PhpMyAdmin\Util;
use PhpMyAdmin\Version;

use function __;
use function htmlspecialchars;
use function mb_substr;
use function rtrim;
use function str_replace;

use const PHP_VERSION;

/**
 * Used to build XML dumps of tables
 */
class ExportXml extends ExportPlugin
{
    /**
     * Table name
     */
    private string $table = '';
    /**
     * Table names
     *
     * @var string[]
     */
    private array $tables = [];

    private bool $exportContents = false;
    private bool $exportEvents = false;
    private bool $exportFunctions = false;
    private bool $exportProcedures = false;
    private bool $exportTables = false;
    private bool $exportTriggers = false;
    private bool $exportViews = false;

    /** @psalm-return non-empty-lowercase-string */
    public function getName(): string
    {
        return 'xml';
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
            __('Object creation options (all are recommended)'),
        );

        // create primary items and add them to the group
        $leaf = new BoolPropertyItem(
            'export_events',
            __('Events'),
        );
        $structure->addProperty($leaf);
        $leaf = new BoolPropertyItem(
            'export_functions',
            __('Functions'),
        );
        $structure->addProperty($leaf);
        $leaf = new BoolPropertyItem(
            'export_procedures',
            __('Procedures'),
        );
        $structure->addProperty($leaf);
        $leaf = new BoolPropertyItem(
            'export_tables',
            __('Tables'),
        );
        $structure->addProperty($leaf);
        $leaf = new BoolPropertyItem(
            'export_triggers',
            __('Triggers'),
        );
        $structure->addProperty($leaf);
        $leaf = new BoolPropertyItem(
            'export_views',
            __('Views'),
        );
        $structure->addProperty($leaf);
        $exportSpecificOptions->addProperty($structure);

        // data main group
        $data = new OptionsPropertyMainGroup(
            'data',
            __('Data dump options'),
        );
        // create primary items and add them to the group
        $leaf = new BoolPropertyItem(
            'export_contents',
            __('Export contents'),
        );
        $data->addProperty($leaf);
        $exportSpecificOptions->addProperty($data);

        // set the options for the export plugin property item
        $exportPluginProperties->setOptions($exportSpecificOptions);

        return $exportPluginProperties;
    }

    /**
     * Generates output for SQL definitions.
     *
     * @param string   $db    Database name
     * @param string   $type  Item type to be used in XML output
     * @param string[] $names Names of items to export
     * @psalm-param 'event'|'function'|'procedure' $type
     *
     * @return string XML with definitions
     */
    private function exportDefinitions(string $db, string $type, array $names): string
    {
        $head = '';

        foreach ($names as $name) {
            $head .= '            <pma:' . $type . ' name="' . htmlspecialchars($name) . '">' . "\n";

            $dbi = DatabaseInterface::getInstance();
            $definition = match ($type) {
                'function' => Routines::getFunctionDefinition($dbi, $db, $name),
                'procedure' => Routines::getProcedureDefinition($dbi, $db, $name),
                default => Events::getDefinition($dbi, $db, $name),
            };

            // Do some formatting
            $sql = htmlspecialchars(rtrim((string) $definition));
            $sql = str_replace("\n", "\n                ", $sql);

            $head .= '                ' . $sql . "\n";
            $head .= '            </pma:' . $type . '>' . "\n";
        }

        return $head;
    }

    /**
     * Outputs export header. It is the first method to be called, so all
     * the required variables are initialized here.
     */
    public function exportHeader(): bool
    {
        $this->setTable(Current::$table);

        $table = $this->getTable();
        $tables = $this->getTables();

        $exportStruct = $this->exportFunctions
            || $this->exportProcedures
            || $this->exportTables
            || $this->exportTriggers
            || $this->exportViews;

        $charset = Export::$outputCharsetConversion ? Current::$charset : 'utf-8';

        $config = Config::getInstance();
        $head = '<?xml version="1.0" encoding="' . $charset . '"?>' . "\n"
            . '<!--' . "\n"
            . '- phpMyAdmin XML Dump' . "\n"
            . '- version ' . Version::VERSION . "\n"
            . '- https://www.phpmyadmin.net' . "\n"
            . '-' . "\n"
            . '- ' . __('Host:') . ' ' . htmlspecialchars($config->selectedServer['host']);
        if (! empty($config->selectedServer['port'])) {
            $head .= ':' . $config->selectedServer['port'];
        }

        $dbi = DatabaseInterface::getInstance();
        $head .= "\n"
            . '- ' . __('Generation Time:') . ' '
            . Util::localisedDate(new DateTimeImmutable()) . "\n"
            . '- ' . __('Server version:') . ' ' . $dbi->getVersionString() . "\n"
            . '- ' . __('PHP Version:') . ' ' . PHP_VERSION . "\n"
            . '-->' . "\n\n";

        $head .= '<pma_xml_export version="1.0"'
            . ($exportStruct
                ? ' xmlns:pma="https://www.phpmyadmin.net/some_doc_url/"'
                : '')
            . '>' . "\n";

        if ($exportStruct) {
            $result = $dbi->fetchSingleRow(
                'SELECT `DEFAULT_CHARACTER_SET_NAME`, `DEFAULT_COLLATION_NAME`'
                . ' FROM `information_schema`.`SCHEMATA` WHERE `SCHEMA_NAME`'
                . ' = ' . $dbi->quoteString(Current::$database) . ' LIMIT 1',
            );
            $dbCollation = $result['DEFAULT_COLLATION_NAME'];
            $dbCharset = $result['DEFAULT_CHARACTER_SET_NAME'];

            $head .= '    <!--' . "\n";
            $head .= '    - Structure schemas' . "\n";
            $head .= '    -->' . "\n";
            $head .= '    <pma:structure_schemas>' . "\n";
            $head .= '        <pma:database name="' . htmlspecialchars(Current::$database)
                . '" collation="' . htmlspecialchars($dbCollation) . '" charset="' . htmlspecialchars($dbCharset)
                . '">' . "\n";

            if ($tables === []) {
                $tables[] = $table;
            }

            foreach ($tables as $table) {
                // Export tables and views
                $result = $dbi->fetchResult(
                    'SHOW CREATE TABLE ' . Util::backquote(Current::$database) . '.'
                    . Util::backquote($table),
                    0,
                );

                if ($result === []) {
                    continue;
                }

                $tbl = (string) $result[$table][1];

                $isView = $dbi->getTable(Current::$database, $table)
                    ->isView();

                $type = $isView ? 'view' : 'table';

                if ($isView && ! $this->exportViews) {
                    continue;
                }

                if (! $isView && ! $this->exportTables) {
                    continue;
                }

                $head .= '            <pma:' . $type . ' name="' . htmlspecialchars($table) . '">'
                    . "\n";

                $tbl = '                ' . htmlspecialchars($tbl);
                $tbl = str_replace("\n", "\n                ", $tbl);

                $head .= $tbl . ';' . "\n";
                $head .= '            </pma:' . $type . '>' . "\n";

                if (! $this->exportTriggers) {
                    continue;
                }

                // Export triggers
                $triggers = Triggers::getDetails($dbi, Current::$database, $table);

                foreach ($triggers as $trigger) {
                    $code = $trigger->getCreateSql();
                    $head .= '            <pma:trigger name="'
                        . htmlspecialchars($trigger->name->getName()) . '">' . "\n";

                    // Do some formatting
                    $code = mb_substr(rtrim($code), 0, -3);
                    $code = '                ' . htmlspecialchars($code);
                    $code = str_replace("\n", "\n                ", $code);

                    $head .= $code . "\n";
                    $head .= '            </pma:trigger>' . "\n";
                }

                unset($trigger, $triggers);
            }

            if ($this->exportFunctions) {
                $head .= $this->exportDefinitions(
                    Current::$database,
                    'function',
                    Routines::getNames($dbi, Current::$database, RoutineType::Function),
                );
            }

            if ($this->exportProcedures) {
                $head .= $this->exportDefinitions(
                    Current::$database,
                    'procedure',
                    Routines::getNames($dbi, Current::$database, RoutineType::Procedure),
                );
            }

            if ($this->exportEvents) {
                // Export events
                $events = $dbi->fetchSingleColumn(
                    'SELECT EVENT_NAME FROM information_schema.EVENTS '
                    . 'WHERE EVENT_SCHEMA=' . $dbi->quoteString(Current::$database),
                );
                $head .= $this->exportDefinitions(Current::$database, 'event', $events);
            }

            $head .= '        </pma:database>' . "\n";
            $head .= '    </pma:structure_schemas>' . "\n";

            if ($this->exportContents) {
                $head .= "\n";
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
    public function exportDBHeader(string $db, string $dbAlias = ''): bool
    {
        if ($dbAlias === '') {
            $dbAlias = $db;
        }

        if ($this->exportContents) {
            $head = '    <!--' . "\n"
                . '    - ' . __('Database:') . ' \''
                . htmlspecialchars($dbAlias) . '\'' . "\n"
                . '    -->' . "\n" . '    <database name="'
                . htmlspecialchars($dbAlias) . '">' . "\n";

            return $this->export->outputHandler($head);
        }

        return true;
    }

    /**
     * Outputs database footer
     *
     * @param string $db Database name
     */
    public function exportDBFooter(string $db): bool
    {
        if ($this->exportContents) {
            return $this->export->outputHandler('    </database>' . "\n");
        }

        return true;
    }

    /**
     * Outputs CREATE DATABASE statement
     *
     * @param string $db      Database name
     * @param string $dbAlias Aliases of db
     */
    public function exportDBCreate(string $db, string $dbAlias = ''): bool
    {
        return true;
    }

    /**
     * Outputs the content of a table in XML format
     *
     * @param string  $db       database name
     * @param string  $table    table name
     * @param string  $sqlQuery SQL query for obtaining data
     * @param mixed[] $aliases  Aliases of db/table/columns
     */
    public function exportData(
        string $db,
        string $table,
        string $sqlQuery,
        array $aliases = [],
    ): bool {
        $dbi = DatabaseInterface::getInstance();
        // Do not export data for merge tables
        if ($dbi->getTable($db, $table)->isMerge()) {
            return true;
        }

        $tableAlias = $this->getTableAlias($aliases, $db, $table);
        if ($this->exportContents) {
            $result = $dbi->query($sqlQuery, ConnectionType::User, DatabaseInterface::QUERY_UNBUFFERED);

            $columnsCnt = $result->numFields();
            $columns = $result->getFieldNames();

            $buffer = '        <!-- ' . __('Table') . ' '
                . htmlspecialchars($tableAlias) . ' -->' . "\n";
            if (! $this->export->outputHandler($buffer)) {
                return false;
            }

            while ($record = $result->fetchRow()) {
                $buffer = '        <table name="'
                    . htmlspecialchars($tableAlias) . '">' . "\n";
                for ($i = 0; $i < $columnsCnt; $i++) {
                    $colAs = $columns[$i];
                    if (! empty($aliases[$db]['tables'][$table]['columns'][$colAs])) {
                        $colAs = $aliases[$db]['tables'][$table]['columns'][$colAs];
                    }

                    // If a cell is NULL, still export it to preserve
                    // the XML structure
                    if (! isset($record[$i])) {
                        $record[$i] = 'NULL';
                    }

                    $buffer .= '            <column name="'
                        . htmlspecialchars($colAs) . '">'
                        . htmlspecialchars($record[$i])
                        . '</column>' . "\n";
                }

                $buffer .= '        </table>' . "\n";

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
     */
    private function getTable(): string
    {
        return $this->table;
    }

    /**
     * Sets the table name
     *
     * @param string $table table name
     */
    private function setTable(string $table): void
    {
        $this->table = $table;
    }

    /**
     * Gets the table names
     *
     * @return string[]
     */
    private function getTables(): array
    {
        return $this->tables;
    }

    /**
     * Sets the table names
     *
     * @param string[] $tables table names
     */
    public function setTables(array $tables): void
    {
        $this->tables = $tables;
    }

    public static function isAvailable(): bool
    {
        // Can't do server export.
        return Current::$database !== '';
    }

    /** @inheritDoc */
    public function setExportOptions(ServerRequest $request, array $exportConfig): void
    {
        $this->structureOrData = $this->setStructureOrData(
            $request->getParsedBodyParam('xml_structure_or_data'),
            $exportConfig['xml_structure_or_data'] ?? null,
            StructureOrData::Data,
        );
        $this->exportContents = (bool) ($request->getParsedBodyParam('xml_export_contents')
            ?? $exportConfig['xml_export_contents'] ?? false);
        $this->exportEvents = (bool) ($request->getParsedBodyParam('xml_export_events')
            ?? $exportConfig['xml_export_events'] ?? false);
        $this->exportFunctions = (bool) ($request->getParsedBodyParam('xml_export_functions')
            ?? $exportConfig['xml_export_functions'] ?? false);
        $this->exportProcedures = (bool) ($request->getParsedBodyParam('xml_export_procedures')
            ?? $exportConfig['xml_export_procedures'] ?? false);
        $this->exportTables = (bool) ($request->getParsedBodyParam('xml_export_tables')
            ?? $exportConfig['xml_export_tables'] ?? false);
        $this->exportTriggers = (bool) ($request->getParsedBodyParam('xml_export_triggers')
            ?? $exportConfig['xml_export_triggers'] ?? false);
        $this->exportViews = (bool) ($request->getParsedBodyParam('xml_export_views')
            ?? $exportConfig['xml_export_views'] ?? false);
    }
}
