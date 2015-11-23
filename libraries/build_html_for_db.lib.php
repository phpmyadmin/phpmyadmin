<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * HTML generator for database listing
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Prepares the $column_order array
 *
 * @return array
 */
function PMA_getColumnOrder()
{
    $column_order = array();
    $column_order['DEFAULT_COLLATION_NAME'] = array(
        'disp_name' => __('Collation'),
        'description_function' => 'PMA_getCollationDescr',
        'format'    => 'string',
        'footer'    => PMA_getServerCollation(),
    );
    $column_order['SCHEMA_TABLES'] = array(
        'disp_name' => __('Tables'),
        'format'    => 'number',
        'footer'    => 0,
    );
    $column_order['SCHEMA_TABLE_ROWS'] = array(
        'disp_name' => __('Rows'),
        'format'    => 'number',
        'footer'    => 0,
    );
    $column_order['SCHEMA_DATA_LENGTH'] = array(
        'disp_name' => __('Data'),
        'format'    => 'byte',
        'footer'    => 0,
    );
    $column_order['SCHEMA_INDEX_LENGTH'] = array(
        'disp_name' => __('Indexes'),
        'format'    => 'byte',
        'footer'    => 0,
    );
    $column_order['SCHEMA_LENGTH'] = array(
        'disp_name' => __('Total'),
        'format'    => 'byte',
        'footer'    => 0,
    );
    // At this point we were preparing the display of Overhead using DATA_FREE
    // but its content does not represent the real overhead in the case
    // of InnoDB

    return $column_order;
}

/**
 * Builds the HTML td elements for one database to display in the list
 * of databases from server_databases.php (which can be modified by
 * db_create.php)
 *
 * @param array   $current           current database
 * @param boolean $is_superuser      user status
 * @param string  $url_query         url query
 * @param array   $column_order      column order
 * @param array   $replication_types replication types
 * @param array   $replication_info  replication info
 * @param string  $tr_class             HTMl class for the row
 *
 * @return array $column_order, $out
 */
function PMA_buildHtmlForDb(
    $current, $is_superuser, $url_query, $column_order,
    $replication_types, $replication_info, $tr_class = ''
) {
    $master_replication = $slave_replication = '';
    foreach ($replication_types as $type) {
        if ($replication_info[$type]['status']) {
            $out = '';
            $key = array_search(
                $current["SCHEMA_NAME"],
                $replication_info[$type]['Ignore_DB']
            );
            if (/*overload*/mb_strlen($key) > 0) {
                $out = PMA\libraries\Util::getIcon(
                    's_cancel.png',
                    __('Not replicated')
                );
            } else {
                $key = array_search(
                    $current["SCHEMA_NAME"], $replication_info[$type]['Do_DB']
                );

                if (/*overload*/mb_strlen($key) > 0
                    || (isset($replication_info[$type]['Do_DB'][0])
                        && $replication_info[$type]['Do_DB'][0] == ""
                        && count($replication_info[$type]['Do_DB']) == 1)
                ) {
                    // if ($key != null) did not work for index "0"
                    $out = PMA\libraries\Util::getIcon(
                        's_success.png',
                        __('Replicated')
                    );
                }
            }

            if ($type == 'master') {
                $master_replication = $out;
            } elseif ($type == 'slave') {
                $slave_replication = $out;
            }
        }
    }

    return PMA\libraries\Template::get('server/databases/table_row')->render(
        array(
            'current' => $current,
            'tr_class' => $tr_class,
            'url_query' => $url_query,
            'column_order' => $column_order,
            'master_replication_status' => $GLOBALS['replication_info']['master']['status'],
            'master_replication' => $master_replication,
            'slave_replication_status' => $GLOBALS['replication_info']['slave']['status'],
            'slave_replication' => $slave_replication,
        )
    );
}
