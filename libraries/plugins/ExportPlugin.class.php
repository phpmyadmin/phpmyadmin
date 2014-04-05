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
require_once 'PluginObserver.class.php';

/**
 * Provides a common interface that will have to be implemented by all of the
 * export plugins. Some of the plugins will also implement other public
 * methods, but those are not declared here, because they are not implemented
 * by all export plugins.
 *
 * @package PhpMyAdmin
 */
abstract class ExportPlugin extends PluginObserver
{
    /**
     * ExportPluginProperties object containing
     * the specific export plugin type properties
     *
     * @var ExportPluginProperties
     */
    protected $properties;

    /**
     * Common methods, must be overwritten by all export plugins
     */


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
     * The following methods are used in export.php or in db_operations.php,
     * but they are not implemented by all export plugins
     */


    /**
     * Exports routines (procedures and functions)
     *
     * @param string $db Database
     *
     * @return bool Whether it succeeded
     */
    public function exportRoutines($db)
    {
        ;
    }

    /**
     * Outputs table's structure
     *
     * @param string $db          database name
     * @param string $table       table name
     * @param string $crlf        the end of line sequence
     * @param string $error_url   the url to go back in case of error
     * @param string $export_mode 'create_table','triggers','create_view',
     *                            'stand_in'
     * @param string $export_type 'server', 'database', 'table'
     * @param bool   $relation    whether to include relation comments
     * @param bool   $comments    whether to include the pmadb-style column comments
     *                            as comments in the structure; this is deprecated
     *                            but the parameter is left here because export.php
     *                            calls exportStructure() also for other export
     *                            types which use this parameter
     * @param bool   $mime        whether to include mime comments
     * @param bool   $dates       whether to include creation/update/check dates
     *
     * @return bool Whether it succeeded
     */
    public function exportStructure(
        $db,
        $table,
        $crlf,
        $error_url,
        $export_mode,
        $export_type,
        $relation = false,
        $comments = false,
        $mime = false,
        $dates = false
    ) {
        ;
    }

    /**
     * Returns a stand-in CREATE definition to resolve view dependencies
     *
     * @param string $db   the database name
     * @param string $view the view name
     * @param string $crlf the end of line sequence
     *
     * @return string resulting definition
     */
    public function getTableDefStandIn($db, $view, $crlf)
    {
        ;
    }

    /**
     * Outputs triggers
     *
     * @param string $db    database name
     * @param string $table table name
     *
     * @return string Formatted triggers list
     */
    protected function getTriggers($db, $table)
    {
        ;
    }

    /**
     * Initialize the specific variables for each export plugin
     *
     * @return void
     */
    protected function initSpecificVariables()
    {
        ;
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
}
?>