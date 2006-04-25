<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

require_once './libraries/common.lib.php';

/**
 * Gets the variables sent or posted to this script, then displays headers
 */
$print_view = true;
require_once './libraries/header.inc.php';


PMA_checkParameters(array('db'));

/**
 * Defines the url to return to in case of error in a sql statement
 */
$err_url = 'db_details.php?' . PMA_generate_common_url($db);

/**
 * Settings for relations stuff
 */
require_once './libraries/relation.lib.php';
$cfgRelation = PMA_getRelationsParam();

/**
 * Gets the list of the table in the current db and informations about these
 * tables if possible
 */
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
        unset($result);

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
                unset($result);
                $sot_ready = true;
            }
        }
    }
}

if (!isset($sot_ready)) {
    $result      = PMA_DBI_query('SHOW TABLE STATUS FROM ' . PMA_backquote($db) . ';');
    if (PMA_DBI_num_rows($result) > 0) {
        while ($sts_tmp = PMA_DBI_fetch_assoc($result)) {
            $tables[] = $sts_tmp;
        }
        PMA_DBI_free_result($result);
        unset($res);
    }
}
$num_tables = isset($tables) ? count($tables) : 0;

if ($cfgRelation['commwork']) {
    $comment = PMA_getComments($db);

    /**
     * Displays DB comment
     */
    if (is_array($comment)) {
        ?>
    <!-- DB comment -->
    <p><i>
        <?php echo htmlspecialchars(implode(' ', $comment)); ?>
    </i></p>
        <?php
    } // end if
}

/**
 * If there is at least one table, displays the printer friendly view, else
 * an error message
 */
// 1. No table
if ($num_tables == 0) {
    echo $strNoTablesFound;
}
// 2. Shows table informations on mysql >= 3.23.03 - staybyte - 11 June 2001
else {
    ?>

<!-- The tables list -->
<table border="<?php echo $cfg['Border']; ?>">
<thead>
<tr>
    <th>&nbsp;<?php echo $strTable; ?>&nbsp;</th>
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
    $i = $sum_entries = $sum_size = 0;
    $odd_row = true;
    foreach ($tables as $keyname => $sts_data) {
        $table     = $sts_data['Name'];
        ?>
<tr class="<?php echo $odd_row ? 'odd' : 'even'; ?>">
    <td nowrap="nowrap">
        &nbsp;<b><?php echo htmlspecialchars($table); ?>&nbsp;</b>&nbsp;
    </td>
        <?php
        $mergetable         = false;
        $nonisam            = false;
        if (isset($sts_data['Type'])) {
            if ($sts_data['Type'] == 'MRG_MyISAM') {
                $mergetable = true;
            } elseif (!preg_match('@ISAM|HEAP@i', $sts_data['Type'])) {
                $nonisam    = true;
            }
        }

        if (isset($sts_data['Rows'])) {
            if ($mergetable == false) {
                if ($cfg['ShowStats'] && $nonisam == false) {
                    $tblsize                        =  $sts_data['Data_length'] + $sts_data['Index_length'];
                    $sum_size                       += $tblsize;
                    if ($tblsize > 0) {
                        list($formated_size, $unit) =  PMA_formatByteDown($tblsize, 3, 1);
                    } else {
                        list($formated_size, $unit) =  PMA_formatByteDown($tblsize, 3, 0);
                    }
                } elseif ($cfg['ShowStats']) {
                    $formated_size                  = '&nbsp;-&nbsp;';
                    $unit                           = '';
                }
                $sum_entries                        += $sts_data['Rows'];
            }
            // MyISAM MERGE Table
            elseif ($cfg['ShowStats'] && $mergetable == true) {
                $formated_size = '&nbsp;-&nbsp;';
                $unit          = '';
            } elseif ($cfg['ShowStats']) {
                $formated_size = 'unknown';
                $unit          = '';
            }
            ?>
    <td align="right">
            <?php
            if ($mergetable == true) {
                echo '<i>' . number_format($sts_data['Rows'], 0, $number_decimal_separator, $number_thousands_separator) . '</i>' . "\n";
            } else {
                echo number_format($sts_data['Rows'], 0, $number_decimal_separator, $number_thousands_separator) . "\n";
            }
            ?>
    </td>
    <td nowrap="nowrap">
        &nbsp;<?php echo (isset($sts_data['Type']) ? $sts_data['Type'] : '&nbsp;'); ?>&nbsp;
    </td>
            <?php
            if ($cfg['ShowStats']) {
                ?>
    <td align="right" nowrap="nowrap">
        &nbsp;<?php echo $formated_size . ' ' . $unit . "\n"; ?>
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
        <?php echo $sts_data['Comment']; ?>
        <?php
            if (!empty($sts_data['Comment'])) {
                $needs_break = '<br />';
            } else {
                $needs_break = '';
            }

            if ((isset($sts_data['Create_time']) && !empty($sts_data['Create_time']))
                 || (isset($sts_data['Update_time']) && !empty($sts_data['Update_time']))
                 || (isset($sts_data['Check_time']) && !empty($sts_data['Check_time']))) {
                echo $needs_break;
                ?>
                <table border="0" cellpadding="1" cellspacing="1" width="100%" class="noborder">
                <?php

                if (isset($sts_data['Create_time']) && !empty($sts_data['Create_time'])) {
                    ?>
                    <tr>
                        <td style="font-size: 80%" align="right"><?php echo $strStatCreateTime . ': '; ?></td>
                        <td style="font-size: 80%" align="right"><?php echo PMA_localisedDate(strtotime($sts_data['Create_time'])); ?></td>
                    </tr>
                    <?php
                }

                if (isset($sts_data['Update_time']) && !empty($sts_data['Update_time'])) {
                    ?>
                    <tr>
                        <td style="font-size: 80%" align="right"><?php echo $strStatUpdateTime . ': '; ?></td>
                        <td style="font-size: 80%" align="right"><?php echo PMA_localisedDate(strtotime($sts_data['Update_time'])); ?></td>
                    </tr>
                    <?php
                }

                if (isset($sts_data['Check_time']) && !empty($sts_data['Check_time'])) {
                    ?>
                    <tr>
                        <td style="font-size: 80%" align="right"><?php echo $strStatCheckTime . ': '; ?></td>
                        <td style="font-size: 80%" align="right"><?php echo PMA_localisedDate(strtotime($sts_data['Check_time'])); ?></td>
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
    // Show Summary
    if ($cfg['ShowStats']) {
        list($sum_formated, $unit) = PMA_formatByteDown($sum_size, 3, 1);
    }
    ?>
<tr>
    <th align="center">
        &nbsp;<b><?php echo sprintf($strTables, number_format($num_tables, 0, $number_decimal_separator, $number_thousands_separator)); ?></b>&nbsp;
    </th>
    <th align="right" nowrap="nowrap">
        <?php echo number_format($sum_entries, 0, $number_decimal_separator, $number_thousands_separator); ?>
    </th>
    <th align="center">
        --
    </th>
    <?php
    if ($cfg['ShowStats']) {
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

<script type="text/javascript" language="javascript">
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

<input type="button" class="print_ignore" style="width: 100px; height: 25px"
    id="print" value="<?php echo $strPrint; ?>" onclick="printPage()" />

<?php
require_once './libraries/footer.inc.php';
?>
