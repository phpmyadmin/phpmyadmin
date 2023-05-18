<?php
/**
 * Set of functions used to build OpenDocument Text dumps of tables
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Export;

use PhpMyAdmin\Database\Triggers;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Dbal\Connection;
use PhpMyAdmin\FieldMetadata;
use PhpMyAdmin\OpenDocument;
use PhpMyAdmin\Plugins\ExportPlugin;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Options\Items\RadioPropertyItem;
use PhpMyAdmin\Properties\Options\Items\TextPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use PhpMyAdmin\Util;

use function __;
use function bin2hex;
use function htmlspecialchars;
use function str_replace;

/**
 * Handles the export for the ODT class
 */
class ExportOdt extends ExportPlugin
{
    protected function init(): void
    {
        $GLOBALS['odt_buffer'] = '';
    }

    /** @psalm-return non-empty-lowercase-string */
    public function getName(): string
    {
        return 'odt';
    }

    protected function setProperties(): ExportPluginProperties
    {
        $GLOBALS['plugin_param'] ??= null;

        $hideStructure = false;
        if ($GLOBALS['plugin_param']['export_type'] === 'table' && ! $GLOBALS['plugin_param']['single_table']) {
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
        $GLOBALS['odt_buffer'] .= '<?xml version="1.0" encoding="utf-8"?' . '>'
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
        $GLOBALS['odt_buffer'] .= '</office:text></office:body></office:document-content>';

        return $this->export->outputHandler(OpenDocument::create(
            'application/vnd.oasis.opendocument.text',
            $GLOBALS['odt_buffer'],
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
        if (empty($dbAlias)) {
            $dbAlias = $db;
        }

        $GLOBALS['odt_buffer'] .= '<text:h text:outline-level="1" text:style-name="Heading_1"'
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
        // Gets the data from the database
        $result = $GLOBALS['dbi']->query($sqlQuery, Connection::TYPE_USER, DatabaseInterface::QUERY_UNBUFFERED);
        $fieldsCnt = $result->numFields();
        /** @var FieldMetadata[] $fieldsMeta */
        $fieldsMeta = $GLOBALS['dbi']->getFieldsMeta($result);

        $GLOBALS['odt_buffer'] .= '<text:h text:outline-level="2" text:style-name="Heading_2"'
            . ' text:is-list-header="true">';
        $tableAlias != ''
            ? $GLOBALS['odt_buffer'] .= __('Dumping data for table') . ' ' . htmlspecialchars($tableAlias)
            : $GLOBALS['odt_buffer'] .= __('Dumping data for query result');
        $GLOBALS['odt_buffer'] .= '</text:h>'
            . '<table:table'
            . ' table:name="' . htmlspecialchars($tableAlias) . '_structure">'
            . '<table:table-column'
            . ' table:number-columns-repeated="' . $fieldsCnt . '"/>';

        // If required, get fields name at the first line
        if (isset($GLOBALS[$GLOBALS['what'] . '_columns'])) {
            $GLOBALS['odt_buffer'] .= '<table:table-row>';
            foreach ($fieldsMeta as $field) {
                $colAs = $field->name;
                if (! empty($aliases[$db]['tables'][$table]['columns'][$colAs])) {
                    $colAs = $aliases[$db]['tables'][$table]['columns'][$colAs];
                }

                $GLOBALS['odt_buffer'] .= '<table:table-cell office:value-type="string">'
                    . '<text:p>'
                    . htmlspecialchars($colAs)
                    . '</text:p>'
                    . '</table:table-cell>';
            }

            $GLOBALS['odt_buffer'] .= '</table:table-row>';
        }

        // Format the data
        while ($row = $result->fetchRow()) {
            $GLOBALS['odt_buffer'] .= '<table:table-row>';
            for ($j = 0; $j < $fieldsCnt; $j++) {
                if ($fieldsMeta[$j]->isMappedTypeGeometry) {
                    // export GIS types as hex
                    $row[$j] = '0x' . bin2hex($row[$j]);
                }

                if (! isset($row[$j])) {
                    $GLOBALS['odt_buffer'] .= '<table:table-cell office:value-type="string">'
                        . '<text:p>'
                        . htmlspecialchars($GLOBALS[$GLOBALS['what'] . '_null'])
                        . '</text:p>'
                        . '</table:table-cell>';
                } elseif ($fieldsMeta[$j]->isBinary && $fieldsMeta[$j]->isBlob) {
                    // ignore BLOB
                    $GLOBALS['odt_buffer'] .= '<table:table-cell office:value-type="string">'
                        . '<text:p></text:p>'
                        . '</table:table-cell>';
                } elseif (
                    $fieldsMeta[$j]->isNumeric
                ) {
                    $GLOBALS['odt_buffer'] .= '<table:table-cell office:value-type="float"'
                        . ' office:value="' . $row[$j] . '" >'
                        . '<text:p>'
                        . htmlspecialchars($row[$j])
                        . '</text:p>'
                        . '</table:table-cell>';
                } else {
                    $GLOBALS['odt_buffer'] .= '<table:table-cell office:value-type="string">'
                        . '<text:p>'
                        . htmlspecialchars($row[$j])
                        . '</text:p>'
                        . '</table:table-cell>';
                }
            }

            $GLOBALS['odt_buffer'] .= '</table:table-row>';
        }

        $GLOBALS['odt_buffer'] .= '</table:table>';

        return true;
    }

    /**
     * Outputs result raw query in ODT format
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
        $dbAlias = $db;
        $viewAlias = $view;
        $this->initAlias($aliases, $dbAlias, $viewAlias);
        /**
         * Gets fields properties
         */
        $GLOBALS['dbi']->selectDb($db);

        /**
         * Displays the table structure
         */
        $GLOBALS['odt_buffer'] .= '<table:table table:name="'
            . htmlspecialchars($viewAlias) . '_data">';
        $columnsCnt = 4;
        $GLOBALS['odt_buffer'] .= '<table:table-column'
            . ' table:number-columns-repeated="' . $columnsCnt . '"/>';
        /* Header */
        $GLOBALS['odt_buffer'] .= '<table:table-row>'
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

        $columns = $GLOBALS['dbi']->getColumns($db, $view);
        foreach ($columns as $column) {
            $colAs = $column['Field'] ?? null;
            if (! empty($aliases[$db]['tables'][$view]['columns'][$colAs])) {
                $colAs = $aliases[$db]['tables'][$view]['columns'][$colAs];
            }

            $GLOBALS['odt_buffer'] .= $this->formatOneColumnDefinition($column, $colAs);
            $GLOBALS['odt_buffer'] .= '</table:table-row>';
        }

        $GLOBALS['odt_buffer'] .= '</table:table>';

        return '';
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
     *                             PMA_exportStructure() also for other
     * @param bool    $doMime     whether to include mime comments
     *                             the end
     * @param mixed[] $aliases    Aliases of db/table/columns
     */
    public function getTableDef(
        string $db,
        string $table,
        bool $doRelation,
        bool $doComments,
        bool $doMime,
        array $aliases = [],
    ): bool {
        $dbAlias = $db;
        $tableAlias = $table;
        $this->initAlias($aliases, $dbAlias, $tableAlias);

        $relationParameters = $this->relation->getRelationParameters();

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
        $GLOBALS['odt_buffer'] .= '<table:table table:name="'
            . htmlspecialchars($tableAlias) . '_structure">';
        $columnsCnt = 4;
        if ($doRelation && $haveRel) {
            $columnsCnt++;
        }

        if ($doComments) {
            $columnsCnt++;
        }

        if ($doMime && $relationParameters->browserTransformationFeature !== null) {
            $columnsCnt++;
        }

        $GLOBALS['odt_buffer'] .= '<table:table-column'
            . ' table:number-columns-repeated="' . $columnsCnt . '"/>';
        /* Header */
        $GLOBALS['odt_buffer'] .= '<table:table-row>'
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
        if ($doRelation && $haveRel) {
            $GLOBALS['odt_buffer'] .= '<table:table-cell office:value-type="string">'
                . '<text:p>' . __('Links to') . '</text:p>'
                . '</table:table-cell>';
        }

        if ($doComments) {
            $GLOBALS['odt_buffer'] .= '<table:table-cell office:value-type="string">'
                . '<text:p>' . __('Comments') . '</text:p>'
                . '</table:table-cell>';
            $comments = $this->relation->getComments($db, $table);
        }

        if ($doMime && $relationParameters->browserTransformationFeature !== null) {
            $GLOBALS['odt_buffer'] .= '<table:table-cell office:value-type="string">'
                . '<text:p>' . __('Media type') . '</text:p>'
                . '</table:table-cell>';
            $mimeMap = $this->transformations->getMime($db, $table, true);
        }

        $GLOBALS['odt_buffer'] .= '</table:table-row>';

        $columns = $GLOBALS['dbi']->getColumns($db, $table);
        foreach ($columns as $column) {
            $colAs = $fieldName = $column['Field'];
            if (! empty($aliases[$db]['tables'][$table]['columns'][$colAs])) {
                $colAs = $aliases[$db]['tables'][$table]['columns'][$colAs];
            }

            $GLOBALS['odt_buffer'] .= $this->formatOneColumnDefinition($column, $colAs);
            if ($doRelation && $haveRel) {
                $foreigner = $this->relation->searchColumnInForeigners($resRel, $fieldName);
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
                    $GLOBALS['odt_buffer'] .= '<table:table-cell office:value-type="string">'
                        . '<text:p>'
                        . htmlspecialchars($relation)
                        . '</text:p>'
                        . '</table:table-cell>';
                }
            }

            if ($doComments) {
                if (isset($comments[$fieldName])) {
                    $GLOBALS['odt_buffer'] .= '<table:table-cell office:value-type="string">'
                        . '<text:p>'
                        . htmlspecialchars($comments[$fieldName])
                        . '</text:p>'
                        . '</table:table-cell>';
                } else {
                    $GLOBALS['odt_buffer'] .= '<table:table-cell office:value-type="string">'
                        . '<text:p></text:p>'
                        . '</table:table-cell>';
                }
            }

            if ($doMime && $relationParameters->browserTransformationFeature !== null) {
                if (isset($mimeMap[$fieldName])) {
                    $GLOBALS['odt_buffer'] .= '<table:table-cell office:value-type="string">'
                        . '<text:p>'
                        . htmlspecialchars(
                            str_replace('_', '/', $mimeMap[$fieldName]['mimetype']),
                        )
                        . '</text:p>'
                        . '</table:table-cell>';
                } else {
                    $GLOBALS['odt_buffer'] .= '<table:table-cell office:value-type="string">'
                        . '<text:p></text:p>'
                        . '</table:table-cell>';
                }
            }

            $GLOBALS['odt_buffer'] .= '</table:table-row>';
        }

        $GLOBALS['odt_buffer'] .= '</table:table>';

        return true;
    }

    /**
     * Outputs triggers
     *
     * @param string  $db      database name
     * @param string  $table   table name
     * @param mixed[] $aliases Aliases of db/table/columns
     */
    protected function getTriggers(string $db, string $table, array $aliases = []): string
    {
        $dbAlias = $db;
        $tableAlias = $table;
        $this->initAlias($aliases, $dbAlias, $tableAlias);
        $GLOBALS['odt_buffer'] .= '<table:table'
            . ' table:name="' . htmlspecialchars($tableAlias) . '_triggers">'
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

        $triggers = Triggers::getDetails($GLOBALS['dbi'], $db, $table);

        foreach ($triggers as $trigger) {
            $GLOBALS['odt_buffer'] .= '<table:table-row>';
            $GLOBALS['odt_buffer'] .= '<table:table-cell office:value-type="string">'
                . '<text:p>'
                . htmlspecialchars($trigger['name'])
                . '</text:p>'
                . '</table:table-cell>';
            $GLOBALS['odt_buffer'] .= '<table:table-cell office:value-type="string">'
                . '<text:p>'
                . htmlspecialchars($trigger['action_timing'])
                . '</text:p>'
                . '</table:table-cell>';
            $GLOBALS['odt_buffer'] .= '<table:table-cell office:value-type="string">'
                . '<text:p>'
                . htmlspecialchars($trigger['event_manipulation'])
                . '</text:p>'
                . '</table:table-cell>';
            $GLOBALS['odt_buffer'] .= '<table:table-cell office:value-type="string">'
                . '<text:p>'
                . htmlspecialchars($trigger['definition'])
                . '</text:p>'
                . '</table:table-cell>';
            $GLOBALS['odt_buffer'] .= '</table:table-row>';
        }

        $GLOBALS['odt_buffer'] .= '</table:table>';

        return $GLOBALS['odt_buffer'];
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
     *                             PMA_exportStructure() also for other
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
        switch ($exportMode) {
            case 'create_table':
                $GLOBALS['odt_buffer'] .= '<text:h text:outline-level="2" text:style-name="Heading_2"'
                . ' text:is-list-header="true">'
                . __('Table structure for table') . ' ' .
                htmlspecialchars($tableAlias)
                . '</text:h>';
                $this->getTableDef($db, $table, $doRelation, $doComments, $doMime, $aliases);
                break;
            case 'triggers':
                $triggers = Triggers::getDetails($GLOBALS['dbi'], $db, $table);
                if ($triggers) {
                    $GLOBALS['odt_buffer'] .= '<text:h text:outline-level="2" text:style-name="Heading_2"'
                    . ' text:is-list-header="true">'
                    . __('Triggers') . ' '
                    . htmlspecialchars($tableAlias)
                    . '</text:h>';
                    $this->getTriggers($db, $table);
                }

                break;
            case 'create_view':
                $GLOBALS['odt_buffer'] .= '<text:h text:outline-level="2" text:style-name="Heading_2"'
                . ' text:is-list-header="true">'
                . __('Structure for view') . ' '
                . htmlspecialchars($tableAlias)
                . '</text:h>';
                $this->getTableDef($db, $table, $doRelation, $doComments, $doMime, $aliases);
                break;
            case 'stand_in':
                $GLOBALS['odt_buffer'] .= '<text:h text:outline-level="2" text:style-name="Heading_2"'
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
     * @param mixed[] $column info about this column
     * @param string  $colAs  column alias
     *
     * @return string Formatted column definition
     */
    protected function formatOneColumnDefinition(array $column, string $colAs = ''): string
    {
        if (empty($colAs)) {
            $colAs = $column['Field'];
        }

        $definition = '<table:table-row>';
        $definition .= '<table:table-cell office:value-type="string">'
            . '<text:p>' . htmlspecialchars($colAs) . '</text:p>'
            . '</table:table-cell>';

        $extractedColumnSpec = Util::extractColumnSpec($column['Type']);
        $type = htmlspecialchars($extractedColumnSpec['print_type']);
        if (empty($type)) {
            $type = '&nbsp;';
        }

        $definition .= '<table:table-cell office:value-type="string">'
            . '<text:p>' . htmlspecialchars($type) . '</text:p>'
            . '</table:table-cell>';
        if (! isset($column['Default'])) {
            if ($column['Null'] !== 'NO') {
                $column['Default'] = 'NULL';
            } else {
                $column['Default'] = '';
            }
        }

        $definition .= '<table:table-cell office:value-type="string">'
            . '<text:p>'
            . ($column['Null'] == '' || $column['Null'] === 'NO'
                ? __('No')
                : __('Yes'))
            . '</text:p>'
            . '</table:table-cell>';
        $definition .= '<table:table-cell office:value-type="string">'
            . '<text:p>' . htmlspecialchars($column['Default']) . '</text:p>'
            . '</table:table-cell>';

        return $definition;
    }
}
