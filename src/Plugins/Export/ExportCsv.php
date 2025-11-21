<?php
/**
 * CSV export code
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Export;

use PhpMyAdmin\Dbal\ConnectionType;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Export\StructureOrData;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Plugins\ExportPlugin;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Options\Items\HiddenPropertyItem;
use PhpMyAdmin\Properties\Options\Items\TextPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;

use function __;
use function implode;
use function is_string;
use function str_contains;
use function str_replace;
use function strtolower;

/**
 * Handles the export for the CSV format
 */
class ExportCsv extends ExportPlugin
{
    private string $terminated = 'AUTO';
    private string $separator = ',';
    private bool $columns = false;
    private string $enclosed = '"';
    private string $escaped = '"';
    private bool $removeCrLf = false;
    private string $null = 'NULL';

    /** @psalm-return non-empty-lowercase-string */
    public function getName(): string
    {
        return 'csv';
    }

    protected function setProperties(): ExportPluginProperties
    {
        $exportPluginProperties = new ExportPluginProperties();
        $exportPluginProperties->setText('CSV');
        $exportPluginProperties->setExtension('csv');
        $exportPluginProperties->setMimeType('text/comma-separated-values');
        $exportPluginProperties->setOptionsText(__('Options'));

        // create the root group that will be the options field for
        // $exportPluginProperties
        // this will be shown as "Format specific options"
        $exportSpecificOptions = new OptionsPropertyRootGroup('Format Specific Options');

        // general options main group
        $generalOptions = new OptionsPropertyMainGroup('general_opts');
        // create leaf items and add them to the group
        $leaf = new TextPropertyItem(
            'separator',
            __('Columns separated with:'),
        );
        $generalOptions->addProperty($leaf);
        $leaf = new TextPropertyItem(
            'enclosed',
            __('Columns enclosed with:'),
        );
        $generalOptions->addProperty($leaf);
        $leaf = new TextPropertyItem(
            'escaped',
            __('Columns escaped with:'),
        );
        $generalOptions->addProperty($leaf);
        $leaf = new TextPropertyItem(
            'terminated',
            __('Lines terminated with:'),
        );
        $generalOptions->addProperty($leaf);
        $leaf = new TextPropertyItem(
            'null',
            __('Replace NULL with:'),
        );
        $generalOptions->addProperty($leaf);
        $leaf = new BoolPropertyItem(
            'removeCRLF',
            __('Remove carriage return/line feed characters within columns'),
        );
        $generalOptions->addProperty($leaf);
        $leaf = new BoolPropertyItem(
            'columns',
            __('Put columns names in the first row'),
        );
        $generalOptions->addProperty($leaf);
        $leaf = new HiddenPropertyItem('structure_or_data');
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
        // Here we just prepare some values for export
        if ($this->terminated === '' || strtolower($this->terminated) === 'auto') {
            $this->terminated = "\n";
        } else {
            $this->terminated = str_replace(
                ['\\r', '\\n', '\\t'],
                ["\015", "\012", "\011"],
                $this->terminated,
            );
        }

        $this->separator = str_replace('\\t', "\011", $this->separator);

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
     * Outputs the content of a table in CSV format
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
        $dbi = DatabaseInterface::getInstance();
        /**
         * Gets the data from the database
         */
        $result = $dbi->query($sqlQuery, ConnectionType::User, DatabaseInterface::QUERY_UNBUFFERED);

        // If required, get fields name at the first line
        if ($this->columns) {
            $insertFields = [];
            foreach ($result->getFieldNames() as $colAs) {
                $colAs = $this->getColumnAlias($aliases, $db, $table, $colAs);

                if (
                    $this->enclosed === ''
                    || (! str_contains($colAs, $this->separator)
                        && ! str_contains($colAs, $this->enclosed)
                        && ! str_contains($colAs, $this->terminated)
                    )
                ) {
                    $insertFields[] = $colAs;
                } else {
                    $insertFields[] = $this->enclosed
                        . str_replace($this->enclosed, $this->escaped . $this->enclosed, $colAs)
                        . $this->enclosed;
                }
            }

            $schemaInsert = implode($this->separator, $insertFields);
            if (! $this->export->outputHandler($schemaInsert . $this->terminated)) {
                return false;
            }
        }

        // Format the data
        while ($row = $result->fetchRow()) {
            $insertValues = [];
            foreach ($row as $field) {
                if ($field === null) {
                    $insertValues[] = $this->null;
                } elseif ($field !== '') {
                    // remove CRLF characters within field
                    if ($this->removeCrLf) {
                        $field = str_replace(
                            ["\r", "\n"],
                            '',
                            $field,
                        );
                    }

                    if (
                        $this->enclosed === ''
                        || (! str_contains($field, $this->separator)
                            && ! str_contains($field, $this->enclosed)
                            && ! str_contains($field, $this->terminated)
                        )
                    ) {
                        $insertValues[] = $field;
                    } elseif ($this->escaped !== $this->enclosed) {
                        // also double the escape string if found in the data
                        $insertValues[] = $this->enclosed
                            . str_replace(
                                $this->enclosed,
                                $this->escaped . $this->enclosed,
                                str_replace(
                                    $this->escaped,
                                    $this->escaped . $this->escaped,
                                    $field,
                                ),
                            )
                            . $this->enclosed;
                    } else {
                        // avoid a problem when escape string equals enclose
                        $insertValues[] = $this->enclosed
                            . str_replace($this->enclosed, $this->escaped . $this->enclosed, $field)
                            . $this->enclosed;
                    }
                } else {
                    $insertValues[] = '';
                }
            }

            $schemaInsert = implode($this->separator, $insertValues);
            if (! $this->export->outputHandler($schemaInsert . $this->terminated)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Outputs result of raw query in CSV format
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

    /** @inheritDoc */
    public function setExportOptions(ServerRequest $request, array $exportConfig): void
    {
        $this->structureOrData = $this->setStructureOrData(
            $request->getParsedBodyParam('csv_structure_or_data'),
            $exportConfig['csv_structure_or_data'] ?? null,
            StructureOrData::Data,
        );
        $this->terminated = $this->setStringValue(
            $request->getParsedBodyParam('csv_terminated'),
            $exportConfig['csv_terminated'] ?? $this->terminated,
        );
        $this->separator = $this->setStringValue(
            $request->getParsedBodyParam('csv_separator'),
            $exportConfig['csv_separator'] ?? $this->separator,
        );
        $this->columns = (bool) ($request->getParsedBodyParam('csv_columns')
            ?? $exportConfig['csv_columns'] ?? false);
        $this->enclosed = $this->setStringValue(
            $request->getParsedBodyParam('csv_enclosed'),
            $exportConfig['csv_enclosed'] ?? $this->enclosed,
        );
        $this->escaped = $this->setStringValue(
            $request->getParsedBodyParam('csv_escaped'),
            $exportConfig['csv_escaped'] ?? $this->escaped,
        );
        $this->removeCrLf = (bool) ($request->getParsedBodyParam('csv_removeCRLF')
            ?? $exportConfig['csv_removeCRLF'] ?? false);
        $this->null = $this->setStringValue(
            $request->getParsedBodyParam('csv_null'),
            $exportConfig['csv_null'] ?? $this->null,
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
