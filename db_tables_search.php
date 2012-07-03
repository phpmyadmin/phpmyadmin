<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * DB search optimisation
 *
 * @package PhpMyAdmin
 */

require_once 'libraries/common.inc.php';
require_once 'libraries/CommonFunctions.class.php';

$db = $_GET['db'];
$table_term = $_GET['table'];
$common_functions = PMA_CommonFunctions::getInstance();
$common_url_query = PMA_generate_common_url($GLOBALS['db']);
$tables_full = $common_functions->getTableList($db);
$tables_response = array();

foreach ($tables_full as $key => $table) {
    if (strpos($key, $table_term) !== false) {
        $link = '<li class="ajax_table"><a class="tableicon" title="'
            . htmlspecialchars($link_title)
            . ': ' . htmlspecialchars($table['Comment'])
            . ' ('
            . $common_functions->formatNumber($table['Rows'], 0)
            . ' ' . __('Rows') . ')"' . ' id="quick_'
            . htmlspecialchars($table_db . '.' . $table['Name']) . '"'
            . ' href="' . $GLOBALS['cfg']['LeftDefaultTabTable'] . '?'
            . $common_url_query
            . '&amp;table=' . urlencode($table['Name'])
            . '&amp;goto=' . $GLOBALS['cfg']['LeftDefaultTabTable']
            . '" >';
        $attr = array(
            'id' => 'icon_' . htmlspecialchars($table_db . '.' . $table['Name'])
        );
        if (PMA_Table::isView($table_db, $table['Name'])) {
            $link .= $common_functions->getImage(
                's_views.png', htmlspecialchars($link_title), $attr
            );
        } else {
            $link .= $common_functions->getImage(
                'b_browse.png', htmlspecialchars($link_title), $attr
            );
        }
        $link .= '</a>';
        // link for the table name itself
        $href = $GLOBALS['cfg']['DefaultTabTable'] . '?'
                . $common_url_query . '&amp;table='
                . urlencode($table['Name']) . '&amp;pos=0';
        $link .= '<a href="' . $href . '" title="'
            . htmlspecialchars(
                $common_functions->getTitleForTarget(
                    $GLOBALS['cfg']['DefaultTabTable']
                )
                . ': ' . $table['Comment']
                . ' (' .
                $common_functions->formatNumber($table['Rows'], 0)
                . ' ' . __('Rows') . ')'
            )
            . '" id="' . htmlspecialchars($table_db . '.' . $table['Name'])
            . '">'
            // preserve spaces in table name
            . str_replace(' ', '&nbsp;', htmlspecialchars($table['disp_name']))
            . '</a>';
        $link .= '</li>' . "\n";
        $table['line'] = $link;
        $tables_response[] = $table;
    }
}

$response = PMA_Response::getInstance();
$response->addJSON('tables', $tables_response);
?>
