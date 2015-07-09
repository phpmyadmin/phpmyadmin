<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Table export
 *
 * @package PhpMyAdmin
 */

/**
 *
 */
require_once 'libraries/common.inc.php';
require_once 'libraries/config/page_settings.class.php';
require_once 'libraries/display_export.lib.php';

PMA_PageSettings::showGroup('Export');

$response = PMA_Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('export.js');

// Get the relation settings
$cfgRelation = PMA_getRelationsParam();

// handling export template actions
if (isset($_REQUEST['templateAction']) && $cfgRelation['export_templateswork']) {

    if (isset($_REQUEST['templateId'])) {
        $templateId = $_REQUEST['templateId'];
        $id = PMA_Util::sqlAddSlashes($templateId);
    }

    $templateTable = PMA_Util::backquote($cfgRelation['db']) . '.'
       . PMA_Util::backquote($cfgRelation['export_templates']);
    $user = PMA_Util::sqlAddSlashes($GLOBALS['cfg']['Server']['user']);

    switch ($_REQUEST['templateAction']) {
    case 'create':
        $query = "INSERT INTO " . $templateTable . "("
            . " `username`, `export_type`,"
            . " `template_name`, `template_data`"
            . ") VALUES ("
            . "'" . $user . "', "
            . "'" . PMA_Util::sqlAddSlashes($_REQUEST['exportType']) . "', "
            . "'" . PMA_Util::sqlAddSlashes($_REQUEST['templateName']) . "', "
            . "'" . PMA_Util::sqlAddSlashes($_REQUEST['templateData']) . "');";
        break;
    case 'load':
        $query = "SELECT `template_data` FROM " . $templateTable
             . " WHERE `id` = " . $id  . " AND `username` = '" . $user . "'";
        break;
    case 'update':
        $query = "UPDATE " . $templateTable . " SET `template_data` = "
          . "'" . PMA_Util::sqlAddSlashes($_REQUEST['templateData']) . "'"
          . " WHERE `id` = " . $id  . " AND `username` = '" . $user . "'";
        break;
    case 'delete':
        $query = "DELETE FROM " . $templateTable
           . " WHERE `id` = " . $id  . " AND `username` = '" . $user . "'";
        break;
    default:
        break;
    }

    $result = PMA_queryAsControlUser($query, false);

    $response = PMA_Response::getInstance();
    if (! $result) {
        $error = $GLOBALS['dbi']->getError($GLOBALS['controllink']);
        $response->isSuccess(false);
        $response->addJSON('message', $error);
        exit;
    }

    $response->isSuccess(true);
    if ('create' == $_REQUEST['templateAction']) {
        $response->addJSON(
            'data',
            PMA_getOptionsForexport_templates($_REQUEST['exportType'])
        );
    } elseif ('load' == $_REQUEST['templateAction']) {
        $data = null;
        while ($row = $GLOBALS['dbi']->fetchAssoc($result, $GLOBALS['controllink'])) {
            $data = $row['template_data'];
        }
        $response->addJSON('data', $data);
    }
    $GLOBALS['dbi']->freeResult($result);
    exit;
}

/**
 * Gets tables information and displays top links
 */
require_once 'libraries/tbl_common.inc.php';
$url_query .= '&amp;goto=tbl_export.php&amp;back=tbl_export.php';
require_once 'libraries/tbl_info.inc.php';

// Dump of a table

$export_page_title = __('View dump (schema) of table');

// When we have some query, we need to remove LIMIT from that and possibly
// generate WHERE clause (if we are asked to export specific rows)

if (! empty($sql_query)) {
    // Parse query so we can work with tokens
    $parsed_sql = PMA_SQP_parse($sql_query);
    $analyzed_sql = PMA_SQP_analyze($parsed_sql);

    // Need to generate WHERE clause?
    if (isset($where_clause)) {

        // If a table alias is used, get rid of it since
        // where clauses are on real table name
        if ($analyzed_sql[0]['table_ref'][0]['table_alias']) {
            // Exporting selected rows is only allowed for queries involving
            // a single table. So we can safely assume that there is only one
            // table in 'table_ref' array.
            $temp_sql_array = preg_split('/\bfrom\b/i', $sql_query);
            $sql_query = $temp_sql_array[0] . 'FROM ';
            if (! empty($analyzed_sql[0]['table_ref'][0]['db'])) {
                $sql_query .= PMA_Util::backquote(
                    $analyzed_sql[0]['table_ref'][0]['db']
                );
                $sql_query .= '.';
            }
            $sql_query .= PMA_Util::backquote(
                $analyzed_sql[0]['table_ref'][0]['table_name']
            );
        }
        unset($temp_sql_array);

        // Regular expressions which can appear in sql query,
        // before the sql segment which remains as it is.
        $regex_array = array(
            '/\bwhere\b/i', '/\bgroup by\b/i', '/\bhaving\b/i', '/\border by\b/i'
        );

        $first_occurring_regex = PMA_Util::getFirstOccurringRegularExpression(
            $regex_array, $sql_query
        );
        unset($regex_array);

        // The part "SELECT `id`, `name` FROM `customers`"
        // is not modified by the next code segment, when exporting
        // the result set from a query such as
        // "SELECT `id`, `name` FROM `customers` WHERE id NOT IN
        //  ( SELECT id FROM companies WHERE name LIKE '%u%')"
        if (! is_null($first_occurring_regex)) {
            $temp_sql_array = preg_split($first_occurring_regex, $sql_query);
            $sql_query = $temp_sql_array[0];
        }
        unset($first_occurring_regex, $temp_sql_array);

        // Append the where clause using the primary key of each row
        if (is_array($where_clause) && (count($where_clause) > 0)) {
            $sql_query .= ' WHERE (' . implode(') OR (', $where_clause) . ')';
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
        $sql_query = $analyzed_sql[0]['section_before_limit']
            . $analyzed_sql[0]['section_after_limit'];
    }
    echo PMA_Util::getMessage(PMA_Message::success());
}

$export_type = 'table';
require_once 'libraries/display_export.inc.php';
