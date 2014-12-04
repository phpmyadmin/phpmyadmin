<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Print view of a database
 *
 * @package PhpMyAdmin
 */

/**
 *
 */
require_once 'libraries/common.inc.php';
require_once 'libraries/db_printview.lib.php';

$response = PMA_Response::getInstance();
$header   = $response->getHeader();
$header->enablePrintView();

PMA_Util::checkParameters(array('db'));

/**
 * Defines the url to return to in case of error in a sql statement
 */
$err_url = 'db_sql.php' . PMA_URL_getCommon(array('db' => $db));

/**
 * Settings for relations stuff
 */
$cfgRelation = PMA_getRelationsParam();

/**
 * If there is at least one table, displays the printer friendly view, else
 * an error message
 */
$tables = $GLOBALS['dbi']->getTablesFull($db);
$num_tables = count($tables);

echo '<br />';

// 1. No table
if ($num_tables == 0) {
    echo __('No tables found in database.');
} else {
    // 2. Shows table information
    echo '<table>';
    echo '<thead>';
    echo '<tr>';
    echo '<th>' . __('Table') . '</th>';
    echo '<th>' . __('Rows') . '</th>';
    echo '<th>' . __('Type') . '</th>';
    if ($cfg['ShowStats']) {
        echo '<th>' . __('Size') . '</th>';
    }
    echo '<th>' . __('Comments') . '</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    $sum_entries = $sum_size = 0;
    $odd_row = true;
    foreach ($tables as $sts_data) {
        if (PMA_Table::isMerge($db, $sts_data['TABLE_NAME'])
            || /*overload*/mb_strtoupper($sts_data['ENGINE']) == 'FEDERATED'
        ) {
            $merged_size = true;
        } else {
            $merged_size = false;
        }
        $sum_entries += $sts_data['TABLE_ROWS'];
        echo '<tr class="' .  ($odd_row ? 'odd' : 'even') . '">';
        echo '<th>';
        echo htmlspecialchars($sts_data['TABLE_NAME']);
        echo '</th>';

        if (isset($sts_data['TABLE_ROWS'])) {
            echo '<td class="right">';
            if ($merged_size) {
                echo '<i>';
                echo PMA_Util::formatNumber($sts_data['TABLE_ROWS'], 0);
                echo '</i>';
            } else {
                echo PMA_Util::formatNumber($sts_data['TABLE_ROWS'], 0);
            }
            echo '</td>';
            echo '<td class="nowrap">';
            echo $sts_data['ENGINE'];
            echo '</td>';
            if ($cfg['ShowStats']) {
                $tblsize =  $sts_data['Data_length'] + $sts_data['Index_length'];
                $sum_size += $tblsize;
                list($formated_size, $unit)
                    =  PMA_Util::formatByteDown($tblsize, 3, 1);
                echo '<td class="right nowrap">';
                echo $formated_size . ' ' . $unit;
                echo '</td>';
            } // end if
        } else {
            echo '<td colspan="3" class="center">';
            if (! PMA_Table::isView($db, $sts_data['TABLE_NAME'])) {
                echo __('in use');
            }
            echo '</td>';
        }
        echo '<td>';
        if (! empty($sts_data['Comment'])) {
            echo htmlspecialchars($sts_data['Comment']);
            $needs_break = '<br />';
        } else {
            $needs_break = '';
        }

        if (! empty($sts_data['Create_time'])
            || ! empty($sts_data['Update_time'])
            || ! empty($sts_data['Check_time'])
        ) {
            echo $needs_break;
            echo '<table width="100%">';

            if (! empty($sts_data['Create_time'])) {
                echo PMA_getHtmlForOneDate(
                    __('Creation:'),
                    $sts_data['Create_time']
                );
            }

            if (! empty($sts_data['Update_time'])) {
                echo PMA_getHtmlForOneDate(
                    __('Last update:'),
                    $sts_data['Update_time']
                );
            }

            if (! empty($sts_data['Check_time'])) {
                echo PMA_getHtmlForOneDate(
                    __('Last check:'),
                    $sts_data['Check_time']
                );
            }
            echo '</table>';
        }
        echo '</td>';
        echo '</tr>';
    }
    echo '<tr>';
    echo '<th class="center">';
    printf(
        _ngettext('%s table', '%s tables', $num_tables),
        PMA_Util::formatNumber($num_tables, 0)
    );
    echo '</th>';
    echo '<th class="right nowrap">';
    echo PMA_Util::formatNumber($sum_entries, 0);
    echo '</th>';
    echo '<th class="center">';
    echo '--';
    echo '</th>';
    if ($cfg['ShowStats']) {
        list($sum_formated, $unit)
            = PMA_Util::formatByteDown($sum_size, 3, 1);
        echo '<th class="right nowrap">';
        echo $sum_formated . ' ' . $unit;
        echo '</th>';
    }
    echo '<th></th>';
    echo '</tr>';
    echo '</tbody>';
    echo '</table>';
}

/**
 * Displays the footer
 */
echo PMA_Util::getButton();

echo "<div id='PMA_disable_floating_menubar'></div>\n";
?>
