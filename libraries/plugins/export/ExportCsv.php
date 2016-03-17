<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * CSV export code
 *
 * @package    PhpMyAdmin-Export
 * @subpackage CSV
 */

namespace PMA\libraries\plugins\export;

use PMA\libraries\properties\options\items\BoolPropertyItem;
use PMA\libraries\plugins\ExportPlugin;
use PMA\libraries\properties\plugins\ExportPluginProperties;
use PMA\libraries\properties\options\items\HiddenPropertyItem;
use PMA\libraries\properties\options\groups\OptionsPropertyMainGroup;
use PMA\libraries\properties\options\groups\OptionsPropertyRootGroup;
use PMA;
use PMA\libraries\properties\options\items\TextPropertyItem;

/**
 * Handles the export for the CSV format
 *
 * @package    PhpMyAdmin-Export
 * @subpackage CSV
 */
class ExportCsv extends ExportPlugin
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->setProperties();
    }

    /**
     * Sets the export CSV properties
     *
     * @return void
     */
    protected function setProperties()
    {
        $exportPluginProperties = new ExportPluginProperties();
        $exportPluginProperties->setText('CSV');
        $exportPluginProperties->setExtension('csv');
        $exportPluginProperties->setMimeType('text/comma-separated-values');
        $exportPluginProperties->setOptionsText(__('Options'));

        // create the root group that will be the options field for
        // $exportPluginProperties
        // this will be shown as "Format specific options"
        $exportSpecificOptions = new OptionsPropertyRootGroup(
            "Format Specific Options"
        );

        // general options main group
        $generalOptions = new OptionsPropertyMainGroup("general_opts");
        // create leaf items and add them to the group
        $leaf = new TextPropertyItem(
            "separator",
            __('Columns separated with:')
        );
        $generalOptions->addProperty($leaf);
        $leaf = new TextPropertyItem(
            "enclosed",
            __('Columns enclosed with:')
        );
        $generalOptions->addProperty($leaf);
        $leaf = new TextPropertyItem(
            "escaped",
            __('Columns escaped with:')
        );
        $generalOptions->addProperty($leaf);
        $leaf = new TextPropertyItem(
            "terminated",
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
        $leaf = new HiddenPropertyItem(
            'structure_or_data'
        );
        $generalOptions->addProperty($leaf);
        // add the main group to the root group
        $exportSpecificOptions->addProperty($generalOptions);

        // set the options for the export plugin property item
        $exportPluginProperties->setOptions($exportSpecificOptions);
        $this->properties = $exportPluginProperties;
    }

    /**
     * Outputs export header
     *
     * @return bool Whether it succeeded
     */
    public function exportHeader()
    {
        global $what, $csv_terminated, $csv_separator, $csv_enclosed, $csv_escaped;

        // Here we just prepare some values for export
        if ($what == 'excel') {
            $csv_terminated = "\015\012";
            switch ($GLOBALS['excel_edition']) {
            case 'win':
                // as tested on Windows with Excel 2002 and Excel 2007
                $csv_separator = ';';
                break;
            case 'mac_excel2003':
                $csv_separator = ';';
                break;
            case 'mac_excel2008':
                $csv_separator = ',';
                break;
            }
            $csv_enclosed = '"';
            $csv_escaped = '"';
            if (isset($GLOBALS['excel_columns'])) {
                $GLOBALS['csv_columns'] = 'yes';
            }
        } else {
            if (empty($csv_terminated)
                || mb_strtolower($csv_terminated) == 'auto'
            ) {
                $csv_terminated = $GLOBALS['crlf'];
            } else {
                $csv_terminated = str_replace('\\r', "\015", $csv_terminated);
                $csv_terminated = str_replace('\\n', "\012", $csv_terminated);
                $csv_terminated = str_replace('\\t', "\011", $csv_terminated);
            } // end if
            $csv_separator = str_replace('\\t', "\011", $csv_separator);
        }

        return true;
    }

    /**
     * Outputs export footer
     *
     * @return bool Whether it succeeded
     */
    public function exportFooter()
    {
        return true;
    }

    /**
     * Outputs database header
     *
     * @param string $db       Database name
     * @param string $db_alias Alias of db
     *
     * @return bool Whether it succeeded
     */
    public function exportDBHeader($db, $db_alias = '')
    {
        return true;
    }

    /**
     * Outputs database footer
     *
     * @param string $db Database name
     *
     * @return bool Whether it succeeded
     */
    public function exportDBFooter($db)
    {
        return true;
    }

    /**
     * Outputs CREATE DATABASE statement
     *
     * @param string $db          Database name
     * @param string $export_type 'server', 'database', 'table'
     * @param string $db_alias    Aliases of db
     *
     * @return bool Whether it succeeded
     */
    public function exportDBCreate($db, $export_type, $db_alias = '')
    {
        return true;
    }

    /**
     * Outputs the content of a table in CSV format
     *
     * @param string $db        database name
     * @param string $table     table name
     * @param string $crlf      the end of line sequence
     * @param string $error_url the url to go back in case of error
     * @param string $sql_query SQL query for obtaining data
     * @param array  $aliases   Aliases of db/table/columns
     *
     * @return bool Whether it succeeded
     */
    public function exportData(
        $db,
        $table,
        $crlf,
        $error_url,
        $sql_query,
        $aliases = array()
    ) {
        global $what, $csv_terminated, $csv_separator, $csv_enclosed, $csv_escaped;

        $db_alias = $db;
        $table_alias = $table;
        $this->initAlias($aliases, $db_alias, $table_alias);

        // Gets the data from the database
        $result = $GLOBALS['dbi']->query(
            $sql_query,
            null,
            PMA\libraries\DatabaseInterface::QUERY_UNBUFFERED
        );
        $fields_cnt = $GLOBALS['dbi']->numFields($result);

        // If required, get fields name at the first line
        if (isset($GLOBALS['csv_columns'])) {
            $schema_insert = '';
            for ($i = 0; $i < $fields_cnt; $i++) {
                $col_as = $GLOBALS['dbi']->fieldName($result, $i);
                if (!empty($aliases[$db]['tables'][$table]['columns'][$col_as])) {
                    $col_as = $aliases[$db]['tables'][$table]['columns'][$col_as];
                }
                $col_as = stripslashes($col_as);
                if ($csv_enclosed == '') {
                    $schema_insert .= $col_as;
                } else {
                    $schema_insert .= $csv_enclosed
                        . str_replace(
                            $csv_enclosed,
                            $csv_escaped . $csv_enclosed,
                            $col_as
                        )
                        . $csv_enclosed;
                }
                $schema_insert .= $csv_separator;
            } // end for
            $schema_insert = trim(mb_substr($schema_insert, 0, -1));
            if (!PMA_exportOutputHandler($schema_insert . $csv_terminated)) {
                return false;
            }
        } // end if

        // Format the data
        while ($row = $GLOBALS['dbi']->fetchRow($result)) {
            $schema_insert = '';
            for ($j = 0; $j < $fields_cnt; $j++) {
                if (!isset($row[$j]) || is_null($row[$j])) {
                    $schema_insert .= $GLOBALS[$what . '_null'];
                } elseif ($row[$j] == '0' || $row[$j] != '') {
                    // always enclose fields
                    if ($what == 'excel') {
                        $row[$j] = preg_replace("/\015(\012)?/", "\012", $row[$j]);
                    }
                    // remove CRLF characters within field
                    if (isset($GLOBALS[$what . '_removeCRLF'])
                        && $GLOBALS[$what . '_removeCRLF']
                    ) {
                        $row[$j] = str_replace(
                            "\n",
                            "",
                            str_replace(
                                "\r",
                                "",
                                $row[$j]
                            )
                        );
                    }
                    if ($csv_enclosed == '') {
                        $schema_insert .= $row[$j];
                    } else {
                        // also double the escape string if found in the data
                        if ($csv_escaped != $csv_enclosed) {
                            $schema_insert .= $csv_enclosed
                                . str_replace(
                                    $csv_enclosed,
                                    $csv_escaped . $csv_enclosed,
                                    str_replace(
                                        $csv_escaped,
                                        $csv_escaped . $csv_escaped,
                                        $row[$j]
                                    )
                                )
                                . $csv_enclosed;
                        } else {
                            // avoid a problem when escape string equals enclose
                            $schema_insert .= $csv_enclosed
                                . str_replace(
                                    $csv_enclosed,
                                    $csv_escaped . $csv_enclosed,
                                    $row[$j]
                                )
                                . $csv_enclosed;
                        }
                    }
                } else {
                    $schema_insert .= '';
                }
                if ($j < $fields_cnt - 1) {
                    $schema_insert .= $csv_separator;
                }
            } // end for

            if (!PMA_exportOutputHandler($schema_insert . $csv_terminated)) {
                return false;
            }
        } // end while
        $GLOBALS['dbi']->freeResult($result);

        return true;
    }
}
