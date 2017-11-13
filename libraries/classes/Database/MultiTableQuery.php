<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Handles DB Multi-table query
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin\Database;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Template;

/**
 * Class to handle database Multi-table querying
 *
 * @package PhpMyAdmin
 */
class MultiTableQuery
{
    /**
     * DatabaseInterface instance
     *
     * @access private
     * @var DatabaseInterface
     */
    private $dbi;

    /**
     * Database name
     *
     * @access private
     * @var string
     */
    private $db;

    /**
     * Default number of columns
     *
     * @access private
     * @var integer
     */
    private $defaultNoOfColumns;

    /**
     * Table names
     *
     * @access private
     * @var array
     */
    private $tables;

    /**
     * Constructor
     *
     * @param DatabaseInterface $dbi                DatabaseInterface instance
     * @param string            $dbName             Database name
     * @param integer           $defaultNoOfColumns Default number of columns
     */
    public function __construct(
        DatabaseInterface $dbi,
        $dbName,
        $defaultNoOfColumns = 3
    ) {
        $this->dbi = $dbi;
        $this->db = $dbName;
        $this->defaultNoOfColumns = $defaultNoOfColumns;

        $this->tables = $this->dbi->getTables($this->db);
    }

    /**
     * Get Multi-Table query page HTML
     *
     * @return string Multi-Table query page HTML
     */
    public function getFormHtml()
    {
        $tables = [];
        foreach($this->tables as $table) {
            $tables[$table]['hash'] = md5($table);
            $tables[$table]['columns'] = array_keys(
                $this->dbi->getColumns($this->db, $table)
            );
        }
        return Template::get('database/multi_table_query/form')->render([
            'db' => $this->db,
            'tables' => $tables,
            'default_no_of_columns' => $this->defaultNoOfColumns,
        ]);
    }
}
