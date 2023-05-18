<?php
/**
 * HTML-Word export code
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
 * Handles the export for the HTML-Word format
 */
class ExportHtmlword extends ExportPlugin
{
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
        $GLOBALS['charset'] ??= null;

        return $this->export->outputHandler(
            '<html xmlns:o="urn:schemas-microsoft-com:office:office"
            xmlns:x="urn:schemas-microsoft-com:office:word"
            xmlns="http://www.w3.org/TR/REC-html40">

            <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"'
            . ' "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
            <html>
            <head>
                <meta http-equiv="Content-type" content="text/html;charset='
            . ($GLOBALS['charset'] ?? 'utf-8') . '" />
            </head>
            <body>',
        );
    }

    /**
     * Outputs export footer
     */
    public function exportFooter(): bool
    {
        return $this->export->outputHandler('</body></html>');
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

        return $this->export->outputHandler(
            '<h1>' . __('Database') . ' ' . htmlspecialchars($dbAlias) . '</h1>',
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
     * Outputs the content of a table in HTML-Word format
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
                '<h2>'
                . __('Dumping data for table') . ' ' . htmlspecialchars($tableAlias)
                . '</h2>',
            )
        ) {
            return false;
        }

        if (! $this->export->outputHandler('<table width="100%" cellspacing="1">')) {
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
        if (isset($GLOBALS['htmlword_columns'])) {
            $schemaInsert = '<tr class="print-category">';
            foreach ($result->getFieldNames() as $colAs) {
                if (! empty($aliases[$db]['tables'][$table]['columns'][$colAs])) {
                    $colAs = $aliases[$db]['tables'][$table]['columns'][$colAs];
                }

                $schemaInsert .= '<td class="print"><strong>'
                    . htmlspecialchars($colAs)
                    . '</strong></td>';
            }

            $schemaInsert .= '</tr>';
            if (! $this->export->outputHandler($schemaInsert)) {
                return false;
            }
        }

        // Format the data
        while ($row = $result->fetchRow()) {
            $schemaInsert = '<tr class="print-category">';
            foreach ($row as $field) {
                if ($field === null) {
                    $value = $GLOBALS[$GLOBALS['what'] . '_null'];
                } else {
                    $value = $field;
                }

                $schemaInsert .= '<td class="print">'
                    . htmlspecialchars((string) $value)
                    . '</td>';
            }

            $schemaInsert .= '</tr>';
            if (! $this->export->outputHandler($schemaInsert)) {
                return false;
            }
        }

        return $this->export->outputHandler('</table>');
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
        $keys = $GLOBALS['dbi']->getTableIndexes($db, $view);
        foreach ($keys as $key) {
            if ($key['Non_unique'] != 0) {
                continue;
            }

            $uniqueKeys[] = $key['Column_name'];
        }

        $columns = $GLOBALS['dbi']->getColumns($db, $view);
        foreach ($columns as $column) {
            $colAs = $column['Field'];
            if (! empty($aliases[$db]['tables'][$view]['columns'][$colAs])) {
                $colAs = $aliases[$db]['tables'][$view]['columns'][$colAs];
            }

            $schemaInsert .= $this->formatOneColumnDefinition($column, $uniqueKeys, $colAs);
            $schemaInsert .= '</tr>';
        }

        $schemaInsert .= '</table>';

        return $schemaInsert;
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

        $schemaInsert = '';

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
        if ($doRelation && $haveRel) {
            $schemaInsert .= '<td class="print"><strong>'
                . __('Links to')
                . '</strong></td>';
        }

        if ($doComments) {
            $schemaInsert .= '<td class="print"><strong>'
                . __('Comments')
                . '</strong></td>';
            $comments = $this->relation->getComments($db, $table);
        }

        if ($doMime && $relationParameters->browserTransformationFeature !== null) {
            $schemaInsert .= '<td class="print"><strong>'
                . __('Media type')
                . '</strong></td>';
            $mimeMap = $this->transformations->getMime($db, $table, true);
        }

        $schemaInsert .= '</tr>';

        $columns = $GLOBALS['dbi']->getColumns($db, $table);
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

        foreach ($columns as $column) {
            $colAs = $column['Field'];
            if (! empty($aliases[$db]['tables'][$table]['columns'][$colAs])) {
                $colAs = $aliases[$db]['tables'][$table]['columns'][$colAs];
            }

            $schemaInsert .= $this->formatOneColumnDefinition($column, $uniqueKeys, $colAs);
            $fieldName = $column['Field'];
            if ($doRelation && $haveRel) {
                $schemaInsert .= '<td class="print">'
                    . htmlspecialchars(
                        $this->getRelationString(
                            $resRel,
                            $fieldName,
                            $db,
                            $aliases,
                        ),
                    )
                    . '</td>';
            }

            if ($doComments && $relationParameters->columnCommentsFeature !== null) {
                $schemaInsert .= '<td class="print">'
                    . (isset($comments[$fieldName])
                        ? htmlspecialchars($comments[$fieldName])
                        : '') . '</td>';
            }

            if ($doMime && $relationParameters->browserTransformationFeature !== null) {
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
     * @param string $db    database name
     * @param string $table table name
     *
     * @return string Formatted triggers list
     */
    protected function getTriggers(string $db, string $table): string
    {
        $dump = '<table width="100%" cellspacing="1">';
        $dump .= '<tr class="print-category">';
        $dump .= '<th class="print">' . __('Name') . '</th>';
        $dump .= '<td class="print"><strong>' . __('Time') . '</strong></td>';
        $dump .= '<td class="print"><strong>' . __('Event') . '</strong></td>';
        $dump .= '<td class="print"><strong>' . __('Definition') . '</strong></td>';
        $dump .= '</tr>';

        $triggers = Triggers::getDetails($GLOBALS['dbi'], $db, $table);

        foreach ($triggers as $trigger) {
            $dump .= '<tr class="print-category">';
            $dump .= '<td class="print">'
                . htmlspecialchars($trigger['name'])
                . '</td>'
                . '<td class="print">'
                . htmlspecialchars($trigger['action_timing'])
                . '</td>'
                . '<td class="print">'
                . htmlspecialchars($trigger['event_manipulation'])
                . '</td>'
                . '<td class="print">'
                . htmlspecialchars($trigger['definition'])
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
                $dump .= '<h2>'
                . __('Table structure for table') . ' '
                . htmlspecialchars($tableAlias)
                . '</h2>';
                $dump .= $this->getTableDef($db, $table, $doRelation, $doComments, $doMime, $aliases);
                break;
            case 'triggers':
                $triggers = Triggers::getDetails($GLOBALS['dbi'], $db, $table);
                if ($triggers) {
                    $dump .= '<h2>'
                    . __('Triggers') . ' ' . htmlspecialchars($tableAlias)
                    . '</h2>';
                    $dump .= $this->getTriggers($db, $table);
                }

                break;
            case 'create_view':
                $dump .= '<h2>'
                . __('Structure for view') . ' ' . htmlspecialchars($tableAlias)
                . '</h2>';
                $dump .= $this->getTableDef($db, $table, $doRelation, $doComments, $doMime, $aliases);
                break;
            case 'stand_in':
                $dump .= '<h2>'
                . __('Stand-in structure for view') . ' '
                . htmlspecialchars($tableAlias)
                . '</h2>';
                // export a stand-in definition to resolve view dependencies
                $dump .= $this->getTableDefStandIn($db, $table, $aliases);
        }

        return $this->export->outputHandler($dump);
    }

    /**
     * Formats the definition for one column
     *
     * @param mixed[] $column     info about this column
     * @param mixed[] $uniqueKeys unique keys of the table
     * @param string  $colAlias   Column Alias
     *
     * @return string Formatted column definition
     */
    protected function formatOneColumnDefinition(
        array $column,
        array $uniqueKeys,
        string $colAlias = '',
    ): string {
        if ($colAlias === '') {
            $colAlias = $column['Field'];
        }

        $definition = '<tr class="print-category">';

        $extractedColumnSpec = Util::extractColumnSpec($column['Type']);

        $type = htmlspecialchars($extractedColumnSpec['print_type']);
        if ($type === '') {
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
            $fmtPre = '<strong>' . $fmtPre;
            $fmtPost .= '</strong>';
        }

        if ($column['Key'] === 'PRI') {
            $fmtPre = '<em>' . $fmtPre;
            $fmtPost .= '</em>';
        }

        $definition .= '<td class="print">' . $fmtPre
            . htmlspecialchars($colAlias) . $fmtPost . '</td>';
        $definition .= '<td class="print">' . htmlspecialchars($type) . '</td>';
        $definition .= '<td class="print">'
            . ($column['Null'] == '' || $column['Null'] === 'NO'
                ? __('No')
                : __('Yes'))
            . '</td>';
        $definition .= '<td class="print">'
            . htmlspecialchars($column['Default'] ?? '')
            . '</td>';

        return $definition;
    }
}
