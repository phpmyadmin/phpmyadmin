<?php
/**
 * Abstract class for the export plugins
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins;

use PhpMyAdmin\ConfigStorage\Foreigners;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Export\OutputHandler;
use PhpMyAdmin\Export\StructureOrData;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use PhpMyAdmin\Properties\Plugins\PluginPropertyItem;
use PhpMyAdmin\Transformations;

use function is_string;

/**
 * Provides a common interface that will have to be implemented by all of the
 * export plugins. Some of the plugins will also implement other public
 * methods, but those are not declared here, because they are not implemented
 * by all export plugins.
 */
abstract class ExportPlugin implements Plugin
{
    /**
     * Object containing the specific export plugin type properties.
     */
    protected ExportPluginProperties $properties;

    public static ExportType $exportType = ExportType::Raw;
    public static bool $singleTable = false;

    protected StructureOrData $structureOrData = StructureOrData::Data;

    final public function __construct(
        public Relation $relation,
        protected OutputHandler $outputHandler,
        public Transformations $transformations,
    ) {
        $this->properties = $this->setProperties();
    }

    /**
     * Outputs export header
     */
    public function exportHeader(): bool
    {
        return true;
    }

    /**
     * Outputs export footer
     */
    public function exportFooter(): bool
    {
        return true;
    }

    /**
     * Outputs database header
     *
     * @param string $db      Database name
     * @param string $dbAlias Aliases of db
     */
    public function exportDBHeader(string $db, string $dbAlias = ''): bool
    {
        return true;
    }

    /**
     * Outputs database footer
     *
     * @param string $db Database name
     */
    public function exportDBFooter(string $db): bool
    {
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
     * Outputs the content of a table
     *
     * @param string  $db       database name
     * @param string  $table    table name
     * @param string  $sqlQuery SQL query for obtaining data
     * @param mixed[] $aliases  Aliases of db/table/columns
     */
    abstract public function exportData(
        string $db,
        string $table,
        string $sqlQuery,
        array $aliases = [],
    ): bool;

    /**
     * The following methods are used in /export or in /database/operations,
     * but they are not implemented by all export plugins
     */

    /**
     * Exports routines (procedures and functions)
     *
     * @param string  $db      Database
     * @param mixed[] $aliases Aliases of db/table/columns
     */
    public function exportRoutines(string $db, array $aliases = []): bool
    {
        return true;
    }

    /**
     * Exports events
     *
     * @param string $db Database
     */
    public function exportEvents(string $db): bool
    {
        return true;
    }

    /**
     * Outputs for raw query
     *
     * @param string|null $db       the database where the query is executed
     * @param string      $sqlQuery the rawquery to output
     */
    public function exportRawQuery(string|null $db, string $sqlQuery): bool
    {
        return false;
    }

    /**
     * Outputs table's structure
     *
     * @param string  $db         database name
     * @param string  $table      table name
     * @param string  $exportMode 'create_table', 'triggers', 'create_view', 'stand_in'
     * @param mixed[] $aliases    Aliases of db/table/columns
     */
    public function exportStructure(string $db, string $table, string $exportMode, array $aliases = []): bool
    {
        return true;
    }

    /**
     * Exports metadata from Configuration Storage
     *
     * @param string          $db            database being exported
     * @param string|string[] $tables        table(s) being exported
     * @param string[]        $metadataTypes types of metadata to export
     */
    public function exportMetadata(
        string $db,
        string|array $tables,
        array $metadataTypes,
    ): bool {
        return true;
    }

    /**
     * Returns a stand-in CREATE definition to resolve view dependencies
     *
     * @param string  $db      the database name
     * @param string  $view    the view name
     * @param mixed[] $aliases Aliases of db/table/columns
     *
     * @return string resulting definition
     */
    public function getTableDefStandIn(string $db, string $view, array $aliases = []): string
    {
        return '';
    }

    /**
     * Gets the export specific format plugin properties
     *
     * @return ExportPluginProperties
     */
    public function getProperties(): PluginPropertyItem
    {
        return $this->properties;
    }

    /**
     * Sets the export plugins properties and is implemented by each export plugin.
     */
    abstract protected function setProperties(): ExportPluginProperties;

    /**
     * The following methods are implemented here so that they
     * can be used by all export plugin without overriding it.
     * Note: If you are creating a export plugin then don't include
     * below methods unless you want to override them.
     */

    /** @param mixed[] $aliases Alias information for db/table/columns */
    public function getDbAlias(array $aliases, string $db): string
    {
        return ! empty($aliases[$db]['alias']) ? $aliases[$db]['alias'] : $db;
    }

    /** @param mixed[] $aliases Alias information for db/table/columns */
    public function getTableAlias(array $aliases, string $db, string $table): string
    {
        return ! empty($aliases[$db]['tables'][$table]['alias']) ? $aliases[$db]['tables'][$table]['alias'] : $table;
    }

    /** @param mixed[] $aliases Alias information for db/table/columns */
    public function getColumnAlias(array $aliases, string $db, string $table, string $column): string
    {
        return ! empty($aliases[$db]['tables'][$table]['columns'][$column])
            ? $aliases[$db]['tables'][$table]['columns'][$column]
            : $column;
    }

    /**
     * Search for alias of a identifier.
     *
     * @param mixed[] $aliases Alias information for db/table/column
     * @param string  $id      the identifier to be searched
     *
     * @return string alias of the identifier if found or ''
     */
    public function getAlias(
        array $aliases,
        string $id,
    ): string {
        // search each database
        foreach ($aliases as $dbKey => $db) {
            // check if id is database and has alias
            if ($dbKey === $id && ! empty($db['alias'])) {
                return $db['alias'];
            }

            if (empty($db['tables'])) {
                continue;
            }

            // search each of its tables
            foreach ($db['tables'] as $tableKey => $table) {
                // check if id is table and has alias
                if ($tableKey === $id && ! empty($table['alias'])) {
                    return $table['alias'];
                }

                if (empty($table['columns'])) {
                    continue;
                }

                // search each of its columns
                foreach ($table['columns'] as $colKey => $col) {
                    // check if id is column
                    if ($colKey === $id && ! empty($col)) {
                        return $col;
                    }
                }
            }
        }

        return '';
    }

    /**
     * Gives the relation string and
     * also substitutes with alias if required
     * in this format:
     * [Foreign Table] ([Foreign Field])
     *
     * @param string  $fieldName the field name
     * @param string  $db        the field name
     * @param mixed[] $aliases   Alias information for db/table/column
     *
     * @return string the Relation string
     */
    public function getRelationString(
        Foreigners $foreigners,
        string $fieldName,
        string $db,
        array $aliases = [],
    ): string {
        $foreigner = $this->relation->searchColumnInForeigners($foreigners, $fieldName);
        if ($foreigner) {
            $ffield = $this->getColumnAlias($aliases, $db, $foreigner['foreign_table'], $foreigner['foreign_field']);
            $ftable = $this->getTableAlias($aliases, $db, $foreigner['foreign_table']);

            return $ftable . ' (' . $ffield . ')';
        }

        return '';
    }

    public static function isAvailable(): bool
    {
        return true;
    }

    /** @param array<mixed> $exportConfig */
    abstract public function setExportOptions(ServerRequest $request, array $exportConfig): void;

    public function includeStructure(): bool
    {
        return $this->structureOrData === StructureOrData::Structure
            || $this->structureOrData === StructureOrData::StructureAndData;
    }

    public function includeData(): bool
    {
        return $this->structureOrData === StructureOrData::Data
            || $this->structureOrData === StructureOrData::StructureAndData;
    }

    protected function setStructureOrData(
        mixed $valueFromRequest,
        mixed $valueFromConfig,
        StructureOrData $defaultValue,
    ): StructureOrData {
        return StructureOrData::tryFrom(is_string($valueFromRequest) ? $valueFromRequest : '')
            ?? StructureOrData::tryFrom(is_string($valueFromConfig) ? $valueFromConfig : '')
            ?? $defaultValue;
    }

    public function getTranslatedText(string $text): string
    {
        return $text;
    }
}
