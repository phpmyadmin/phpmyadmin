<?php
/**
 * Set of functions used to build OpenDocument Text dumps of tables
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Export;

use PhpMyAdmin\Column;
use PhpMyAdmin\Dbal\ConnectionType;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Export\StructureOrData;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\OpenDocument;
use PhpMyAdmin\Plugins\ExportPlugin;
use PhpMyAdmin\Plugins\ExportType;
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
use function bin2hex;
use function htmlspecialchars;
use function is_string;
use function str_replace;

/**
 * Handles the export for the ODT class
 */
class ExportOdt extends ExportPlugin
{
    public string $buffer = '';
    private bool $columns = false;
    private bool $doComments = false;
    private bool $doMime = false;
    private bool $doRelation = false;
    private string $null = '';

    protected function init(): void
    {
        $this->buffer = '';
    }

    /** @psalm-return non-empty-lowercase-string */
    public function getName(): string
    {
        return 'odt';
    }

    protected function setProperties(): ExportPluginProperties
    {
        $hideStructure = false;
        if (ExportPlugin::$exportType === ExportType::Table && ! ExportPlugin::$singleTable) {
            $hideStructure = true;
        }

        $exportPluginProperties = new ExportPluginProperties();
        $exportPluginProperties->setText('OpenDocument Text');
        $exportPluginProperties->setExtension('odt');
        $exportPluginProperties->setMimeType('application/vnd.oasis.opendocument.text');
        $exportPluginProperties->setForceFile(true);
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

        // structure options main group
        if (! $hideStructure) {
            $structureOptions = new OptionsPropertyMainGroup(
                'structure',
                __('Object creation options'),
            );
            $structureOptions->setForce('data');
            $relationParameters = $this->relation->getRelationParameters();
            // create primary items and add them to the group
            if ($relationParameters->relationFeature !== null) {
                $leaf = new BoolPropertyItem(
                    'relation',
                    __('Display foreign key relationships'),
                );
                $structureOptions->addProperty($leaf);
            }

            $leaf = new BoolPropertyItem(
                'comments',
                __('Display comments'),
            );
            $structureOptions->addProperty($leaf);
            if ($relationParameters->browserTransformationFeature !== null) {
                $leaf = new BoolPropertyItem(
                    'mime',
                    __('Display media types'),
                );
                $structureOptions->addProperty($leaf);
            }

            // add the main group to the root group
            $exportSpecificOptions->addProperty($structureOptions);
        }

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
        $this->buffer .= '<?xml version="1.0" encoding="utf-8"?>'
            . '<office:document-content '
            . OpenDocument::NS . ' office:version="1.0">'
            . '<office:body>'
            . '<office:text>';

        return true;
    }

    /**
     * Outputs export footer
     */
    public function exportFooter(): bool
    {
        $this->buffer .= '</office:text></office:body></office:document-content>';

        return $this->export->outputHandler(OpenDocument::create(
            'application/vnd.oasis.opendocument.text',
            $this->buffer,
        ));
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

        $this->buffer .= '<text:h text:outline-level="1" text:style-name="Heading_1"'
            . ' text:is-list-header="true">'
            . __('Database') . ' ' . htmlspecialchars($dbAlias)
            . '</text:h>';

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
        $dbAlias = $db;
        $tableAlias = $table;
        $this->initAlias($aliases, $dbAlias, $tableAlias);
        $dbi = DatabaseInterface::getInstance();
        // Gets the data from the database
        $result = $dbi->query($sqlQuery, ConnectionType::User, DatabaseInterface::QUERY_UNBUFFERED);
        $fieldsCnt = $result->numFields();
        $fieldsMeta = $dbi->getFieldsMeta($result);

        $this->buffer .= '<text:h text:outline-level="2" text:style-name="Heading_2"'
            . ' text:is-list-header="true">';
        $tableAlias !== ''
            ? $this->buffer .= __('Dumping data for table') . ' ' . htmlspecialchars($tableAlias)
            : $this->buffer .= __('Dumping data for query result');
        $this->buffer .= '</text:h>'
            . '<table:table'
            . ' table:name="' . htmlspecialchars($tableAlias) . '_structure">'
            . '<table:table-column'
            . ' table:number-columns-repeated="' . $fieldsCnt . '"/>';

        // If required, get fields name at the first line
        if ($this->columns) {
            $this->buffer .= '<table:table-row>';
            foreach ($fieldsMeta as $field) {
                $colAs = $field->name;
                if (! empty($aliases[$db]['tables'][$table]['columns'][$colAs])) {
                    $colAs = $aliases[$db]['tables'][$table]['columns'][$colAs];
                }

                $this->buffer .= '<table:table-cell office:value-type="string">'
                    . '<text:p>'
                    . htmlspecialchars($colAs)
                    . '</text:p>'
                    . '</table:table-cell>';
            }

            $this->buffer .= '</table:table-row>';
        }

        // Format the data
        while ($row = $result->fetchRow()) {
            $this->buffer .= '<table:table-row>';
            /** @infection-ignore-all */
            for ($j = 0; $j < $fieldsCnt; $j++) {
                if ($fieldsMeta[$j]->isMappedTypeGeometry) {
                    // export GIS types as hex
                    $row[$j] = '0x' . bin2hex($row[$j]);
                }

                if (! isset($row[$j])) {
                    $this->buffer .= '<table:table-cell office:value-type="string">'
                        . '<text:p>'
                        . htmlspecialchars($this->null)
                        . '</text:p>'
                        . '</table:table-cell>';
                } elseif ($fieldsMeta[$j]->isBinary && $fieldsMeta[$j]->isBlob) {
                    // ignore BLOB
                    $this->buffer .= '<table:table-cell office:value-type="string">'
                        . '<text:p></text:p>'
                        . '</table:table-cell>';
                } elseif (
                    $fieldsMeta[$j]->isNumeric
                ) {
                    $this->buffer .= '<table:table-cell office:value-type="float"'
                        . ' office:value="' . $row[$j] . '" >'
                        . '<text:p>'
                        . htmlspecialchars($row[$j])
                        . '</text:p>'
                        . '</table:table-cell>';
                } else {
                    $this->buffer .= '<table:table-cell office:value-type="string">'
                        . '<text:p>'
                        . htmlspecialchars($row[$j])
                        . '</text:p>'
                        . '</table:table-cell>';
                }
            }

            $this->buffer .= '</table:table-row>';
        }

        $this->buffer .= '</table:table>';

        return true;
    }

    /**
     * Outputs result raw query in ODT format
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
        $dbAlias = $db;
        $viewAlias = $view;
        $this->initAlias($aliases, $dbAlias, $viewAlias);
        $dbi = DatabaseInterface::getInstance();
        /**
         * Gets fields properties
         */
        $dbi->selectDb($db);

        /**
         * Displays the table structure
         */
        $this->buffer .= '<table:table table:name="'
            . htmlspecialchars($viewAlias) . '_data">';
        $columnsCnt = 4;
        $this->buffer .= '<table:table-column'
            . ' table:number-columns-repeated="' . $columnsCnt . '"/>';
        /* Header */
        $this->buffer .= '<table:table-row>'
            . '<table:table-cell office:value-type="string">'
            . '<text:p>' . __('Column') . '</text:p>'
            . '</table:table-cell>'
            . '<table:table-cell office:value-type="string">'
            . '<text:p>' . __('Type') . '</text:p>'
            . '</table:table-cell>'
            . '<table:table-cell office:value-type="string">'
            . '<text:p>' . __('Null') . '</text:p>'
            . '</table:table-cell>'
            . '<table:table-cell office:value-type="string">'
            . '<text:p>' . __('Default') . '</text:p>'
            . '</table:table-cell>'
            . '</table:table-row>';

        $columns = $dbi->getColumns($db, $view);
        foreach ($columns as $column) {
            $colAs = $column->field;
            if (! empty($aliases[$db]['tables'][$view]['columns'][$colAs])) {
                $colAs = $aliases[$db]['tables'][$view]['columns'][$colAs];
            }

            $this->buffer .= $this->formatOneColumnDefinition($column, $colAs);
            $this->buffer .= '</table:table-row>';
        }

        $this->buffer .= '</table:table>';

        return '';
    }

    /**
     * Returns $table's CREATE definition
     *
     * @param string  $db      the database name
     * @param string  $table   the table name
     * @param mixed[] $aliases Aliases of db/table/columns
     */
    public function getTableDef(string $db, string $table, array $aliases = []): bool
    {
        $dbAlias = $db;
        $tableAlias = $table;
        $this->initAlias($aliases, $dbAlias, $tableAlias);

        $relationParameters = $this->relation->getRelationParameters();

        $dbi = DatabaseInterface::getInstance();
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
        $this->buffer .= '<table:table table:name="'
            . htmlspecialchars($tableAlias) . '_structure">';
        $columnsCnt = 4;
        if ($this->doRelation && $foreigners !== []) {
            $columnsCnt++;
        }

        if ($this->doComments) {
            $columnsCnt++;
        }

        if ($this->doMime && $relationParameters->browserTransformationFeature !== null) {
            $columnsCnt++;
        }

        $this->buffer .= '<table:table-column'
            . ' table:number-columns-repeated="' . $columnsCnt . '"/>';
        /* Header */
        $this->buffer .= '<table:table-row>'
            . '<table:table-cell office:value-type="string">'
            . '<text:p>' . __('Column') . '</text:p>'
            . '</table:table-cell>'
            . '<table:table-cell office:value-type="string">'
            . '<text:p>' . __('Type') . '</text:p>'
            . '</table:table-cell>'
            . '<table:table-cell office:value-type="string">'
            . '<text:p>' . __('Null') . '</text:p>'
            . '</table:table-cell>'
            . '<table:table-cell office:value-type="string">'
            . '<text:p>' . __('Default') . '</text:p>'
            . '</table:table-cell>';
        if ($this->doRelation && $foreigners !== []) {
            $this->buffer .= '<table:table-cell office:value-type="string">'
                . '<text:p>' . __('Links to') . '</text:p>'
                . '</table:table-cell>';
        }

        if ($this->doComments) {
            $this->buffer .= '<table:table-cell office:value-type="string">'
                . '<text:p>' . __('Comments') . '</text:p>'
                . '</table:table-cell>';
            $comments = $this->relation->getComments($db, $table);
        }

        if ($this->doMime && $relationParameters->browserTransformationFeature !== null) {
            $this->buffer .= '<table:table-cell office:value-type="string">'
                . '<text:p>' . __('Media type') . '</text:p>'
                . '</table:table-cell>';
            $mimeMap = $this->transformations->getMime($db, $table, true);
        }

        $this->buffer .= '</table:table-row>';

        $columns = $dbi->getColumns($db, $table);
        foreach ($columns as $column) {
            $colAs = $fieldName = $column->field;
            if (! empty($aliases[$db]['tables'][$table]['columns'][$colAs])) {
                $colAs = $aliases[$db]['tables'][$table]['columns'][$colAs];
            }

            $this->buffer .= $this->formatOneColumnDefinition($column, $colAs);
            if ($this->doRelation && $foreigners !== []) {
                $foreigner = $this->relation->searchColumnInForeigners($foreigners, $fieldName);
                if ($foreigner) {
                    $rtable = $foreigner['foreign_table'];
                    $rfield = $foreigner['foreign_field'];
                    if (! empty($aliases[$db]['tables'][$rtable]['columns'][$rfield])) {
                        $rfield = $aliases[$db]['tables'][$rtable]['columns'][$rfield];
                    }

                    if (! empty($aliases[$db]['tables'][$rtable]['alias'])) {
                        $rtable = $aliases[$db]['tables'][$rtable]['alias'];
                    }

                    $relation = htmlspecialchars($rtable . ' (' . $rfield . ')');
                    $this->buffer .= '<table:table-cell office:value-type="string">'
                        . '<text:p>'
                        . htmlspecialchars($relation)
                        . '</text:p>'
                        . '</table:table-cell>';
                }
            }

            if ($this->doComments) {
                if (isset($comments[$fieldName])) {
                    $this->buffer .= '<table:table-cell office:value-type="string">'
                        . '<text:p>'
                        . htmlspecialchars($comments[$fieldName])
                        . '</text:p>'
                        . '</table:table-cell>';
                } else {
                    $this->buffer .= '<table:table-cell office:value-type="string">'
                        . '<text:p></text:p>'
                        . '</table:table-cell>';
                }
            }

            if ($this->doMime && $relationParameters->browserTransformationFeature !== null) {
                if (isset($mimeMap[$fieldName])) {
                    $this->buffer .= '<table:table-cell office:value-type="string">'
                        . '<text:p>'
                        . htmlspecialchars(
                            str_replace('_', '/', $mimeMap[$fieldName]['mimetype']),
                        )
                        . '</text:p>'
                        . '</table:table-cell>';
                } else {
                    $this->buffer .= '<table:table-cell office:value-type="string">'
                        . '<text:p></text:p>'
                        . '</table:table-cell>';
                }
            }

            $this->buffer .= '</table:table-row>';
        }

        $this->buffer .= '</table:table>';

        return true;
    }

    /**
     * Outputs triggers
     *
     * @param string    $table    table name
     * @param Trigger[] $triggers
     */
    protected function getTriggers(string $table, array $triggers): string
    {
        $this->buffer .= '<table:table'
            . ' table:name="' . htmlspecialchars($table) . '_triggers">'
            . '<table:table-column'
            . ' table:number-columns-repeated="4"/>'
            . '<table:table-row>'
            . '<table:table-cell office:value-type="string">'
            . '<text:p>' . __('Name') . '</text:p>'
            . '</table:table-cell>'
            . '<table:table-cell office:value-type="string">'
            . '<text:p>' . __('Time') . '</text:p>'
            . '</table:table-cell>'
            . '<table:table-cell office:value-type="string">'
            . '<text:p>' . __('Event') . '</text:p>'
            . '</table:table-cell>'
            . '<table:table-cell office:value-type="string">'
            . '<text:p>' . __('Definition') . '</text:p>'
            . '</table:table-cell>'
            . '</table:table-row>';

        foreach ($triggers as $trigger) {
            $this->buffer .= '<table:table-row>';
            $this->buffer .= '<table:table-cell office:value-type="string">'
                . '<text:p>'
                . htmlspecialchars($trigger->name->getName())
                . '</text:p>'
                . '</table:table-cell>';
            $this->buffer .= '<table:table-cell office:value-type="string">'
                . '<text:p>'
                . htmlspecialchars($trigger->timing->value)
                . '</text:p>'
                . '</table:table-cell>';
            $this->buffer .= '<table:table-cell office:value-type="string">'
                . '<text:p>'
                . htmlspecialchars($trigger->event->value)
                . '</text:p>'
                . '</table:table-cell>';
            $this->buffer .= '<table:table-cell office:value-type="string">'
                . '<text:p>'
                . htmlspecialchars($trigger->statement)
                . '</text:p>'
                . '</table:table-cell>';
            $this->buffer .= '</table:table-row>';
        }

        $this->buffer .= '</table:table>';

        return $this->buffer;
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
        $dbAlias = $db;
        $tableAlias = $table;
        $this->initAlias($aliases, $dbAlias, $tableAlias);
        switch ($exportMode) {
            case 'create_table':
                $this->buffer .= '<text:h text:outline-level="2" text:style-name="Heading_2"'
                . ' text:is-list-header="true">'
                . __('Table structure for table') . ' ' .
                htmlspecialchars($tableAlias)
                . '</text:h>';
                $this->getTableDef($db, $table, $aliases);
                break;
            case 'triggers':
                $triggers = Triggers::getDetails(DatabaseInterface::getInstance(), $db, $table);
                if ($triggers !== []) {
                    $this->buffer .= '<text:h text:outline-level="2" text:style-name="Heading_2"'
                    . ' text:is-list-header="true">'
                    . __('Triggers') . ' '
                    . htmlspecialchars($tableAlias)
                    . '</text:h>';
                    $this->getTriggers($tableAlias, $triggers);
                }

                break;
            case 'create_view':
                $this->buffer .= '<text:h text:outline-level="2" text:style-name="Heading_2"'
                . ' text:is-list-header="true">'
                . __('Structure for view') . ' '
                . htmlspecialchars($tableAlias)
                . '</text:h>';
                $this->getTableDef($db, $table, $aliases);
                break;
            case 'stand_in':
                $this->buffer .= '<text:h text:outline-level="2" text:style-name="Heading_2"'
                . ' text:is-list-header="true">'
                . __('Stand-in structure for view') . ' '
                . htmlspecialchars($tableAlias)
                . '</text:h>';
                // export a stand-in definition to resolve view dependencies
                $this->getTableDefStandIn($db, $table, $aliases);
        }

        return true;
    }

    /**
     * Formats the definition for one column
     *
     * @param Column $column info about this column
     * @param string $colAs  column alias
     *
     * @return string Formatted column definition
     */
    protected function formatOneColumnDefinition(Column $column, string $colAs = ''): string
    {
        if ($colAs === '') {
            $colAs = $column->field;
        }

        $definition = '<table:table-row>';
        $definition .= '<table:table-cell office:value-type="string">'
            . '<text:p>' . htmlspecialchars($colAs) . '</text:p>'
            . '</table:table-cell>';

        $extractedColumnSpec = Util::extractColumnSpec($column->type);
        $type = htmlspecialchars($extractedColumnSpec['print_type']);
        if ($type === '') {
            $type = '&nbsp;';
        }

        $definition .= '<table:table-cell office:value-type="string">'
            . '<text:p>' . htmlspecialchars($type) . '</text:p>'
            . '</table:table-cell>';

        $definition .= '<table:table-cell office:value-type="string">'
            . '<text:p>'
            . ($column->isNull ? __('Yes') : __('No'))
            . '</text:p>'
            . '</table:table-cell>';
        $definition .= '<table:table-cell office:value-type="string">'
            . '<text:p>' . htmlspecialchars($column->default ?? ($column->isNull ? 'NULL' : '')) . '</text:p>'
            . '</table:table-cell>';

        return $definition;
    }

    /** @inheritDoc */
    public function setExportOptions(ServerRequest $request, array $exportConfig): void
    {
        $this->structureOrData = $this->setStructureOrData(
            $request->getParsedBodyParam('odt_structure_or_data'),
            $exportConfig['odt_structure_or_data'] ?? null,
            StructureOrData::StructureAndData,
        );
        $this->columns = (bool) ($request->getParsedBodyParam('odt_columns')
            ?? $exportConfig['odt_columns'] ?? false);
        $this->doRelation = (bool) ($request->getParsedBodyParam('odt_relation')
            ?? $exportConfig['odt_relation'] ?? false);
        $this->doMime = (bool) ($request->getParsedBodyParam('odt_mime') ?? $exportConfig['odt_mime'] ?? false);
        $this->doComments = (bool) ($request->getParsedBodyParam('odt_comments')
            ?? $exportConfig['odt_comments'] ?? false);
        $this->null = $this->setStringValue(
            $request->getParsedBodyParam('odt_null'),
            $exportConfig['odt_null'] ?? null,
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
