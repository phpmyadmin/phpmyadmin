<?php
/**
 * MediaWiki import plugin for phpMyAdmin
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Import;

use PhpMyAdmin\File;
use PhpMyAdmin\Message;
use PhpMyAdmin\Plugins\ImportPlugin;
use PhpMyAdmin\Properties\Plugins\ImportPluginProperties;

use function __;
use function count;
use function explode;
use function mb_strlen;
use function mb_strpos;
use function mb_substr;
use function preg_match;
use function str_contains;
use function str_replace;
use function strcmp;
use function strlen;
use function trim;

/**
 * Handles the import for the MediaWiki format
 */
class ImportMediawiki extends ImportPlugin
{
    /**
     * Whether to analyze tables
     */
    private bool $analyze = false;

    /** @psalm-return non-empty-lowercase-string */
    public function getName(): string
    {
        return 'mediawiki';
    }

    protected function setProperties(): ImportPluginProperties
    {
        $this->setAnalyze(false);
        if ($GLOBALS['plugin_param'] !== 'table') {
            $this->setAnalyze(true);
        }

        $importPluginProperties = new ImportPluginProperties();
        $importPluginProperties->setText(__('MediaWiki Table'));
        $importPluginProperties->setExtension('txt');
        $importPluginProperties->setMimeType('text/plain');
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

        $sqlStatements = [];

        // Defaults for parser

        // The buffer that will be used to store chunks read from the imported file
        $buffer = '';

        // Used as storage for the last part of the current chunk data
        // Will be appended to the first line of the next chunk, if there is one
        $lastChunkLine = '';

        // Remembers whether the current buffer line is part of a comment
        $insideComment = false;
        // Remembers whether the current buffer line is part of a data comment
        $insideDataComment = false;
        // Remembers whether the current buffer line is part of a structure comment
        $insideStructureComment = false;

        // MediaWiki only accepts "\n" as row terminator
        $mediawikiNewLine = "\n";

        // Initialize the name of the current table
        $curTableName = '';

        $curTempTableHeaders = [];
        $curTempTable = [];

        $inTableHeader = false;

        while (! $GLOBALS['finished'] && ! $GLOBALS['error'] && ! $GLOBALS['timeout_passed']) {
            $data = $this->import->getNextChunk($importHandle);

            if ($data === false) {
                // Subtract data we didn't handle yet and stop processing
                $GLOBALS['offset'] -= mb_strlen($buffer);
                break;
            }

            if ($data !== true) {
                // Append new data to buffer
                $buffer = $data;
                unset($data);
                // Don't parse string if we're not at the end
                // and don't have a new line inside
                if (! str_contains($buffer, $mediawikiNewLine)) {
                    continue;
                }
            }

            // Because of reading chunk by chunk, the first line from the buffer
            // contains only a portion of an actual line from the imported file.
            // Therefore, we have to append it to the last line from the previous
            // chunk. If we are at the first chunk, $last_chunk_line should be empty.
            $buffer = $lastChunkLine . $buffer;

            // Process the buffer line by line
            $bufferLines = explode($mediawikiNewLine, $buffer);

            $fullBufferLinesCount = count($bufferLines);
            // If the reading is not finalized, the final line of the current chunk
            // will not be complete
            if (! $GLOBALS['finished']) {
                $lastChunkLine = $bufferLines[--$fullBufferLinesCount];
            }

            $curTempLine = [];
            for ($lineNr = 0; $lineNr < $fullBufferLinesCount; ++$lineNr) {
                $curBufferLine = trim($bufferLines[$lineNr]);

                // If the line is empty, go to the next one
                if ($curBufferLine === '') {
                    continue;
                }

                $firstCharacter = $curBufferLine[0];
                $matches = [];

                // Check beginning of comment
                if (! strcmp(mb_substr($curBufferLine, 0, 4), '<!--')) {
                    $insideComment = true;
                    continue;
                }

                if ($insideComment) {
                    // Check end of comment
                    if (! strcmp(mb_substr($curBufferLine, 0, 4), '-->')) {
                        // Only data comments are closed. The structure comments
                        // will be closed when a data comment begins (in order to
                        // skip structure tables)
                        if ($insideDataComment) {
                            $insideDataComment = false;
                        }

                        // End comments that are not related to table structure
                        if (! $insideStructureComment) {
                            $insideComment = false;
                        }
                    } else {
                        // Check table name
                        $matchTableName = [];
                        if (preg_match('/^Table data for `(.*)`$/', $curBufferLine, $matchTableName)) {
                            $curTableName = $matchTableName[1];
                            $insideDataComment = true;

                            $insideStructureComment = false;
                        } elseif (preg_match('/^Table structure for `(.*)`$/', $curBufferLine, $matchTableName)) {
                            // The structure comments will be ignored
                            $insideStructureComment = true;
                        }
                    }

                    continue;
                }

                if (preg_match('/^\{\|(.*)$/', $curBufferLine, $matches)) {
                    // Check start of table

                    // This will store all the column info on all rows from
                    // the current table read from the buffer
                    $curTempTable = [];

                    // Will be used as storage for the current row in the buffer
                    // Once all its columns are read, it will be added to
                    // $cur_temp_table and then it will be emptied
                    $curTempLine = [];

                    // Helps us differentiate the header columns
                    // from the normal columns
                    $inTableHeader = false;
                    // End processing because the current line does not
                    // contain any column information
                } elseif (
                    mb_substr($curBufferLine, 0, 2) === '|-'
                    || mb_substr($curBufferLine, 0, 2) === '|+'
                    || mb_substr($curBufferLine, 0, 2) === '|}'
                ) {
                    // Check begin row or end table

                    // Add current line to the values storage
                    if ($curTempLine !== []) {
                        // If the current line contains header cells
                        // ( marked with '!' ),
                        // it will be marked as table header
                        if ($inTableHeader) {
                            // Set the header columns
                            $curTempTableHeaders = $curTempLine;
                        } else {
                            // Normal line, add it to the table
                            $curTempTable[] = $curTempLine;
                        }
                    }

                    // Empty the temporary buffer
                    $curTempLine = [];

                    // No more processing required at the end of the table
                    if (mb_substr($curBufferLine, 0, 2) === '|}') {
                        $currentTable = [$curTableName, $curTempTableHeaders, $curTempTable];

                        // Import the current table data into the database
                        $this->importDataOneTable($currentTable, $sqlStatements);

                        // Reset table name
                        $curTableName = '';
                    }
                    // What's after the row tag is now only attributes
                } elseif (($firstCharacter === '|') || ($firstCharacter === '!')) {
                    // Check cell elements

                    // Header cells
                    if ($firstCharacter === '!') {
                        // Mark as table header, but treat as normal row
                        $curBufferLine = str_replace('!!', '||', $curBufferLine);
                        // Will be used to set $cur_temp_line as table header
                        $inTableHeader = true;
                    } else {
                        $inTableHeader = false;
                    }

                    // Loop through each table cell
                    $cells = $this->explodeMarkup($curBufferLine);
                    foreach ($cells as $cell) {
                        $cell = $this->getCellData($cell);

                        // Delete the beginning of the column, if there is one
                        $cell = trim($cell);
                        foreach (['|', '!'] as $colStartChar) {
                            $cell = $this->getCellContent($cell, $colStartChar);
                        }

                        // Add the cell to the row
                        $curTempLine[] = $cell;
                    }
                } else {
                    // If it's none of the above, then the current line has a bad
                    // format
                    $message = Message::error(
                        __('Invalid format of mediawiki input on line: <br>%s.'),
                    );
                    $message->addParam($curBufferLine);
                    $GLOBALS['error'] = true;
                }
            }
        }

        return $sqlStatements;
    }

    /**
     * Imports data from a single table
     *
     * @param mixed[]  $table         containing all table info:
     *                              <code> $table[0] - string
     *                              containing table name
     *                              $table[1] - array[]   of
     *                              table headers $table[2] -
     *                              array[][] of table content
     *                              rows </code>
     * @param string[] $sqlStatements List of SQL statements to be executed
     *
     * @global bool $analyze whether to scan for column types
     */
    private function importDataOneTable(array $table, array &$sqlStatements): void
    {
        $analyze = $this->getAnalyze();
        if ($analyze) {
            // Set the table name
            $this->setTableName($table[0]);

            // Set generic names for table headers if they don't exist
            $this->setTableHeaders($table[1], $table[2][0]);

            // Create the tables array to be used in Import::buildSql()
            $tables = [];
            $tables[] = [$table[0], $table[1], $table[2]];

            // Obtain the best-fit MySQL types for each column
            $analyses = [];
            $analyses[] = $this->import->analyzeTable($tables[0]);

            $this->executeImportTables($tables, $analyses, $sqlStatements);
        }

        // Commit any possible data in buffers
        $this->import->runQuery('', $sqlStatements);
    }

    /**
     * Sets the table name
     *
     * @param string $tableName reference to the name of the table
     */
    private function setTableName(string &$tableName): void
    {
        if (! empty($tableName)) {
            return;
        }

        $result = $GLOBALS['dbi']->fetchResult('SHOW TABLES');
        // todo check if the name below already exists
        $tableName = 'TABLE ' . (count($result) + 1);
    }

    /**
     * Set generic names for table headers, if they don't exist
     *
     * @param mixed[] $tableHeaders reference to the array containing the headers
     *                             of a table
     * @param mixed[] $tableRow     array containing the first content row
     */
    private function setTableHeaders(array &$tableHeaders, array $tableRow): void
    {
        if ($tableHeaders !== []) {
            return;
        }

        // The first table row should contain the number of columns
        // If they are not set, generic names will be given (COL 1, COL 2, etc)
        $numCols = count($tableRow);
        for ($i = 0; $i < $numCols; ++$i) {
            $tableHeaders[$i] = 'COL ' . ($i + 1);
        }
    }

    /**
     * Sets the database name and additional options and calls Import::buildSql()
     * Used in PMA_importDataAllTables() and $this->importDataOneTable()
     *
     * @param mixed[]  $tables        structure:
     *                              array(
     *                              array(table_name, array() column_names, array()()
     *                              rows)
     *                              )
     * @param mixed[]  $analyses      structure:
     *                              $analyses = array(
     *                              array(array() column_types, array() column_sizes)
     *                              )
     * @param string[] $sqlStatements List of SQL statements to be executed
     *
     * @global string $db      name of the database to import in
     */
    private function executeImportTables(array &$tables, array $analyses, array &$sqlStatements): void
    {
        $dbName = $GLOBALS['db'] !== '' ? $GLOBALS['db'] : 'mediawiki_DB';
        $createDb = $GLOBALS['db'] === '';

        // Create and execute necessary SQL statements from data
        $this->import->buildSql($dbName, $tables, $analyses, createDb:$createDb, sqlData:$sqlStatements);
    }

    /**
     * Replaces all instances of the '||' separator between delimiters
     * in a given string
     *
     * @param string $replace the string to be replaced with
     * @param string $subject the text to be replaced
     *
     * @return string with replacements
     */
    private function delimiterReplace(string $replace, string $subject): string
    {
        // String that will be returned
        $cleaned = '';
        // Possible states of current character
        $insideTag = false;
        $insideAttribute = false;
        // Attributes can be declared with either " or '
        $startAttributeCharacter = false;

        // The full separator is "||";
        // This remembers if the previous character was '|'
        $partialSeparator = false;

        // Parse text char by char
        for ($i = 0, $iMax = strlen($subject); $i < $iMax; $i++) {
            $curChar = $subject[$i];
            // Check for separators
            if ($curChar === '|') {
                // If we're not inside a tag, then this is part of a real separator,
                // so we append it to the current segment
                if (! $insideAttribute) {
                    $cleaned .= $curChar;
                    if ($partialSeparator) {
                        $insideTag = false;
                        $insideAttribute = false;
                    }
                } elseif ($partialSeparator) {
                    // If we are inside a tag, we replace the current char with
                    // the placeholder and append that to the current segment
                    $cleaned .= $replace;
                }

                // If the previous character was also '|', then this ends a
                // full separator. If not, this may be the beginning of one
                $partialSeparator = ! $partialSeparator;
            } else {
                // If we're inside a tag attribute and the current character is
                // not '|', but the previous one was, it means that the single '|'
                // was not appended, so we append it now
                if ($partialSeparator && $insideAttribute) {
                    $cleaned .= '|';
                }

                // If the char is different from "|", no separator can be formed
                $partialSeparator = false;

                // any other character should be appended to the current segment
                $cleaned .= $curChar;

                if ($curChar === '<' && ! $insideAttribute) {
                    // start of a tag
                    $insideTag = true;
                } elseif ($curChar === '>' && ! $insideAttribute) {
                    // end of a tag
                    $insideTag = false;
                } elseif (($curChar === '"' || $curChar == "'") && $insideTag) {
                    // start or end of an attribute
                    if (! $insideAttribute) {
                        $insideAttribute = true;
                        // remember the attribute`s declaration character (" or ')
                        $startAttributeCharacter = $curChar;
                    } elseif ($curChar == $startAttributeCharacter) {
                        $insideAttribute = false;
                        // unset attribute declaration character
                        $startAttributeCharacter = false;
                    }
                }
            }
        }

        return $cleaned;
    }

    /**
     * Separates a string into items, similarly to explode
     * Uses the '||' separator (which is standard in the mediawiki format)
     * and ignores any instances of it inside markup tags
     * Used in parsing buffer lines containing data cells
     *
     * @param string $text text to be split
     *
     * @return mixed[]
     */
    private function explodeMarkup(string $text): array
    {
        $separator = '||';
        $placeholder = "\x00";

        // Remove placeholder instances
        $text = str_replace($placeholder, '', $text);

        // Replace instances of the separator inside HTML-like
        // tags with the placeholder
        $cleaned = $this->delimiterReplace($placeholder, $text);
        // Explode, then put the replaced separators back in
        $items = explode($separator, $cleaned);
        foreach ($items as $i => $str) {
            $items[$i] = str_replace($placeholder, $separator, $str);
        }

        return $items;
    }

    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */

    /**
     * Returns true if the table should be analyzed, false otherwise
     */
    private function getAnalyze(): bool
    {
        return $this->analyze;
    }

    /**
     * Sets to true if the table should be analyzed, false otherwise
     *
     * @param bool $analyze status
     */
    private function setAnalyze(bool $analyze): void
    {
        $this->analyze = $analyze;
    }

    /**
     * Get cell
     *
     * @param string $cell Cell
     */
    private function getCellData(string $cell): string
    {
        // A cell could contain both parameters and data
        $cellData = explode('|', $cell, 2);

        // A '|' inside an invalid link should not
        // be mistaken as delimiting cell parameters
        if (! str_contains($cellData[0], '[[')) {
            return $cell;
        }

        if (count($cellData) === 1) {
            return $cellData[0];
        }

        return $cellData[1];
    }

    /**
     * Get cell content
     *
     * @param string $cell         Cell
     * @param string $colStartChar Start char
     */
    private function getCellContent(string $cell, string $colStartChar): string
    {
        if (mb_strpos($cell, $colStartChar) === 0) {
            return trim(mb_substr($cell, 1));
        }

        return $cell;
    }
}
