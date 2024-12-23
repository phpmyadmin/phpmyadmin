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
use function mb_strtolower;
use function preg_replace;
use function str_replace;

/**
 * Handles the export for the CSV format
 */
class ExportCsv extends ExportPlugin
{
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
        $GLOBALS['csv_terminated'] ??= null;
        $GLOBALS['csv_separator'] ??= null;
        $GLOBALS['csv_enclosed'] ??= null;
        $GLOBALS['csv_escaped'] ??= null;

        // Here we just prepare some values for export
        if ($this->getName() === 'excel') {
            $GLOBALS['csv_terminated'] = "\015\012";
            switch ($GLOBALS['excel_edition']) {
                case 'win': // as tested on Windows with Excel 2002 and Excel 2007
                case 'mac_excel2003':
                    $GLOBALS['csv_separator'] = ';';
                    break;
                case 'mac_excel2008':
                    $GLOBALS['csv_separator'] = ',';
                    break;
            }

            $GLOBALS['csv_enclosed'] = '"';
            $GLOBALS['csv_escaped'] = '"';
            if (isset($GLOBALS['excel_columns'])) {
                $GLOBALS['csv_columns'] = true;
            }
        } else {
            if (empty($GLOBALS['csv_terminated']) || mb_strtolower($GLOBALS['csv_terminated']) === 'auto') {
                $GLOBALS['csv_terminated'] = "\n";
            } else {
                $GLOBALS['csv_terminated'] = str_replace(
                    ['\\r', '\\n', '\\t'],
                    ["\015", "\012", "\011"],
                    $GLOBALS['csv_terminated'],
                );
            }

            $GLOBALS['csv_separator'] = str_replace('\\t', "\011", $GLOBALS['csv_separator']);
        }

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
        $GLOBALS['csv_terminated'] ??= null;
        $GLOBALS['csv_separator'] ??= '';
        $GLOBALS['csv_enclosed'] ??= null;
        $GLOBALS['csv_escaped'] ??= null;

        $dbAlias = $db;
        $tableAlias = $table;
        $this->initAlias($aliases, $dbAlias, $tableAlias);

        $dbi = DatabaseInterface::getInstance();
        /**
         * Gets the data from the database
         */
        $result = $dbi->query($sqlQuery, ConnectionType::User, DatabaseInterface::QUERY_UNBUFFERED);

        // If required, get fields name at the first line
        if (isset($GLOBALS['csv_columns']) && $GLOBALS['csv_columns']) {
            $insertFields = [];
            foreach ($result->getFieldNames() as $colAs) {
                if (! empty($aliases[$db]['tables'][$table]['columns'][$colAs])) {
                    $colAs = $aliases[$db]['tables'][$table]['columns'][$colAs];
                }

                if ($GLOBALS['csv_enclosed'] == '') {
                    $insertFields[] = $colAs;
                } else {
                    $insertFields[] = $GLOBALS['csv_enclosed']
                        . str_replace(
                            $GLOBALS['csv_enclosed'],
                            $GLOBALS['csv_escaped'] . $GLOBALS['csv_enclosed'],
                            $colAs,
                        )
                        . $GLOBALS['csv_enclosed'];
                }
            }

            $schemaInsert = implode($GLOBALS['csv_separator'], $insertFields);
            if (! $this->export->outputHandler($schemaInsert . $GLOBALS['csv_terminated'])) {
                return false;
            }
        }

        // Format the data
        while ($row = $result->fetchRow()) {
            $insertValues = [];
            foreach ($row as $field) {
                if ($field === null) {
                    $insertValues[] = $this->getName() === 'excel' ? $GLOBALS['excel_null'] : $GLOBALS['csv_null'];
                } elseif ($field !== '') {
                    // always enclose fields
                    if ($this->getName() === 'excel') {
                        $field = preg_replace("/\015(\012)?/", "\012", $field);
                    }

                    // remove CRLF characters within field
                    if (
                        isset($GLOBALS['excel_removeCRLF']) && $GLOBALS['excel_removeCRLF']
                        || isset($GLOBALS['csv_removeCRLF']) && $GLOBALS['csv_removeCRLF']
                    ) {
                        $field = str_replace(
                            ["\r", "\n"],
                            '',
                            $field,
                        );
                    }

                    if ($GLOBALS['csv_enclosed'] == '') {
                        $insertValues[] = $field;
                    } elseif ($GLOBALS['csv_escaped'] != $GLOBALS['csv_enclosed']) {
                        // also double the escape string if found in the data
                        $insertValues[] = $GLOBALS['csv_enclosed']
                            . str_replace(
                                $GLOBALS['csv_enclosed'],
                                $GLOBALS['csv_escaped'] . $GLOBALS['csv_enclosed'],
                                str_replace(
                                    $GLOBALS['csv_escaped'],
                                    $GLOBALS['csv_escaped'] . $GLOBALS['csv_escaped'],
                                    $field,
                                ),
                            )
                            . $GLOBALS['csv_enclosed'];
                    } else {
                        // avoid a problem when escape string equals enclose
                        $insertValues[] = $GLOBALS['csv_enclosed']
                            . str_replace(
                                $GLOBALS['csv_enclosed'],
                                $GLOBALS['csv_escaped'] . $GLOBALS['csv_enclosed'],
                                $field,
                            )
                            . $GLOBALS['csv_enclosed'];
                    }
                } else {
                    $insertValues[] = '';
                }
            }

            $schemaInsert = implode($GLOBALS['csv_separator'], $insertValues);
            if (! $this->export->outputHandler($schemaInsert . $GLOBALS['csv_terminated'])) {
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
    }
}
