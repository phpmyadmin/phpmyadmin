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

/**
 * Provides a common interface that will have to be implemented by all of the
 * export plugins. Some of the plugins will also implement other public
 * methods, but those are not declared here, because they are not implemented
 * by all export plugins.
 *
 * @package PhpMyAdmin
 */
abstract class ExportPlugin
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
    abstract public function exportHeader();

    /**
     * Outputs export footer
     *
     * @return bool Whether it succeeded
     */
    abstract public function exportFooter();

    /**
     * Outputs database header
     *
     * @param string $db       Database name
     * @param string $db_alias Aliases of db
     *
     * @return bool Whether it succeeded
     */
    abstract public function exportDBHeader($db, $db_alias = '');

    /**
     * Outputs database footer
     *
     * @param string $db Database name
     *
     * @return bool Whether it succeeded
     */
    abstract public function exportDBFooter($db);

    /**
     * Outputs CREATE DATABASE statement
     *
     * @param string $db          Database name
     * @param string $export_type 'server', 'database', 'table'
     * @param string $db_alias    Aliases of db
     *
     * @return bool Whether it succeeded
     */
    abstract public function exportDBCreate($db, $export_type, $db_alias = '');

     /**
     * Outputs the content of a table
     *
     * @param string $db        database name
     * @param string $table     table name
     * @param string $crlf      the end of line sequence
     * @param string $error_url the url to go back in case of error
     * @param string $sql_query SQL query for obtaining data
     * @param array  $aliases   Aliases of db/table/columns
     *
     * @return bool Whether it succeeded
     */
    abstract public function exportData(
        $db, $table, $crlf, $error_url, $sql_query, $aliases = array()
    );

    /**
     * The following methods are used in export.php or in db_operations.php,
     * but they are not implemented by all export plugins
     */

    /**
     * Exports routines (procedures and functions)
     *
     * @param string $db      Database
     * @param array  $aliases Aliases of db/table/columns
     *
     * @return bool Whether it succeeded
     */
    public function exportRoutines($db, $aliases = array())
    {
        ;
    }

    /**
     * Exports events
     *
     * @param string $db Database
     *
     * @return bool Whether it succeeded
     */
    public function exportEvents($db)
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
     * @param array  $aliases     Aliases of db/table/columns
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
        $dates = false,
        $aliases = array()
    ) {
        ;
    }

    /**
     * Exports metadata from Configuration Storage
     *
     * @param string       $db            database being exported
     * @param string|array $tables        table(s) being exported
     * @param array        $metadataTypes types of metadata to export
     * @param array        $targetNames   associative array of db and table names of
     *                                    target configuraton storage
     *
     * @return bool Whether it succeeded
     */
    public function exportMetadata(
        $db, $tables, $metadataTypes, $targetNames = array()
    ) {
        ;
    }

    /**
     * Returns a stand-in CREATE definition to resolve view dependencies
     *
     * @param string $db      the database name
     * @param string $view    the view name
     * @param string $crlf    the end of line sequence
     * @param array  $aliases Aliases of db/table/columns
     *
     * @return string resulting definition
     */
    public function getTableDefStandIn($db, $view, $crlf, $aliases = array())
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
     * @return ExportPluginProperties
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
     * The following methods are implemented here so that they
     * can be used by all export plugin without overriding it.
     * Note: If you are creating a export plugin then don't include
     * below methods unless you want to override them.
     */

    /**
     * Initialize aliases
     *
     * @param array  $aliases Alias information for db/table/column
     * @param string &$db     the database
     * @param string &$table  the table
     *
     * @return nothing
     */
    public function initAlias($aliases, &$db, &$table = null)
    {
        if (!empty($aliases[$db]['tables'][$table]['alias'])) {
            $table = $aliases[$db]['tables'][$table]['alias'];
        }
        if (!empty($aliases[$db]['alias'])) {
            $db = $aliases[$db]['alias'];
        }
    }

    /**
     * Search for alias of a identifier.
     *
     * @param array  $aliases Alias information for db/table/column
     * @param string $id      the identifier to be searched
     * @param string $type    db/tbl/col or any combination of them
     *                        representing what to be searched
     * @param string $db      the database in which search is to be done
     * @param string $tbl     the table in which search is to be done
     *
     * @return string alias of the identifier if found or ''
     */
    public function getAlias($aliases, $id, $type = 'dbtblcol', $db = '', $tbl = '')
    {
        if (!empty($db) && isset($aliases[$db])) {
            $aliases = array(
                $db => $aliases[$db]
            );
        }
        // search each database
        foreach ($aliases as $db_key => $db) {
            // check if id is database and has alias
            if (stristr($type, 'db') !== false
                && $db_key === $id && !empty($db['alias'])
            ) {
                return $db['alias'];
            }
            if (empty($db['tables'])) {
                continue;
            }
            if (!empty($tbl) && isset($db['tables'][$tbl])) {
                $db['tables'] = array(
                    $tbl => $db['tables'][$tbl]
                );
            }
            // search each of its tables
            foreach ($db['tables'] as $table_key => $table) {
                // check if id is table and has alias
                if (stristr($type, 'tbl') !== false
                    && $table_key === $id && !empty($table['alias'])
                ) {
                    return $table['alias'];
                }
                if (empty($table['columns'])) {
                    continue;
                }
                // search each of its columns
                foreach ($table['columns'] as $col_key => $col) {
                    // check if id is column
                    if (stristr($type, 'col') !== false
                        && $col_key === $id && !empty($col)
                    ) {
                        return $col;
                    }
                }
            }
        }
        return '';
    }

    /**
     * Gives the relation string and
     * also substitutes with alias if required
     * in this format:
     * [Foreign Table] ([Foreign Field])
     *
     * @param array  $res_rel    the foreigners array
     * @param string $field_name the field name
     * @param string $db         the field name
     * @param array  $aliases    Alias information for db/table/column
     *
     * @return string the Relation string
     */
    public function getRelationString(
        $res_rel, $field_name, $db, $aliases = array()
    ) {
        $relation = '';
        $foreigner = PMA_searchColumnInForeigners($res_rel, $field_name);
        if ($foreigner) {
            $ftable = $foreigner['foreign_table'];
            $ffield = $foreigner['foreign_field'];
            if (!empty($aliases[$db]['tables'][$ftable]['columns'][$ffield])) {
                $ffield = $aliases[$db]['tables'][$ftable]['columns'][$ffield];
            }
            if (!empty($aliases[$db]['tables'][$ftable]['alias'])) {
                $ftable = $aliases[$db]['tables'][$ftable]['alias'];
            }
            $relation = $ftable . ' (' . $ffield . ')';
        }
        return $relation;
    }
}
