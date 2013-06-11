<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * OpenDocument Spreadsheet import plugin for phpMyAdmin
 *
 * @todo    Pretty much everything
 * @todo    Importing of accented characters seems to fail
 * @package    PhpMyAdmin-Import
 * @subpackage ODS
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * We need way to disable external XML entities processing.
 */
if (!function_exists('libxml_disable_entity_loader')) {
    return;
}

/* Get the import interface */
require_once 'libraries/plugins/ImportPlugin.class.php';

/**
 * Handles the import for the ODS format
 *
 * @package    PhpMyAdmin-Import
 * @subpackage ODS
 */
class ImportOds extends ImportPlugin
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->setProperties();
    }

    /**
     * Sets the import plugin properties.
     * Called in the constructor.
     *
     * @return void
     */
    protected function setProperties()
    {
        $props = 'libraries/properties/';
        include_once "$props/plugins/ImportPluginProperties.class.php";
        include_once "$props/options/groups/OptionsPropertyRootGroup.class.php";
        include_once "$props/options/groups/OptionsPropertyMainGroup.class.php";
        include_once "$props/options/items/BoolPropertyItem.class.php";

        $importPluginProperties = new ImportPluginProperties();
        $importPluginProperties->setText('OpenDocument Spreadsheet');
        $importPluginProperties->setExtension('ods');
        $importPluginProperties->setOptionsText(__('Options'));

        // create the root group that will be the options field for
        // $importPluginProperties
        // this will be shown as "Format specific options"
        $importSpecificOptions = new OptionsPropertyRootGroup();
        $importSpecificOptions->setName("Format Specific Options");

        // general options main group
        $generalOptions = new OptionsPropertyMainGroup();
        $generalOptions->setName("general_opts");
        // create primary items and add them to the group
        $leaf = new BoolPropertyItem();
        $leaf->setName("col_names");
        $leaf->setText(
            __(
                'The first line of the file contains the table column names'
                . ' <i>(if this is unchecked, the first line will become part'
                . ' of the data)</i>'
            )
        );
        $generalOptions->addProperty($leaf);
        $leaf = new BoolPropertyItem();
        $leaf->setName("empty_rows");
        $leaf->setText(__('Do not import empty rows'));
        $generalOptions->addProperty($leaf);
        $leaf = new BoolPropertyItem();
        $leaf->setName("recognize_percentages");
        $leaf->setText(
            __(
                'Import percentages as proper decimals <i>(ex. 12.00% to .12)</i>'
            )
        );
        $generalOptions->addProperty($leaf);
        $leaf = new BoolPropertyItem();
        $leaf->setName("recognize_currency");
        $leaf->setText(__('Import currencies <i>(ex. $5.00 to 5.00)</i>'));
        $generalOptions->addProperty($leaf);

        // add the main group to the root group
        $importSpecificOptions->addProperty($generalOptions);

        // set the options for the import plugin property item
        $importPluginProperties->setOptions($importSpecificOptions);
        $this->properties = $importPluginProperties;
    }

    /**
     * This method is called when any PluginManager to which the observer
     * is attached calls PluginManager::notify()
     *
     * @param SplSubject $subject The PluginManager notifying the observer
     *                            of an update.
     *
     * @return void
     */
    public function update (SplSubject $subject)
    {
    }

    /**
     * Handles the whole import logic
     *
     * @return void
     */
    public function doImport()
    {
        global $db, $error, $timeout_passed, $finished;

        $i = 0;
        $len = 0;
        $buffer = "";

        /**
         * Read in the file via PMA_importGetNextChunk so that
         * it can process compressed files
         */
        while (! ($finished && $i >= $len) && ! $error && ! $timeout_passed) {
            $data = PMA_importGetNextChunk();
            if ($data === false) {
                /* subtract data we didn't handle yet and stop processing */
                $offset -= strlen($buffer);
                break;
            } elseif ($data === true) {
                /* Handle rest of buffer */
            } else {
                /* Append new data to buffer */
                $buffer .= $data;
                unset($data);
            }
        }

        unset($data);

        /**
         * Disable loading of external XML entities.
         */
        libxml_disable_entity_loader();

        /**
         * Load the XML string
         *
         * The option LIBXML_COMPACT is specified because it can
         * result in increased performance without the need to
         * alter the code in any way. It's basically a freebee.
         */
        $xml = simplexml_load_string($buffer, "SimpleXMLElement", LIBXML_COMPACT);

        unset($buffer);

        if ($xml === false) {
            $sheets = array();
            $message = PMA_Message::error(
                __(
                    'The XML file specified was either malformed or incomplete.'
                    . ' Please correct the issue and try again.'
                )
            );
            $error = true;
        } else {
            $root = $xml->children('office', true)->{'body'}->{'spreadsheet'};
            if (empty($root)) {
                $sheets = array();
                $message = PMA_Message::error(
                    __('Could not parse OpenDocument Spreasheet!')
                );
                $error = true;
            } else {
                $sheets = $root->children('table', true);
            }
        }

        $tables = array();

        $max_cols = 0;

        $row_count = 0;
        $col_count = 0;
        $col_names = array();

        $tempRow = array();
        $tempRows = array();
        $rows = array();

        /* Iterate over tables */
        foreach ($sheets as $sheet) {
            $col_names_in_first_row = isset($_REQUEST['ods_col_names']);

            /* Iterate over rows */
            foreach ($sheet as $row) {
                $type = $row->getName();
                if (! strcmp('table-row', $type)) {
                    /* Iterate over columns */
                    foreach ($row as $cell) {
                        $text = $cell->children('text', true);
                        $cell_attrs = $cell->attributes('office', true);

                        if (count($text) != 0) {
                            $attr = $cell->attributes('table', true);
                            $num_repeat = (int) $attr['number-columns-repeated'];
                            $num_iterations = $num_repeat ? $num_repeat : 1;

                            for ($k = 0; $k < $num_iterations; $k++) {
                                if ($_REQUEST['ods_recognize_percentages']
                                    && ! strcmp(
                                        'percentage',
                                        $cell_attrs['value-type']
                                    )
                                ) {
                                    $value = (double)$cell_attrs['value'];
                                } elseif ($_REQUEST['ods_recognize_currency']
                                    && !strcmp('currency', $cell_attrs['value-type'])
                                ) {
                                    $value = (double)$cell_attrs['value'];
                                } else {
                                    /* We need to concatenate all paragraphs */
                                    $values = array();
                                    foreach ($text as $paragraph) {
                                        $values[] = (string)$paragraph;
                                    }
                                    $value = implode("\n", $values);
                                }
                                if (! $col_names_in_first_row) {
                                    $tempRow[] = $value;
                                } else {
                                    $col_names[] = $value;
                                }

                                ++$col_count;
                            }
                        } else {
                            /* Number of blank columns repeated */
                            if ($col_count < count($row->children('table', true)) - 1
                            ) {
                                $attr = $cell->attributes('table', true);
                                $num_null = (int)$attr['number-columns-repeated'];

                                if ($num_null) {
                                    if (! $col_names_in_first_row) {
                                        for ($i = 0; $i < $num_null; ++$i) {
                                            $tempRow[] = 'NULL';
                                            ++$col_count;
                                        }
                                    } else {
                                        for ($i = 0; $i < $num_null; ++$i) {
                                            $col_names[] = PMA_getColumnAlphaName(
                                                $col_count + 1
                                            );
                                            ++$col_count;
                                        }
                                    }
                                } else {
                                    if (! $col_names_in_first_row) {
                                        $tempRow[] = 'NULL';
                                    } else {
                                        $col_names[] = PMA_getColumnAlphaName(
                                            $col_count + 1
                                        );
                                    }

                                    ++$col_count;
                                }
                            }
                        }
                    }

                    /* Find the widest row */
                    if ($col_count > $max_cols) {
                        $max_cols = $col_count;
                    }

                    /* Don't include a row that is full of NULL values */
                    if (! $col_names_in_first_row) {
                        if ($_REQUEST['ods_empty_rows']) {
                            foreach ($tempRow as $cell) {
                                if (strcmp('NULL', $cell)) {
                                    $tempRows[] = $tempRow;
                                    break;
                                }
                            }
                        } else {
                            $tempRows[] = $tempRow;
                        }
                    }

                    $col_count = 0;
                    $col_names_in_first_row = false;
                    $tempRow = array();
                }
            }

            /* Skip over empty sheets */
            if (count($tempRows) == 0 || count($tempRows[0]) == 0) {
                $col_names = array();
                $tempRow = array();
                $tempRows = array();
                continue;
            }

            /**
             * Fill out each row as necessary to make
             * every one exactly as wide as the widest
             * row. This included column names.
             */

            /* Fill out column names */
            for ($i = count($col_names); $i < $max_cols; ++$i) {
                $col_names[] = PMA_getColumnAlphaName($i + 1);
            }

            /* Fill out all rows */
            $num_rows = count($tempRows);
            for ($i = 0; $i < $num_rows; ++$i) {
                for ($j = count($tempRows[$i]); $j < $max_cols; ++$j) {
                    $tempRows[$i][] = 'NULL';
                }
            }

            /* Store the table name so we know where to place the row set */
            $tbl_attr = $sheet->attributes('table', true);
            $tables[] = array((string)$tbl_attr['name']);

            /* Store the current sheet in the accumulator */
            $rows[] = array((string)$tbl_attr['name'], $col_names, $tempRows);
            $tempRows = array();
            $col_names = array();
            $max_cols = 0;
        }

        unset($tempRow);
        unset($tempRows);
        unset($col_names);
        unset($sheets);
        unset($xml);

        /**
         * Bring accumulated rows into the corresponding table
         */
        $num_tbls = count($tables);
        for ($i = 0; $i < $num_tbls; ++$i) {
            for ($j = 0; $j < count($rows); ++$j) {
                if (! strcmp($tables[$i][TBL_NAME], $rows[$j][TBL_NAME])) {
                    if (! isset($tables[$i][COL_NAMES])) {
                        $tables[$i][] = $rows[$j][COL_NAMES];
                    }

                    $tables[$i][ROWS] = $rows[$j][ROWS];
                }
            }
        }

        /* No longer needed */
        unset($rows);

        /* Obtain the best-fit MySQL types for each column */
        $analyses = array();

        $len = count($tables);
        for ($i = 0; $i < $len; ++$i) {
            $analyses[] = PMA_analyzeTable($tables[$i]);
        }

        /**
         * string $db_name (no backquotes)
         *
         * array $table = array(table_name, array() column_names, array()() rows)
         * array $tables = array of "$table"s
         *
         * array $analysis = array(array() column_types, array() column_sizes)
         * array $analyses = array of "$analysis"s
         *
         * array $create = array of SQL strings
         *
         * array $options = an associative array of options
         */

        /* Set database name to the currently selected one, if applicable */
        if (strlen($db)) {
            $db_name = $db;
            $options = array('create_db' => false);
        } else {
            $db_name = 'ODS_DB';
            $options = null;
        }

        /* Non-applicable parameters */
        $create = null;

        /* Created and execute necessary SQL statements from data */
        PMA_buildSQL($db_name, $tables, $analyses, $create, $options);

        unset($tables);
        unset($analyses);

        /* Commit any possible data in buffers */
        PMA_importRunQuery();
    }
}
