<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id$
 */

/**
 *
 */
require_once './libraries/common.inc.php';

/**
 * Gets tables informations and displays top links
 */
require_once './libraries/tbl_common.php';
$url_query .= '&amp;goto=tbl_export.php&amp;back=tbl_export.php';
require_once './libraries/tbl_info.inc.php';

// Dump of a table

$export_page_title = $strViewDump;

// When we have some query, we need to remove LIMIT from that and possibly
// generate WHERE clause (if we are asked to export specific rows)

if (! empty($sql_query)) {
    // Parse query so we can work with tokens
    $parsed_sql = PMA_SQP_parse($sql_query);

    // Need to generate WHERE clause?
    if (isset($primary_key)) {
        // Yes => rebuild query from scracts, this doesn't work with nested
        // selects :-(
        $analyzed_sql = PMA_SQP_analyze($parsed_sql);
        $sql_query = 'SELECT ';

        if (isset($analyzed_sql[0]['queryflags']['distinct'])) {
            $sql_query .= ' DISTINCT ';
        }

        $sql_query .= $analyzed_sql[0]['select_expr_clause'];

        if (!empty($analyzed_sql[0]['from_clause'])) {
            $sql_query .= ' FROM ' . $analyzed_sql[0]['from_clause'];
        }

        $wheres = array();

        if (isset($primary_key) && is_array($primary_key)
         && count($primary_key) > 0) {
            $wheres[] = '(' . implode(') OR (',$primary_key) . ')';
        }

        if (!empty($analyzed_sql[0]['where_clause']))  {
            $wheres[] = $analyzed_sql[0]['where_clause'];
        }

        if (count($wheres) > 0) {
            $sql_query .= ' WHERE (' . implode(') AND (', $wheres) . ')';
        }

        if (!empty($analyzed_sql[0]['group_by_clause'])) {
            $sql_query .= ' GROUP BY ' . $analyzed_sql[0]['group_by_clause'];
        }
        if (!empty($analyzed_sql[0]['having_clause'])) {
            $sql_query .= ' HAVING ' . $analyzed_sql[0]['having_clause'];
        }
        if (!empty($analyzed_sql[0]['order_by_clause'])) {
            $sql_query .= ' ORDER BY ' . $analyzed_sql[0]['order_by_clause'];
        }
    } else {
        // Just crop LIMIT clause
        $inside_bracket = FALSE;
        for ($i = $parsed_sql['len'] - 1; $i >= 0; $i--) {
            if ($parsed_sql[$i]['type'] == 'punct_bracket_close_round') {
                $inside_bracket = TRUE;
                continue;
            }
            if ($parsed_sql[$i]['type'] == 'punct_bracket_open_round') {
                $inside_bracket = FALSE;
                continue;
            }
            if (!$inside_bracket && $parsed_sql[$i]['type'] == 'alpha_reservedWord' && strtoupper($parsed_sql[$i]['data']) == 'LIMIT') {
                // We found LIMIT to remove

                $sql_query = '';

                // Concatenate parts before
                for ($j = 0; $j < $i; $j++) {
                    $sql_query .= $parsed_sql[$j]['data'] . ' ';
                }

                // Skip LIMIT
                $i++;
                while ($i < $parsed_sql['len'] &&
                    ($parsed_sql[$i]['type'] != 'alpha_reservedWord' ||
                    ($parsed_sql[$i]['type'] == 'alpha_reservedWord' && $parsed_sql[$i]['data'] == 'OFFSET'))) {
                    $i++;
                }

                // Add remaining parts
                while ($i < $parsed_sql['len']) {
                    $sql_query .= $parsed_sql[$i]['data'] . ' ';
                    $i++;
                }
                break;
            }
        }
    }
    $message = PMA_Message::success();
}

/**
 * Displays top menu links
 */
require './libraries/tbl_links.inc.php';

$export_type = 'table';
require_once './libraries/display_export.lib.php';


/**
 * Displays the footer
 */
require_once './libraries/footer.inc.php';
?>
