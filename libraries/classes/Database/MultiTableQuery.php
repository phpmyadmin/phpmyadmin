<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Handles DB Multi-table query
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin\Database;

use PhpMyAdmin\Template;

/**
 * Class to handle database Multi-table querying
 *
 * @package PhpMyAdmin
 */
class MultiTableQuery
{
    /**
     * Database name
     *
     * @access private
     * @var string
     */
    private $_db;

    /**
     * Default no. of columns
     *
     * @access private
     * @var integer
     */
    private $_default_no_of_columns;

    public function __construct($db_name)
    {
        $this->_db = $db_name;
        $this->_default_no_of_columns = 3;
    }

    private function getColumnsHTML()
    {
        $tables = $GLOBALS['dbi']->getTables($this->_db);
        return Template::get('database/multi_table_query/columns')->render([
            'tables' => $tables,
            'dbi' => $GLOBALS['dbi'],
            'db' => $this->_db,
            'default_no_of_columns' => $this->_default_no_of_columns,
        ]);
    }

    public function getFormHTML()
    {
        return Template::get('database/multi_table_query/form')->render([
            'db' => $this->_db,
            'columns' => $this->getColumnsHTML(),
        ]);
    }
}
