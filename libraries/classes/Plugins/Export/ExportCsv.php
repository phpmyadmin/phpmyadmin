<?php
/**
 * CSV export code
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Export;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Plugins\ExportPlugin;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Options\Items\HiddenPropertyItem;
use PhpMyAdmin\Properties\Options\Items\TextPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;

use function __;
use function mb_strtolower;
use function mb_substr;
use function preg_replace;
use function str_replace;
use function trim;

/**
 * Handles the export for the CSV format
 */
class ExportCsv extends ExportPlugin
{
    /**
     * @psalm-return non-empty-lowercase-string
     */
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
            __('Columns separated with:')
        );
        $generalOptions->addProperty($leaf);
        $leaf = new TextPropertyItem(
            'enclosed',
            __('Columns enclosed with:')
        );
        $generalOptions->addProperty($leaf);
        $leaf = new TextPropertyItem(
            'escaped',
            __('Columns escaped with:')
        );
        $generalOptions->addProperty($leaf);
        $leaf = new TextPropertyItem(
            'terminated',
            __('Lines terminated with:')
        );
        $generalOptions->addProperty($leaf);
        $leaf = new TextPropertyItem(
            'null',
            __('Replace NULL with:')
        );
        $generalOptions->addProperty($leaf);
        $leaf = new BoolPropertyItem(
            'removeCRLF',
            __('Remove carriage return/line feed characters within columns')
        );
        $generalOptions->addProperty($leaf);
        $leaf = new BoolPropertyItem(
            'columns',
            __('Put columns names in the first row')
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
        //Enable columns names by default for CSV
        if ($GLOBALS['what'] === 'csv') {
            $GLOBALS['csv_columns'] = 'yes';
        }

        // Here we just prepare some values for export
        if ($GLOBALS['what'] === 'excel') {
            $GLOBALS['csv_terminated'] = "\015\012";
            switch ($GLOBALS['excel_edition']) {
                case 'win':
                    // as tested on Windows with Excel 2002 and Excel 2007
                    $GLOBALS['csv_separator'] = ';';
                    break;
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
                $GLOBALS['csv_columns'] = 'yes';
            }
        } else {
            if (empty($GLOBALS['csv_terminated']) || mb_strtolower($GLOBALS['csv_terminated']) === 'auto') {
                $GLOBALS['csv_terminated'] = $GLOBALS['crlf'];
            } else {
                $GLOBALS['csv_terminated'] = str_replace(
                    [
                        '\\r',
                        '\\n',
                        '\\t',
                    ],
                    [
                        "\015",
                        "\012",
                        "\011",
                    ],
                    $GLOBALS['csv_terminated']
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
    public function exportDBHeader($db, $dbAlias = ''): bool
    {
        return true;
    }

    /**
     * Outputs database footer
     *
     * @param string $db Database name
     */
    public function exportDBFooter($db): bool
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
    public function exportDBCreate($db, $exportType, $dbAlias = ''): bool
    {
        return true;
    }

    /**
     * Outputs the content of a table in CSV format
     *
     * @param string $db       database name
     * @param string $table    table name
     * @param string $crlf     the end of line sequence
     * @param string $errorUrl the url to go back in case of error
     * @param string $sqlQuery SQL query for obtaining data
     * @param array  $aliases  Aliases of db/table/columns
     */
    public function exportData(
        $db,
        $table,
        $crlf,
        $errorUrl,
        $sqlQuery,
        array $aliases = []
    ): bool {
        $db_alias = $db;
        $table_alias = $table;
        $this->initAlias($aliases, $db_alias, $table_alias);

        // Gets the data from the database
        $result = $GLOBALS['dbi']->query(
            $sqlQuery,
            DatabaseInterface::CONNECT_USER,
            DatabaseInterface::QUERY_UNBUFFERED
        );
        $fields_cnt = $result->numFields();

        // If required, get fields name at the first line
        if (isset($GLOBALS['csv_columns'])) {
            $schema_insert = '';
            foreach ($result->getFieldNames() as $col_as) {
                if (! empty($aliases[$db]['tables'][$table]['columns'][$col_as])) {
                    $col_as = $aliases[$db]['tables'][$table]['columns'][$col_as];
                }

                if ($GLOBALS['csv_enclosed'] == '') {
                    $schema_insert .= $col_as;
                } else {
                    $schema_insert .= $GLOBALS['csv_enclosed']
                        . str_replace(
                            $GLOBALS['csv_enclosed'],
                            $GLOBALS['csv_escaped'] . $GLOBALS['csv_enclosed'],
                            $col_as
                        )
                        . $GLOBALS['csv_enclosed'];
                }

                $schema_insert .= $GLOBALS['csv_separator'];
            }

            $schema_insert = trim(mb_substr($schema_insert, 0, -1));
            if (! $this->export->outputHandler($schema_insert . $GLOBALS['csv_terminated'])) {
                return false;
            }
        }

        // Format the data
        while ($row = $result->fetchRow()) {
            $schema_insert = '';
            for ($j = 0; $j < $fields_cnt; $j++) {
                if (! isset($row[$j])) {
                    $schema_insert .= $GLOBALS[$GLOBALS['what'] . '_null'];
                } elseif ($row[$j] == '0' || $row[$j] != '') {
                    // always enclose fields
                    if ($GLOBALS['what'] === 'excel') {
                        $row[$j] = preg_replace("/\015(\012)?/", "\012", $row[$j]);
                    }

                    // remove CRLF characters within field
                    if (
                        isset($GLOBALS[$GLOBALS['what'] . '_removeCRLF']) && $GLOBALS[$GLOBALS['what'] . '_removeCRLF']
                    ) {
                        $row[$j] = str_replace(
                            [
                                "\r",
                                "\n",
                            ],
                            '',
                            $row[$j]
                        );
                    }

                    if ($GLOBALS['csv_enclosed'] == '') {
                        $schema_insert .= $row[$j];
                    } else {
                        // also double the escape string if found in the data
                        if ($GLOBALS['csv_escaped'] != $GLOBALS['csv_enclosed']) {
                            $schema_insert .= $GLOBALS['csv_enclosed']
                                . str_replace(
                                    $GLOBALS['csv_enclosed'],
                                    $GLOBALS['csv_escaped'] . $GLOBALS['csv_enclosed'],
                                    str_replace(
                                        $GLOBALS['csv_escaped'],
                                        $GLOBALS['csv_escaped'] . $GLOBALS['csv_escaped'],
                                        $row[$j]
                                    )
                                )
                                . $GLOBALS['csv_enclosed'];
                        } else {
                            // avoid a problem when escape string equals enclose
                            $schema_insert .= $GLOBALS['csv_enclosed']
                                . str_replace(
                                    $GLOBALS['csv_enclosed'],
                                    $GLOBALS['csv_escaped'] . $GLOBALS['csv_enclosed'],
                                    $row[$j]
                                )
                                . $GLOBALS['csv_enclosed'];
                        }
                    }
                } else {
                    $schema_insert .= '';
                }

                if ($j >= $fields_cnt - 1) {
                    continue;
                }

                $schema_insert .= $GLOBALS['csv_separator'];
            }

            if (! $this->export->outputHandler($schema_insert . $GLOBALS['csv_terminated'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Outputs result of raw query in CSV format
     *
     * @param string $errorUrl the url to go back in case of error
     * @param string $sqlQuery the rawquery to output
     * @param string $crlf     the end of line sequence
     */
    public function exportRawQuery(string $errorUrl, string $sqlQuery, string $crlf): bool
    {
        return $this->exportData('', '', $crlf, $errorUrl, $sqlQuery);
    }
}
