<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package PhpMyAdmin
 */

/**
 *
 */
require_once 'libraries/common.inc.php';

$response = PMA_Response::getInstance();
$header   = $response->getHeader();
$header->enablePrintView();

PMA_Util::checkParameters(array('db'));

/**
 * Defines the url to return to in case of error in a sql statement
 */
$err_url = 'db_sql.php?' . PMA_generate_common_url($db);

/**
 * Settings for relations stuff
 */
$cfgRelation = PMA_getRelationsParam();

/**
 * Gets the list of the table in the current db and informations about these
 * tables if possible
 *
 * @todo merge this speedup _optionaly_ into PMA_DBI_get_tables_full()
 *
// speedup view on locked tables
// Special speedup for newer MySQL Versions (in 4.0 format changed)
if ($cfg['SkipLockedTables'] == true) {
    $result = PMA_DBI_query('SHOW OPEN TABLES FROM ' . PMA_Util::backquote($db) . ';');
    // Blending out tables in use
    if ($result != false && PMA_DBI_num_rows($result) > 0) {
        while ($tmp = PMA_DBI_fetch_row($result)) {
            // if in use memorize tablename
            if (preg_match('@in_use=[1-9]+@i', $tmp[0])) {
                $sot_cache[$tmp[0]] = true;
            }
        }
        PMA_DBI_free_result($result);

        if (isset($sot_cache)) {
            $result      = PMA_DBI_query('SHOW TABLES FROM ' . PMA_Util::backquote($db) . ';', null, PMA_DBI_QUERY_STORE);
            if ($result != false && PMA_DBI_num_rows($result) > 0) {
                while ($tmp = PMA_DBI_fetch_row($result)) {
                    if (! isset($sot_cache[$tmp[0]])) {
                        $sts_result  = PMA_DBI_query('SHOW TABLE STATUS FROM ' . PMA_Util::backquote($db) . ' LIKE \'' . sqlAddSlashes($tmp[0], true) . '\';');
                        $sts_tmp     = PMA_DBI_fetch_assoc($sts_result);
                        $tables[]    = $sts_tmp;
                    } else { // table in use
                        $tables[]    = array('Name' => $tmp[0]);
                    }
                }
                PMA_DBI_free_result($result);
                $sot_ready = true;
            }
        }
        unset($tmp, $result);
    }
}

if (! isset($sot_ready)) {
    $result      = PMA_DBI_query('SHOW TABLE STATUS FROM ' . PMA_Util::backquote($db) . ';');
    if (PMA_DBI_num_rows($result) > 0) {
        while ($sts_tmp = PMA_DBI_fetch_assoc($result)) {
            $tables[] = $sts_tmp;
        }
        PMA_DBI_free_result($result);
        unset($res);
    }
}
 */

/**
 * If there is at least one table, displays the printer friendly view, else
 * an error message
 */
$tables = PMA_DBI_get_tables_full($db);
$num_tables = count($tables);

echo '<br />';

// 1. No table
if ($num_tables == 0) {
    echo __('No tables found in database.');
} else {
// 2. Shows table information
    ?>
<table>
<thead>
<tr>
    <th><?php echo __('Table'); ?></th>
    <th><?php echo __('Rows'); ?></th>
    <th><?php echo __('Type'); ?></th>
    <?php
    if ($cfg['ShowStats']) {
        echo '<th>' . __('Size') . '</th>';
    }
    ?>
    <th><?php echo __('Comments'); ?></th>
</tr>
</thead>
<tbody>
    <?php
    $sum_entries = $sum_size = 0;
    $odd_row = true;
    foreach ($tables as $sts_data) {
        if (PMA_Table::isMerge($db, $sts_data['TABLE_NAME'])
            || strtoupper($sts_data['ENGINE']) == 'FEDERATED'
        ) {
            $merged_size = true;
        } else {
            $merged_size = false;
        }
        $sum_entries += $sts_data['TABLE_ROWS'];
        ?>
<tr class="<?php echo $odd_row ? 'odd' : 'even'; ?>">
    <th>
        <?php echo htmlspecialchars($sts_data['TABLE_NAME']); ?>
    </th>
        <?php

        if (isset($sts_data['TABLE_ROWS'])) {
            ?>
    <td class="right">
            <?php
            if ($merged_size) {
                echo '<i>' . PMA_Util::formatNumber($sts_data['TABLE_ROWS'], 0) . '</i>' . "\n";
            } else {
                echo PMA_Util::formatNumber($sts_data['TABLE_ROWS'], 0) . "\n";
            }
            ?>
    </td>
    <td class="nowrap">
        <?php echo $sts_data['ENGINE']; ?>
    </td>
            <?php
            if ($cfg['ShowStats']) {
                $tblsize =  $sts_data['Data_length'] + $sts_data['Index_length'];
                $sum_size += $tblsize;
                list($formated_size, $unit)
                    =  PMA_Util::formatByteDown($tblsize, 3, 1);
                ?>
    <td class="right nowrap">
        <?php echo $formated_size . ' ' . $unit; ?>
    </td>
                <?php
            } // end if
        } else {
            ?>
    <td colspan="3" class="center">
        <?php echo __('in use'); ?>
    </td>
            <?php
        }
        ?>
    <td>
        <?php
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
            ?>
            <table width="100%">
            <?php

            if (! empty($sts_data['Create_time'])) {
                ?>
                <tr>
                    <td class="right"><?php echo __('Creation') . ': '; ?></td>
                    <td class="right"><?php echo PMA_Util::localisedDate(strtotime($sts_data['Create_time'])); ?></td>
                </tr>
                <?php
            }

            if (! empty($sts_data['Update_time'])) {
                ?>
                <tr>
                    <td class="right"><?php echo __('Last update') . ': '; ?></td>
                    <td class="right"><?php echo PMA_Util::localisedDate(strtotime($sts_data['Update_time'])); ?></td>
                </tr>
                <?php
            }

            if (! empty($sts_data['Check_time'])) {
                ?>
                <tr>
                    <td class="right"><?php echo __('Last check') . ': '; ?></td>
                    <td class="right"><?php echo PMA_Util::localisedDate(strtotime($sts_data['Check_time'])); ?></td>
                </tr>
                <?php
            }
            ?>
            </table>
            <?php
        }
        ?>
    </td>
</tr>
        <?php
    }
    ?>
<tr>
    <th class="center">
        <?php echo sprintf(_ngettext('%s table', '%s tables', $num_tables), PMA_Util::formatNumber($num_tables, 0)); ?>
    </th>
    <th class="right nowrap">
        <?php echo PMA_Util::formatNumber($sum_entries, 0); ?>
    </th>
    <th class="center">
        --
    </th>
    <?php
    if ($cfg['ShowStats']) {
        list($sum_formated, $unit)
            = PMA_Util::formatByteDown($sum_size, 3, 1);
        ?>
    <th class="right nowrap">
        <?php echo $sum_formated . ' ' . $unit; ?>
    </th>
        <?php
    }
    ?>
    <th></th>
</tr>
</tbody>
</table>
    <?php
}

/**
 * Displays the footer
 */
echo PMA_Util::getButton();

echo "<div id='PMA_disable_floating_menubar'></div>\n";
?>
