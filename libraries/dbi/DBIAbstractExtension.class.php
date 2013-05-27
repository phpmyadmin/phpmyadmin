<?php
/**
 * Abstract class to implement the common functionalities of DBI_Extensions
 *
 * @package PhpMyAdmin-DBI
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

require_once 'libraries/dbi/DBIExtension.int.php';

/**
 * Abstract class to implement the common functionalities of DBI_Extensions
 *
 * @package PhpMyAdmin-DBI
 */
abstract class PMA_DBI_AbstractExtension implements PMA_DBI_Extension
{
    /**
     * Returns SQL query for fetching columns for a table
     *
     * The 'Key' column is not calculated properly, use $GLOBALS['dbi']->getColumns()
     * to get correct values.
     *
     * @param string  $database name of database
     * @param string  $table    name of table to retrieve columns from
     * @param string  $column   name of column, null to show all columns
     * @param boolean $full     whether to return full info or only column names
     *
     * @return string
     */
    public function getColumnsSql($database, $table, $column = null, $full = false)
    {
        return 'SHOW ' . ($full ? 'FULL' : '') . ' COLUMNS FROM '
            . PMA_Util::backquote($database) . '.' . PMA_Util::backquote($table)
            . (($column != null) ? "LIKE '"
            . PMA_Util::sqlAddSlashes($column, true) . "'" : '');
    }
}
?>