<?php
/**
 * Set of methods used to build dumps of tables as Latex
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Export;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Dbal\Connection;
use PhpMyAdmin\Plugins\ExportPlugin;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Options\Items\RadioPropertyItem;
use PhpMyAdmin\Properties\Options\Items\TextPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use PhpMyAdmin\Util;
use PhpMyAdmin\Version;

use function __;
use function count;
use function in_array;
use function mb_strpos;
use function mb_substr;
use function str_repeat;
use function str_replace;

use const PHP_VERSION;

/**
 * Handles the export for the Latex format
 */
class ExportLatex extends ExportPlugin
{
    /** @psalm-return non-empty-lowercase-string */
    public function getName(): string
    {
        return 'latex';
    }

    /**
     * Initialize the local variables that are used for export Latex.
     */
    protected function init(): void
    {
        /* Messages used in default captions */
        $GLOBALS['strLatexContent'] = __('Content of table @TABLE@');
        $GLOBALS['strLatexContinued'] = __('(continued)');
        $GLOBALS['strLatexStructure'] = __('Structure of table @TABLE@');
    }

    protected function setProperties(): ExportPluginProperties
    {
        $GLOBALS['plugin_param'] ??= null;

        $hideStructure = false;
        if ($GLOBALS['plugin_param']['export_type'] === 'table' && ! $GLOBALS['plugin_param']['single_table']) {
            $hideStructure = true;
        }

        $exportPluginProperties = new ExportPluginProperties();
        $exportPluginProperties->setText('LaTeX');
        $exportPluginProperties->setExtension('tex');
        $exportPluginProperties->setMimeType('application/x-tex');
        $exportPluginProperties->setOptionsText(__('Options'));

        // create the root group that will be the options field for
        // $exportPluginProperties
        // this will be shown as "Format specific options"
        $exportSpecificOptions = new OptionsPropertyRootGroup('Format Specific Options');

        // general options main group
        $generalOptions = new OptionsPropertyMainGroup('general_opts');
        // create primary items and add them to the group
        $leaf = new BoolPropertyItem(
            'caption',
            __('Include table caption'),
        );
        $generalOptions->addProperty($leaf);
        // add the main group to the root group
        $exportSpecificOptions->addProperty($generalOptions);

        // what to dump (structure/data/both) main group
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

        // structure options main group
        if (! $hideStructure) {
            $structureOptions = new OptionsPropertyMainGroup(
                'structure',
                __('Object creation options'),
            );
            $structureOptions->setForce('data');
            // create primary items and add them to the group
            $leaf = new TextPropertyItem(
                'structure_caption',
                __('Table caption:'),
            );
            $leaf->setDoc('faq6-27');
            $structureOptions->addProperty($leaf);
            $leaf = new TextPropertyItem(
                'structure_continued_caption',
                __('Table caption (continued):'),
            );
            $leaf->setDoc('faq6-27');
            $structureOptions->addProperty($leaf);
            $leaf = new TextPropertyItem(
                'structure_label',
                __('Label key:'),
            );
            $leaf->setDoc('faq6-27');
            $structureOptions->addProperty($leaf);
            $relationParameters = $this->relation->getRelationParameters();
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
            __('Put columns names in the first row:'),
        );
        $dataOptions->addProperty($leaf);
        $leaf = new TextPropertyItem(
            'data_caption',
            __('Table caption:'),
        );
        $leaf->setDoc('faq6-27');
        $dataOptions->addProperty($leaf);
        $leaf = new TextPropertyItem(
            'data_continued_caption',
            __('Table caption (continued):'),
        );
        $leaf->setDoc('faq6-27');
        $dataOptions->addProperty($leaf);
        $leaf = new TextPropertyItem(
            'data_label',
            __('Label key:'),
        );
        $leaf->setDoc('faq6-27');
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
        $head = '% phpMyAdmin LaTeX Dump' . "\n"
            . '% version ' . Version::VERSION . "\n"
            . '% https://www.phpmyadmin.net/' . "\n"
            . '%' . "\n"
            . '% ' . __('Host:') . ' ' . $GLOBALS['cfg']['Server']['host'];
        if (! empty($GLOBALS['cfg']['Server']['port'])) {
            $head .= ':' . $GLOBALS['cfg']['Server']['port'];
        }

        $head .= "\n"
            . '% ' . __('Generation Time:') . ' '
            . Util::localisedDate() . "\n"
            . '% ' . __('Server version:') . ' ' . $GLOBALS['dbi']->getVersionString() . "\n"
            . '% ' . __('PHP Version:') . ' ' . PHP_VERSION . "\n";

        return $this->export->outputHandler($head);
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
        if ($dbAlias === '') {
            $dbAlias = $db;
        }

        $head = '% ' . "\n"
            . '% ' . __('Database:') . ' \'' . $dbAlias . '\'' . "\n"
            . '% ' . "\n";

        return $this->export->outputHandler($head);
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
     * Outputs the content of a table in JSON format
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
        $dbAlias = $db;
        $tableAlias = $table;
        $this->initAlias($aliases, $dbAlias, $tableAlias);

        $result = $GLOBALS['dbi']->tryQuery($sqlQuery, Connection::TYPE_USER, DatabaseInterface::QUERY_UNBUFFERED);

        $columnsCnt = $result->numFields();
        $columns = [];
        $columnsAlias = [];
        foreach ($result->getFieldNames() as $i => $colAs) {
            $columns[$i] = $colAs;
            if (! empty($aliases[$db]['tables'][$table]['columns'][$colAs])) {
                $colAs = $aliases[$db]['tables'][$table]['columns'][$colAs];
            }

            $columnsAlias[$i] = $colAs;
        }

        $buffer = "\n" . '%' . "\n" . '% ' . __('Data:') . ' ' . $tableAlias
            . "\n" . '%' . "\n" . ' \\begin{longtable}{|';

        $buffer .= str_repeat('l|', $columnsCnt);

        $buffer .= '} ' . "\n";

        $buffer .= ' \\hline \\endhead \\hline \\endfoot \\hline ' . "\n";
        if (isset($GLOBALS['latex_caption'])) {
            $buffer .= ' \\caption{'
                . Util::expandUserString(
                    $GLOBALS['latex_data_caption'],
                    [static::class, 'texEscape'],
                    ['table' => $tableAlias, 'database' => $dbAlias],
                )
                . '} \\label{'
                . Util::expandUserString(
                    $GLOBALS['latex_data_label'],
                    null,
                    ['table' => $tableAlias, 'database' => $dbAlias],
                )
                . '} \\\\';
        }

        if (! $this->export->outputHandler($buffer)) {
            return false;
        }

        // show column names
        if (isset($GLOBALS['latex_columns'])) {
            $buffer = '\\hline ';
            for ($i = 0; $i < $columnsCnt; $i++) {
                $buffer .= '\\multicolumn{1}{|c|}{\\textbf{'
                    . self::texEscape($columnsAlias[$i]) . '}} & ';
            }

            $buffer = mb_substr($buffer, 0, -2) . '\\\\ \\hline \hline ';
            if (! $this->export->outputHandler($buffer . ' \\endfirsthead ' . "\n")) {
                return false;
            }

            if (isset($GLOBALS['latex_caption'])) {
                if (
                    ! $this->export->outputHandler(
                        '\\caption{'
                        . Util::expandUserString(
                            $GLOBALS['latex_data_continued_caption'],
                            [static::class, 'texEscape'],
                            ['table' => $tableAlias, 'database' => $dbAlias],
                        )
                        . '} \\\\ ',
                    )
                ) {
                    return false;
                }
            }

            if (! $this->export->outputHandler($buffer . '\\endhead \\endfoot' . "\n")) {
                return false;
            }
        } elseif (! $this->export->outputHandler('\\\\ \hline')) {
            return false;
        }

        // print the whole table
        while ($record = $result->fetchAssoc()) {
            $buffer = '';
            // print each row
            for ($i = 0; $i < $columnsCnt; $i++) {
                if ($record[$columns[$i]] !== null) {
                    $columnValue = self::texEscape($record[$columns[$i]]);
                } else {
                    $columnValue = $GLOBALS['latex_null'];
                }

                // last column ... no need for & character
                if ($i == $columnsCnt - 1) {
                    $buffer .= $columnValue;
                } else {
                    $buffer .= $columnValue . ' & ';
                }
            }

            $buffer .= ' \\\\ \\hline ' . "\n";
            if (! $this->export->outputHandler($buffer)) {
                return false;
            }
        }

        $buffer = ' \\end{longtable}' . "\n";

        return $this->export->outputHandler($buffer);
    }

    /**
     * Outputs result raw query
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
     *                             exportStructure() also for other
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

        $relationParameters = $this->relation->getRelationParameters();

        /* We do not export triggers */
        if ($exportMode === 'triggers') {
            return true;
        }

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
        $buffer = "\n" . '%' . "\n" . '% ' . __('Structure:') . ' '
            . $tableAlias . "\n" . '%' . "\n" . ' \\begin{longtable}{';
        if (! $this->export->outputHandler($buffer)) {
            return false;
        }

        $alignment = '|l|c|c|c|';
        if ($doRelation && $haveRel) {
            $alignment .= 'l|';
        }

        if ($doComments) {
            $alignment .= 'l|';
        }

        if ($doMime && $relationParameters->browserTransformationFeature !== null) {
            $alignment .= 'l|';
        }

        $buffer = $alignment . '} ' . "\n";

        $header = ' \\hline ';
        $header .= '\\multicolumn{1}{|c|}{\\textbf{' . __('Column')
            . '}} & \\multicolumn{1}{|c|}{\\textbf{' . __('Type')
            . '}} & \\multicolumn{1}{|c|}{\\textbf{' . __('Null')
            . '}} & \\multicolumn{1}{|c|}{\\textbf{' . __('Default') . '}}';
        if ($doRelation && $haveRel) {
            $header .= ' & \\multicolumn{1}{|c|}{\\textbf{' . __('Links to') . '}}';
        }

        if ($doComments) {
            $header .= ' & \\multicolumn{1}{|c|}{\\textbf{' . __('Comments') . '}}';
            $comments = $this->relation->getComments($db, $table);
        }

        if ($doMime && $relationParameters->browserTransformationFeature !== null) {
            $header .= ' & \\multicolumn{1}{|c|}{\\textbf{MIME}}';
            $mimeMap = $this->transformations->getMime($db, $table, true);
        }

        // Table caption for first page and label
        if (isset($GLOBALS['latex_caption'])) {
            $buffer .= ' \\caption{'
                . Util::expandUserString(
                    $GLOBALS['latex_structure_caption'],
                    [static::class, 'texEscape'],
                    ['table' => $tableAlias, 'database' => $dbAlias],
                )
                . '} \\label{'
                . Util::expandUserString(
                    $GLOBALS['latex_structure_label'],
                    null,
                    ['table' => $tableAlias, 'database' => $dbAlias],
                )
                . '} \\\\' . "\n";
        }

        $buffer .= $header . ' \\\\ \\hline \\hline' . "\n"
            . '\\endfirsthead' . "\n";
        // Table caption on next pages
        if (isset($GLOBALS['latex_caption'])) {
            $buffer .= ' \\caption{'
                . Util::expandUserString(
                    $GLOBALS['latex_structure_continued_caption'],
                    [static::class, 'texEscape'],
                    ['table' => $tableAlias, 'database' => $dbAlias],
                )
                . '} \\\\ ' . "\n";
        }

        $buffer .= $header . ' \\\\ \\hline \\hline \\endhead \\endfoot ' . "\n";

        if (! $this->export->outputHandler($buffer)) {
            return false;
        }

        $fields = $GLOBALS['dbi']->getColumns($db, $table);
        foreach ($fields as $row) {
            $extractedColumnSpec = Util::extractColumnSpec($row['Type']);
            $type = $extractedColumnSpec['print_type'];
            if (empty($type)) {
                $type = ' ';
            }

            if (! isset($row['Default'])) {
                if ($row['Null'] !== 'NO') {
                    $row['Default'] = 'NULL';
                }
            }

            $fieldName = $colAs = $row['Field'];
            if (! empty($aliases[$db]['tables'][$table]['columns'][$colAs])) {
                $colAs = $aliases[$db]['tables'][$table]['columns'][$colAs];
            }

            $localBuffer = $colAs . "\000" . $type . "\000"
                . ($row['Null'] == '' || $row['Null'] === 'NO'
                    ? __('No') : __('Yes'))
                . "\000" . ($row['Default'] ?? '');

            if ($doRelation && $haveRel) {
                $localBuffer .= "\000";
                $localBuffer .= $this->getRelationString($resRel, $fieldName, $db, $aliases);
            }

            if ($doComments && $relationParameters->columnCommentsFeature !== null) {
                $localBuffer .= "\000";
                if (isset($comments[$fieldName])) {
                    $localBuffer .= $comments[$fieldName];
                }
            }

            if ($doMime && $relationParameters->browserTransformationFeature !== null) {
                $localBuffer .= "\000";
                if (isset($mimeMap[$fieldName])) {
                    $localBuffer .= str_replace('_', '/', $mimeMap[$fieldName]['mimetype']);
                }
            }

            $localBuffer = self::texEscape($localBuffer);
            if ($row['Key'] === 'PRI') {
                $pos = (int) mb_strpos($localBuffer, "\000");
                $localBuffer = '\\textit{' . mb_substr($localBuffer, 0, $pos) . '}' . mb_substr($localBuffer, $pos);
            }

            if (in_array($fieldName, $uniqueKeys)) {
                $pos = (int) mb_strpos($localBuffer, "\000");
                $localBuffer = '\\textbf{' . mb_substr($localBuffer, 0, $pos) . '}' . mb_substr($localBuffer, $pos);
            }

            $buffer = str_replace("\000", ' & ', $localBuffer);
            $buffer .= ' \\\\ \\hline ' . "\n";

            if (! $this->export->outputHandler($buffer)) {
                return false;
            }
        }

        $buffer = ' \\end{longtable}' . "\n";

        return $this->export->outputHandler($buffer);
    }

    /**
     * Escapes some special characters for use in TeX/LaTeX
     *
     * @param string $string the string to convert
     *
     * @return string the converted string with escape codes
     */
    public static function texEscape(string $string): string
    {
        $escape = ['$', '%', '{', '}', '&', '#', '_', '^'];
        $cntEscape = count($escape);
        for ($k = 0; $k < $cntEscape; $k++) {
            $string = str_replace($escape[$k], '\\' . $escape[$k], $string);
        }

        return $string;
    }
}
