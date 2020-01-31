<?php
/**
 * Handles DB Multi-table query
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin\Database;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\ParseAnalyze;
use PhpMyAdmin\Sql;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;

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
     * @var Template
     */
    public $template;

    /**
     * Constructor
     *
     * @param DatabaseInterface $dbi                DatabaseInterface instance
     * @param Template          $template           Template instance
     * @param string            $dbName             Database name
     * @param integer           $defaultNoOfColumns Default number of columns
     */
    public function __construct(
        DatabaseInterface $dbi,
        Template $template,
        $dbName,
        $defaultNoOfColumns = 3
    ) {
        $this->dbi = $dbi;
        $this->db = $dbName;
        $this->defaultNoOfColumns = $defaultNoOfColumns;

        $this->template = $template;

        $this->tables = $this->dbi->getTables($this->db);
    }

    /**
     * Get Multi-Table query page HTML
     *
     * @return string Multi-Table query page HTML
     */
    public function getFormHtml()
    {
        global $route;

        $tables = [];
        foreach ($this->tables as $table) {
            $tables[$table]['hash'] = md5($table);
            $tables[$table]['columns'] = array_keys(
                $this->dbi->getColumns($this->db, $table)
            );
        }
        return $this->template->render('database/multi_table_query/form', [
            'db' => $this->db,
            'tables' => $tables,
            'default_no_of_columns' => $this->defaultNoOfColumns,
            'route' => $route,
        ]);
    }

    /**
     * Displays multi-table query results
     *
     * @param string $sqlQuery      The query to parse
     * @param string $db            The current database
     * @param string $pmaThemeImage Uri of the PMA theme image
     *
     * @return void
     */
    public static function displayResults($sqlQuery, $db, $pmaThemeImage)
    {
        list(,$db,) = ParseAnalyze::sqlQuery($sqlQuery, $db);

        $goto = Url::getFromRoute('/database/multi_table_query');
        $sql = new Sql();
        $sql->executeQueryAndSendQueryResponse(
            null, // analyzed_sql_results
            false, // is_gotofile
            $db, // db
            null, // table
            null, // find_real_end
            null, // sql_query_for_bookmark - see below
            null, // extra_data
            null, // message_to_show
            null, // message
            null, // sql_data
            $goto, // goto
            $pmaThemeImage, // pmaThemeImage
            null, // disp_query
            null, // disp_message
            null, // query_type
            $sqlQuery, // sql_query
            null, // selectedTables
            null // complete_query
        );
    }
}
