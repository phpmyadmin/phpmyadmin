<?php
/**
 * Set of methods used to build dumps of tables as JSON
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Export;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Dbal\Connection;
use PhpMyAdmin\FieldMetadata;
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
        if (isset($GLOBALS['json_pretty_print']) && $GLOBALS['json_pretty_print']) {
            $options |= JSON_PRETTY_PRINT;
        }

        if (isset($GLOBALS['json_unicode']) && $GLOBALS['json_unicode']) {
            $options |= JSON_UNESCAPED_UNICODE;
        }

        return json_encode($data, $options);
    }

    protected function setProperties(): ExportPluginProperties
    {
        $exportPluginProperties = new ExportPluginProperties();
        $exportPluginProperties->setText('JSON');
        $exportPluginProperties->setExtension('json');
        $exportPluginProperties->setMimeType('text/plain');
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
    public function exportHeader(): bool
    {
        $data = $this->encode([
            'type' => 'header',
            'version' => Version::VERSION,
            'comment' => 'Export to JSON plugin for PHPMyAdmin',
        ]);
        if ($data === false) {
            return false;
        }

        return $this->export->outputHandler('[' . "\n" . $data . ',' . "\n");
    }

    /**
     * Outputs export footer
     */
    public function exportFooter(): bool
    {
        return $this->export->outputHandler(']' . "\n");
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

        $data = $this->encode(['type' => 'database', 'name' => $dbAlias]);
        if ($data === false) {
            return false;
        }

        return $this->export->outputHandler($data . ',' . "\n");
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

        if (! $this->first) {
            if (! $this->export->outputHandler(',')) {
                return false;
            }
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
            return false;
        }

        return $this->doExportForQuery($GLOBALS['dbi'], $sqlQuery, $buffer, $aliases, $db, $table);
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
    protected function doExportForQuery(
        DatabaseInterface $dbi,
        string $sqlQuery,
        string $buffer,
        array|null $aliases,
        string|null $db,
        string|null $table,
    ): bool {
        [$header, $footer] = explode('"@@DATA@@"', $buffer);

        if (! $this->export->outputHandler($header . "\n" . '[' . "\n")) {
            return false;
        }

        $result = $dbi->query($sqlQuery, Connection::TYPE_USER, DatabaseInterface::QUERY_UNBUFFERED);
        $columnsCnt = $result->numFields();
        $fieldsMeta = $dbi->getFieldsMeta($result);

        $columns = [];
        foreach ($fieldsMeta as $i => $field) {
            $colAs = $field->name;
            if (
                $db !== null && $table !== null && $aliases !== null
                && ! empty($aliases[$db]['tables'][$table]['columns'][$colAs])
            ) {
                $colAs = $aliases[$db]['tables'][$table]['columns'][$colAs];
            }

            $columns[$i] = $colAs;
        }

        $recordCnt = 0;
        while ($record = $result->fetchRow()) {
            $recordCnt++;

            // Output table name as comment if this is the first record of the table
            if ($recordCnt > 1) {
                if (! $this->export->outputHandler(',' . "\n")) {
                    return false;
                }
            }

            $data = [];

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
            if (! $encodedData) {
                return false;
            }

            if (! $this->export->outputHandler($encodedData)) {
                return false;
            }
        }

        return $this->export->outputHandler("\n" . ']' . "\n" . $footer . "\n");
    }

    /**
     * Outputs result raw query in JSON format
     *
     * @param string      $errorUrl the url to go back in case of error
     * @param string|null $db       the database where the query is executed
     * @param string      $sqlQuery the rawquery to output
     */
    public function exportRawQuery(string $errorUrl, string|null $db, string $sqlQuery): bool
    {
        $buffer = $this->encode(['type' => 'raw', 'data' => '@@DATA@@']);
        if ($buffer === false) {
            return false;
        }

        if ($db !== null) {
            $GLOBALS['dbi']->selectDb($db);
        }

        return $this->doExportForQuery($GLOBALS['dbi'], $sqlQuery, $buffer, null, $db, null);
    }
}
