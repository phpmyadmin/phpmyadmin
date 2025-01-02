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
use function preg_replace;
use function str_replace;

/**
 * Handles the export for the CSV-Excel format
 */
class ExportExcel extends ExportPlugin
{
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
        $GLOBALS['excel_separator'] ??= null;
        $GLOBALS['excel_enclosed'] ??= null;
        $GLOBALS['excel_escaped'] ??= null;

        // Here we just prepare some values for export
        $GLOBALS['excel_terminated'] = "\015\012";
        switch ($GLOBALS['excel_edition']) {
            case 'win': // as tested on Windows with Excel 2002 and Excel 2007
            case 'mac_excel2003':
                $GLOBALS['excel_separator'] = ';';
                break;
            case 'mac_excel2008':
                $GLOBALS['excel_separator'] = ',';
                break;
        }

        $GLOBALS['excel_enclosed'] = '"';
        $GLOBALS['excel_escaped'] = '"';
        $GLOBALS['excel_columns'] = isset($GLOBALS['excel_columns']) && $GLOBALS['excel_columns'];

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
        $GLOBALS['excel_terminated'] ??= null;
        $GLOBALS['excel_separator'] ??= '';
        $GLOBALS['excel_enclosed'] ??= null;
        $GLOBALS['excel_escaped'] ??= null;

        $dbAlias = $db;
        $tableAlias = $table;
        $this->initAlias($aliases, $dbAlias, $tableAlias);

        $dbi = DatabaseInterface::getInstance();
        /**
         * Gets the data from the database
         */
        $result = $dbi->query($sqlQuery, ConnectionType::User, DatabaseInterface::QUERY_UNBUFFERED);

        // If required, get fields name at the first line
        if (isset($GLOBALS['excel_columns']) && $GLOBALS['excel_columns']) {
            $insertFields = [];
            foreach ($result->getFieldNames() as $colAs) {
                if (! empty($aliases[$db]['tables'][$table]['columns'][$colAs])) {
                    $colAs = $aliases[$db]['tables'][$table]['columns'][$colAs];
                }

                if ($GLOBALS['excel_enclosed'] == '') {
                    $insertFields[] = $colAs;
                } else {
                    $insertFields[] = $GLOBALS['excel_enclosed']
                        . str_replace(
                            $GLOBALS['excel_enclosed'],
                            $GLOBALS['excel_escaped'] . $GLOBALS['excel_enclosed'],
                            $colAs,
                        )
                        . $GLOBALS['excel_enclosed'];
                }
            }

            $schemaInsert = implode($GLOBALS['excel_separator'], $insertFields);
            if (! $this->export->outputHandler($schemaInsert . $GLOBALS['excel_terminated'])) {
                return false;
            }
        }

        // Format the data
        while ($row = $result->fetchRow()) {
            $insertValues = [];
            foreach ($row as $field) {
                if ($field === null) {
                    $insertValues[] = $GLOBALS['excel_null'];
                } elseif ($field !== '') {
                    // always enclose fields
                    $field = preg_replace("/\015(\012)?/", "\012", $field);

                    // remove CRLF characters within field
                    if (isset($GLOBALS['excel_removeCRLF']) && $GLOBALS['excel_removeCRLF']) {
                        $field = str_replace(
                            ["\r", "\n"],
                            '',
                            $field,
                        );
                    }

                    if ($GLOBALS['excel_enclosed'] == '') {
                        $insertValues[] = $field;
                    } elseif ($GLOBALS['excel_escaped'] != $GLOBALS['excel_enclosed']) {
                        // also double the escape string if found in the data
                        $insertValues[] = $GLOBALS['excel_enclosed']
                            . str_replace(
                                $GLOBALS['excel_enclosed'],
                                $GLOBALS['excel_escaped'] . $GLOBALS['excel_enclosed'],
                                str_replace(
                                    $GLOBALS['excel_escaped'],
                                    $GLOBALS['excel_escaped'] . $GLOBALS['excel_escaped'],
                                    $field,
                                ),
                            )
                            . $GLOBALS['excel_enclosed'];
                    } else {
                        // avoid a problem when escape string equals enclose
                        $insertValues[] = $GLOBALS['excel_enclosed']
                            . str_replace(
                                $GLOBALS['excel_enclosed'],
                                $GLOBALS['excel_escaped'] . $GLOBALS['excel_enclosed'],
                                $field,
                            )
                            . $GLOBALS['excel_enclosed'];
                    }
                } else {
                    $insertValues[] = '';
                }
            }

            $schemaInsert = implode($GLOBALS['excel_separator'], $insertValues);
            if (! $this->export->outputHandler($schemaInsert . $GLOBALS['excel_terminated'])) {
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
    }
}
