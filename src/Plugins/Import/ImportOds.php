<?php
/**
 * OpenDocument Spreadsheet import plugin for phpMyAdmin
 *
 * @todo       Pretty much everything
 * @todo       Importing of accented characters seems to fail
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Import;

use PhpMyAdmin\Current;
use PhpMyAdmin\File;
use PhpMyAdmin\Import\ImportSettings;
use PhpMyAdmin\Import\ImportTable;
use PhpMyAdmin\Message;
use PhpMyAdmin\Plugins\ImportPlugin;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Plugins\ImportPluginProperties;
use SimpleXMLElement;

use function __;
use function array_map;
use function array_pad;
use function count;
use function implode;
use function max;
use function rtrim;
use function simplexml_load_string;
use function strlen;

use const LIBXML_COMPACT;

/**
 * Handles the import for the ODS format
 */
class ImportOds extends ImportPlugin
{
    /** @psalm-return non-empty-lowercase-string */
    public function getName(): string
    {
        return 'ods';
    }

    protected function setProperties(): ImportPluginProperties
    {
        $importPluginProperties = new ImportPluginProperties();
        $importPluginProperties->setText('OpenDocument Spreadsheet');
        $importPluginProperties->setExtension('ods');
        $importPluginProperties->setOptionsText(__('Options'));

        // create the root group that will be the options field for
        // $importPluginProperties
        // this will be shown as "Format specific options"
        $importSpecificOptions = new OptionsPropertyRootGroup('Format Specific Options');

        // general options main group
        $generalOptions = new OptionsPropertyMainGroup('general_opts');
        // create primary items and add them to the group
        $leaf = new BoolPropertyItem(
            'col_names',
            __(
                'The first line of the file contains the table column names'
                . ' <i>(if this is unchecked, the first line will become part'
                . ' of the data)</i>',
            ),
        );
        $generalOptions->addProperty($leaf);
        $leaf = new BoolPropertyItem(
            'empty_rows',
            __('Do not import empty rows'),
        );
        $generalOptions->addProperty($leaf);
        $leaf = new BoolPropertyItem(
            'recognize_percentages',
            __(
                'Import percentages as proper decimals <i>(ex. 12.00% to .12)</i>',
            ),
        );
        $generalOptions->addProperty($leaf);
        $leaf = new BoolPropertyItem(
            'recognize_currency',
            __('Import currencies <i>(ex. $5.00 to 5.00)</i>'),
        );
        $generalOptions->addProperty($leaf);

        // add the main group to the root group
        $importSpecificOptions->addProperty($generalOptions);

        // set the options for the import plugin property item
        $importPluginProperties->setOptions($importSpecificOptions);

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

        $sqlStatements = [];
        $buffer = '';

        /**
         * Read in the file via Import::getNextChunk so that
         * it can process compressed files
         */
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

        if ($xml === false) {
            $sheets = [];
            $GLOBALS['message'] = Message::error(__(
                'The XML file specified was either malformed or incomplete. Please correct the issue and try again.',
            ));
            $GLOBALS['error'] = true;
        } else {
            $root = $xml->children('office', true)->body->spreadsheet;
            if ($root === null) {
                $sheets = [];
                $GLOBALS['message'] = Message::error(
                    __('Could not parse OpenDocument Spreadsheet!'),
                );
                $GLOBALS['error'] = true;
            } else {
                $sheets = $root->children('table', true);
            }
        }

        $tables = $this->readSheets($sheets, isset($_REQUEST['ods_col_names']));

        /* Obtain the best-fit MySQL types for each column */
        $analyses = array_map($this->import->analyzeTable(...), $tables);

        /* Set database name to the currently selected one, if applicable */
        $dbName = Current::$database !== '' ? Current::$database : 'ODS_DB';
        $createDb = Current::$database === '';

        if ($createDb) {
            $sqlStatements = $this->import->createDatabase($dbName, 'utf8', 'utf8_general_ci', $sqlStatements);
        }

        /* Created and execute necessary SQL statements from data */
        $this->import->buildSql($dbName, $tables, $analyses, sqlData: $sqlStatements);

        /* Commit any possible data in buffers */
        $this->import->runQuery('', $sqlStatements);

        return $sqlStatements;
    }

    /**
     * Get value
     *
     * @param SimpleXMLElement $cellAttrs Cell attributes
     * @param SimpleXMLElement $text      Texts
     */
    protected function getValue(SimpleXMLElement $cellAttrs, SimpleXMLElement $text): float|string
    {
        if (
            isset($_REQUEST['ods_recognize_percentages'])
            && $_REQUEST['ods_recognize_percentages']
            && (string) $cellAttrs['value-type'] === 'percentage'
        ) {
            return (float) $cellAttrs['value'];
        }

        if (
            isset($_REQUEST['ods_recognize_currency'])
            && $_REQUEST['ods_recognize_currency']
            && (string) $cellAttrs['value-type'] === 'currency'
        ) {
            return (float) $cellAttrs['value'];
        }

        /* We need to concatenate all paragraphs */
        $values = [];
        foreach ($text as $paragraph) {
            // Maybe a text node has the content ? (email, url, ...)
            // Example: <text:a ... xlink:href="mailto:contact@example.org">test@example.fr</text:a>
            $paragraphValue = $paragraph->__toString();
            if ($paragraphValue === '' && isset($paragraph->{'a'})) {
                $values[] = $paragraph->{'a'}->__toString();
                continue;
            }

            $values[] = $paragraphValue;
        }

        return implode("\n", $values);
    }

    /** @return list<float|string> */
    private function readCells(SimpleXMLElement $row): array
    {
        $tempRow = [];
        $cellCount = $row->count();
        $a = 0;
        foreach ($row as $cell) {
            $a++;
            $text = $cell->children('text', true);
            $cellAttrs = $cell->attributes('office', true);

            if ($text->count() != 0) {
                $attr = $cell->attributes('table', true);
                $numRepeat = (int) $attr['number-columns-repeated'];
                $numIterations = $numRepeat !== 0 ? $numRepeat : 1;

                for ($k = 0; $k < $numIterations; $k++) {
                    $tempRow[] = $this->getValue($cellAttrs, $text);
                }

                continue;
            }

            // skip empty repeats in the last row
            if ($a == $cellCount) {
                continue;
            }

            $attr = $cell->attributes('table', true);
            $numNull = (int) $attr['number-columns-repeated'];

            if ($numNull !== 0) {
                for ($i = 0; $i < $numNull; ++$i) {
                    $tempRow[] = 'NULL';
                }
            } else {
                $tempRow[] = 'NULL';
            }
        }

        return $tempRow;
    }

    /** @return array{list<string>, int, list<list<float|string>>} */
    private function readRows(
        SimpleXMLElement $sheet,
        bool $colNamesInFirstRow,
        int $maxCols,
    ): array {
        $tempRows = [];
        $colNames = [];
        foreach ($sheet as $row) {
            $type = $row->getName();
            if ($type !== 'table-row') {
                continue;
            }

            if ($colNamesInFirstRow) {
                $colNamesInFirstRow = false;
                foreach ($this->readCells($row) as $columnIndex => $value) {
                    if ($value === 'NULL') {
                        $colNames[] = $this->import->getColumnAlphaName($columnIndex + 1);
                    } else {
                        // MySQL column names can't end with a space character.
                        $colNames[] = rtrim((string) $value);
                    }
                }

                $maxCols = max(count($colNames), $maxCols);
                continue;
            }

            $tempRow = $this->readCells($row);
            $maxCols = max(count($tempRow), $maxCols);

            /* Don't include a row that is full of NULL values */
            if ($_REQUEST['ods_empty_rows'] ?? false) {
                foreach ($tempRow as $cell) {
                    if ((string) $cell !== 'NULL') {
                        $tempRows[] = $tempRow;
                        break;
                    }
                }
            } else {
                $tempRows[] = $tempRow;
            }
        }

        return [$colNames, $maxCols, $tempRows];
    }

    /**
     * @param mixed[]|SimpleXMLElement $sheets Sheets of the spreadsheet.
     *
     * @return ImportTable[]
     */
    private function readSheets(array|SimpleXMLElement $sheets, bool $colNamesInFirstRow): array
    {
        $maxCols = 0;
        $rows = [];

        /** @var SimpleXMLElement $sheet */
        foreach ($sheets as $sheet) {
            [$colNames, $maxCols, $tempRows] = $this->readRows($sheet, $colNamesInFirstRow, $maxCols);

            /* Skip over empty sheets */
            if ($tempRows === [] || $tempRows[0] === []) {
                continue;
            }

            /**
             * Fill out each row as necessary to make
             * every one exactly as wide as the widest
             * row. This included column names.
             */

            /* Fill out column names */
            /** @infection-ignore-all */
            for ($i = count($colNames); $i < $maxCols; ++$i) {
                $colNames[] = $this->import->getColumnAlphaName($i + 1);
            }

            /* Fill out all rows */
            foreach ($tempRows as $i => $row) {
                $tempRows[$i] = array_pad($row, $maxCols, 'NULL');
            }

            /* Store the table name so we know where to place the row set */
            $tblAttr = $sheet->attributes('table', true);

            /* Store the current sheet in the accumulator */
            $rows[] = new ImportTable((string) $tblAttr['name'], $colNames, $tempRows);
            $maxCols = 0;
        }

        return $rows;
    }
}
