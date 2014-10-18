<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * SQL import plugin for phpMyAdmin
 *
 * @package    PhpMyAdmin-Import
 * @subpackage SQL
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* Get the import interface */
require_once 'libraries/plugins/ImportPlugin.class.php';

/**
 * Handles the import for the SQL format
 *
 * @package    PhpMyAdmin-Import
 * @subpackage SQL
 */
class ImportSql extends ImportPlugin
{
    const BIG_VALUE = 2147483647;
    const READ_MB_FALSE = 0;
    const READ_MB_TRUE = 1;

    private $_delimiter;

    private $_isInString = false;

    private $_quote = null;

    private $_isInComment = false;

    private $_openingComment = null;

    private $_delimiter_keyword = 'DELIMITER ';

    private $_readMb = self::READ_MB_FALSE;

    //@todo Move this part in string functions definition file.
    private $_stringFunctions = array(
        self::READ_MB_FALSE => array(
            'substr' => 'substr',
            'strlen' => 'strlen',
            'strpos' => 'strpos',
        ),
        self::READ_MB_TRUE => array(
            'substr' => 'mb_substr',
            'strlen' => 'mb_strlen',
            'strpos' => 'mb_strpos',
        ),
    );
    private $_stringFunctionsToUse = false;

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
        include_once "$props/options/items/SelectPropertyItem.class.php";
        include_once "$props/options/items/BoolPropertyItem.class.php";

        $importPluginProperties = new ImportPluginProperties();
        $importPluginProperties->setText('SQL');
        $importPluginProperties->setExtension('sql');
        $importPluginProperties->setOptionsText(__('Options'));

        $compats = $GLOBALS['dbi']->getCompatibilities();
        if (count($compats) > 0) {
            $values = array();
            foreach ($compats as $val) {
                $values[$val] = $val;
            }

            // create the root group that will be the options field for
            // $importPluginProperties
            // this will be shown as "Format specific options"
            $importSpecificOptions = new OptionsPropertyRootGroup();
            $importSpecificOptions->setName("Format Specific Options");

            // general options main group
            $generalOptions = new OptionsPropertyMainGroup();
            $generalOptions->setName("general_opts");
            // create primary items and add them to the group
            $leaf = new SelectPropertyItem();
            $leaf->setName("compatibility");
            $leaf->setText(__('SQL compatibility mode:'));
            $leaf->setValues($values);
            $leaf->setDoc(
                array(
                    'manual_MySQL_Database_Administration',
                    'Server_SQL_mode',
                )
            );
            $generalOptions->addProperty($leaf);
            $leaf = new BoolPropertyItem();
            $leaf->setName("no_auto_value_on_zero");
            $leaf->setText(
                __('Do not use <code>AUTO_INCREMENT</code> for zero values')
            );
            $leaf->setDoc(
                array(
                    'manual_MySQL_Database_Administration',
                    'Server_SQL_mode',
                    'sqlmode_no_auto_value_on_zero'
                )
            );
            $generalOptions->addProperty($leaf);

            $leaf = new BoolPropertyItem();
            $leaf->setName("read_as_multibytes");
            $leaf->setText(
                __('Read as multibytes')
            );
            $generalOptions->addProperty($leaf);

            // add the main group to the root group
            $importSpecificOptions->addProperty($generalOptions);
            // set the options for the import plugin property item
            $importPluginProperties->setOptions($importSpecificOptions);
        }

        $this->properties = $importPluginProperties;
    }

    /**
     * Return the position of first SQL delimiter or false if no SQL delimiter found.
     *
     * @param string $data current data to parse
     *
     * @return bool|int
     */
    private function _findDelimiter($data) {
        $lengthData = $this->_stringFunctionsToUse['strlen']($data);
        $posInData = 0;

        $firstSearchChar = null;
        $firstSqlDelimiter = null;

        /* while not at end of line */
        while ($posInData <= $lengthData) {
            if ($this->_isInString) {
                //Search for closing quote
                $posClosingString = $this->_stringFunctionsToUse['strpos'](
                    $data, $this->_quote, $posInData
                );

                if (false === $posClosingString) {
                    return false;
                }

                $posEscape = $posClosingString-1;
                while ($this->_stringFunctionsToUse['substr']($data, $posEscape, 1) == '\\') {
                    $posEscape--;
                }

                // Odd count means it was escaped
                $quoteEscaped = (((($posClosingString - 1) - $posEscape) % 2) === 1);
                if ($quoteEscaped) {
                    //Move after the escaped string.
                    $posInData = $posClosingString + 1;
                    continue;
                }

                $posInData = $posClosingString + 1;
                $this->_isInString = false;
                $this->_quote = null;
                continue;
            }

            if ($this->_isInComment) {
                if (in_array($this->_openingComment, array('#', '-- '))) {
                    $posClosingComment = $this->_stringFunctionsToUse['strpos']($data, "\n", $posInData);
                    if (false === $posClosingComment) {
                        return false;
                    }
                    //Move after the end of the line.
                    $posInData = $posClosingComment + 1;
                    $this->_isInComment = false;
                    $this->_openingComment = null;
                } elseif ('/*' === $this->_openingComment) {
                    //Search for closing comment
                    $posClosingComment = $this->_stringFunctionsToUse['strpos']($data, '*/', $posInData);
                    if (false === $posClosingComment) {
                        return false;
                    }
                    //Move after closing comment.
                    $posInData = $posClosingComment + 2;
                    $this->_isInComment = false;
                    $this->_openingComment = null;
                } else {
                    die('WHAT ?');
                }
                continue;
            }

            //Don't look for a string/comment/"DELIMITER" if not found previously
            //or if it's still after current position.
            if (null === $firstSearchChar
                || (false !== $firstSearchChar && $firstSearchChar < $posInData)
            ) {
                $bFind = preg_match(
                    '/(\'|"|#|-- |\/\*|`|(?i)(?<![A-Z0-9_])'
                    . $this->_delimiter_keyword . ')/',
                    $this->_stringFunctionsToUse['substr']($data, $posInData),
                    $matches,
                    PREG_OFFSET_CAPTURE
                );

                if (1 === $bFind) {
                    $firstSearchChar = $matches[1][1] + $posInData;
                } else {
                    $firstSearchChar = false;
                }
            }

            //Don't look for the SQL delimiter if not found previously
            //or if it's still after current position.
            if (null === $firstSqlDelimiter
                || (false !== $firstSqlDelimiter && $firstSqlDelimiter < $posInData)
            ) {
                // the cost of doing this one with preg_match() would be too high
                $firstSqlDelimiter = $this->_stringFunctionsToUse['strpos'](
                    $data,
                    $this->_delimiter,
                    $posInData
                );
            }

            if (false === $firstSqlDelimiter && false === $firstSearchChar) {
                return false;
            }

            //If first char is delimiter.
            if (false === $firstSearchChar || $firstSqlDelimiter < $firstSearchChar
            ) {
                return $firstSqlDelimiter;
            }

            //Else first char is result of preg_match.

            //If string is opened.
            if (in_array($matches[1][0], array('\'', '"', '`'))) {
                $this->_isInString = true;
                $this->_quote = $matches[1][0];
                //Move after quote.
                $posInData = $firstSearchChar + 1;
                continue;
            }

            //If comment is opened.
            if (in_array($matches[1][0], array('#', '-- ', '/*'))) {
                $this->_isInComment = true;
                $this->_openingComment = $matches[1][0];
                //Move after comment opening.
                $posInData = $firstSearchChar
                    + $this->_stringFunctionsToUse['strlen']($matches[1][0]);
                continue;
            }
        }
        //var_dump('too many loops');
        return false;
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
        global $error, $timeout_passed;

        if (isset($_POST['sql_delimiter'])) {
            $this->_delimiter = $_POST['sql_delimiter'];
        } else {
            $this->_delimiter = ';';
        }

        // Handle compatibility options
        $sql_modes = array();
        if (isset($_REQUEST['sql_compatibility'])
            && 'NONE' != $_REQUEST['sql_compatibility']
        ) {
            $sql_modes[] = $_REQUEST['sql_compatibility'];
        }
        if (isset($_REQUEST['sql_no_auto_value_on_zero'])) {
            $sql_modes[] = 'NO_AUTO_VALUE_ON_ZERO';
        }
        if (count($sql_modes) > 0) {
            $GLOBALS['dbi']->tryQuery(
                'SET SQL_MODE="' . implode(',', $sql_modes) . '"'
            );
        }
        unset($sql_modes);

        //Manage multibytes or not
        if (isset($_REQUEST['sql_read_as_multibytes'])) {
            $this->_readMb = self::READ_MB_TRUE;
        }
        $this->_stringFunctionsToUse = $this->_stringFunctions[$this->_readMb];

        /**
         * will be set in PMA_importGetNextChunk()
         *
         * @global boolean $GLOBALS['finished']
         */
        $GLOBALS['finished'] = false;
        $positionDelimiter = false;
        $query = null;
        $data = null;
        $newData = null;

        while (!$timeout_passed) {
            if (false === $positionDelimiter) {
                $newData = PMA_importGetNextChunk(200);
                if ($newData === false) {
                    // subtract data we didn't handle yet and stop processing
                    $GLOBALS['offset']
                        -= $this->_stringFunctionsToUse['strlen']($query);
                    break;
                }

                if ($newData === true) {
                    $GLOBALS['finished'] = true;
                    break;
                }

                // Convert CR (but not CRLF) to LF otherwise all queries
                // may not get executed on some platforms
                $data .= preg_replace("/\r($|[^\n])/", "\n$1", $newData);
                unset($newData);
            }

            //Find quotes, comments, DELIMITER or delimiter.
            $positionDelimiter = $this->_findDelimiter($data);

            //No delimiter found.
            if (false === $positionDelimiter) {
                continue;
            }

            $query = $this->_stringFunctionsToUse['substr'](
                $data, 0, $positionDelimiter + 1
            );
            $data = $this->_stringFunctionsToUse['substr'](
                $data, $positionDelimiter + 1
            );
            PMA_importRunQuery(
                $query,
                null, //Set query to display
                false,
                $sql_data
            );

            //After execution, $buffer can be empty.
            $query = null;
        }

        if ($timeout_passed) {
            die('timeout');
        }

        if (!$GLOBALS['finished']) {
            die('issue 1');
        }

        if (true === $this->_isInComment || true === $this->_isInString) {
            die('issue 2');
        }

        return;

        while (! ($GLOBALS['finished'] && $posInQueryString >= $len)
            && ! $error
            && ! $timeout_passed
        ) {
            $data = PMA_importGetNextChunk();
            if ($data === false) {
                // subtract data we didn't handle yet and stop processing
                $GLOBALS['offset'] -= /*overload*/mb_strlen($query);
                break;
            } elseif ($data === true) {
                // Handle rest of buffer
            } else {
                // Append new data to buffer
                $query .= $data;
                // free memory
                unset($data);
                // Do not parse string when we're not at the end
                // and don't have ; inside
                if (/*overload*/mb_strpos($query, $sql_delimiter, $posInQueryString) === false
                    && ! $GLOBALS['finished']
                ) {
                    continue;
                }
            }

            // Convert CR (but not CRLF) to LF otherwise all queries
            // may not get executed on some platforms
            $query = preg_replace("/\r($|[^\n])/", "\n$1", $query);

            // Current length of our buffer
            $len = /*overload*/mb_strlen($query);

            // Grab some SQL queries out of it
            while ($posInQueryString < $len) {
                $found_delimiter = false;
                // Find first interesting character
                $old_i = $posInQueryString;
                // this is about 7 times faster that looking for each sequence i
                // one by one with strpos()
                $posPattern = /*overload*/mb_preg_strpos(
                    '/(\'|"|#|-- |\/\*|`|(?i)(?<![A-Z0-9_])'
                    . $delimiter_keyword . ')/',
                    $query,
                    $posInQueryString
                );
                if (false !== $posPattern) {
                    // in $matches, index 0 contains the match for the complete
                    // expression but we don't use it
                    $first_position = $posPattern;
                } else {
                    $first_position = $big_value;
                }

                // the cost of doing this one with preg_match() would be too high
                $first_sql_delimiter = /*overload*/mb_strpos(
                    $query,
                    $sql_delimiter,
                    $posInQueryString
                );
                if ($first_sql_delimiter === false) {
                    $first_sql_delimiter = $big_value;
                } else {
                    $found_delimiter = true;
                }

                // set $i to the position of the first quote,
                // comment.start or delimiter found
                $posInQueryString = min($first_position, $first_sql_delimiter);

                if ($posInQueryString == $big_value) {
                    // none of the above was found in the string

                    $posInQueryString = $old_i;
                    if (! $GLOBALS['finished']) {
                        break;
                    }
                    // at the end there might be some whitespace...
                    if (trim($query) == '') {
                        $query = '';
                        $len = 0;
                        break;
                    }
                    // We hit end of query, go there!
                    $posInQueryString = /*overload*/mb_strlen($query) - 1;
                }

                // Grab current character
                //$ch = $buffer[$i]; //Don't use this syntax, because of UTF8 strings
                $ch = /*overload*/mb_substr($query, $posInQueryString, 1);

                // Quotes
                if (strpos('\'"`', $ch) !== false) {
                    $quote = $ch;
                    $endq = false;
                    while (! $endq) {
                        // Find next quote
                        $posQuote = /*overload*/mb_strpos($query, $quote, $posInQueryString + 1);
                        /*
                         * Behave same as MySQL and accept end of query as end
                         * of backtick.
                         * I know this is sick, but MySQL behaves like this:
                         *
                         * SELECT * FROM `table
                         *
                         * is treated like
                         *
                         * SELECT * FROM `table`
                         */
                        if ($posQuote === false && $quote == '`'
                            && $found_delimiter
                        ) {
                            $posQuote = $first_sql_delimiter - 1;
                        } elseif ($posQuote === false) {// No quote? Too short string
                            // We hit end of string => unclosed quote,
                            // but we handle it as end of query
                            list($endq, $posInQueryString) = $this
                                ->getEndQuoteAndPos($len, $endq, $posInQueryString);
                            $found_delimiter = false;
                            break;
                        }
                        // Was not the quote escaped?
                        $posEscape = $posQuote - 1;
                        while (/*overload*/mb_substr($query, $posEscape, 1) == '\\') {
                            $posEscape--;
                        }
                        // Even count means it was not escaped
                        $endq = (((($posQuote - 1) - $posEscape) % 2) == 0);
                        // Skip the string
                        $posInQueryString = $posQuote;

                        if ($first_sql_delimiter < $posQuote) {
                            $found_delimiter = false;
                        }
                    }
                    if (! $endq) {
                        break;
                    }
                    $posInQueryString++;
                    // Aren't we at the end?
                    if ($GLOBALS['finished'] && $posInQueryString == $len) {
                        $posInQueryString--;
                    } else {
                        continue;
                    }
                }

                // Not enough data to decide
                if ((($posInQueryString == ($len - 1) && ($ch == '-' || $ch == '/'))
                    || ($posInQueryString == ($len - 2) && (($ch == '-'
                    && /*overload*/mb_substr($query, $posInQueryString + 1, 1) == '-')
                    || ($ch == '/'
                    && /*overload*/mb_substr($query, $posInQueryString + 1, 1) == '*'))))
                    && ! $GLOBALS['finished']
                ) {
                    break;
                }

                // Comments
                if ($ch == '#'
                    || ($posInQueryString < ($len - 1) && $ch == '-'
                    && /*overload*/mb_substr($query, $posInQueryString + 1, 1) == '-'
                    && (($posInQueryString < ($len - 2)
                    && /*overload*/mb_substr($query, $posInQueryString + 2, 1) <= ' ')
                    || ($posInQueryString == ($len - 1) && $GLOBALS['finished'])))
                    || ($posInQueryString < ($len - 1) && $ch == '/'
                    && /*overload*/mb_substr($query, $posInQueryString + 1, 1) == '*')
                ) {
                    // Copy current string to SQL
                    if ($start_pos != $posInQueryString) {
                        $sql .= /*overload*/mb_substr(
                            $query,
                            $start_pos,
                            $posInQueryString - $start_pos
                        );
                    }
                    // Skip the rest
                    $start_of_comment = $posInQueryString;
                    // do not use PHP_EOL here instead of "\n", because the export
                    // file might have been produced on a different system
                    $posInQueryString = /*overload*/mb_strpos(
                        $query,
                        $ch == '/' ? '*/' : "\n",
                        $posInQueryString
                    );
                    // didn't we hit end of string?
                    if ($posInQueryString === false) {
                        if ($GLOBALS['finished']) {
                            $posInQueryString = $len - 1;
                        } else {
                            break;
                        }
                    }
                    // Skip *
                    if ($ch == '/') {
                        $posInQueryString++;
                    }
                    // Skip last char
                    $posInQueryString++;
                    // We need to send the comment part in case we are defining
                    // a procedure or function and comments in it are valuable
                    $sql .= /*overload*/mb_substr(
                        $query,
                        $start_of_comment,
                        $posInQueryString - $start_of_comment
                    );
                    // Next query part will start here
                    $start_pos = $posInQueryString;
                    // Aren't we at the end?
                    if ($posInQueryString == $len) {
                        $posInQueryString--;
                    } else {
                        continue;
                    }
                }
                // Change delimiter, if redefined, and skip it
                // (don't send to server!)
                if (($posInQueryString + $length_of_delimiter_keyword < $len)
                    && /*overload*/mb_strtoupper(
                        /*overload*/mb_substr($query, $posInQueryString, $length_of_delimiter_keyword)
                    ) == $delimiter_keyword
                ) {
                     // look for EOL on the character immediately after 'DELIMITER '
                     // (see previous comment about PHP_EOL)
                    $new_line_pos = /*overload*/mb_strpos(
                        $query,
                        "\n",
                        $posInQueryString + $length_of_delimiter_keyword
                    );
                    // it might happen that there is no EOL
                    if (false === $new_line_pos) {
                        $new_line_pos = $len;
                    }
                    $sql_delimiter = /*overload*/mb_substr(
                        $query,
                        $posInQueryString + $length_of_delimiter_keyword,
                        $new_line_pos - $posInQueryString - $length_of_delimiter_keyword
                    );
                    $posInQueryString = $new_line_pos + 1;
                    // Next query part will start here
                    $start_pos = $posInQueryString;
                    continue;
                }

                // End of SQL
                if ($found_delimiter
                    || ($GLOBALS['finished']
                    && ($posInQueryString == $len - 1))
                ) {
                    $tmp_sql = $sql;
                    if ($start_pos < $len) {
                        $length_to_grab = $posInQueryString - $start_pos;

                        if (! $found_delimiter) {
                            $length_to_grab++;
                        }
                        $tmp_sql .= /*overload*/mb_substr(
                            $query,
                            $start_pos,
                            $length_to_grab
                        );
                        unset($length_to_grab);
                    }
                    // Do not try to execute empty SQL
                    if (! preg_match('/^([\s]*;)*$/', trim($tmp_sql))) {
                        $sql = $tmp_sql;
                        PMA_importRunQuery(
                            $sql,
                            /*overload*/mb_substr(
                                $query,
                                0,
                                $posInQueryString + /*overload*/mb_strlen($sql_delimiter)
                            ),
                            false,
                            $sql_data
                        );
                        $query = /*overload*/mb_substr(
                            $query,
                            $posInQueryString + /*overload*/mb_strlen($sql_delimiter)
                        );
                        // Reset parser:
                        $len = /*overload*/mb_strlen($query);
                        $sql = '';
                        $posInQueryString = 0;
                        $start_pos = 0;
                        // Any chance we will get a complete query?
                        //if ((strpos($buffer, ';') === false)
                        //&& ! $GLOBALS['finished']) {
                        if (/*overload*/mb_strpos($query, $sql_delimiter) === false
                            && ! $GLOBALS['finished']
                        ) {
                            break;
                        }
                    } else {
                        $posInQueryString++;
                        $start_pos = $posInQueryString;
                    }
                }
            } // End of parser loop
        } // End of import loop
        // Commit any possible data in buffers
        PMA_importRunQuery(
            '',
            /*overload*/mb_substr($query, 0, $len),
            false,
            $sql_data
        );
        PMA_importRunQuery('', '', false, $sql_data);
    }

    /**
     * Get end quote and position
     *
     * @param int  $len      Length
     * @param bool $endq     End quote
     * @param int  $position Position
     *
     * @return array End quote, position
     */
    protected function getEndQuoteAndPos($len, $endq, $position)
    {
        if ($GLOBALS['finished']) {
            $endq = true;
            $position = $len - 1;
        }
        return array($endq, $position);
    }
}
