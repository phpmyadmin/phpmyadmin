<?php
/**
 * Export to Texy! text.
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Export;

use PhpMyAdmin\Column;
use PhpMyAdmin\Dbal\ConnectionType;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Export\StructureOrData;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Plugins\ExportPlugin;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Options\Items\RadioPropertyItem;
use PhpMyAdmin\Properties\Options\Items\TextPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use PhpMyAdmin\Triggers\Trigger;
use PhpMyAdmin\Triggers\Triggers;
use PhpMyAdmin\Util;

use function __;
use function htmlspecialchars;
use function in_array;
use function is_string;
use function str_replace;

/**
 * Handles the export for the Texy! text class
 */
class ExportTexytext extends ExportPlugin
{
    private bool $columns = false;
    private bool $doComments = false;
    private bool $doMime = false;
    private bool $doRelation = false;
    private string $null = '';

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
     * @param string $db      Database name
     * @param string $dbAlias Aliases of db
     */
    public function exportDBCreate(string $db, string $dbAlias = ''): bool
    {
        return true;
    }

    /**
     * Outputs the content of a table in NHibernate format
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
        $tableAlias = $this->getTableAlias($aliases, $db, $table);

        if (
            ! $this->export->outputHandler(
                $tableAlias !== ''
                ? '== ' . __('Dumping data for table') . ' ' . $tableAlias . "\n\n"
                : '==' . __('Dumping data for query result') . "\n\n",
            )
        ) {
            return false;
        }

        $dbi = DatabaseInterface::getInstance();
        /**
         * Gets the data from the database
         */
        $result = $dbi->query($sqlQuery, ConnectionType::User, DatabaseInterface::QUERY_UNBUFFERED);

        // If required, get fields name at the first line
        if ($this->columns) {
            $textOutput = "|------\n";
            foreach ($result->getFieldNames() as $colAs) {
                $colAs = $this->getColumnAlias($aliases, $db, $table, $colAs);

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
                    $value = $this->null;
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
     * @param string|null $db       the database where the query is executed
     * @param string      $sqlQuery the rawquery to output
     */
    public function exportRawQuery(string|null $db, string $sqlQuery): bool
    {
        if ($db !== null) {
            DatabaseInterface::getInstance()->selectDb($db);
        }

        return $this->exportData($db ?? '', '', $sqlQuery);
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
        $dbi = DatabaseInterface::getInstance();
        $keys = $dbi->getTableIndexes($db, $view);
        foreach ($keys as $key) {
            if ($key['Non_unique'] != 0) {
                continue;
            }

            $uniqueKeys[] = $key['Column_name'];
        }

        /**
         * Gets fields properties
         */
        $dbi->selectDb($db);

        /**
         * Displays the table structure
         */

        $textOutput .= "|------\n"
            . '|' . __('Column')
            . '|' . __('Type')
            . '|' . __('Null')
            . '|' . __('Default')
            . "\n|------\n";

        $columns = $dbi->getColumns($db, $view);
        foreach ($columns as $column) {
            $colAs = $this->getColumnAlias($aliases, $db, $view, $column->field);

            $textOutput .= $this->formatOneColumnDefinition($column, $uniqueKeys, $colAs);
            $textOutput .= "\n";
        }

        return $textOutput;
    }

    /**
     * Returns $table's CREATE definition
     *
     * @param string  $db      the database name
     * @param string  $table   the table name
     * @param mixed[] $aliases Aliases of db/table/columns
     *
     * @return string resulting schema
     */
    public function getTableDef(string $db, string $table, array $aliases = []): string
    {
        $relationParameters = $this->relation->getRelationParameters();

        $textOutput = '';

        /**
         * Get the unique keys in the table
         */
        $uniqueKeys = [];
        $dbi = DatabaseInterface::getInstance();
        $keys = $dbi->getTableIndexes($db, $table);
        foreach ($keys as $key) {
            if ($key['Non_unique'] != 0) {
                continue;
            }

            $uniqueKeys[] = $key['Column_name'];
        }

        /**
         * Gets fields properties
         */
        $dbi->selectDb($db);

        // Check if we can use Relations
        $foreigners = $this->doRelation && $relationParameters->relationFeature !== null ?
            $this->relation->getForeigners($db, $table)
            : [];

        /**
         * Displays the table structure
         */

        $textOutput .= "|------\n";
        $textOutput .= '|' . __('Column');
        $textOutput .= '|' . __('Type');
        $textOutput .= '|' . __('Null');
        $textOutput .= '|' . __('Default');
        if ($this->doRelation && $foreigners !== []) {
            $textOutput .= '|' . __('Links to');
        }

        if ($this->doComments) {
            $textOutput .= '|' . __('Comments');
            $comments = $this->relation->getComments($db, $table);
        }

        if ($this->doMime && $relationParameters->browserTransformationFeature !== null) {
            $textOutput .= '|' . __('Media type');
            $mimeMap = $this->transformations->getMime($db, $table, true);
        }

        $textOutput .= "\n|------\n";

        $columns = $dbi->getColumns($db, $table);
        foreach ($columns as $column) {
            $colAs = $this->getColumnAlias($aliases, $db, $table, $column->field);

            $textOutput .= $this->formatOneColumnDefinition($column, $uniqueKeys, $colAs);
            $fieldName = $column->field;
            if ($this->doRelation && $foreigners !== []) {
                $textOutput .= '|' . htmlspecialchars(
                    $this->getRelationString(
                        $foreigners,
                        $fieldName,
                        $db,
                        $aliases,
                    ),
                );
            }

            if ($this->doComments && $relationParameters->columnCommentsFeature !== null) {
                $textOutput .= '|'
                    . (isset($comments[$fieldName])
                        ? htmlspecialchars($comments[$fieldName])
                        : '');
            }

            if ($this->doMime && $relationParameters->browserTransformationFeature !== null) {
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
     * @param Trigger[] $triggers
     *
     * @return string Formatted triggers list
     */
    public function getTriggers(array $triggers): string
    {
        $dump = "|------\n";
        $dump .= '|' . __('Name');
        $dump .= '|' . __('Time');
        $dump .= '|' . __('Event');
        $dump .= '|' . __('Definition');
        $dump .= "\n|------\n";

        foreach ($triggers as $trigger) {
            $dump .= '|' . $trigger->name->getName();
            $dump .= '|' . $trigger->timing->value;
            $dump .= '|' . $trigger->event->value;
            $dump .= '|' . str_replace('|', '&#124;', htmlspecialchars($trigger->statement));
            $dump .= "\n";
        }

        return $dump;
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
        $tableAlias = $this->getTableAlias($aliases, $db, $table);
        $dump = '';

        switch ($exportMode) {
            case 'create_table':
                $dump .= '== ' . __('Table structure for table') . ' '
                . $tableAlias . "\n\n";
                $dump .= $this->getTableDef($db, $table, $aliases);
                break;
            case 'triggers':
                $triggers = Triggers::getDetails(DatabaseInterface::getInstance(), $db, $table);
                if ($triggers !== []) {
                    $dump .= '== ' . __('Triggers') . ' ' . $tableAlias . "\n\n";
                    $dump .= $this->getTriggers($triggers);
                }

                break;
            case 'create_view':
                $dump .= '== ' . __('Structure for view') . ' ' . $tableAlias . "\n\n";
                $dump .= $this->getTableDef($db, $table, $aliases);
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
     * @param Column  $column     info about this column
     * @param mixed[] $uniqueKeys unique keys for this table
     * @param string  $colAlias   Column Alias
     *
     * @return string Formatted column definition
     */
    public function formatOneColumnDefinition(
        Column $column,
        array $uniqueKeys,
        string $colAlias = '',
    ): string {
        if ($colAlias === '') {
            $colAlias = $column->field;
        }

        $extractedColumnSpec = Util::extractColumnSpec($column->type);
        $type = $extractedColumnSpec['print_type'];
        if (empty($type)) {
            $type = '&nbsp;';
        }

        $fmtPre = '';
        $fmtPost = '';
        if (in_array($column->field, $uniqueKeys, true)) {
            $fmtPre = '**' . $fmtPre;
            $fmtPost .= '**';
        }

        if ($column->key === 'PRI') {
            $fmtPre = '//' . $fmtPre;
            $fmtPost .= '//';
        }

        $definition = '|' . $fmtPre . htmlspecialchars($colAlias) . $fmtPost;
        $definition .= '|' . htmlspecialchars($type);
        $definition .= '|' . ($column->isNull ? __('Yes') : __('No'));
        $definition .= '|' . htmlspecialchars($column->default ?? ($column->isNull ? 'NULL' : ''));

        return $definition;
    }

    /** @inheritDoc */
    public function setExportOptions(ServerRequest $request, array $exportConfig): void
    {
        $this->structureOrData = $this->setStructureOrData(
            $request->getParsedBodyParam('texytext_structure_or_data'),
            $exportConfig['texytext_structure_or_data'] ?? null,
            StructureOrData::StructureAndData,
        );
        $this->columns = (bool) ($request->getParsedBodyParam('texytext_columns')
            ?? $exportConfig['texytext_columns'] ?? false);
        $this->doRelation = (bool) ($request->getParsedBodyParam('texytext_relation')
            ?? $exportConfig['texytext_relation'] ?? false);
        $this->doMime = (bool) ($request->getParsedBodyParam('texytext_mime')
            ?? $exportConfig['texytext_mime'] ?? false);
        $this->doComments = (bool) ($request->getParsedBodyParam('texytext_comments')
            ?? $exportConfig['texytext_comments'] ?? false);
        $this->null = $this->setStringValue(
            $request->getParsedBodyParam('texytext_null'),
            $exportConfig['texytext_null'] ?? null,
        );
    }

    private function setStringValue(mixed $fromRequest, mixed $fromConfig): string
    {
        if (is_string($fromRequest) && $fromRequest !== '') {
            return $fromRequest;
        }

        if (is_string($fromConfig) && $fromConfig !== '') {
            return $fromConfig;
        }

        return '';
    }
}
