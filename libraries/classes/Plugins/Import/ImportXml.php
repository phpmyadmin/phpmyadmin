<?php
/**
 * XML import plugin for phpMyAdmin
 *
 * @todo       Improve efficiency
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Import;

use PhpMyAdmin\File;
use PhpMyAdmin\Import;
use PhpMyAdmin\Message;
use PhpMyAdmin\Plugins\ImportPlugin;
use PhpMyAdmin\Properties\Plugins\ImportPluginProperties;
use PhpMyAdmin\Util;
use SimpleXMLElement;

use function __;
use function count;
use function in_array;
use function simplexml_load_string;
use function str_replace;
use function strcmp;
use function strlen;

use const LIBXML_COMPACT;

/**
 * Handles the import for the XML format
 */
class ImportXml extends ImportPlugin
{
    /** @psalm-return non-empty-lowercase-string */
    public function getName(): string
    {
        return 'xml';
    }

    protected function setProperties(): ImportPluginProperties
    {
        $importPluginProperties = new ImportPluginProperties();
        $importPluginProperties->setText(__('XML'));
        $importPluginProperties->setExtension('xml');
        $importPluginProperties->setMimeType('text/xml');
        $importPluginProperties->setOptionsText(__('Options'));

        return $importPluginProperties;
    }

    /**
     * Handles the whole import logic
     *
     * @return string[]
     */
    public function doImport(File|null $importHandle = null): array
    {
        $GLOBALS['error'] ??= null;
        $GLOBALS['timeout_passed'] ??= null;
        $GLOBALS['finished'] ??= null;

        $buffer = '';

        /**
         * Read in the file via Import::getNextChunk so that
         * it can process compressed files
         */
        while (! $GLOBALS['finished'] && ! $GLOBALS['error'] && ! $GLOBALS['timeout_passed']) {
            $data = $this->import->getNextChunk($importHandle);
            if ($data === false) {
                /* subtract data we didn't handle yet and stop processing */
                $GLOBALS['offset'] -= strlen($buffer);
                break;
            }

            if ($data === true) {
                continue;
            }

            /* Append new data to buffer */
            $buffer .= $data;
        }

        /**
         * Load the XML string
         *
         * The option LIBXML_COMPACT is specified because it can
         * result in increased performance without the need to
         * alter the code in any way. It's basically a freebee.
         */
        $xml = @simplexml_load_string($buffer, SimpleXMLElement::class, LIBXML_COMPACT);

        unset($buffer);

        /**
         * The XML was malformed
         */
        if ($xml === false) {
            echo Message::error(__(
                'The XML file specified was either malformed or incomplete. Please correct the issue and try again.',
            ))->getDisplay();
            unset($xml);
            $GLOBALS['finished'] = false;

            return [];
        }

        /**
         * Table accumulator
         */
        $tables = [];
        /**
         * Row accumulator
         */
        $rows = [];

        /**
         * Temp arrays
         */
        $tempRow = [];
        $tempCells = [];

        /**
         * CREATE code included (by default: no)
         */
        $structPresent = false;

        /**
         * Analyze the data in each table
         */
        $namespaces = $xml->getNamespaces(true);

        /**
         * Get the database name, collation and charset
         */
        $dbAttr = $xml->children($namespaces['pma'] ?? null)
            ->{'structure_schemas'}->{'database'};

        if ($dbAttr instanceof SimpleXMLElement) {
            $dbAttr = $dbAttr->attributes();
            $dbName = (string) $dbAttr['name'];
            $collation = (string) $dbAttr['collation'];
            $charset = (string) $dbAttr['charset'];
        } else {
            /**
             * If the structure section is not present
             * get the database name from the data section
             */
            $dbAttr = $xml->children()
                ->attributes();
            $dbName = (string) $dbAttr['name'];
            $collation = null;
            $charset = null;
        }

        /**
         * The XML was malformed
         */
        if ($dbName === '') {
            echo Message::error(__(
                'The XML file specified was either malformed or incomplete. Please correct the issue and try again.',
            ))->getDisplay();
            unset($xml);
            $GLOBALS['finished'] = false;

            return [];
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

            $create = [];

            foreach ($struct as $val1) {
                foreach ($val1 as $val2) {
                    // Need to select the correct database for the creation of
                    // tables, views, triggers, etc.
                    /**
                     * @todo    Generating a USE here blocks importing of a table
                     *          into another database.
                     */
                    $attrs = $val2->attributes();
                    $create[] = 'USE ' . Util::backquote((string) $attrs['name']);

                    foreach ($val2 as $val3) {
                        /**
                         * Remove the extra cosmetic spacing
                         */
                        $val3 = str_replace('                ', '', (string) $val3);
                        $create[] = $val3;
                    }
                }
            }

            $structPresent = true;
        }

        /**
         * Move down the XML tree to the actual data
         */
        $xml = $xml->children()
            ->children();

        $dataPresent = false;
        $analyses = null;

        /**
         * Only attempt to analyze/collect data if there is data present
         */
        if ($xml && $xml->children()->count()) {
            $dataPresent = true;

            /**
             * Process all database content
             */
            foreach ($xml as $v1) {
                /** @psalm-suppress PossiblyNullReference */
                $tblAttr = $v1->attributes();

                $isInTables = false;
                $numTables = count($tables);
                for ($i = 0; $i < $numTables; ++$i) {
                    if (! strcmp($tables[$i][Import::TBL_NAME], (string) $tblAttr['name'])) {
                        $isInTables = true;
                        break;
                    }
                }

                if (! $isInTables) {
                    $tables[] = [(string) $tblAttr['name']];
                }

                foreach ($v1 as $v2) {
                    /** @psalm-suppress PossiblyNullReference */
                    $rowAttr = $v2->attributes();
                    if (! in_array((string) $rowAttr['name'], $tempRow)) {
                        $tempRow[] = (string) $rowAttr['name'];
                    }

                    $tempCells[] = (string) $v2;
                }

                $rows[] = [(string) $tblAttr['name'], $tempRow, $tempCells];

                $tempRow = [];
                $tempCells = [];
            }

            unset($tempRow, $tempCells, $xml);

            /**
             * Bring accumulated rows into the corresponding table
             */
            $numTables = count($tables);
            for ($i = 0; $i < $numTables; ++$i) {
                $numRows = count($rows);
                for ($j = 0; $j < $numRows; ++$j) {
                    if (strcmp($tables[$i][Import::TBL_NAME], $rows[$j][Import::TBL_NAME])) {
                        continue;
                    }

                    if (! isset($tables[$i][Import::COL_NAMES])) {
                        $tables[$i][] = $rows[$j][Import::COL_NAMES];
                    }

                    $tables[$i][Import::ROWS][] = $rows[$j][Import::ROWS];
                }
            }

            unset($rows);

            if (! $structPresent) {
                $analyses = [];

                $len = count($tables);
                for ($i = 0; $i < $len; ++$i) {
                    $analyses[] = $this->import->analyzeTable($tables[$i]);
                }
            }
        }

        unset($xml, $tempCells, $rows);

        /**
         * Only build SQL from data if there is data present
         */
        if ($dataPresent) {
            /**
             * Set values to NULL if they were not present
             * to maintain Import::buildSql() call integrity
             */
            if (! isset($analyses)) {
                $analyses = null;
                if (! $structPresent) {
                    $create = null;
                }
            }
        }

        /* Set database name to the currently selected one, if applicable */
        if ($GLOBALS['db'] !== '') {
            /* Override the database name in the XML file, if one is selected */
            $dbName = $GLOBALS['db'];
            $options = null;
        } else {
            /* Set database collation/charset */
            $options = ['db_collation' => $collation, 'db_charset' => $charset];
        }

        $createDb = $GLOBALS['db'] === '';

        /* Created and execute necessary SQL statements from data */
        $sqlStatements = [];
        $this->import->buildSql($dbName, $tables, $analyses, $create, $createDb, $options, $sqlStatements);

        /* Commit any possible data in buffers */
        $this->import->runQuery('', $sqlStatements);

        return $sqlStatements;
    }
}
