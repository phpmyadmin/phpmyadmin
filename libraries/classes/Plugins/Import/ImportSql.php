<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * SQL import plugin for phpMyAdmin
 *
 * @package    PhpMyAdmin-Import
 * @subpackage SQL
 */
namespace PhpMyAdmin\Plugins\Import;

use PhpMyAdmin\Import;
use PhpMyAdmin\Plugins\ImportPlugin;
use PhpMyAdmin\Properties\Plugins\ImportPluginProperties;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Options\Items\SelectPropertyItem;
use PhpMyAdmin\SqlParser\Utils\BufferedQuery;

/**
 * Handles the import for the SQL format
 *
 * @package    PhpMyAdmin-Import
 * @subpackage SQL
 */
class ImportSql extends ImportPlugin
{
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
            $importSpecificOptions = new OptionsPropertyRootGroup(
                "Format Specific Options"
            );

            // general options main group
            $generalOptions = new OptionsPropertyMainGroup("general_opts");
            // create primary items and add them to the group
            $leaf = new SelectPropertyItem(
                "compatibility",
                __('SQL compatibility mode:')
            );
            $leaf->setValues($values);
            $leaf->setDoc(
                array(
                    'manual_MySQL_Database_Administration',
                    'Server_SQL_mode',
                )
            );
            $generalOptions->addProperty($leaf);
            $leaf = new BoolPropertyItem(
                "no_auto_value_on_zero",
                __('Do not use <code>AUTO_INCREMENT</code> for zero values')
            );
            $leaf->setDoc(
                array(
                    'manual_MySQL_Database_Administration',
                    'Server_SQL_mode',
                    'sqlmode_no_auto_value_on_zero',
                )
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
    public function doImport(array &$sql_data = array())
    {
        global $error, $timeout_passed;

        // Handle compatibility options.
        $this->_setSQLMode($GLOBALS['dbi'], $_REQUEST);

        $bq = new BufferedQuery();
        if (isset($_POST['sql_delimiter'])) {
            $bq->setDelimiter($_POST['sql_delimiter']);
        }

        /**
         * Will be set in Import::getNextChunk().
         *
         * @global bool $GLOBALS ['finished']
         */
        $GLOBALS['finished'] = false;

        while ((!$error) && (!$timeout_passed)) {

            // Getting the first statement, the remaining data and the last
            // delimiter.
            $statement = $bq->extract();

            // If there is no full statement, we are looking for more data.
            if (empty($statement)) {

                // Importing new data.
                $newData = Import::getNextChunk();

                // Subtract data we didn't handle yet and stop processing.
                if ($newData === false) {
                    $GLOBALS['offset'] -= mb_strlen($bq->query);
                    break;
                }

                // Checking if the input buffer has finished.
                if ($newData === true) {
                    $GLOBALS['finished'] = true;
                    break;
                }

                // Convert CR (but not CRLF) to LF otherwise all queries may
                // not get executed on some platforms.
                $bq->query .= preg_replace("/\r($|[^\n])/", "\n$1", $newData);

                continue;
            }

            // Executing the query.
            Import::runQuery($statement, $statement, $sql_data);
        }

        // Extracting remaining statements.
        while ((!$error) && (!$timeout_passed) && (!empty($bq->query))) {
            $statement = $bq->extract(true);
            if (!empty($statement)) {
                Import::runQuery($statement, $statement, $sql_data);
            }
        }

        // Finishing.
        Import::runQuery('', '', $sql_data);
    }

    /**
     * Handle compatibility options
     *
     * @param PhpMyAdmin\DatabaseInterface $dbi     Database interface
     * @param array                        $request Request array
     *
     * @return void
     */
    private function _setSQLMode($dbi, array $request)
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
}
