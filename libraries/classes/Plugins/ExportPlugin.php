<?php
/**
 * Abstract class for the export plugins
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Export;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use PhpMyAdmin\Properties\Plugins\PluginPropertyItem;
use PhpMyAdmin\Transformations;

use function stripos;

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

    final public function __construct(
        public Relation $relation,
        protected Export $export,
        protected Transformations $transformations,
    ) {
        $this->init();
        $this->properties = $this->setProperties();
    }

    /**
     * Outputs export header
     */
    abstract public function exportHeader(): bool;

    /**
     * Outputs export footer
     */
    abstract public function exportFooter(): bool;

    /**
     * Outputs database header
     *
     * @param string $db      Database name
     * @param string $dbAlias Aliases of db
     */
    abstract public function exportDBHeader(string $db, string $dbAlias = ''): bool;

    /**
     * Outputs database footer
     *
     * @param string $db Database name
     */
    abstract public function exportDBFooter(string $db): bool;

    /**
     * Outputs CREATE DATABASE statement
     *
     * @param string $db         Database name
     * @param string $exportType 'server', 'database', 'table'
     * @param string $dbAlias    Aliases of db
     */
    abstract public function exportDBCreate(string $db, string $exportType, string $dbAlias = ''): bool;

    /**
     * Outputs the content of a table
     *
     * @param string  $db       database name
     * @param string  $table    table name
     * @param string  $errorUrl the url to go back in case of error
     * @param string  $sqlQuery SQL query for obtaining data
     * @param mixed[] $aliases  Aliases of db/table/columns
     */
    abstract public function exportData(
        string $db,
        string $table,
        string $errorUrl,
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
     * @param string      $errorUrl the url to go back in case of error
     * @param string|null $db       the database where the query is executed
     * @param string      $sqlQuery the rawquery to output
     */
    public function exportRawQuery(string $errorUrl, string|null $db, string $sqlQuery): bool
    {
        return false;
    }

    /**
     * Outputs table's structure
     *
     * @param string  $db         database name
     * @param string  $table      table name
     * @param string  $errorUrl   the url to go back in case of error
     * @param string  $exportMode 'create_table','triggers','create_view',
     *                             'stand_in'
     * @param string  $exportType 'server', 'database', 'table'
     * @param bool    $relation   whether to include relation comments
     * @param bool    $comments   whether to include the pmadb-style column comments
     *                            as comments in the structure; this is deprecated
     *                            but the parameter is left here because /export
     *                            calls exportStructure() also for other export
     *                            types which use this parameter
     * @param bool    $mime       whether to include mime comments
     * @param bool    $dates      whether to include creation/update/check dates
     * @param mixed[] $aliases    Aliases of db/table/columns
     */
    public function exportStructure(
        string $db,
        string $table,
        string $errorUrl,
        string $exportMode,
        string $exportType,
        bool $relation = false,
        bool $comments = false,
        bool $mime = false,
        bool $dates = false,
        array $aliases = [],
    ): bool {
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
     * Outputs triggers
     *
     * @param string $db    database name
     * @param string $table table name
     *
     * @return string Formatted triggers list
     */
    protected function getTriggers(string $db, string $table): string
    {
        return '';
    }

    /**
     * Plugin specific initializations.
     */
    protected function init(): void
    {
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

    /**
     * Initialize aliases
     *
     * @param mixed[]     $aliases Alias information for db/table/column
     * @param string      $db      the database
     * @param string|null $table   the table
     */
    public function initAlias(array $aliases, string &$db, string|null &$table = null): void
    {
        if (! empty($aliases[$db]['tables'][$table]['alias'])) {
            $table = $aliases[$db]['tables'][$table]['alias'];
        }

        if (empty($aliases[$db]['alias'])) {
            return;
        }

        $db = $aliases[$db]['alias'];
    }

    /**
     * Search for alias of a identifier.
     *
     * @param mixed[] $aliases Alias information for db/table/column
     * @param string  $id      the identifier to be searched
     * @param string  $type    db/tbl/col or any combination of them
     *                         representing what to be searched
     * @param string  $db      the database in which search is to be done
     * @param string  $tbl     the table in which search is to be done
     *
     * @return string alias of the identifier if found or ''
     */
    public function getAlias(
        array $aliases,
        string $id,
        string $type = 'dbtblcol',
        string $db = '',
        string $tbl = '',
    ): string {
        if ($db !== '' && isset($aliases[$db])) {
            $aliases = [$db => $aliases[$db]];
        }

        // search each database
        foreach ($aliases as $dbKey => $db) {
            // check if id is database and has alias
            if (stripos($type, 'db') !== false && $dbKey === $id && ! empty($db['alias'])) {
                return $db['alias'];
            }

            if (empty($db['tables'])) {
                continue;
            }

            if ($tbl !== '' && isset($db['tables'][$tbl])) {
                $db['tables'] = [$tbl => $db['tables'][$tbl]];
            }

            // search each of its tables
            foreach ($db['tables'] as $tableKey => $table) {
                // check if id is table and has alias
                if (stripos($type, 'tbl') !== false && $tableKey === $id && ! empty($table['alias'])) {
                    return $table['alias'];
                }

                if (empty($table['columns'])) {
                    continue;
                }

                // search each of its columns
                foreach ($table['columns'] as $colKey => $col) {
                    // check if id is column
                    if (stripos($type, 'col') !== false && $colKey === $id && ! empty($col)) {
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
     * @param mixed[] $foreigners the foreigners array
     * @param string  $fieldName  the field name
     * @param string  $db         the field name
     * @param mixed[] $aliases    Alias information for db/table/column
     *
     * @return string the Relation string
     */
    public function getRelationString(
        array $foreigners,
        string $fieldName,
        string $db,
        array $aliases = [],
    ): string {
        $foreigner = $this->relation->searchColumnInForeigners($foreigners, $fieldName);
        if ($foreigner) {
            $ftable = $foreigner['foreign_table'];
            $ffield = $foreigner['foreign_field'];
            if (! empty($aliases[$db]['tables'][$ftable]['columns'][$ffield])) {
                $ffield = $aliases[$db]['tables'][$ftable]['columns'][$ffield];
            }

            if (! empty($aliases[$db]['tables'][$ftable]['alias'])) {
                $ftable = $aliases[$db]['tables'][$ftable]['alias'];
            }

            return $ftable . ' (' . $ffield . ')';
        }

        return '';
    }

    public static function isAvailable(): bool
    {
        return true;
    }
}
