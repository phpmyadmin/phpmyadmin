<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * DocSQL import plugin for phpMyAdmin
 *
 * @package    PhpMyAdmin-Import
 * @subpackage DocSQL
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* Get the import interface */
require_once "libraries/plugins/ImportPlugin.class.php";

// We need relations enabled and we work only on database
if ($GLOBALS['plugin_param'] !== 'database') {
    $GLOBALS['skip_import'] = true;
    return;
}
            
/**
 * Handles the import for the DocSQL format
 *
 * @package PhpMyAdmin-Import
 */
class ImportDocsql extends ImportPlugin
{
    /**
     * Relations configuration
     *
     * @var array
     */
    private $_cfgRelation;

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
        $this->_setCfgRelation(PMA_getRelationsParam());
        $cfgRelation = $this->_getCfgRelation();
        if ( $GLOBALS['num_tables'] < 1
            || ! $cfgRelation['relwork']
            || ! $cfgRelation['commwork']
        ) {
            return;
        }

        $this->properties = array(
            'text' => __('DocSQL'),
            'extension' => '',
            'options' => array(),
            'options_text' => __('Options'),
        );

        $this->properties['options'] = array(
            array(
                'type' => 'begin_group',
                'name' => 'general_opts'
            ),
            array(
                'type' => 'text',
                'name' => 'table',
                'text' => __('Table name')
            ),
            array(
                'type' => 'end_group'
            )
        );
    }

    /**
     * This method is called when any PluginManager to which the observer
     * is attached calls PluginManager::notify()
     *
     * @param SplSubject $subject The PluginManager notifying the observer
     *                            of an update.
     *
     * @return void
     */
    public function update (SplSubject $subject)
    {
    }

    /**
     * Handles the whole import logic
     *
     * @return void
     */
    public function doImport()
    {
        global $error, $timeout_passed, $finished;
        $cfgRelation = $this->_getCfgRelation();
    
        $tab = $_POST['docsql_table'];
        $buffer = '';

        /* Read whole buffer, we except it is small enough */
        while (! $finished && ! $error && ! $timeout_passed) {
            $data = PMA_importGetNextChunk();
            if ($data === false) {
                // subtract data we didn't handle yet and stop processing
                break;
            } elseif ($data === true) {
                // nothing to read
                break;
            } else {
                // Append new data to buffer
                $buffer .= $data;
            }
        } // End of import loop

        /* Process the data */
        if ($data === true && ! $error && ! $timeout_passed) {
            $buffer = str_replace("\r\n", "\n", $buffer);
            $buffer = str_replace("\r", "\n", $buffer);
            $lines = explode("\n", $buffer);
            foreach ($lines AS $lkey => $line) {
                //echo '<p>' . $line . '</p>';
                $inf = explode('|', $line);
                if (!empty($inf[1]) && strlen(trim($inf[1])) > 0) {
                    $qry = '
                         INSERT INTO
                                ' . PMA_backquote($cfgRelation['db']) . '.'
                        . PMA_backquote($cfgRelation['column_info']) . '
                              (db_name, table_name, column_name, comment)
                         VALUES (
                                \'' . PMA_sqlAddSlashes($GLOBALS['db']) . '\',
                                \'' . PMA_sqlAddSlashes(trim($tab)) . '\',
                                \'' . PMA_sqlAddSlashes(trim($inf[0])) . '\',
                                \'' . PMA_sqlAddSlashes(trim($inf[1])) . '\')';

                    PMA_importRunQuery(
                        $qry, $qry . '-- ' . htmlspecialchars($tab)
                        . '.' . htmlspecialchars($inf[0]), true
                    );
                } // end inf[1] exists

                if (!empty($inf[2]) && strlen(trim($inf[2])) > 0) {
                    $for = explode('->', $inf[2]);
                    $qry = '
                         INSERT INTO
                                ' . PMA_backquote($cfgRelation['db']) . '.'
                        . PMA_backquote($cfgRelation['relation']) . '
                              (master_db, master_table, master_field,'
                        . ' foreign_db, foreign_table, foreign_field)
                         VALUES (
                                \'' . PMA_sqlAddSlashes($GLOBALS['db']) . '\',
                                \'' . PMA_sqlAddSlashes(trim($tab)) . '\',
                                \'' . PMA_sqlAddSlashes(trim($inf[0])) . '\',
                                \'' . PMA_sqlAddSlashes($GLOBALS['db']) . '\',
                                \'' . PMA_sqlAddSlashes(trim($for[0])) . '\',
                                \'' . PMA_sqlAddSlashes(trim($for[1])) . '\')';

                    PMA_importRunQuery(
                        $qry, $qry . '-- ' . htmlspecialchars($tab)
                        . '.' . htmlspecialchars($inf[0])
                        . '(' . htmlspecialchars($inf[2]) . ')', true
                    );
                } // end inf[2] exists
            } // End lines loop
        } // End import
        // Commit any possible data in buffers
        PMA_importRunQuery();
    }


    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */


    /**
     * Gets relation configuration
     *
     * @return array
     */
    private function _getCfgRelation()
    {
        return $this->_cfgRelation;
    }

    /**
     * Sets relation configuration
     *
     * @param array $cfgRelation relation configuration
     *
     * @return void
     */
    private function _setCfgRelation($cfgRelation)
    {
        $this->_cfgRelation = $cfgRelation;
    }
}