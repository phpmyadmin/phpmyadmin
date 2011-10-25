<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
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
    $column_order['SCHEMA_DATA_FREE'] = array(
        'disp_name' => __('Overhead'),
        'format'    => 'byte',
        'footer'    => 0,
    );

    return $column_order;
}

/*
 * Builds the HTML td elements for one database to display in the list
 * of databases from server_databases.php (which can be modified by
 * db_create.php)
 *
 * @param array $current
 * @param boolean $is_superuser
 * @param string $checkall
 * @param string $url_query
 * @param array $column_order
 * @param array $replication_types
 * @param array $replication_info
 *
 * @return array $column_order, $out
 */
function PMA_buildHtmlForDb($current, $is_superuser, $checkall, $url_query, $column_order, $replication_types, $replication_info)
{

    $out = '';
    if ($is_superuser || $GLOBALS['cfg']['AllowUserDropDatabase']) {
        $out .= '<td class="tool">';
        $out .= '<input type="checkbox" name="selected_dbs[]" title="' . htmlspecialchars($current['SCHEMA_NAME']) . '" value="' . htmlspecialchars($current['SCHEMA_NAME']) . '" ';

        if (!PMA_is_system_schema($current['SCHEMA_NAME'], true)) {
            $out .= (empty($checkall) ? '' : 'checked="checked" ') . '/>';
        } else {
            $out .= ' disabled="disabled" />';
        }
        $out .= '</td>';
    }
    $out .= '<td class="name">'
           . '        <a onclick="'
           . 'if (window.parent.openDb &amp;&amp; window.parent.openDb(\'' . PMA_jsFormat($current['SCHEMA_NAME'], false) . '\')) return false;'
           . '" href="index.php?' . $url_query . '&amp;db='
           . urlencode($current['SCHEMA_NAME']) . '" title="'
           . sprintf(__('Jump to database'), htmlspecialchars($current['SCHEMA_NAME']))
           . '" target="_parent">'
           . ' ' . htmlspecialchars($current['SCHEMA_NAME'])
           . '</a>'
           . '</td>';

    foreach ($column_order as $stat_name => $stat) {
        if (array_key_exists($stat_name, $current)) {
            if (is_numeric($stat['footer'])) {
                $column_order[$stat_name]['footer'] += $current[$stat_name];
            }
            if ($stat['format'] === 'byte') {
                list($value, $unit) = PMA_formatByteDown($current[$stat_name], 3, 1);
            } elseif ($stat['format'] === 'number') {
                $value = PMA_formatNumber($current[$stat_name], 0);
            } else {
                $value = htmlentities($current[$stat_name], 0);
            }
            $out .= '<td class="value">';
            if (isset($stat['description_function'])) {
                $out .= '<dfn title="' . $stat['description_function']($current[$stat_name]) . '">';
            }
            $out .= $value;
            if (isset($stat['description_function'])) {
                $out .= '</dfn>';
            }
            $out .= '</td>';
            if ($stat['format'] === 'byte') {
                $out .= '<td class="unit">' . $unit . '</td>';
            }
        }
    }
    foreach ($replication_types as $type) {
        if ($replication_info[$type]['status']) {
            $out .= '<td class="tool" style="text-align: center;">';

            if (strlen(array_search($current["SCHEMA_NAME"], $replication_info[$type]['Ignore_DB'])) > 0) {
                $out .= PMA_getIcon('s_cancel.png',  __('Not replicated'));
            } else {
                $key = array_search($current["SCHEMA_NAME"], $replication_info[$type]['Do_DB']);

                if (strlen($key) > 0 || ($replication_info[$type]['Do_DB'][0] == "" && count($replication_info[$type]['Do_DB']) == 1)) {
                    // if ($key != null) did not work for index "0"
                    $out .= PMA_getIcon('s_success.png', __('Replicated'));
                }
            }

            $out .= '</td>';
        }
    }

    if ($is_superuser && !PMA_DRIZZLE) {
        $out .= '<td class="tool">'
               . '<a onclick="'
               . 'if (window.parent.setDb) window.parent.setDb(\'' . PMA_jsFormat($current['SCHEMA_NAME']) . '\');'
               . '" href="./server_privileges.php?' . $url_query
               . '&amp;checkprivs=' . urlencode($current['SCHEMA_NAME'])
               . '" title="' . sprintf(__('Check privileges for database &quot;%s&quot;.'), htmlspecialchars($current['SCHEMA_NAME']))
               . '">'
               . ' '
               . PMA_getIcon('s_rights.png', __('Check Privileges'))
               . '</a></td>';
    }
    return array($column_order, $out);
}
?>
