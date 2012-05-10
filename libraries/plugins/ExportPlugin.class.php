<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Abstract class for the export plugins
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* This class extends the PluginObserver class */
require_once "PluginObserver.class.php";

/**
 * Provides a common interface that will have to implemented by all of the
 * export plugins. Some of the plugins will also implement other public
 * methods, but those are not declared here, because they are not implemented
 * by all export plugins.
 *
 * @package PhpMyAdmin
 */
abstract class ExportPlugin extends PluginObserver
{
    /**
     * Array containing the specific export plugin type properties
     *
     * @var type array
     */
    protected $properties;

    /**
     * Type of the newline character
     *
     * @var type string
     */
    private $_crlf;

    /**
     * Contains configuration settings
     *
     * @var type array
     */
    private $_cfg;

    /**
     * Database name
     *
     * @var type string
     */
    private $_db;

    /**
     * Outputs export header
     *
     * @return bool Whether it succeeded
     */
    abstract public function exportHeader ();

    /**
     * Outputs export footer
     *
     * @return bool Whether it succeeded
     */
    abstract public function exportFooter ();

    /**
     * Outputs database header
     *
     * @param string $db Database name
     *
     * @return bool Whether it succeeded
     */
    abstract public function exportDBHeader ($db);

    /**
     * Outputs database footer
     *
     * @param string $db Database name
     *
     * @return bool Whether it succeeded
     */
    abstract public function exportDBFooter ($db);

    /**
     * Outputs CREATE DATABASE statement
     *
     * @param string $db Database name
     *
     * @return bool Whether it succeeded
     */
    abstract public function exportDBCreate($db);

     /**
     * Outputs the content of a table
     *
     * @param string $db        database name
     * @param string $table     table name
     * @param string $crlf      the end of line sequence
     * @param string $error_url the url to go back in case of error
     * @param string $sql_query SQL query for obtaining data
     *
     * @return bool Whether it succeeded
     */
    abstract public function exportData ($db, $table, $crlf, $error_url, $sql_query);

    /**
     * Initializes the local variables with the global values.
     * These are variables that are used by all of the export plugins.
     *
     * @global String $crlf type of the newline character
     * @global array  $cfg  array with configuration settings
     * @global String $db   database name
     * 
     * @return void
     */
    protected function initExportCommonVariables()
    {
        global $crlf;
        global $cfg;
        global $db;
        $this->setCrlf($crlf);
        $this->setCfg($cfg);
        $this->setDb($db);
    }


    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */


    /**
     * Gets the export specific format plugin properties
     *
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * Sets the export plugins properties and is implemented by each export
     * plugin
     *
     * @return void
     */
    abstract protected function setProperties();

    /**
     * Gets the type of the newline character
     *
     * @return string
     */
    public function getCrlf()
    {
        return $this->_crlf;
    }

    /**
     * Sets the type of the newline character
     *
     * @param String $crlf type of the newline character
     *
     * @return void
     */
    protected function setCrlf($crlf)
    {
        $this->_crlf = $crlf;
    }

    /**
     * Gets the configuration settings
     *
     * @return array
     */
    public function getCfg()
    {
        return $this->_cfg;
    }

    /**
     * Sets the configuration settings
     *
     * @param array $cfg array with configuration settings
     *
     * @return void
     */
    protected function setCfg($cfg)
    {
        $this->_cfg = $cfg;
    }

    /**
     * Gets the database name
     *
     * @return string
     */
    public function getDb()
    {
        return $this->_db;
    }

    /**
     * Sets the database name
     *
     * @param String $db database name
     *
     * @return void
     */
    protected function setDb($db)
    {
        $this->_db = $db;
    }
}
?>