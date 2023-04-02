<?php
/**
 * OpenDocument Spreadsheet import plugin for phpMyAdmin
 *
 * @todo       Pretty much everything
 * @todo       Importing of accented characters seems to fail
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Import;

use PhpMyAdmin\File;
use PhpMyAdmin\Import;
use PhpMyAdmin\Message;
use PhpMyAdmin\Plugins\ImportPlugin;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Plugins\ImportPluginProperties;
use SimpleXMLElement;

use function __;
use function count;
use function implode;
use function rtrim;
use function simplexml_load_string;
use function strcmp;
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
        $GLOBALS['timeout_passed'] ??= null;
        $GLOBALS['finished'] ??= null;

        $sqlStatements = [];
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

        if ($xml === false) {
            $sheets = [];
            $GLOBALS['message'] = Message::error(__(
                'The XML file specified was either malformed or incomplete. Please correct the issue and try again.',
            ));
            $GLOBALS['error'] = true;
        } else {
            /** @var SimpleXMLElement $root */
            $root = $xml->children('office', true)->{'body'}->{'spreadsheet'};
            if (empty($root)) {
                $sheets = [];
                $GLOBALS['message'] = Message::error(
                    __('Could not parse OpenDocument Spreadsheet!'),
                );
                $GLOBALS['error'] = true;
            } else {
                $sheets = $root->children('table', true);
            }
        }

        [$tables, $rows] = $this->iterateOverTables($sheets);

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

                $tables[$i][Import::ROWS] = $rows[$j][Import::ROWS];
            }
        }

        /* No longer needed */
        unset($rows);

        /* Obtain the best-fit MySQL types for each column */
        $analyses = [];

        $len = count($tables);
        for ($i = 0; $i < $len; ++$i) {
            $analyses[] = $this->import->analyzeTable($tables[$i]);
        }

        /* Set database name to the currently selected one, if applicable */
        $dbName = $GLOBALS['db'] !== '' ? $GLOBALS['db'] : 'ODS_DB';
        $createDb = $GLOBALS['db'] === '';

        /* Created and execute necessary SQL statements from data */
        $this->import->buildSql($dbName, $tables, $analyses, createDb:$createDb, sqlData:$sqlStatements);

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
            && ! strcmp('percentage', (string) $cellAttrs['value-type'])
        ) {
            return (float) $cellAttrs['value'];
        }

        if (
            isset($_REQUEST['ods_recognize_currency'])
            && $_REQUEST['ods_recognize_currency']
            && ! strcmp('currency', (string) $cellAttrs['value-type'])
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

    /**
     * @param mixed[] $tempRow
     * @param mixed[] $colNames
     *
     * @return mixed[]
     */
    private function iterateOverColumns(
        SimpleXMLElement $row,
        bool $colNamesInFirstRow,
        array $tempRow,
        array $colNames,
        int $colCount,
    ): array {
        $cellCount = $row->count();
        $a = 0;
        foreach ($row as $cell) {
            $a++;
            $text = $cell->children('text', true);
            $cellAttrs = $cell->attributes('office', true);

            if ($text->count() != 0) {
                $attr = $cell->attributes('table', true);
                $numRepeat = (int) $attr['number-columns-repeated'];
                $numIterations = $numRepeat ?: 1;

                for ($k = 0; $k < $numIterations; $k++) {
                    $value = $this->getValue($cellAttrs, $text);
                    if (! $colNamesInFirstRow) {
                        $tempRow[] = $value;
                    } else {
                        // MySQL column names can't end with a space
                        // character.
                        $colNames[] = rtrim((string) $value);
                    }

                    ++$colCount;
                }

                continue;
            }

            // skip empty repeats in the last row
            if ($a == $cellCount) {
                continue;
            }

            $attr = $cell->attributes('table', true);
            $numNull = (int) $attr['number-columns-repeated'];

            if ($numNull) {
                if (! $colNamesInFirstRow) {
                    for ($i = 0; $i < $numNull; ++$i) {
                        $tempRow[] = 'NULL';
                        ++$colCount;
                    }
                } else {
                    for ($i = 0; $i < $numNull; ++$i) {
                        $colNames[] = $this->import->getColumnAlphaName($colCount + 1);
                        ++$colCount;
                    }
                }
            } else {
                if (! $colNamesInFirstRow) {
                    $tempRow[] = 'NULL';
                } else {
                    $colNames[] = $this->import->getColumnAlphaName($colCount + 1);
                }

                ++$colCount;
            }
        }

        return [$tempRow, $colNames, $colCount];
    }

    /**
     * @param mixed[] $tempRow
     * @param mixed[] $colNames
     * @param mixed[] $tempRows
     *
     * @return mixed[]
     */
    private function iterateOverRows(
        SimpleXMLElement $sheet,
        bool $colNamesInFirstRow,
        array $tempRow,
        array $colNames,
        int $colCount,
        int $maxCols,
        array $tempRows,
    ): array {
        foreach ($sheet as $row) {
            $type = $row->getName();
            if (strcmp('table-row', $type)) {
                continue;
            }

            [$tempRow, $colNames, $colCount] = $this->iterateOverColumns(
                $row,
                $colNamesInFirstRow,
                $tempRow,
                $colNames,
                $colCount,
            );

            /* Find the widest row */
            if ($colCount > $maxCols) {
                $maxCols = $colCount;
            }

            /* Don't include a row that is full of NULL values */
            if (! $colNamesInFirstRow) {
                if ($_REQUEST['ods_empty_rows'] ?? false) {
                    foreach ($tempRow as $cell) {
                        if (strcmp('NULL', (string) $cell)) {
                            $tempRows[] = $tempRow;
                            break;
                        }
                    }
                } else {
                    $tempRows[] = $tempRow;
                }
            }

            $colCount = 0;
            $colNamesInFirstRow = false;
            $tempRow = [];
        }

        return [$tempRow, $colNames, $maxCols, $tempRows];
    }

    /**
     * @param mixed[]|SimpleXMLElement $sheets Sheets of the spreadsheet.
     *
     * @return mixed[]|mixed[][]
     */
    private function iterateOverTables(array|SimpleXMLElement $sheets): array
    {
        $tables = [];
        $maxCols = 0;
        $colCount = 0;
        $colNames = [];
        $tempRow = [];
        $tempRows = [];
        $rows = [];

        /** @var SimpleXMLElement $sheet */
        foreach ($sheets as $sheet) {
            $colNamesInFirstRow = isset($_REQUEST['ods_col_names']);

            [$tempRow, $colNames, $maxCols, $tempRows] = $this->iterateOverRows(
                $sheet,
                $colNamesInFirstRow,
                $tempRow,
                $colNames,
                $colCount,
                $maxCols,
                $tempRows,
            );

            /* Skip over empty sheets */
            if (count($tempRows) == 0 || count($tempRows[0]) === 0) {
                $colNames = [];
                $tempRow = [];
                $tempRows = [];
                continue;
            }

            /**
             * Fill out each row as necessary to make
             * every one exactly as wide as the widest
             * row. This included column names.
             */

            /* Fill out column names */
            for ($i = count($colNames); $i < $maxCols; ++$i) {
                $colNames[] = $this->import->getColumnAlphaName($i + 1);
            }

            /* Fill out all rows */
            $numRows = count($tempRows);
            for ($i = 0; $i < $numRows; ++$i) {
                for ($j = count($tempRows[$i]); $j < $maxCols; ++$j) {
                    $tempRows[$i][] = 'NULL';
                }
            }

            /* Store the table name so we know where to place the row set */
            $tblAttr = $sheet->attributes('table', true);
            $tables[] = [(string) $tblAttr['name']];

            /* Store the current sheet in the accumulator */
            $rows[] = [(string) $tblAttr['name'], $colNames, $tempRows];
            $tempRows = [];
            $colNames = [];
            $maxCols = 0;
        }

        return [$tables, $rows];
    }
}
