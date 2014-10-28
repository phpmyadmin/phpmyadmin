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
    private $_delimiterLength;

    private $_isInString = false;

    private $_quote = null;

    private $_isInComment = false;

    private $_openingComment = null;

    private $_isInDelimiter = false;

    private $_delimiterKeyword = 'DELIMITER ';

    private $_readMb = self::READ_MB_FALSE;

    private $_data = null;
    private $_dataLength = 0;
    private $_posInData = false;

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
            $this->_data, $this->_quote, $this->_posInData
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

        //Move after the escaped guote.
        $this->_posInData = $posClosingString + 1;

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
        $firstSearchChar = null;
        $firstSqlDelimiter = null;
        $matches = null;

        /* while not at end of line */
        while ($this->_posInData < $this->_dataLength) {
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
                        $this->_posInData
                    );
                    if (false === $posClosingComment) {
                        return false;
                    }
                    //Move after the end of the line.
                    $this->_posInData = $posClosingComment + 1;
                    $this->_isInComment = false;
                    $this->_openingComment = null;
                } elseif ('/*' === $this->_openingComment) {
                    //Search for closing comment
                    $posClosingComment = $this->_stringFctToUse['strpos'](
                        $this->_data,
                        '*/',
                        $this->_posInData
                    );
                    if (false === $posClosingComment) {
                        return false;
                    }
                    //Move after closing comment.
                    $this->_posInData = $posClosingComment + 2;
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
                        $this->_posInData
                    ),
                    $matches,
                    PREG_OFFSET_CAPTURE
                )) {
                    return false;
                }

                $this->_setDelimiter($matches[1][0]);
                //Move after delimiter and new line.
                $this->_setData(
                    $this->_stringFctToUse['substr'](
                        $this->_data,
                        $this->_posInData + $matches[1][1] + $this->_delimiterLength
                        + 1
                    )
                );
                $this->_isInDelimiter = false;
                $firstSqlDelimiter = null;
                $firstSearchChar = null;
                continue;
            }

            list($matches, $firstSearchChar) = $this->_searchSpecialChars(
                $this->_data,
                $firstSearchChar,
                $matches
            );

            $firstSqlDelimiter = $this->_searchSqlDelimiter(
                $this->_data,
                $firstSqlDelimiter
            );

            if (false === $firstSqlDelimiter && false === $firstSearchChar) {
                return false;
            }

            //If first char is delimiter.
            if (false === $firstSearchChar
                || (false !== $firstSqlDelimiter && $firstSqlDelimiter < $firstSearchChar)
            ) {
                return $firstSqlDelimiter;
            }

            //Else first char is result of preg_match.

            $specialChars = $matches[1][0];

            //If string is opened.
            if (in_array($specialChars, array('\'', '"', '`'))) {
                $this->_isInString = true;
                $this->_quote = $specialChars;
                //Move after quote.
                $this->_posInData = $firstSearchChar + 1;

                continue;
            }

            //If comment is opened.
            if (in_array($specialChars, array('#', '-- ', '/*'))) {
                $this->_isInComment = true;
                $this->_openingComment = $specialChars;
                //Move after comment opening.
                $this->_posInData = $firstSearchChar
                    + $this->_stringFctToUse['strlen']($specialChars);
                continue;
            }

            //If DELIMITER is found.
            if ($specialChars === $this->_delimiterKeyword) {
                $this->_isInDelimiter =  true;
                $this->_posInData = $firstSearchChar
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
        $positionDelimiter = false;
        $query = null;

        while (!$error && !$timeout_passed) {
            if (false === $positionDelimiter) {
                $newData = PMA_importGetNextChunk(200);
                if ($newData === false) {
                    // subtract data we didn't handle yet and stop processing
                    $GLOBALS['offset']
                        -= $this->_stringFctToUse['strlen']($query);
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
            $positionDelimiter = $this->_findDelimiterPosition();

            //If no delimiter found, restart and get more data.
            if (false === $positionDelimiter) {
                continue;
            }

            $query = $this->_stringFctToUse['substr'](
                $this->_data,
                0,
                $positionDelimiter
            );
            $this->_setData(
                $this->_stringFctToUse['substr'](
                    $this->_data,
                    $positionDelimiter + $this->_delimiterLength
                )
            );

            PMA_importRunQuery(
                $query, //Query to execute
                $query, //Query to display
                false,
                $sql_data
            );

            //After execution, $buffer can be empty.
            $query = null;
        }

        //Commit any possible data in buffers
        PMA_importRunQuery('', $this->_data, false, $sql_data);
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
     * @param string $data            Data to parse
     * @param int    $firstSearchChar First found char position
     * @param array  $matches         Special chars found in $data
     *
     * @return array 0: matches, 1: first found char position
     */
    private function _searchSpecialChars(
        $data,
        $firstSearchChar,
        $matches
    ) {
        //Don't look for a string/comment/"DELIMITER" if not found previously
        //or if it's still after current position.
        if (null === $firstSearchChar
            || (false !== $firstSearchChar && $firstSearchChar < $this->_posInData)
        ) {
            $bFind = preg_match(
                '/(\'|"|#|-- |\/\*|`|(?i)(?<![A-Z0-9_])'
                . $this->_delimiterKeyword . ')/',
                $this->_stringFctToUse['substr']($data, $this->_posInData),
                $matches,
                PREG_OFFSET_CAPTURE
            );

            if (1 === $bFind) {
                $firstSearchChar = $matches[1][1] + $this->_posInData;
            } else {
                $firstSearchChar = false;
            }
        }
        return array($matches, $firstSearchChar);
    }

    /**
     * Look for SQL delimiter
     *
     * @param string $data              Data to parse
     * @param int    $firstSqlDelimiter First found char position
     *
     * @return int
     */
    private function _searchSqlDelimiter($data, $firstSqlDelimiter)
    {
        //Don't look for the SQL delimiter if not found previously
        //or if it's still after current position.
        if (null === $firstSqlDelimiter
            || (false !== $firstSqlDelimiter && $firstSqlDelimiter < $this->_posInData)
        ) {
            // the cost of doing this one with preg_match() would be too high
            $firstSqlDelimiter = $this->_stringFctToUse['strpos'](
                $data,
                $this->_delimiter,
                $this->_posInData
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
        $this->_posInData = 0;

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
