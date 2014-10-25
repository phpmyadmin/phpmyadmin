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
    private function _findDelimiterPosition($data) {
        $lengthData = $this->_stringFunctionsToUse['strlen']($data);
        $posInData = 0;

        $firstSearchChar = null;
        $firstSqlDelimiter = null;

        /* while not at end of line */
        while ($posInData < $lengthData) {
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
                    . $this->_delimiter_keyword . ')((.*)\n)?/',
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

            //If DELIMITER is found.
            if ($matches[1][0] === $this->_delimiter_keyword) {
                $this->_delimiter = $matches[3][0];
                //Move after new line.
                $posInData = $matches[3][1]
                    + $this->_stringFunctionsToUse['strlen']($this->_delimiter) + 1;
                //@todo Couldn't we return the position of delimiter and run it alone ?
                //Reinit SQL delimiter search.
                $firstSqlDelimiter = null;
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

        while (!$error && !$timeout_passed) {
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
            $positionDelimiter = $this->_findDelimiterPosition($data);

            //No delimiter found.
            if (false === $positionDelimiter) {
                continue;
            }

            $query = $this->_stringFunctionsToUse['substr'](
                $data,
                0,
                $positionDelimiter + $this->_stringFunctionsToUse['strlen']($this->_delimiter)
            );
            $data = $this->_stringFunctionsToUse['substr'](
                $data,
                $positionDelimiter + $this->_stringFunctionsToUse['strlen']($this->_delimiter)
            );
            $data = ltrim($data);
            PMA_importRunQuery(
                $query,
                null, //Set query to display
                false,
                $sql_data
            );

            //After execution, $buffer can be empty.
            $query = null;
        }

        //Commit any possible data in buffers
        PMA_importRunQuery('', $data, false, $sql_data);
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
