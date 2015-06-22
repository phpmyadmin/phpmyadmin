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

    /**
     * @var string SQL delimiter
     */
    private $_delimiter;

    /**
     * @var int SQL delimiter length
     */
    private $_delimiterLength;

    /**
     * @var bool|int SQL delimiter position or false if not found
     */
    private $_delimiterPosition = false;

    /**
     * @var int Query start position
     */
    private $_queryBeginPosition = 0;

    /**
     * @var int|false First special chars position or false if not found
     */
    private $_firstSearchChar = null;

    /**
     * @var bool Current position is in string
     */
    private $_isInString = false;

    /**
     * @var string Quote of current string or null if out of string
     */
    private $_quote = null;

    /**
     * @var bool Current position is in comment
     */
    private $_isInComment = false;

    /**
     * @var string Current comment opener
     */
    private $_openingComment = null;

    /**
     * @var bool Current position is in delimiter definition
     */
    private $_isInDelimiter = false;

    /**
     * @var string Delimiter keyword
     */
    private $_delimiterKeyword = 'DELIMITER ';

    /**
     * @var int Import should be done using multibytes
     */
    private $_readMb = self::READ_MB_FALSE;

    /**
     * @var string Data to parse
     */
    private $_data = null;

    /**
     * @var int Length of data to parse
     */
    private $_dataLength = 0;

    /**
     * @var array List of string functions
     * @todo Move this part in string functions definition file.
     */
    private $_stringFunctions = array(
        self::READ_MB_FALSE => array(
            'substr'     => 'substr',
            'strlen'     => 'strlen',
            'strpos'     => 'strpos',
            'strtoupper' => 'strtoupper',
        ),
        self::READ_MB_TRUE => array(
            'substr'     => 'mb_substr',
            'strlen'     => 'mb_strlen',
            'strpos'     => 'mb_strpos',
            'strtoupper' => 'mb_strtoupper',
        ),
    );

    /**
     * @var bool|int List of string functions to use
     */
    private $_stringFctToUse = false;

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
     * Look for end of string
     *
     * @return bool End of string found
     */
    private function _searchStringEnd()
    {
        //Search for closing quote
        $posClosingString = $this->_stringFctToUse['strpos'](
            $this->_data, $this->_quote, $this->_delimiterPosition
        );

        if (false === $posClosingString) {
            return false;
        }

        //Quotes escaped by quote will be considered as 2 consecutive strings
        //and won't pass in this loop.
        $posEscape = $posClosingString-1;
        while ($this->_stringFctToUse['substr']($this->_data, $posEscape, 1) == '\\'
        ) {
            $posEscape--;
        }

        // Odd count means it was escaped
        $quoteEscaped = (((($posClosingString - 1) - $posEscape) % 2) === 1);

        //Move after the escaped quote.
        $this->_delimiterPosition = $posClosingString + 1;

        if ($quoteEscaped) {
            return true;
        }

        $this->_isInString = false;
        $this->_quote = null;
        return true;
    }

    /**
     * Return the position of first SQL delimiter or false if no SQL delimiter found.
     *
     * @return int|bool Delimiter position or false if no delimiter found
     */
    private function _findDelimiterPosition()
    {
        $this->_firstSearchChar = null;
        $firstSqlDelimiter = null;
        $matches = null;

        /* while not at end of line */
        while ($this->_delimiterPosition < $this->_dataLength) {
            if ($this->_isInString) {
                if (false === $this->_searchStringEnd()) {
                    return false;
                }

                continue;
            }

            if ($this->_isInComment) {
                if (in_array($this->_openingComment, array('#', '-- '))) {
                    $posClosingComment = $this->_stringFctToUse['strpos'](
                        $this->_data,
                        "\n",
                        $this->_delimiterPosition
                    );
                    if (false === $posClosingComment) {
                        return false;
                    }
                    //Move after the end of the line.
                    $this->_delimiterPosition = $posClosingComment + 1;
                    $this->_isInComment = false;
                    $this->_openingComment = null;
                } elseif ('/*' === $this->_openingComment) {
                    //Search for closing comment
                    $posClosingComment = $this->_stringFctToUse['strpos'](
                        $this->_data,
                        '*/',
                        $this->_delimiterPosition
                    );
                    if (false === $posClosingComment) {
                        return false;
                    }
                    //Move after closing comment.
                    $this->_delimiterPosition = $posClosingComment + 2;
                    $this->_isInComment = false;
                    $this->_openingComment = null;
                } else {
                    //We shouldn't be able to come here.
                    //throw new Exception('Unknown case.');
                    break;
                }

                continue;
            }

            if ($this->_isInDelimiter) {
                //Search for new line.
                if (!preg_match(
                    "/^(.*)\n/",
                    $this->_stringFctToUse['substr'](
                        $this->_data,
                        $this->_delimiterPosition
                    ),
                    $matches,
                    PREG_OFFSET_CAPTURE
                )) {
                    return false;
                }

                $this->_setDelimiter($matches[1][0]);
                //Start after delimiter and new line.
                $this->_queryBeginPosition = $this->_delimiterPosition
                    + $matches[1][1] + $this->_delimiterLength + 1;
                $this->_delimiterPosition = $this->_queryBeginPosition;
                $this->_isInDelimiter = false;
                $firstSqlDelimiter = null;
                $this->_firstSearchChar = null;
                continue;
            }

            $matches = $this->_searchSpecialChars($matches);

            $firstSqlDelimiter = $this->_searchSqlDelimiter($firstSqlDelimiter);

            if (false === $firstSqlDelimiter && false === $this->_firstSearchChar) {
                return false;
            }

            //If first char is delimiter.
            if (false === $this->_firstSearchChar
                || (false !== $firstSqlDelimiter
                && $firstSqlDelimiter < $this->_firstSearchChar)
            ) {
                $this->_delimiterPosition = $firstSqlDelimiter;
                return true;
            }

            //Else first char is result of preg_match.

            $specialChars = $matches[1][0];

            //If string is opened.
            if (in_array($specialChars, array('\'', '"', '`'))) {
                $this->_isInString = true;
                $this->_quote = $specialChars;
                //Move before quote.
                $this->_delimiterPosition = $this->_firstSearchChar + 1;
                continue;
            }

            //If comment is opened.
            if (in_array($specialChars, array('#', '-- ', '/*'))) {
                $this->_isInComment = true;
                $this->_openingComment = $specialChars;
                //Move before comment opening.
                $this->_delimiterPosition = $this->_firstSearchChar
                    + $this->_stringFctToUse['strlen']($specialChars);
                continue;
            }

            //If DELIMITER is found.
            $specialCharsUpper = $this->_stringFctToUse['strtoupper']($specialChars);
            if ($specialCharsUpper === $this->_delimiterKeyword) {
                $this->_isInDelimiter =  true;
                $this->_delimiterPosition = $this->_firstSearchChar
                    + $this->_stringFctToUse['strlen']($specialChars);
                continue;
            }
        }

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

        //Manage multibytes or not
        if (isset($_REQUEST['sql_read_as_multibytes'])) {
            $this->_readMb = self::READ_MB_TRUE;
        }
        $this->_stringFctToUse = $this->_stringFunctions[$this->_readMb];

        if (isset($_POST['sql_delimiter'])) {
            $this->_setDelimiter($_POST['sql_delimiter']);
        } else {
            $this->_setDelimiter(';');
        }

        // Handle compatibility options
        $this->_setSQLMode($GLOBALS['dbi'], $_REQUEST);

        //Initialise data.
        $this->_setData(null);

        /**
         * will be set in PMA_importGetNextChunk()
         *
         * @global boolean $GLOBALS['finished']
         */
        $GLOBALS['finished'] = false;
        $delimiterFound = false;

        while (!$error && !$timeout_passed) {
            if (false === $delimiterFound) {
                $newData = PMA_importGetNextChunk(200);
                if ($newData === false) {
                    // subtract data we didn't handle yet and stop processing
                    $GLOBALS['offset'] -= $this->_dataLength;
                    break;
                }

                if ($newData === true) {
                    $GLOBALS['finished'] = true;
                    break;
                }

                //Convert CR (but not CRLF) to LF otherwise all queries
                //may not get executed on some platforms
                $this->_addData(preg_replace("/\r($|[^\n])/", "\n$1", $newData));
                unset($newData);
            }

            //Find quotes, comments, delimiter definition or delimiter itself.
            $delimiterFound = $this->_findDelimiterPosition();

            //If no delimiter found, restart and get more data.
            if (false === $delimiterFound) {
                continue;
            }

            PMA_importRunQuery(
                $this->_stringFctToUse['substr'](
                    $this->_data,
                    $this->_queryBeginPosition,
                    $this->_delimiterPosition - $this->_queryBeginPosition
                ), //Query to execute
                $this->_stringFctToUse['substr'](
                    $this->_data,
                    0,
                    $this->_delimiterPosition + $this->_delimiterLength
                ), //Query to display
                false,
                $sql_data
            );

            $this->_setData(
                $this->_stringFctToUse['substr'](
                    $this->_data,
                    $this->_delimiterPosition + $this->_delimiterLength
                )
            );
        }

        if (! $timeout_passed) {
            //Commit any possible data in buffers
            PMA_importRunQuery(
                $this->_stringFctToUse['substr'](
                    $this->_data,
                    $this->_queryBeginPosition
                ), //Query to execute
                $this->_data,
                false,
                $sql_data
            );
        }
        PMA_importRunQuery('', '', false, $sql_data);
    }

    /**
     * Handle compatibility options
     *
     * @param PMA_DatabaseInterface $dbi     Database interface
     * @param array                 $request Request array
     *
     * @return void
     */
    private function _setSQLMode($dbi, $request)
    {
        $sql_modes = array();
        if (isset($request['sql_compatibility'])
            && 'NONE' != $request['sql_compatibility']
        ) {
            $sql_modes[] = $request['sql_compatibility'];
        }
        if (isset($request['sql_no_auto_value_on_zero'])) {
            $sql_modes[] = 'NO_AUTO_VALUE_ON_ZERO';
        }
        if (count($sql_modes) > 0) {
            $dbi->tryQuery(
                'SET SQL_MODE="' . implode(',', $sql_modes) . '"'
            );
        }
    }

    /**
     * Look for special chars: comment, string or DELIMITER
     *
     * @param array $matches Special chars found in data
     *
     * @return array matches
     */
    private function _searchSpecialChars(
        $matches
    ) {
        //Don't look for a string/comment/"DELIMITER" if not found previously
        //or if it's still after current position.
        if (null === $this->_firstSearchChar
            || (false !== $this->_firstSearchChar
            && $this->_firstSearchChar < $this->_delimiterPosition)
        ) {
            $bFind = preg_match(
                '/(\'|"|#|-- |\/\*|`|(?i)(?<![A-Z0-9_])'
                . $this->_delimiterKeyword . ')/',
                $this->_stringFctToUse['substr'](
                    $this->_data,
                    $this->_delimiterPosition
                ),
                $matches,
                PREG_OFFSET_CAPTURE
            );

            if (1 === $bFind) {
                $this->_firstSearchChar = $matches[1][1] + $this->_delimiterPosition;
            } else {
                $this->_firstSearchChar = false;
            }
        }
        return $matches;
    }

    /**
     * Look for SQL delimiter
     *
     * @param int $firstSqlDelimiter First found char position
     *
     * @return int
     */
    private function _searchSqlDelimiter($firstSqlDelimiter)
    {
        //Don't look for the SQL delimiter if not found previously
        //or if it's still after current position.
        if (null === $firstSqlDelimiter
            || (false !== $firstSqlDelimiter
            && $firstSqlDelimiter < $this->_delimiterPosition)
        ) {
            // the cost of doing this one with preg_match() would be too high
            $firstSqlDelimiter = $this->_stringFctToUse['strpos'](
                $this->_data,
                $this->_delimiter,
                $this->_delimiterPosition
            );
        }

        return $firstSqlDelimiter;
    }

    /**
     * Set new delimiter
     *
     * @param string $delimiter New delimiter
     *
     * @return int delimiter length
     */
    private function _setDelimiter($delimiter)
    {
        $this->_delimiter = $delimiter;
        $this->_delimiterLength = $this->_stringFctToUse['strlen']($delimiter);

        return $this->_delimiterLength;
    }

    /**
     * Set data to parse
     *
     * @param string $data Data to parse
     *
     * @return int Data length
     */
    private function _setData($data)
    {
        $this->_data = ltrim($data);
        $this->_dataLength = $this->_stringFctToUse['strlen']($this->_data);
        $this->_queryBeginPosition = 0;
        $this->_delimiterPosition = 0;

        return $this->_dataLength;
    }

    /**
     * Add data to parse
     *
     * @param string $data Data to add to data to parse
     *
     * @return int Data length
     */
    private function _addData($data)
    {
        $this->_data .= $data;
        $this->_dataLength += $this->_stringFctToUse['strlen']($data);

        return $this->_dataLength;
    }
}
