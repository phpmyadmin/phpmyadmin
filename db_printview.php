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
 * Gets the variables sent or posted to this script, then displays headers
 */
$print_view = true;
require_once './libraries/header.inc.php';

PMA_checkParameters(array('db'));

/**
 * Defines the url to return to in case of error in a sql statement
 */
$err_url = 'db_sql.php?' . PMA_generate_common_url($db);

/**
 * Settings for relations stuff
 */
require_once './libraries/relation.lib.php';
$cfgRelation = PMA_getRelationsParam();

/**
 * Gets the list of the table in the current db and informations about these
 * tables if possible
 *
 * @todo merge this speedup _optionaly_ into PMA_DBI_get_tables_full()
 *
// staybyte: speedup view on locked tables - 11 June 2001
// Special speedup for newer MySQL Versions (in 4.0 format changed)
if ($cfg['SkipLockedTables'] == true) {
    $result = PMA_DBI_query('SHOW OPEN TABLES FROM ' . PMA_backquote($db) . ';');
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
            $result      = PMA_DBI_query('SHOW TABLES FROM ' . PMA_backquote($db) . ';', null, PMA_DBI_QUERY_STORE);
            if ($result != false && PMA_DBI_num_rows($result) > 0) {
                while ($tmp = PMA_DBI_fetch_row($result)) {
                    if (!isset($sot_cache[$tmp[0]])) {
                        $sts_result  = PMA_DBI_query('SHOW TABLE STATUS FROM ' . PMA_backquote($db) . ' LIKE \'' . addslashes($tmp[0]) . '\';');
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
    $result      = PMA_DBI_query('SHOW TABLE STATUS FROM ' . PMA_backquote($db) . ';');
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
    echo $strNoTablesFound;
}
// 2. Shows table informations on mysql >= 3.23.03 - staybyte - 11 June 2001
else {
    ?>
<table>
<thead>
<tr>
    <th><?php echo $strTable; ?></th>
    <th><?php echo $strRecords; ?></th>
    <th><?php echo $strType; ?></th>
    <?php
    if ($cfg['ShowStats']) {
        echo '<th>' . $strSize . '</th>';
    }
    ?>
    <th><?php echo $strComments; ?></th>
</tr>
</thead>
<tbody>
    <?php
    $sum_entries = $sum_size = 0;
    $odd_row = true;
    foreach ($tables as $sts_data) {
        if (strtoupper($sts_data['ENGINE']) == 'MRG_MYISAM'
         || strtoupper($sts_data['ENGINE']) == 'FEDERATED') {
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
    <td align="right">
            <?php
            if ($merged_size) {
                echo '<i>' . PMA_formatNumber($sts_data['TABLE_ROWS'], 0) . '</i>' . "\n";
            } else {
                echo PMA_formatNumber($sts_data['TABLE_ROWS'], 0) . "\n";
            }
            ?>
    </td>
    <td nowrap="nowrap">
        <?php echo $sts_data['ENGINE']; ?>
    </td>
            <?php
            if ($cfg['ShowStats']) {
                $tblsize =  $sts_data['Data_length'] + $sts_data['Index_length'];
                $sum_size += $tblsize;
                list($formated_size, $unit) =  PMA_formatByteDown($tblsize, 3, 1);
                ?>
    <td align="right" nowrap="nowrap">
        <?php echo $formated_size . ' ' . $unit; ?>
    </td>
                <?php
            } // end if
        } else {
            ?>
    <td colspan="3" align="center">
        <?php echo $strInUse; ?>
    </td>
            <?php
        }
        ?>
    <td>
        <?php
        if (! empty($sts_data['Comment'])) {
            echo $sts_data['Comment'];
            $needs_break = '<br />';
        } else {
            $needs_break = '';
        }

        if (! empty($sts_data['Create_time'])
         || ! empty($sts_data['Update_time'])
         || ! empty($sts_data['Check_time'])) {
            echo $needs_break;
            ?>
            <table width="100%">
            <?php

            if (! empty($sts_data['Create_time'])) {
                ?>
                <tr>
                    <td align="right"><?php echo $strStatCreateTime . ': '; ?></td>
                    <td align="right"><?php echo PMA_localisedDate(strtotime($sts_data['Create_time'])); ?></td>
                </tr>
                <?php
            }

            if (! empty($sts_data['Update_time'])) {
                ?>
                <tr>
                    <td align="right"><?php echo $strStatUpdateTime . ': '; ?></td>
                    <td align="right"><?php echo PMA_localisedDate(strtotime($sts_data['Update_time'])); ?></td>
                </tr>
                <?php
            }

            if (! empty($sts_data['Check_time'])) {
                ?>
                <tr>
                    <td align="right"><?php echo $strStatCheckTime . ': '; ?></td>
                    <td align="right"><?php echo PMA_localisedDate(strtotime($sts_data['Check_time'])); ?></td>
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
    <th align="center">
        <?php echo sprintf($strTables, PMA_formatNumber($num_tables, 0)); ?>
    </th>
    <th align="right" nowrap="nowrap">
        <?php echo PMA_formatNumber($sum_entries, 0); ?>
    </th>
    <th align="center">
        --
    </th>
    <?php
    if ($cfg['ShowStats']) {
        list($sum_formated, $unit) = PMA_formatByteDown($sum_size, 3, 1);
        ?>
    <th align="right" nowrap="nowrap">
        <?php echo $sum_formated . ' ' . $unit; ?>
    </th>
        <?php
    }
    ?>
    <th>&nbsp;</th>
</tr>
</tbody>
</table>
    <?php
}

/**
 * Displays the footer
 */
?>

<script type="text/javascript">
//<![CDATA[
function printPage()
{
    // Do print the page
    if (typeof(window.print) != 'undefined') {
        window.print();
    }
}
//]]>
</script>
<br /><br />

<input type="button" class="print_ignore"
    id="print" value="<?php echo $strPrint; ?>" onclick="printPage()" />

<?php
require_once './libraries/footer.inc.php';
?>
