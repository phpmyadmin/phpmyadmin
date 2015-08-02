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
     * Handles the whole import logic
     *
     * @param array &$sql_data 2-element array with sql data
     *
     * @return void
     */
    public function doImport(&$sql_data = array())
    {
        global $error, $timeout_passed;

        // Manage multibytes or not.
        if (isset($_REQUEST['sql_read_as_multibytes'])) {
            $this->_readMb = self::READ_MB_TRUE;
        }
        $this->_stringFctToUse = $this->_stringFunctions[$this->_readMb];

        // Handle compatibility options.
        $this->_setSQLMode($GLOBALS['dbi'], $_REQUEST);

        // Initialise data.
        $this->_setData(null);

        /**
         * The SQL delimiter.
         * @var string
         */
        $delimiter = isset($_POST['sql_delimiter']) ? $_POST['sql_delimiter'] : ';';

        /**
         * Will be set in PMA_importGetNextChunk().
         * @global bool $GLOBALS['finished']
         */
        $GLOBALS['finished'] = false;

        while ((!$error) && (!$timeout_passed)) {

            // Getting the first statement, the remaining data and the last
            // delimiter.
             list($statement, $data, $delimiter) =
                SqlParser\Utils\Query::getFirstStatement($this->_data, $delimiter);

            // If there is no full statement, we are looking for more data.
            if (empty($statement)) {

                // Importing new data.
                $newData = PMA_importGetNextChunk(200);

                // Subtract data we didn't handle yet and stop processing.
                if ($newData === false) {
                    $GLOBALS['offset'] -= $this->_dataLength;
                    break;
                }

                // Checking if the input buffer has finished.
                if ($newData === true) {
                    $GLOBALS['finished'] = true;
                    break;
                }

                // Convert CR (but not CRLF) to LF otherwise all queries may
                // not get executed on some platforms.
                $this->_addData(preg_replace("/\r($|[^\n])/", "\n$1", $newData));
                unset($newData);
                continue;
            }

            // Updating the remaining data.
            $this->_setData($data);

            // Executing the query.
            PMA_importRunQuery($statement, $statement, false, $sql_data);
        }

        // Execute remaining data.
        // PMA_importRunQuery($this->_data, $this->_data, false, $sql_data);
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
