<?php
/**
 * Set of methods used to build dumps of tables as JSON
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Export;

use PhpMyAdmin\Dbal\ConnectionType;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Exceptions\ExportException;
use PhpMyAdmin\Export\StructureOrData;
use PhpMyAdmin\FieldMetadata;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Plugins\ExportPlugin;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Options\Items\HiddenPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use PhpMyAdmin\Version;

use function __;
use function bin2hex;
use function explode;
use function json_encode;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_UNICODE;

/**
 * Handles the export for the JSON format
 */
class ExportJson extends ExportPlugin
{
    private bool $first = true;
    private bool $prettyPrint = false;
    private bool $unicode = false;

    /** @psalm-return non-empty-lowercase-string */
    public function getName(): string
    {
        return 'json';
    }

    /**
     * Encodes the data into JSON
     *
     * @param mixed $data Data to encode
     */
    public function encode(mixed $data): string|false
    {
        $options = 0;
        if ($this->prettyPrint) {
            $options |= JSON_PRETTY_PRINT;
        }

        if ($this->unicode) {
            $options |= JSON_UNESCAPED_UNICODE;
        }

        return json_encode($data, $options);
    }

    protected function setProperties(): ExportPluginProperties
    {
        $exportPluginProperties = new ExportPluginProperties();
        $exportPluginProperties->setText('JSON');
        $exportPluginProperties->setExtension('json');
        $exportPluginProperties->setMimeType('application/json');
        $exportPluginProperties->setOptionsText(__('Options'));

        // create the root group that will be the options field for
        // $exportPluginProperties
        // this will be shown as "Format specific options"
        $exportSpecificOptions = new OptionsPropertyRootGroup('Format Specific Options');

        // general options main group
        $generalOptions = new OptionsPropertyMainGroup('general_opts');
        // create primary items and add them to the group
        $leaf = new HiddenPropertyItem('structure_or_data');
        $generalOptions->addProperty($leaf);

        $leaf = new BoolPropertyItem(
            'pretty_print',
            __('Output pretty-printed JSON (Use human-readable formatting)'),
        );
        $generalOptions->addProperty($leaf);

        $leaf = new BoolPropertyItem(
            'unicode',
            __('Output unicode characters unescaped'),
        );
        $generalOptions->addProperty($leaf);

        // add the main group to the root group
        $exportSpecificOptions->addProperty($generalOptions);

        // set the options for the export plugin property item
        $exportPluginProperties->setOptions($exportSpecificOptions);

        return $exportPluginProperties;
    }

    /**
     * Outputs export header
     */
    public function exportHeader(): void
    {
        $data = $this->encode([
            'type' => 'header',
            'version' => Version::VERSION,
            'comment' => 'Export to JSON plugin for phpMyAdmin',
        ]);
        if ($data === false) {
            throw new ExportException('Failure during header export.');
        }

        $this->outputHandler->addLine('[' . "\n" . $data . ',' . "\n");
    }

    /**
     * Outputs export footer
     */
    public function exportFooter(): void
    {
        $this->outputHandler->addLine(']' . "\n");
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

        $data = $this->encode(['type' => 'database', 'name' => $dbAlias]);
        if ($data === false) {
            throw new ExportException('Failure during header export.');
        }

        $this->outputHandler->addLine($data . ',' . "\n");
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

        if (! $this->first) {
            $this->outputHandler->addLine(',');
        } else {
            $this->first = false;
        }

        $buffer = $this->encode([
            'type' => 'table',
            'name' => $tableAlias,
            'database' => $dbAlias,
            'data' => '@@DATA@@',
        ]);
        if ($buffer === false) {
            throw new ExportException('Failure during data export.');
        }

        $this->doExportForQuery(DatabaseInterface::getInstance(), $sqlQuery, $buffer, $aliases, $db, $table);
    }

    /**
     * Export to JSON
     *
     * @phpstan-param array<
     *   string,
     *   array{
     *     tables: array<
     *       string,
     *       array{columns: array<string, string>}
     *     >
     *   }
     * >|null $aliases
     */
    private function doExportForQuery(
        DatabaseInterface $dbi,
        string $sqlQuery,
        string $buffer,
        array|null $aliases,
        string|null $db,
        string|null $table,
    ): void {
        [$header, $footer] = explode('"@@DATA@@"', $buffer);

        $this->outputHandler->addLine($header . "\n" . '[' . "\n");

        $result = $dbi->query($sqlQuery, ConnectionType::User, DatabaseInterface::QUERY_UNBUFFERED);
        $columnsCnt = $result->numFields();
        $fieldsMeta = $dbi->getFieldsMeta($result);

        $columns = [];
        foreach ($fieldsMeta as $i => $field) {
            $colAs = $field->name;
            if (
                $db !== null && $table !== null && $aliases !== null
            ) {
                $colAs = $this->getColumnAlias($aliases, $db, $table, $colAs);
            }

            $columns[$i] = $colAs;
        }

        $recordCnt = 0;
        while ($record = $result->fetchRow()) {
            $recordCnt++;

            // Output table name as comment if this is the first record of the table
            if ($recordCnt > 1) {
                $this->outputHandler->addLine(',' . "\n");
            }

            $data = [];

            /** @infection-ignore-all */
            for ($i = 0; $i < $columnsCnt; $i++) {
                // 63 is the binary charset, see: https://dev.mysql.com/doc/internals/en/charsets.html
                $isBlobAndIsBinaryCharset = isset($fieldsMeta[$i])
                                                && $fieldsMeta[$i]->isType(FieldMetadata::TYPE_BLOB)
                                                && $fieldsMeta[$i]->charsetnr === 63;
                // This can occur for binary fields
                $isBinaryString = isset($fieldsMeta[$i])
                                    && $fieldsMeta[$i]->isType(FieldMetadata::TYPE_STRING)
                                    && $fieldsMeta[$i]->charsetnr === 63;
                if (
                    isset($fieldsMeta[$i]) &&
                    (
                        $fieldsMeta[$i]->isMappedTypeGeometry ||
                        $isBlobAndIsBinaryCharset ||
                        $isBinaryString
                    ) &&
                    $record[$i] !== null
                ) {
                    // export GIS and blob types as hex
                    $record[$i] = '0x' . bin2hex($record[$i]);
                }

                $data[$columns[$i]] = $record[$i];
            }

            $encodedData = $this->encode($data);
            if ($encodedData === '' || $encodedData === false) {
                throw new ExportException('Failure during data export.');
            }

            $this->outputHandler->addLine($encodedData);
        }

        $this->outputHandler->addLine("\n" . ']' . "\n" . $footer . "\n");
    }

    /**
     * Outputs result raw query in JSON format
     *
     * @param string|null $db       the database where the query is executed
     * @param string      $sqlQuery the rawquery to output
     */
    public function exportRawQuery(string|null $db, string $sqlQuery): void
    {
        $buffer = $this->encode(['type' => 'raw', 'data' => '@@DATA@@']);
        if ($buffer === false) {
            throw new ExportException('Failure during data export.');
        }

        $dbi = DatabaseInterface::getInstance();
        if ($db !== null) {
            $dbi->selectDb($db);
        }

        $this->doExportForQuery($dbi, $sqlQuery, $buffer, null, $db, null);
    }

    /** @inheritDoc */
    public function setExportOptions(ServerRequest $request, array $exportConfig): void
    {
        $this->structureOrData = $this->setStructureOrData(
            $request->getParsedBodyParam('json_structure_or_data'),
            $exportConfig['json_structure_or_data'] ?? null,
            StructureOrData::Data,
        );
        $this->prettyPrint = (bool) ($request->getParsedBodyParam('json_pretty_print')
            ?? $exportConfig['json_pretty_print'] ?? false);
        $this->unicode = (bool) ($request->getParsedBodyParam('json_unicode')
            ?? $exportConfig['json_unicode'] ?? false);
    }
}
