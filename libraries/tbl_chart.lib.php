<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * functions for displaying chart
 *
 * @usedby  tbl_chart.php
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

require_once 'libraries/Template.class.php';
use PMA\Template;

/**
 * Function to get html for displaying table chart
 *
 * @param string $url_query            url query
 * @param array  $url_params           url parameters
 * @param array  $keys                 keys
 * @param array  $fields_meta          fields meta
 * @param array  $numeric_types        numeric types
 * @param int    $numeric_column_count numeric column count
 * @param string $sql_query            sql query
 *
 * @return string
 */
function PMA_getHtmlForTableChartDisplay($url_query, $url_params, $keys,
    $fields_meta, $numeric_types, $numeric_column_count, $sql_query
) {
       return Template::get('tbl_chart')->render(array(
           'url_query' => $url_query,
           'url_params' => $url_params,
           'keys' => $keys,
           'fields_meta' => $fields_meta,
           'numeric_types' => $numeric_types,
           'numeric_column_count' => $numeric_column_count,
           'sql_query' => $sql_query
       ));
}
