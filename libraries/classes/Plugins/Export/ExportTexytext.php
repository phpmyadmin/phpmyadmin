<?php
/**
 * Export to Texy! text.
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Export;

use PhpMyAdmin\Database\Triggers;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Dbal\Connection;
use PhpMyAdmin\Dbal\ResultInterface;
use PhpMyAdmin\Plugins\ExportPlugin;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Options\Items\RadioPropertyItem;
use PhpMyAdmin\Properties\Options\Items\TextPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use PhpMyAdmin\Util;

use function __;
use function htmlspecialchars;
use function in_array;
use function str_replace;

/**
 * Handles the export for the Texy! text class
 */
class ExportTexytext extends ExportPlugin
{
    /** @psalm-return non-empty-lowercase-string */
    public function getName(): string
    {
        return 'texytext';
    }

    protected function setProperties(): ExportPluginProperties
    {
        $exportPluginProperties = new ExportPluginProperties();
        $exportPluginProperties->setText('Texy! text');
        $exportPluginProperties->setExtension('txt');
        $exportPluginProperties->setMimeType('text/plain');
        $exportPluginProperties->setOptionsText(__('Options'));

        // create the root group that will be the options field for
        // $exportPluginProperties
        // this will be shown as "Format specific options"
        $exportSpecificOptions = new OptionsPropertyRootGroup('Format Specific Options');

        // what to dump (structure/data/both) main group
        $dumpWhat = new OptionsPropertyMainGroup(
            'general_opts',
            __('Dump table'),
        );
        // create primary items and add them to the group
        $leaf = new RadioPropertyItem('structure_or_data');
        $leaf->setValues(
            ['structure' => __('structure'), 'data' => __('data'), 'structure_and_data' => __('structure and data')],
        );
        $dumpWhat->addProperty($leaf);
        // add the main group to the root group
        $exportSpecificOptions->addProperty($dumpWhat);

        // data options main group
        $dataOptions = new OptionsPropertyMainGroup(
            'data',
            __('Data dump options'),
        );
        $dataOptions->setForce('structure');
        // create primary items and add them to the group
        $leaf = new BoolPropertyItem(
            'columns',
            __('Put columns names in the first row'),
        );
        $dataOptions->addProperty($leaf);
        $leaf = new TextPropertyItem(
            'null',
            __('Replace NULL with:'),
        );
        $dataOptions->addProperty($leaf);
        // add the main group to the root group
        $exportSpecificOptions->addProperty($dataOptions);

        // set the options for the export plugin property item
        $exportPluginProperties->setOptions($exportSpecificOptions);

        return $exportPluginProperties;
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
     * @param string $dbAlias Alias of db
     */
    public function exportDBHeader(string $db, string $dbAlias = ''): bool
    {
        if ($dbAlias === '') {
            $dbAlias = $db;
        }

        return $this->export->outputHandler(
            '===' . __('Database') . ' ' . $dbAlias . "\n\n",
        );
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
     * @param string $db         Database name
     * @param string $exportType 'server', 'database', 'table'
     * @param string $dbAlias    Aliases of db
     */
    public function exportDBCreate(string $db, string $exportType, string $dbAlias = ''): bool
    {
        return true;
    }

    /**
     * Outputs the content of a table in NHibernate format
     *
     * @param string  $db       database name
     * @param string  $table    table name
     * @param string  $errorUrl the url to go back in case of error
     * @param string  $sqlQuery SQL query for obtaining data
     * @param mixed[] $aliases  Aliases of db/table/columns
     */
    public function exportData(
        string $db,
        string $table,
        string $errorUrl,
        string $sqlQuery,
        array $aliases = [],
    ): bool {
        $GLOBALS['what'] ??= null;

        $dbAlias = $db;
        $tableAlias = $table;
        $this->initAlias($aliases, $dbAlias, $tableAlias);

        if (
            ! $this->export->outputHandler(
                $tableAlias != ''
                ? '== ' . __('Dumping data for table') . ' ' . $tableAlias . "\n\n"
                : '==' . __('Dumping data for query result') . "\n\n",
            )
        ) {
            return false;
        }

        /**
         * Gets the data from the database
         *
         * @var ResultInterface $result
         * @psalm-ignore-var
         */
        $result = $GLOBALS['dbi']->query($sqlQuery, Connection::TYPE_USER, DatabaseInterface::QUERY_UNBUFFERED);

        // If required, get fields name at the first line
        if (isset($GLOBALS[$GLOBALS['what'] . '_columns'])) {
            $textOutput = "|------\n";
            foreach ($result->getFieldNames() as $colAs) {
                if (! empty($aliases[$db]['tables'][$table]['columns'][$colAs])) {
                    $colAs = $aliases[$db]['tables'][$table]['columns'][$colAs];
                }

                $textOutput .= '|' . htmlspecialchars($colAs);
            }

            $textOutput .= "\n|------\n";
            if (! $this->export->outputHandler($textOutput)) {
                return false;
            }
        }

        // Format the data
        while ($row = $result->fetchRow()) {
            $textOutput = '';
            foreach ($row as $field) {
                if ($field === null) {
                    $value = $GLOBALS[$GLOBALS['what'] . '_null'];
                } elseif ($field !== '') {
                    $value = $field;
                } else {
                    $value = ' ';
                }

                $textOutput .= '|'
                    . str_replace(
                        '|',
                        '&#124;',
                        htmlspecialchars($value),
                    );
            }

            $textOutput .= "\n";
            if (! $this->export->outputHandler($textOutput)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Outputs result raw query in TexyText format
     *
     * @param string      $errorUrl the url to go back in case of error
     * @param string|null $db       the database where the query is executed
     * @param string      $sqlQuery the rawquery to output
     */
    public function exportRawQuery(string $errorUrl, string|null $db, string $sqlQuery): bool
    {
        if ($db !== null) {
            $GLOBALS['dbi']->selectDb($db);
        }

        return $this->exportData($db ?? '', '', $errorUrl, $sqlQuery);
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
        $textOutput = '';

        /**
         * Get the unique keys in the table
         */
        $uniqueKeys = [];
        $keys = $GLOBALS['dbi']->getTableIndexes($db, $view);
        foreach ($keys as $key) {
            if ($key['Non_unique'] != 0) {
                continue;
            }

            $uniqueKeys[] = $key['Column_name'];
        }

        /**
         * Gets fields properties
         */
        $GLOBALS['dbi']->selectDb($db);

        /**
         * Displays the table structure
         */

        $textOutput .= "|------\n"
            . '|' . __('Column')
            . '|' . __('Type')
            . '|' . __('Null')
            . '|' . __('Default')
            . "\n|------\n";

        $columns = $GLOBALS['dbi']->getColumns($db, $view);
        foreach ($columns as $column) {
            $colAs = $column['Field'] ?? null;
            if (! empty($aliases[$db]['tables'][$view]['columns'][$colAs])) {
                $colAs = $aliases[$db]['tables'][$view]['columns'][$colAs];
            }

            $textOutput .= $this->formatOneColumnDefinition($column, $uniqueKeys, $colAs);
            $textOutput .= "\n";
        }

        return $textOutput;
    }

    /**
     * Returns $table's CREATE definition
     *
     * @param string  $db         the database name
     * @param string  $table      the table name
     * @param bool    $doRelation whether to include relation comments
     * @param bool    $doComments whether to include the pmadb-style column
     *                             comments as comments in the structure;
     *                             this is deprecated but the parameter is
     *                             left here because /export calls
     *                             $this->exportStructure() also for other
     *                             export types which use this parameter
     * @param bool    $doMime     whether to include mime comments
     *                             at the end
     * @param mixed[] $aliases    Aliases of db/table/columns
     *
     * @return string resulting schema
     */
    public function getTableDef(
        string $db,
        string $table,
        bool $doRelation,
        bool $doComments,
        bool $doMime,
        array $aliases = [],
    ): string {
        $relationParameters = $this->relation->getRelationParameters();

        $textOutput = '';

        /**
         * Get the unique keys in the table
         */
        $uniqueKeys = [];
        $keys = $GLOBALS['dbi']->getTableIndexes($db, $table);
        foreach ($keys as $key) {
            if ($key['Non_unique'] != 0) {
                continue;
            }

            $uniqueKeys[] = $key['Column_name'];
        }

        /**
         * Gets fields properties
         */
        $GLOBALS['dbi']->selectDb($db);

        // Check if we can use Relations
        [$resRel, $haveRel] = $this->relation->getRelationsAndStatus(
            $doRelation && $relationParameters->relationFeature !== null,
            $db,
            $table,
        );

        /**
         * Displays the table structure
         */

        $textOutput .= "|------\n";
        $textOutput .= '|' . __('Column');
        $textOutput .= '|' . __('Type');
        $textOutput .= '|' . __('Null');
        $textOutput .= '|' . __('Default');
        if ($doRelation && $haveRel) {
            $textOutput .= '|' . __('Links to');
        }

        if ($doComments) {
            $textOutput .= '|' . __('Comments');
            $comments = $this->relation->getComments($db, $table);
        }

        if ($doMime && $relationParameters->browserTransformationFeature !== null) {
            $textOutput .= '|' . __('Media type');
            $mimeMap = $this->transformations->getMime($db, $table, true);
        }

        $textOutput .= "\n|------\n";

        $columns = $GLOBALS['dbi']->getColumns($db, $table);
        foreach ($columns as $column) {
            $colAs = $column['Field'];
            if (! empty($aliases[$db]['tables'][$table]['columns'][$colAs])) {
                $colAs = $aliases[$db]['tables'][$table]['columns'][$colAs];
            }

            $textOutput .= $this->formatOneColumnDefinition($column, $uniqueKeys, $colAs);
            $fieldName = $column['Field'];
            if ($doRelation && $haveRel) {
                $textOutput .= '|' . htmlspecialchars(
                    $this->getRelationString(
                        $resRel,
                        $fieldName,
                        $db,
                        $aliases,
                    ),
                );
            }

            if ($doComments && $relationParameters->columnCommentsFeature !== null) {
                $textOutput .= '|'
                    . (isset($comments[$fieldName])
                        ? htmlspecialchars($comments[$fieldName])
                        : '');
            }

            if ($doMime && $relationParameters->browserTransformationFeature !== null) {
                $textOutput .= '|'
                    . (isset($mimeMap[$fieldName])
                        ? htmlspecialchars(
                            str_replace('_', '/', $mimeMap[$fieldName]['mimetype']),
                        )
                        : '');
            }

            $textOutput .= "\n";
        }

        return $textOutput;
    }

    /**
     * Outputs triggers
     *
     * @param string $db    database name
     * @param string $table table name
     *
     * @return string Formatted triggers list
     */
    public function getTriggers(string $db, string $table): string
    {
        $dump = "|------\n";
        $dump .= '|' . __('Name');
        $dump .= '|' . __('Time');
        $dump .= '|' . __('Event');
        $dump .= '|' . __('Definition');
        $dump .= "\n|------\n";

        $triggers = Triggers::getDetails($GLOBALS['dbi'], $db, $table);

        foreach ($triggers as $trigger) {
            $dump .= '|' . $trigger['name'];
            $dump .= '|' . $trigger['action_timing'];
            $dump .= '|' . $trigger['event_manipulation'];
            $dump .= '|' . str_replace('|', '&#124;', htmlspecialchars($trigger['definition']));
            $dump .= "\n";
        }

        return $dump;
    }

    /**
     * Outputs table's structure
     *
     * @param string  $db         database name
     * @param string  $table      table name
     * @param string  $errorUrl   the url to go back in case of error
     * @param string  $exportMode 'create_table', 'triggers', 'create_view',
     *                             'stand_in'
     * @param string  $exportType 'server', 'database', 'table'
     * @param bool    $doRelation whether to include relation comments
     * @param bool    $doComments whether to include the pmadb-style column
     *                             comments as comments in the structure;
     *                             this is deprecated but the parameter is
     *                             left here because /export calls
     *                             $this->exportStructure() also for other
     *                             export types which use this parameter
     * @param bool    $doMime     whether to include mime comments
     * @param bool    $dates      whether to include creation/update/check dates
     * @param mixed[] $aliases    Aliases of db/table/columns
     */
    public function exportStructure(
        string $db,
        string $table,
        string $errorUrl,
        string $exportMode,
        string $exportType,
        bool $doRelation = false,
        bool $doComments = false,
        bool $doMime = false,
        bool $dates = false,
        array $aliases = [],
    ): bool {
        $dbAlias = $db;
        $tableAlias = $table;
        $this->initAlias($aliases, $dbAlias, $tableAlias);
        $dump = '';

        switch ($exportMode) {
            case 'create_table':
                $dump .= '== ' . __('Table structure for table') . ' '
                . $tableAlias . "\n\n";
                $dump .= $this->getTableDef($db, $table, $doRelation, $doComments, $doMime, $aliases);
                break;
            case 'triggers':
                $triggers = Triggers::getDetails($GLOBALS['dbi'], $db, $table);
                if ($triggers) {
                    $dump .= '== ' . __('Triggers') . ' ' . $tableAlias . "\n\n";
                    $dump .= $this->getTriggers($db, $table);
                }

                break;
            case 'create_view':
                $dump .= '== ' . __('Structure for view') . ' ' . $tableAlias . "\n\n";
                $dump .= $this->getTableDef($db, $table, $doRelation, $doComments, $doMime, $aliases);
                break;
            case 'stand_in':
                $dump .= '== ' . __('Stand-in structure for view')
                . ' ' . $table . "\n\n";
                // export a stand-in definition to resolve view dependencies
                $dump .= $this->getTableDefStandIn($db, $table, $aliases);
        }

        return $this->export->outputHandler($dump);
    }

    /**
     * Formats the definition for one column
     *
     * @param mixed[] $column     info about this column
     * @param mixed[] $uniqueKeys unique keys for this table
     * @param string  $colAlias   Column Alias
     *
     * @return string Formatted column definition
     */
    public function formatOneColumnDefinition(
        array $column,
        array $uniqueKeys,
        string $colAlias = '',
    ): string {
        if ($colAlias === '') {
            $colAlias = $column['Field'];
        }

        $extractedColumnSpec = Util::extractColumnSpec($column['Type']);
        $type = $extractedColumnSpec['print_type'];
        if (empty($type)) {
            $type = '&nbsp;';
        }

        if (! isset($column['Default'])) {
            if ($column['Null'] !== 'NO') {
                $column['Default'] = 'NULL';
            }
        }

        $fmtPre = '';
        $fmtPost = '';
        if (in_array($column['Field'], $uniqueKeys)) {
            $fmtPre = '**' . $fmtPre;
            $fmtPost .= '**';
        }

        if ($column['Key'] === 'PRI') {
            $fmtPre = '//' . $fmtPre;
            $fmtPost .= '//';
        }

        $definition = '|'
            . $fmtPre . htmlspecialchars($colAlias) . $fmtPost;
        $definition .= '|' . htmlspecialchars($type);
        $definition .= '|'
            . ($column['Null'] == '' || $column['Null'] === 'NO'
                ? __('No') : __('Yes'));
        $definition .= '|'
            . htmlspecialchars($column['Default'] ?? '');

        return $definition;
    }
}
