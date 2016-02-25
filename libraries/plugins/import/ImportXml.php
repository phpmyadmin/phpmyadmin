<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * XML import plugin for phpMyAdmin
 *
 * @todo       Improve efficiency
 * @package    PhpMyAdmin-Import
 * @subpackage XML
 */

namespace PMA\libraries\plugins\import;

use PMA\libraries\properties\plugins\ImportPluginProperties;
use PMA;
use PMA\libraries\plugins\ImportPlugin;
use SimpleXMLElement;

/**
 * Handles the import for the XML format
 *
 * @package    PhpMyAdmin-Import
 * @subpackage XML
 */
class ImportXml extends ImportPlugin
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
        $importPluginProperties = new ImportPluginProperties();
        $importPluginProperties->setText(__('XML'));
        $importPluginProperties->setExtension('xml');
        $importPluginProperties->setMimeType('text/xml');
        $importPluginProperties->setOptions(array());
        $importPluginProperties->setOptionsText(__('Options'));

        $this->properties = $importPluginProperties;
    }

    /**
     * Handles the whole import logic
     *
     * @param array &$sql_data 2-element array with sql data
     *
     * @return void
     */
    public function doImport(&$sql_data = array())
    {
        global $error, $timeout_passed, $finished, $db;

        $i = 0;
        $len = 0;
        $buffer = "";

        /**
         * Read in the file via PMA_importGetNextChunk so that
         * it can process compressed files
         */
        while (!($finished && $i >= $len) && !$error && !$timeout_passed) {
            $data = PMA_importGetNextChunk();
            if ($data === false) {
                /* subtract data we didn't handle yet and stop processing */
                $GLOBALS['offset'] -= strlen($buffer);
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
        $xml = @simplexml_load_string($buffer, "SimpleXMLElement", LIBXML_COMPACT);

        unset($buffer);

        /**
         * The XML was malformed
         */
        if ($xml === false) {
            PMA\libraries\Message::error(
                __(
                    'The XML file specified was either malformed or incomplete.'
                    . ' Please correct the issue and try again.'
                )
            )
                ->display();
            unset($xml);
            $GLOBALS['finished'] = false;

            return;
        }

        /**
         * Table accumulator
         */
        $tables = array();
        /**
         * Row accumulator
         */
        $rows = array();

        /**
         * Temp arrays
         */
        $tempRow = array();
        $tempCells = array();

        /**
         * CREATE code included (by default: no)
         */
        $struct_present = false;

        /**
         * Analyze the data in each table
         */
        $namespaces = $xml->getNameSpaces(true);

        /**
         * Get the database name, collation and charset
         */
        $db_attr = $xml->children($namespaces['pma'])
            ->{'structure_schemas'}->{'database'};

        if ($db_attr instanceof SimpleXMLElement) {
            $db_attr = $db_attr->attributes();
            $db_name = (string)$db_attr['name'];
            $collation = (string)$db_attr['collation'];
            $charset = (string)$db_attr['charset'];
        } else {
            /**
             * If the structure section is not present
             * get the database name from the data section
             */
            $db_attr = $xml->children()
                ->attributes();
            $db_name = (string)$db_attr['name'];
            $collation = null;
            $charset = null;
        }

        /**
         * The XML was malformed
         */
        if ($db_name === null) {
            PMA\libraries\Message::error(
                __(
                    'The XML file specified was either malformed or incomplete.'
                    . ' Please correct the issue and try again.'
                )
            )
                ->display();
            unset($xml);
            $GLOBALS['finished'] = false;

            return;
        }

        /**
         * Retrieve the structure information
         */
        if (isset($namespaces['pma'])) {
            /**
             * Get structures for all tables
             *
             * @var SimpleXMLElement $struct
             */
            $struct = $xml->children($namespaces['pma']);

            $create = array();

            /** @var SimpleXMLElement $val1 */
            foreach ($struct as $val1) {
                /** @var SimpleXMLElement $val2 */
                foreach ($val1 as $val2) {
                    // Need to select the correct database for the creation of
                    // tables, views, triggers, etc.
                    /**
                     * @todo    Generating a USE here blocks importing of a table
                     *          into another database.
                     */
                    $attrs = $val2->attributes();
                    $create[] = "USE "
                        . PMA\libraries\Util::backquote(
                            $attrs["name"]
                        );

                    foreach ($val2 as $val3) {
                        /**
                         * Remove the extra cosmetic spacing
                         */
                        $val3 = str_replace("                ", "", (string)$val3);
                        $create[] = $val3;
                    }
                }
            }

            $struct_present = true;
        }

        /**
         * Move down the XML tree to the actual data
         */
        $xml = $xml->children()
            ->children();

        $data_present = false;

        /**
         * Only attempt to analyze/collect data if there is data present
         */
        if ($xml && @count($xml->children())) {
            $data_present = true;

            /**
             * Process all database content
             */
            foreach ($xml as $v1) {
                $tbl_attr = $v1->attributes();

                $isInTables = false;
                $num_tables = count($tables);
                for ($i = 0; $i < $num_tables; ++$i) {
                    if (!strcmp($tables[$i][TBL_NAME], (string)$tbl_attr['name'])) {
                        $isInTables = true;
                        break;
                    }
                }

                if (!$isInTables) {
                    $tables[] = array((string)$tbl_attr['name']);
                }

                foreach ($v1 as $v2) {
                    $row_attr = $v2->attributes();
                    if (!array_search((string)$row_attr['name'], $tempRow)) {
                        $tempRow[] = (string)$row_attr['name'];
                    }
                    $tempCells[] = (string)$v2;
                }

                $rows[] = array((string)$tbl_attr['name'], $tempRow, $tempCells);

                $tempRow = array();
                $tempCells = array();
            }

            unset($tempRow);
            unset($tempCells);
            unset($xml);

            /**
             * Bring accumulated rows into the corresponding table
             */
            $num_tables = count($tables);
            for ($i = 0; $i < $num_tables; ++$i) {
                $num_rows = count($rows);
                for ($j = 0; $j < $num_rows; ++$j) {
                    if (!strcmp($tables[$i][TBL_NAME], $rows[$j][TBL_NAME])) {
                        if (!isset($tables[$i][COL_NAMES])) {
                            $tables[$i][] = $rows[$j][COL_NAMES];
                        }

                        $tables[$i][ROWS][] = $rows[$j][ROWS];
                    }
                }
            }

            unset($rows);

            if (!$struct_present) {
                $analyses = array();

                $len = count($tables);
                for ($i = 0; $i < $len; ++$i) {
                    $analyses[] = PMA_analyzeTable($tables[$i]);
                }
            }
        }

        unset($xml);
        unset($tempCells);
        unset($rows);

        /**
         * Only build SQL from data if there is data present
         */
        if ($data_present) {
            /**
             * Set values to NULL if they were not present
             * to maintain PMA_buildSQL() call integrity
             */
            if (!isset($analyses)) {
                $analyses = null;
                if (!$struct_present) {
                    $create = null;
                }
            }
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
            /* Override the database name in the XML file, if one is selected */
            $db_name = $db;
            $options = array('create_db' => false);
        } else {
            if ($db_name === null) {
                $db_name = 'XML_DB';
            }

            /* Set database collation/charset */
            $options = array(
                'db_collation' => $collation,
                'db_charset'   => $charset,
            );
        }

        /* Created and execute necessary SQL statements from data */
        PMA_buildSQL($db_name, $tables, $analyses, $create, $options, $sql_data);

        unset($analyses);
        unset($tables);
        unset($create);

        /* Commit any possible data in buffers */
        PMA_importRunQuery('', '', $sql_data);
    }
}
