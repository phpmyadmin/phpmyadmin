<?php
/**
 * Class for exporting CSV dumps of tables for excel
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
use PhpMyAdmin\Properties\Options\Items\SelectPropertyItem;
use PhpMyAdmin\Properties\Options\Items\TextPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;

use function __;
use function implode;
use function in_array;
use function is_string;
use function preg_replace;
use function str_replace;

/**
 * Handles the export for the CSV-Excel format
 */
class ExportExcel extends ExportPlugin
{
    /** @var 'win'|'mac_excel2003'|'mac_excel2008' */
    private string $edition = 'win';
    private bool $columns = false;
    private bool $removeCrLf = false;
    private string $null = 'NULL';
    private string $separator = ',';
    private string $enclosed = '"';
    private string $escaped = '"';
    private string $terminated = '';

    /** @psalm-return non-empty-lowercase-string */
    public function getName(): string
    {
        return 'excel';
    }

    protected function setProperties(): ExportPluginProperties
    {
        $exportPluginProperties = new ExportPluginProperties();
        $exportPluginProperties->setText('CSV for MS Excel');
        $exportPluginProperties->setExtension('csv');
        $exportPluginProperties->setMimeType('text/comma-separated-values');
        $exportPluginProperties->setOptionsText(__('Options'));

        // create the root group that will be the options field for
        // $exportPluginProperties
        // this will be shown as "Format specific options"
        $exportSpecificOptions = new OptionsPropertyRootGroup('Format Specific Options');

        // general options main group
        $generalOptions = new OptionsPropertyMainGroup('general_opts');
        // create primary items and add them to the group
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
        $leaf = new SelectPropertyItem(
            'edition',
            __('Excel edition:'),
        );
        $leaf->setValues(
            [
                'win' => 'Windows',
                'mac_excel2003' => 'Excel 2003 / Macintosh',
                'mac_excel2008' => 'Excel 2008 / Macintosh',
            ],
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
        $this->terminated = "\015\012";
        switch ($this->edition) {
            case 'win': // as tested on Windows with Excel 2002 and Excel 2007
            case 'mac_excel2003':
                $this->separator = ';';
                break;
            case 'mac_excel2008':
                $this->separator = ',';
                break;
        }

        $this->enclosed = '"';
        $this->escaped = '"';

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
                if (! empty($aliases[$db]['tables'][$table]['columns'][$colAs])) {
                    $colAs = $aliases[$db]['tables'][$table]['columns'][$colAs];
                }

                if ($this->enclosed === '') {
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
                    // always enclose fields
                    $field = preg_replace("/\015(\012)?/", "\012", $field);

                    // remove CRLF characters within field
                    if ($this->removeCrLf) {
                        $field = str_replace(
                            ["\r", "\n"],
                            '',
                            $field,
                        );
                    }

                    if ($this->enclosed === '') {
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
            $request->getParsedBodyParam('excel_structure_or_data'),
            $exportConfig['excel_structure_or_data'] ?? null,
            StructureOrData::Data,
        );
        $this->edition = $this->setEdition($this->setStringValue(
            $request->getParsedBodyParam('excel_edition'),
            $exportConfig['excel_edition'] ?? null,
        ));
        $this->columns = (bool) ($request->getParsedBodyParam('excel_columns')
            ?? $exportConfig['excel_columns'] ?? false);
        $this->removeCrLf = (bool) ($request->getParsedBodyParam('excel_removeCRLF')
            ?? $exportConfig['excel_removeCRLF'] ?? false);
        $this->null = $this->setStringValue(
            $request->getParsedBodyParam('excel_null'),
            $exportConfig['excel_null'] ?? null,
        );
        $this->separator = $this->setStringValue(
            $request->getParsedBodyParam('excel_separator'),
            $exportConfig['excel_separator'] ?? null,
        );
        $this->enclosed = $this->setStringValue(
            $request->getParsedBodyParam('excel_enclosed'),
            $exportConfig['excel_enclosed'] ?? null,
        );
        $this->escaped = $this->setStringValue(
            $request->getParsedBodyParam('excel_escaped'),
            $exportConfig['excel_escaped'] ?? null,
        );
        $this->terminated = $this->setStringValue(
            $request->getParsedBodyParam('excel_terminated'),
            $exportConfig['excel_terminated'] ?? null,
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

    /** @return 'win'|'mac_excel2003'|'mac_excel2008' */
    private function setEdition(string $edition): string
    {
        if (in_array($edition, ['mac_excel2003', 'mac_excel2008'], true)) {
            return $edition;
        }

        return 'win';
    }
}
