<?php
/**
 * HTML-Word export code
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Export;

use PhpMyAdmin\Column;
use PhpMyAdmin\Current;
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
 * Handles the export for the HTML-Word format
 */
class ExportHtmlword extends ExportPlugin
{
    private bool $columns = false;
    private bool $doComments = false;
    private bool $doMime = false;
    private bool $doRelation = false;
    private string $null = '';

    /** @psalm-return non-empty-lowercase-string */
    public function getName(): string
    {
        return 'htmlword';
    }

    protected function setProperties(): ExportPluginProperties
    {
        $exportPluginProperties = new ExportPluginProperties();
        $exportPluginProperties->setText('Microsoft Word 2000');
        $exportPluginProperties->setExtension('doc');
        $exportPluginProperties->setMimeType('application/vnd.ms-word');
        $exportPluginProperties->setForceFile(true);
        $exportPluginProperties->setOptionsText(__('Options'));

        // create the root group that will be the options field for
        // $exportPluginProperties
        // this will be shown as "Format specific options"
        $exportSpecificOptions = new OptionsPropertyRootGroup('Format Specific Options');

        // what to dump (structure/data/both)
        $dumpWhat = new OptionsPropertyMainGroup(
            'dump_what',
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
            'dump_what',
            __('Data dump options'),
        );
        $dataOptions->setForce('structure');
        // create primary items and add them to the group
        $leaf = new TextPropertyItem(
            'null',
            __('Replace NULL with:'),
        );
        $dataOptions->addProperty($leaf);
        $leaf = new BoolPropertyItem(
            'columns',
            __('Put columns names in the first row'),
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
        return $this->outputHandler->addLine(
            '<html xmlns:o="urn:schemas-microsoft-com:office:office"
            xmlns:x="urn:schemas-microsoft-com:office:word"
            xmlns="http://www.w3.org/TR/REC-html40">

            <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"'
            . ' "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
            <html>
            <head>
                <meta http-equiv="Content-type" content="text/html;charset='
            . (Current::$charset ?? 'utf-8') . '" />
            </head>
            <body>',
        );
    }

    /**
     * Outputs export footer
     */
    public function exportFooter(): bool
    {
        return $this->outputHandler->addLine('</body></html>');
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

        return $this->outputHandler->addLine(
            '<h1>' . __('Database') . ' ' . htmlspecialchars($dbAlias) . '</h1>',
        );
    }

    /**
     * Outputs the content of a table in HTML-Word format
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
            ! $this->outputHandler->addLine(
                '<h2>'
                . __('Dumping data for table') . ' ' . htmlspecialchars($tableAlias)
                . '</h2>',
            )
        ) {
            return false;
        }

        if (! $this->outputHandler->addLine('<table width="100%" cellspacing="1">')) {
            return false;
        }

        $dbi = DatabaseInterface::getInstance();
        /**
         * Gets the data from the database
         */
        $result = $dbi->query($sqlQuery, ConnectionType::User, DatabaseInterface::QUERY_UNBUFFERED);

        // If required, get fields name at the first line
        if ($this->columns) {
            $schemaInsert = '<tr class="print-category">';
            foreach ($result->getFieldNames() as $colAs) {
                $colAs = $this->getColumnAlias($aliases, $db, $table, $colAs);

                $schemaInsert .= '<td class="print"><strong>'
                    . htmlspecialchars($colAs)
                    . '</strong></td>';
            }

            $schemaInsert .= '</tr>';
            if (! $this->outputHandler->addLine($schemaInsert)) {
                return false;
            }
        }

        // Format the data
        while ($row = $result->fetchRow()) {
            $schemaInsert = '<tr class="print-category">';
            foreach ($row as $field) {
                $schemaInsert .= '<td class="print">'
                    . htmlspecialchars($field ?? $this->null)
                    . '</td>';
            }

            $schemaInsert .= '</tr>';
            if (! $this->outputHandler->addLine($schemaInsert)) {
                return false;
            }
        }

        return $this->outputHandler->addLine('</table>');
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
        $schemaInsert = '<table width="100%" cellspacing="1">'
            . '<tr class="print-category">'
            . '<th class="print">'
            . __('Column')
            . '</th>'
            . '<td class="print"><strong>'
            . __('Type')
            . '</strong></td>'
            . '<td class="print"><strong>'
            . __('Null')
            . '</strong></td>'
            . '<td class="print"><strong>'
            . __('Default')
            . '</strong></td>'
            . '</tr>';

        /**
         * Get the unique keys in the view
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

        $columns = $dbi->getColumns($db, $view);
        foreach ($columns as $column) {
            $colAs = $this->getColumnAlias($aliases, $db, $view, $column->field);

            $schemaInsert .= $this->formatOneColumnDefinition($column, $uniqueKeys, $colAs);
            $schemaInsert .= '</tr>';
        }

        $schemaInsert .= '</table>';

        return $schemaInsert;
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

        $schemaInsert = '';

        $dbi = DatabaseInterface::getInstance();
        /**
         * Gets fields properties
         */
        $dbi->selectDb($db);

        // Check if we can use Relations
        $foreigners = $this->doRelation && $relationParameters->relationFeature !== null
            ? $this->relation->getForeigners($db, $table)
            : null;

        /**
         * Displays the table structure
         */
        $schemaInsert .= '<table width="100%" cellspacing="1">';

        $schemaInsert .= '<tr class="print-category">';
        $schemaInsert .= '<th class="print">'
            . __('Column')
            . '</th>';
        $schemaInsert .= '<td class="print"><strong>'
            . __('Type')
            . '</strong></td>';
        $schemaInsert .= '<td class="print"><strong>'
            . __('Null')
            . '</strong></td>';
        $schemaInsert .= '<td class="print"><strong>'
            . __('Default')
            . '</strong></td>';
        if ($this->doRelation && $foreigners !== null && ! $foreigners->isEmpty()) {
            $schemaInsert .= '<td class="print"><strong>'
                . __('Links to')
                . '</strong></td>';
        }

        if ($this->doComments) {
            $schemaInsert .= '<td class="print"><strong>'
                . __('Comments')
                . '</strong></td>';
            $comments = $this->relation->getComments($db, $table);
        }

        if ($this->doMime && $relationParameters->browserTransformationFeature !== null) {
            $schemaInsert .= '<td class="print"><strong>'
                . __('Media type')
                . '</strong></td>';
            $mimeMap = $this->transformations->getMime($db, $table, true);
        }

        $schemaInsert .= '</tr>';

        $columns = $dbi->getColumns($db, $table);
        /**
         * Get the unique keys in the table
         */
        $uniqueKeys = [];
        $keys = $dbi->getTableIndexes($db, $table);
        foreach ($keys as $key) {
            if ($key['Non_unique'] != 0) {
                continue;
            }

            $uniqueKeys[] = $key['Column_name'];
        }

        foreach ($columns as $column) {
            $colAs = $this->getColumnAlias($aliases, $db, $table, $column->field);

            $schemaInsert .= $this->formatOneColumnDefinition($column, $uniqueKeys, $colAs);
            $fieldName = $column->field;
            if ($this->doRelation && $foreigners !== null && ! $foreigners->isEmpty()) {
                $schemaInsert .= '<td class="print">'
                    . htmlspecialchars(
                        $this->getRelationString(
                            $foreigners,
                            $fieldName,
                            $db,
                            $aliases,
                        ),
                    )
                    . '</td>';
            }

            if ($this->doComments && $relationParameters->columnCommentsFeature !== null) {
                $schemaInsert .= '<td class="print">'
                    . (isset($comments[$fieldName])
                        ? htmlspecialchars($comments[$fieldName])
                        : '') . '</td>';
            }

            if ($this->doMime && $relationParameters->browserTransformationFeature !== null) {
                $schemaInsert .= '<td class="print">'
                    . (isset($mimeMap[$fieldName]) ?
                        htmlspecialchars(
                            str_replace('_', '/', $mimeMap[$fieldName]['mimetype']),
                        )
                        : '') . '</td>';
            }

            $schemaInsert .= '</tr>';
        }

        $schemaInsert .= '</table>';

        return $schemaInsert;
    }

    /**
     * Outputs triggers
     *
     * @param Trigger[] $triggers
     *
     * @return string Formatted triggers list
     */
    protected function getTriggers(array $triggers): string
    {
        $dump = '<table width="100%" cellspacing="1">';
        $dump .= '<tr class="print-category">';
        $dump .= '<th class="print">' . __('Name') . '</th>';
        $dump .= '<td class="print"><strong>' . __('Time') . '</strong></td>';
        $dump .= '<td class="print"><strong>' . __('Event') . '</strong></td>';
        $dump .= '<td class="print"><strong>' . __('Definition') . '</strong></td>';
        $dump .= '</tr>';

        foreach ($triggers as $trigger) {
            $dump .= '<tr class="print-category">';
            $dump .= '<td class="print">'
                . htmlspecialchars($trigger->name->getName())
                . '</td>'
                . '<td class="print">'
                . htmlspecialchars($trigger->timing->value)
                . '</td>'
                . '<td class="print">'
                . htmlspecialchars($trigger->event->value)
                . '</td>'
                . '<td class="print">'
                . htmlspecialchars($trigger->statement)
                . '</td>'
                . '</tr>';
        }

        $dump .= '</table>';

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
                $dump .= '<h2>'
                . __('Table structure for table') . ' '
                . htmlspecialchars($tableAlias)
                . '</h2>';
                $dump .= $this->getTableDef($db, $table, $aliases);
                break;
            case 'triggers':
                $triggers = Triggers::getDetails(DatabaseInterface::getInstance(), $db, $table);
                if ($triggers !== []) {
                    $dump .= '<h2>'
                    . __('Triggers') . ' ' . htmlspecialchars($tableAlias)
                    . '</h2>';
                    $dump .= $this->getTriggers($triggers);
                }

                break;
            case 'create_view':
                $dump .= '<h2>'
                . __('Structure for view') . ' ' . htmlspecialchars($tableAlias)
                . '</h2>';
                $dump .= $this->getTableDef($db, $table, $aliases);
                break;
            case 'stand_in':
                $dump .= '<h2>'
                . __('Stand-in structure for view') . ' '
                . htmlspecialchars($tableAlias)
                . '</h2>';
                // export a stand-in definition to resolve view dependencies
                $dump .= $this->getTableDefStandIn($db, $table, $aliases);
        }

        return $this->outputHandler->addLine($dump);
    }

    /**
     * Formats the definition for one column
     *
     * @param Column                $column     info about this column
     * @param list<(string | null)> $uniqueKeys unique keys of the table
     * @param string                $colAlias   Column Alias
     *
     * @return string Formatted column definition
     */
    protected function formatOneColumnDefinition(
        Column $column,
        array $uniqueKeys,
        string $colAlias = '',
    ): string {
        if ($colAlias === '') {
            $colAlias = $column->field;
        }

        $definition = '<tr class="print-category">';

        $extractedColumnSpec = Util::extractColumnSpec($column->type);

        $type = htmlspecialchars($extractedColumnSpec['print_type']);
        if ($type === '') {
            $type = '&nbsp;';
        }

        $fmtPre = '';
        $fmtPost = '';
        if (in_array($column->field, $uniqueKeys, true)) {
            $fmtPre = '<strong>' . $fmtPre;
            $fmtPost .= '</strong>';
        }

        if ($column->key === 'PRI') {
            $fmtPre = '<em>' . $fmtPre;
            $fmtPost .= '</em>';
        }

        $definition .= '<td class="print">' . $fmtPre
            . htmlspecialchars($colAlias) . $fmtPost . '</td>';
        $definition .= '<td class="print">' . htmlspecialchars($type) . '</td>';
        $definition .= '<td class="print">'
            . ($column->isNull ? __('Yes') : __('No'))
            . '</td>';
        $definition .= '<td class="print">'
            . htmlspecialchars($column->default ?? ($column->isNull ? 'NULL' : ''))
            . '</td>';

        return $definition;
    }

    /** @inheritDoc */
    public function setExportOptions(ServerRequest $request, array $exportConfig): void
    {
        $this->structureOrData = $this->setStructureOrData(
            $request->getParsedBodyParam('htmlword_structure_or_data'),
            $exportConfig['htmlword_structure_or_data'] ?? null,
            StructureOrData::StructureAndData,
        );
        $this->columns = (bool) ($request->getParsedBodyParam('htmlword_columns')
            ?? $exportConfig['htmlword_columns'] ?? false);
        $this->doRelation = (bool) ($request->getParsedBodyParam('htmlword_relation')
            ?? $exportConfig['htmlword_relation'] ?? false);
        $this->doMime = (bool) ($request->getParsedBodyParam('htmlword_mime')
            ?? $exportConfig['htmlword_mime'] ?? false);
        $this->doComments = (bool) ($request->getParsedBodyParam('htmlword_comments')
            ?? $exportConfig['htmlword_comments'] ?? false);
        $this->null = $this->setStringValue(
            $request->getParsedBodyParam('htmlword_null'),
            $exportConfig['htmlword_null'] ?? null,
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
