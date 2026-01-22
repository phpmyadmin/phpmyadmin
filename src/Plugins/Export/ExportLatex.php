<?php
/**
 * Set of methods used to build dumps of tables as Latex
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Export;

use DateTimeImmutable;
use PhpMyAdmin\Config;
use PhpMyAdmin\Dbal\ConnectionType;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Export\StructureOrData;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Plugins\ExportPlugin;
use PhpMyAdmin\Plugins\ExportType;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Options\Items\RadioPropertyItem;
use PhpMyAdmin\Properties\Options\Items\TextPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use PhpMyAdmin\Util;
use PhpMyAdmin\Version;

use function __;
use function addcslashes;
use function array_keys;
use function array_values;
use function in_array;
use function is_string;
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
    private bool $caption = false;
    private bool $columns = false;
    private string $dataCaption = '';
    private string $dataContinuedCaption = '';
    private string $dataLabel = '';
    private bool $doComments = false;
    private bool $doMime = false;
    private bool $doRelation = false;
    private string $null = '';
    private string $structureCaption = '';
    private string $structureContinuedCaption = '';
    private string $structureLabel = '';

    /** @psalm-return non-empty-lowercase-string */
    public function getName(): string
    {
        return 'latex';
    }

    public function getTranslatedText(string $text): string
    {
        /* Messages used in default captions */
        $messages = [
            'strLatexContent' => __('Content of table @TABLE@'),
            'strLatexContinued' => __('(continued)'),
            'strLatexStructure' => __('Structure of table @TABLE@'),
        ];

        return str_replace(array_keys($messages), array_values($messages), $text);
    }

    protected function setProperties(): ExportPluginProperties
    {
        $hideStructure = false;
        if (ExportPlugin::$exportType === ExportType::Table && ! ExportPlugin::$singleTable) {
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
    public function exportHeader(): void
    {
        $config = Config::getInstance();
        $head = '% phpMyAdmin LaTeX Dump' . "\n"
            . '% version ' . Version::VERSION . "\n"
            . '% https://www.phpmyadmin.net/' . "\n"
            . '%' . "\n"
            . '% ' . __('Host:') . ' ' . $config->selectedServer['host'];
        if (! empty($config->selectedServer['port'])) {
            $head .= ':' . $config->selectedServer['port'];
        }

        $head .= "\n"
            . '% ' . __('Generation Time:') . ' '
            . Util::localisedDate(new DateTimeImmutable()) . "\n"
            . '% ' . __('Server version:') . ' ' . DatabaseInterface::getInstance()->getVersionString() . "\n"
            . '% ' . __('PHP Version:') . ' ' . PHP_VERSION . "\n";

        $this->outputHandler->addLine($head);
    }

    /**
     * Outputs database header
     *
     * @param string $db      Database name
     * @param string $dbAlias Aliases of db
     */
    public function exportDBHeader(string $db, string $dbAlias = ''): void
    {
        if ($dbAlias === '') {
            $dbAlias = $db;
        }

        $head = '% ' . "\n"
            . '% ' . __('Database:') . ' \'' . $dbAlias . '\'' . "\n"
            . '% ' . "\n";

        $this->outputHandler->addLine($head);
    }

    /**
     * Outputs the content of a table in JSON format
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
    ): void {
        $dbAlias = $this->getDbAlias($aliases, $db);
        $tableAlias = $this->getTableAlias($aliases, $db, $table);

        $dbi = DatabaseInterface::getInstance();
        $result = $dbi->tryQuery($sqlQuery, ConnectionType::User, DatabaseInterface::QUERY_UNBUFFERED);

        $columnsCnt = $result->numFields();
        $columns = [];
        $columnsAlias = [];
        foreach ($result->getFieldNames() as $i => $colAs) {
            $columns[$i] = $colAs;
            $colAs = $this->getColumnAlias($aliases, $db, $table, $colAs);

            $columnsAlias[$i] = $colAs;
        }

        $buffer = "\n" . '%' . "\n" . '% ' . __('Data:') . ' ' . $tableAlias
            . "\n" . '%' . "\n" . ' \\begin{longtable}{|';

        $buffer .= str_repeat('l|', $columnsCnt);

        $buffer .= '} ' . "\n";

        $buffer .= ' \\hline \\endhead \\hline \\endfoot \\hline ' . "\n";
        if ($this->caption) {
            $buffer .= ' \\caption{'
                . Util::expandUserString(
                    $this->dataCaption,
                    [static::class, 'texEscape'],
                    ['table' => $tableAlias, 'database' => $dbAlias],
                )
                . '} \\label{'
                . Util::expandUserString(
                    $this->dataLabel,
                    null,
                    ['table' => $tableAlias, 'database' => $dbAlias],
                )
                . '} \\\\';
        }

        $this->outputHandler->addLine($buffer);

        // show column names
        if ($this->columns) {
            $buffer = '\\hline ';
            for ($i = 0; $i < $columnsCnt; $i++) {
                $buffer .= '\\multicolumn{1}{|c|}{\\textbf{'
                    . self::texEscape($columnsAlias[$i]) . '}} & ';
            }

            $buffer = mb_substr($buffer, 0, -2) . '\\\\ \\hline \hline ';
            $this->outputHandler->addLine($buffer . ' \\endfirsthead ' . "\n");

            if ($this->caption) {
                $this->outputHandler->addLine(
                    '\\caption{'
                    . Util::expandUserString(
                        $this->dataContinuedCaption,
                        [static::class, 'texEscape'],
                        ['table' => $tableAlias, 'database' => $dbAlias],
                    )
                    . '} \\\\ ',
                );
            }

            $this->outputHandler->addLine($buffer . '\\endhead \\endfoot' . "\n");
        } else {
            $this->outputHandler->addLine('\\\\ \hline');
        }

        // print the whole table
        while ($record = $result->fetchAssoc()) {
            $buffer = '';
            // print each row
            /** @infection-ignore-all */
            for ($i = 0; $i < $columnsCnt; $i++) {
                if ($record[$columns[$i]] !== null) {
                    $columnValue = self::texEscape($record[$columns[$i]]);
                } else {
                    $columnValue = $this->null;
                }

                // last column ... no need for & character
                if ($i === $columnsCnt - 1) {
                    $buffer .= $columnValue;
                } else {
                    $buffer .= $columnValue . ' & ';
                }
            }

            $buffer .= ' \\\\ \\hline ' . "\n";
            $this->outputHandler->addLine($buffer);
        }

        $buffer = ' \\end{longtable}' . "\n";

        $this->outputHandler->addLine($buffer);
    }

    /**
     * Outputs result raw query
     *
     * @param string $db       the database where the query is executed
     * @param string $sqlQuery the rawquery to output
     */
    public function exportRawQuery(string $db, string $sqlQuery): void
    {
        if ($db !== '') {
            DatabaseInterface::getInstance()->selectDb($db);
        }

        $this->exportData($db, '', $sqlQuery);
    }

    /**
     * Outputs table's structure
     *
     * @param string  $db         database name
     * @param string  $table      table name
     * @param string  $exportMode 'create_table', 'triggers', 'create_view', 'stand_in'
     * @param mixed[] $aliases    Aliases of db/table/columns
     */
    public function exportStructure(string $db, string $table, string $exportMode, array $aliases = []): void
    {
        $dbAlias = $this->getDbAlias($aliases, $db);
        $tableAlias = $this->getTableAlias($aliases, $db, $table);

        $relationParameters = $this->relation->getRelationParameters();

        /* We do not export triggers */
        if ($exportMode === 'triggers') {
            return;
        }

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
            : null;
        /**
         * Displays the table structure
         */
        $buffer = "\n" . '%' . "\n" . '% ' . __('Structure:') . ' '
            . $tableAlias . "\n" . '%' . "\n" . ' \\begin{longtable}{';
        $this->outputHandler->addLine($buffer);

        $alignment = '|l|c|c|c|';
        if ($this->doRelation && $foreigners !== null && ! $foreigners->isEmpty()) {
            $alignment .= 'l|';
        }

        if ($this->doComments) {
            $alignment .= 'l|';
        }

        if ($this->doMime && $relationParameters->browserTransformationFeature !== null) {
            $alignment .= 'l|';
        }

        $buffer = $alignment . '} ' . "\n";

        $header = ' \\hline ';
        $header .= '\\multicolumn{1}{|c|}{\\textbf{' . __('Column')
            . '}} & \\multicolumn{1}{|c|}{\\textbf{' . __('Type')
            . '}} & \\multicolumn{1}{|c|}{\\textbf{' . __('Null')
            . '}} & \\multicolumn{1}{|c|}{\\textbf{' . __('Default') . '}}';
        if ($this->doRelation && $foreigners !== null && ! $foreigners->isEmpty()) {
            $header .= ' & \\multicolumn{1}{|c|}{\\textbf{' . __('Links to') . '}}';
        }

        if ($this->doComments) {
            $header .= ' & \\multicolumn{1}{|c|}{\\textbf{' . __('Comments') . '}}';
            $comments = $this->relation->getComments($db, $table);
        }

        if ($this->doMime && $relationParameters->browserTransformationFeature !== null) {
            $header .= ' & \\multicolumn{1}{|c|}{\\textbf{MIME}}';
            $mimeMap = $this->transformations->getMime($db, $table, true);
        }

        // Table caption for first page and label
        if ($this->caption) {
            $buffer .= ' \\caption{'
                . Util::expandUserString(
                    $this->structureCaption,
                    [static::class, 'texEscape'],
                    ['table' => $tableAlias, 'database' => $dbAlias],
                )
                . '} \\label{'
                . Util::expandUserString(
                    $this->structureLabel,
                    null,
                    ['table' => $tableAlias, 'database' => $dbAlias],
                )
                . '} \\\\' . "\n";
        }

        $buffer .= $header . ' \\\\ \\hline \\hline' . "\n"
            . '\\endfirsthead' . "\n";
        // Table caption on next pages
        if ($this->caption) {
            $buffer .= ' \\caption{'
                . Util::expandUserString(
                    $this->structureContinuedCaption,
                    [static::class, 'texEscape'],
                    ['table' => $tableAlias, 'database' => $dbAlias],
                )
                . '} \\\\ ' . "\n";
        }

        $buffer .= $header . ' \\\\ \\hline \\hline \\endhead \\endfoot ' . "\n";

        $this->outputHandler->addLine($buffer);

        $fields = $dbi->getColumns($db, $table);
        foreach ($fields as $row) {
            $extractedColumnSpec = Util::extractColumnSpec($row->type);
            $type = $extractedColumnSpec['print_type'];
            if (empty($type)) {
                $type = ' ';
            }

            $fieldName = $row->field;
            $colAs = $this->getColumnAlias($aliases, $db, $table, $row->field);

            $localBuffer = $colAs . "\000" . $type . "\000"
                . ($row->isNull ? __('Yes') : __('No'))
                . "\000" . ($row->default ?? ($row->isNull ? 'NULL' : ''));

            if ($this->doRelation && $foreigners !== null && ! $foreigners->isEmpty()) {
                $localBuffer .= "\000";
                $localBuffer .= $this->getRelationString($foreigners, $fieldName, $db, $aliases);
            }

            if ($this->doComments && $relationParameters->columnCommentsFeature !== null) {
                $localBuffer .= "\000";
                if (isset($comments[$fieldName])) {
                    $localBuffer .= $comments[$fieldName];
                }
            }

            if ($this->doMime && $relationParameters->browserTransformationFeature !== null) {
                $localBuffer .= "\000";
                if (isset($mimeMap[$fieldName])) {
                    $localBuffer .= str_replace('_', '/', $mimeMap[$fieldName]['mimetype']);
                }
            }

            $localBuffer = self::texEscape($localBuffer);
            if ($row->key === 'PRI') {
                $pos = (int) mb_strpos($localBuffer, "\000");
                $localBuffer = '\\textit{' . mb_substr($localBuffer, 0, $pos) . '}' . mb_substr($localBuffer, $pos);
            }

            if (in_array($fieldName, $uniqueKeys, true)) {
                $pos = (int) mb_strpos($localBuffer, "\000");
                $localBuffer = '\\textbf{' . mb_substr($localBuffer, 0, $pos) . '}' . mb_substr($localBuffer, $pos);
            }

            $buffer = str_replace("\000", ' & ', $localBuffer);
            $buffer .= ' \\\\ \\hline ' . "\n";

            $this->outputHandler->addLine($buffer);
        }

        $buffer = ' \\end{longtable}' . "\n";

        $this->outputHandler->addLine($buffer);
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
        return addcslashes($string, '$%{}&#_^');
    }

    /** @inheritDoc */
    public function setExportOptions(ServerRequest $request, array $exportConfig): void
    {
        $this->structureOrData = $this->setStructureOrData(
            $request->getParsedBodyParam('latex_structure_or_data'),
            $exportConfig['latex_structure_or_data'] ?? null,
            StructureOrData::StructureAndData,
        );
        $this->caption = (bool) ($request->getParsedBodyParam('latex_caption')
            ?? $exportConfig['latex_caption'] ?? false);
        $this->columns = (bool) ($request->getParsedBodyParam('latex_columns')
            ?? $exportConfig['latex_columns'] ?? false);
        $this->doRelation = (bool) ($request->getParsedBodyParam('latex_relation')
            ?? $exportConfig['latex_relation'] ?? false);
        $this->doMime = (bool) ($request->getParsedBodyParam('latex_mime') ?? $exportConfig['latex_mime'] ?? false);
        $this->doComments = (bool) ($request->getParsedBodyParam('latex_comments')
            ?? $exportConfig['latex_comments'] ?? false);
        $this->dataCaption = $this->setStringValue(
            $request->getParsedBodyParam('latex_data_caption'),
            $exportConfig['latex_data_caption'] ?? null,
        );
        $this->dataContinuedCaption = $this->setStringValue(
            $request->getParsedBodyParam('latex_data_continued_caption'),
            $exportConfig['latex_data_continued_caption'] ?? null,
        );
        $this->dataLabel = $this->setStringValue(
            $request->getParsedBodyParam('latex_data_label'),
            $exportConfig['latex_data_label'] ?? null,
        );
        $this->null = $this->setStringValue(
            $request->getParsedBodyParam('latex_null'),
            $exportConfig['latex_null'] ?? null,
        );
        $this->structureCaption = $this->setStringValue(
            $request->getParsedBodyParam('latex_structure_caption'),
            $exportConfig['latex_structure_caption'] ?? null,
        );
        $this->structureContinuedCaption = $this->setStringValue(
            $request->getParsedBodyParam('latex_structure_continued_caption'),
            $exportConfig['latex_structure_continued_caption'] ?? null,
        );
        $this->structureLabel = $this->setStringValue(
            $request->getParsedBodyParam('latex_structure_label'),
            $exportConfig['latex_structure_label'] ?? null,
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
