<?php
/**
 * Parse and analyse a SQL query
 */

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\SqlParser\Utils\Query;
use function count;
use function strcasecmp;

/**
 * PhpMyAdmin\ParseAnalyze class
 */
class ParseAnalyze
{
    /**
     * Calls the parser on a query
     *
     * @param string $sql_query the query to parse
     * @param string $db        the current database
     *
     * @return array
     *
     * @access public
     */
    public static function sqlQuery($sql_query, $db)
    {
        // @todo: move to returned results (also in all the calling chain)
        $GLOBALS['unparsed_sql'] = $sql_query;

        // Get details about the SQL query.
        $analyzed_sql_results = Query::getAll($sql_query);

        $table = '';

        // If the targeted table (and database) are different than the ones that is
        // currently browsed, edit `$db` and `$table` to match them so other elements
        // (page headers, links, navigation panel) can be updated properly.
        if (! empty($analyzed_sql_results['select_tables'])) {
            // Previous table and database name is stored to check if it changed.
            $prev_db = $db;

            if (count($analyzed_sql_results['select_tables']) > 1) {

                /**
                 * @todo if there are more than one table name in the Select:
                 * - do not extract the first table name
                 * - do not show a table name in the page header
                 * - do not display the sub-pages links)
                 */
                $table = '';
            } else {
                $table = $analyzed_sql_results['select_tables'][0][0];
                if (! empty($analyzed_sql_results['select_tables'][0][1])) {
                    $db = $analyzed_sql_results['select_tables'][0][1];
                }
            }
            // There is no point checking if a reload is required if we already decided
            // to reload. Also, no reload is required for AJAX requests.
            $response = Response::getInstance();
            if (empty($analyzed_sql_results['reload']) && ! $response->isAjax()) {
                // NOTE: Database names are case-insensitive.
                $analyzed_sql_results['reload'] = strcasecmp($db, $prev_db) != 0;
            }
        }

        return [
            $analyzed_sql_results,
            $db,
            $table,
        ];
    }
}
