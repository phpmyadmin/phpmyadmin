<?php
/**
 * XML import plugin for phpMyAdmin
 *
 * @todo       Improve efficiency
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Import;

use PhpMyAdmin\Current;
use PhpMyAdmin\File;
use PhpMyAdmin\Import\ImportSettings;
use PhpMyAdmin\Import\ImportTable;
use PhpMyAdmin\Message;
use PhpMyAdmin\Plugins\ImportPlugin;
use PhpMyAdmin\Properties\Plugins\ImportPluginProperties;
use PhpMyAdmin\Util;
use SimpleXMLElement;

use function __;
use function array_map;
use function array_values;
use function in_array;
use function simplexml_load_string;
use function str_replace;
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

        $buffer = '';

        /**
         * Read in the file via Import::getNextChunk so that
         * it can process compressed files
         */
        /** @infection-ignore-all */
        while (! ImportSettings::$finished && ! $GLOBALS['error'] && ! ImportSettings::$timeoutPassed) {
            $data = $this->import->getNextChunk($importHandle);
            if ($data === false) {
                /* subtract data we didn't handle yet and stop processing */
                ImportSettings::$offset -= strlen($buffer);
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
            ImportSettings::$finished = false;

            return [];
        }

        /**
         * Analyze the data in each table
         */
        $namespaces = $xml->getNamespaces(true);

        /**
         * Get the database name, collation and charset
         */
        $dbAttr = $xml->children($namespaces['pma'] ?? null)->structure_schemas->database;

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
            $dbAttr = $xml->children()->attributes();
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
            ImportSettings::$finished = false;

            return [];
        }

        /**
         * CREATE code included (by default: no)
         */
        $structPresent = false;

        /**
         * Retrieve the structure information
         */
        $create = [];
        if (isset($namespaces['pma'])) {
            /**
             * Get structures for all tables
             *
             * @var SimpleXMLElement $struct
             */
            $struct = $xml->children($namespaces['pma']);

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
        $databaseXml = $xml->database;

        $analyses = null;
        /** @var ImportTable[] $tables */
        $tables = [];

        $databaseName = (string) $databaseXml['name'];

        /** @var SimpleXMLElement $tableRowXml */
        foreach ($databaseXml->table as $tableRowXml) {
            $tableName = (string) $tableRowXml['name'];

            $table = $tables[$tableName] ?? new ImportTable($tableName);

            $tableRow = [];
            /** @var SimpleXMLElement $tableCellXml */
            foreach ($tableRowXml->column as $tableCellXml) {
                /** @psalm-suppress PossiblyNullArrayAccess */
                $columnName = (string) $tableCellXml['name'];
                if (! in_array($columnName, $table->columns, true)) {
                    $table->columns[] = $columnName;
                }

                $tableRow[] = (string) $tableCellXml;
            }

            $table->rows[] = $tableRow;

            $tables[$tableName] = $table;
        }

        $tables = array_values($tables);

        foreach ($tables as $table) {
            $table->tableName = $this->import->getNextAvailableTableName($databaseName, $table->tableName);
        }

        if (! $structPresent) {
            $analyses = array_map($this->import->analyzeTable(...), $tables);
        }

        unset($xml);

        /**
         * Only build SQL from data if there is data present.
         * Set values to NULL if they were not present
         * to maintain Import::buildSql() call integrity
         */
        if ($tables !== [] && $analyses === null) {
            $create = null;
        }

        /* Created and execute necessary SQL statements from data */
        $sqlStatements = [];

        /* Set database name to the currently selected one, if applicable */
        if (Current::$database !== '') {
            /* Override the database name in the XML file, if one is selected */
            $dbName = Current::$database;
        } else {
            $sqlStatements = $this->import->createDatabase(
                $dbName,
                $charset ?? 'utf8',
                $collation ?? 'utf8_general_ci',
                [],
            );
        }

        $this->import->buildSql($dbName, $tables, $analyses, $create, $sqlStatements);

        /* Commit any possible data in buffers */
        $this->import->runQuery('', $sqlStatements);

        return $sqlStatements;
    }
}
