<?php
/**
 * Handles DB Multi-table query
 */

declare(strict_types=1);

namespace PhpMyAdmin\Database;

use PhpMyAdmin\Bookmarks\BookmarkRepository;
use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationCleanup;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Operations;
use PhpMyAdmin\ParseAnalyze;
use PhpMyAdmin\Sql;
use PhpMyAdmin\Template;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Url;

use function md5;
use function sprintf;

/**
 * Class to handle database Multi-table querying
 */
class MultiTableQuery
{
    public function __construct(
        private DatabaseInterface $dbi,
        public Template $template,
        private string $db,
        private int $defaultNoOfColumns = 3,
    ) {
    }

    /**
     * Get Multi-Table query page HTML
     *
     * @return string Multi-Table query page HTML
     */
    public function getFormHtml(): string
    {
        $columnsInTables = $this->dbi->query(sprintf(
            'SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.columns WHERE table_schema = %s',
            $this->dbi->quoteString($this->db),
        ));

        $tables = [];
        /** @var array{TABLE_NAME:string, COLUMN_NAME:string} $column */
        foreach ($columnsInTables as $column) {
            $table = $column['TABLE_NAME'];
            $tables[$table]['hash'] ??= md5($table);
            $tables[$table]['columns'][] = $column['COLUMN_NAME'];
        }

        return $this->template->render('database/multi_table_query/form', [
            'db' => $this->db,
            'tables' => $tables,
            'default_no_of_columns' => $this->defaultNoOfColumns,
        ]);
    }

    /**
     * Displays multi-table query results
     *
     * @param string $sqlQuery The query to parse
     * @param string $db       The current database
     */
    public static function displayResults(string $sqlQuery, string $db): string
    {
        [, $db] = ParseAnalyze::sqlQuery($sqlQuery, $db);

        $goto = Url::getFromRoute('/database/multi-table-query');

        $dbi = DatabaseInterface::getInstance();
        $relation = new Relation($dbi);
        $bookmarkRepository = new BookmarkRepository($dbi, $relation);
        $sql = new Sql(
            $dbi,
            $relation,
            new RelationCleanup($dbi, $relation),
            new Operations($dbi, $relation),
            new Transformations(),
            new Template(),
            $bookmarkRepository,
            Config::getInstance(),
        );

        return $sql->executeQueryAndSendQueryResponse(
            null,
            false, // is_gotofile
            $db, // db
            null, // table
            null, // sql_query_for_bookmark - see below
            null, // message_to_show
            null, // sql_data
            $goto, // goto
            null, // disp_query
            null, // disp_message
            $sqlQuery, // sql_query
            null, // complete_query
        );
    }
}
