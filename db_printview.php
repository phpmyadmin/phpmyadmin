<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


/**
 * Gets the variables sent or posted to this script, then displays headers
 */
require_once('./libraries/grab_globals.lib.php');
require_once('./header.inc.php');

// Check parameters
require_once('./libraries/common.lib.php');

PMA_checkParameters(array('db'));

/**
 * Defines the url to return to in case of error in a sql statement
 */
$err_url = 'db_details.php?' . PMA_generate_common_url($db);

/**
 * Settings for relations stuff
 */
require_once('./libraries/relation.lib.php');
$cfgRelation = PMA_getRelationsParam();

/**
 * Gets the list of the table in the current db and informations about these
 * tables if possible
 */
// staybyte: speedup view on locked tables - 11 June 2001
// Special speedup for newer MySQL Versions (in 4.0 format changed)
if ($cfg['SkipLockedTables'] == TRUE) {
    $local_query  = 'SHOW OPEN TABLES FROM ' . PMA_backquote($db);
    $result        = PMA_mysql_query($local_query) or PMA_mysqlDie('', $local_query, '', $err_url);
    // Blending out tables in use
    if ($result != FALSE && mysql_num_rows($result) > 0) {
        while ($tmp = PMA_mysql_fetch_array($result)) {
            // if in use memorize tablename
            if (preg_match('@in_use=[1-9]+@i', $tmp[0])) {
                $sot_cache[$tmp[0]] = TRUE;
            }
        }
        mysql_free_result($result);

        if (isset($sot_cache)) {
            $local_query = 'SHOW TABLES FROM ' . PMA_backquote($db);
            $result      = PMA_mysql_query($local_query) or PMA_mysqlDie('', $local_query, '', $err_url);
            if ($result != FALSE && mysql_num_rows($result) > 0) {
                while ($tmp = PMA_mysql_fetch_array($result)) {
                    if (!isset($sot_cache[$tmp[0]])) {
                        $local_query = 'SHOW TABLE STATUS FROM ' . PMA_backquote($db) . ' LIKE \'' . addslashes($tmp[0]) . '\'';
                        $sts_result  = PMA_mysql_query($local_query) or PMA_mysqlDie('', $local_query, '', $err_url);
                        $sts_tmp     = PMA_mysql_fetch_array($sts_result);
                        $tables[]    = $sts_tmp;
                    } else { // table in use
                        $tables[]    = array('Name' => $tmp[0]);
                    }
                }
                mysql_free_result($result);
                $sot_ready = TRUE;
            }
        }
    }
}
if (!isset($sot_ready)) {
    $local_query = 'SHOW TABLE STATUS FROM ' . PMA_backquote($db);
    $result      = PMA_mysql_query($local_query) or PMA_mysqlDie('', $local_query, '', $err_url);
    if ($result != FALSE && mysql_num_rows($result) > 0) {
        while ($sts_tmp = PMA_mysql_fetch_array($result)) {
            $tables[] = $sts_tmp;
        }
        mysql_free_result($result);
    }
}
$num_tables = (isset($tables) ? count($tables) : 0);

if ($cfgRelation['commwork']) {
    $comment = PMA_getComments($db);

    /**
     * Displays DB comment
     */
    if (is_array($comment)) {
        ?>
    <!-- DB comment -->
    <p><i>
        <?php echo htmlspecialchars(implode(' ', $comment)) . "\n"; ?>
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
<tr>
    <th>&nbsp;<?php echo $strTable; ?>&nbsp;</th>
    <th><?php echo $strRecords; ?></th>
    <th><?php echo $strType; ?></th>
    <?php
    if ($cfg['ShowStats']) {
        echo '<th>' . $strSize . '</th>';
    }
    echo "\n";
    ?>
    <th><?php echo $strComments; ?></th>
</tr>
    <?php
    $i = $sum_entries = $sum_size = 0;
    foreach($tables AS $keyname => $sts_data) {
        $table     = $sts_data['Name'];
        $bgcolor   = ($i++ % 2) ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo'];
        echo "\n";
        ?>
<tr>
    <td bgcolor="<?php echo $bgcolor; ?>" nowrap="nowrap">
        &nbsp;<b><?php echo htmlspecialchars($table); ?>&nbsp;</b>&nbsp;
    </td>
        <?php
        echo "\n";
        $mergetable         = FALSE;
        $nonisam            = FALSE;
        if (isset($sts_data['Type'])) {
            if ($sts_data['Type'] == 'MRG_MyISAM') {
                $mergetable = TRUE;
            } else if (!preg_match('@ISAM|HEAP@i', $sts_data['Type'])) {
                $nonisam    = TRUE;
            }
        }

        if (isset($sts_data['Rows'])) {
            if ($mergetable == FALSE) {
                if ($cfg['ShowStats'] && $nonisam == FALSE) {
                    $tblsize                        =  $sts_data['Data_length'] + $sts_data['Index_length'];
                    $sum_size                       += $tblsize;
                    if ($tblsize > 0) {
                        list($formated_size, $unit) =  PMA_formatByteDown($tblsize, 3, 1);
                    } else {
                        list($formated_size, $unit) =  PMA_formatByteDown($tblsize, 3, 0);
                    }
                } else if ($cfg['ShowStats']) {
                    $formated_size                  = '&nbsp;-&nbsp;';
                    $unit                           = '';
                }
                $sum_entries                        += $sts_data['Rows'];
            }
            // MyISAM MERGE Table
            else if ($cfg['ShowStats'] && $mergetable == TRUE) {
                $formated_size = '&nbsp;-&nbsp;';
                $unit          = '';
            }
            else if ($cfg['ShowStats']) {
                $formated_size = 'unknown';
                $unit          = '';
            }
            ?>
    <td align="right" bgcolor="<?php echo $bgcolor; ?>">
            <?php
            echo "\n" . '        ';
            if ($mergetable == TRUE) {
                echo '<i>' . number_format($sts_data['Rows'], 0, $number_decimal_separator, $number_thousands_separator) . '</i>' . "\n";
            } else {
                echo number_format($sts_data['Rows'], 0, $number_decimal_separator, $number_thousands_separator) . "\n";
            }
            ?>
    </td>
    <td nowrap="nowrap" bgcolor="<?php echo $bgcolor; ?>">
        &nbsp;<?php echo (isset($sts_data['Type']) ? $sts_data['Type'] : '&nbsp;'); ?>&nbsp;
    </td>
            <?php
            if ($cfg['ShowStats']) {
                echo "\n";
                ?>
    <td align="right" bgcolor="<?php echo $bgcolor; ?>" nowrap="nowrap">
        &nbsp;<?php echo $formated_size . ' ' . $unit . "\n"; ?>
    </td>
                <?php
                echo "\n";
            } // end if
        } else {
            ?>
    <td colspan="3" align="center" bgcolor="<?php echo $bgcolor; ?>">
        <?php echo $strInUse . "\n"; ?>
    </td>
            <?php
        }
        echo "\n";
        ?>
    <td bgcolor="<?php echo $bgcolor; ?>">
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
                <table border="0" cellpadding="1" cellspacing="1" width="100%">
                <?php

                if (isset($sts_data['Create_time']) && !empty($sts_data['Create_time'])) {
                    ?>
                    <tr>
                        <td style="font-size: <?php echo $font_smaller; ?>" align="right"><?php echo $strStatCreateTime . ': '; ?></td>
                        <td style="font-size: <?php echo $font_smaller; ?>" align="right"><?php echo PMA_localisedDate(strtotime($sts_data['Create_time'])); ?></td>
                    </tr>
                    <?php
                }

                if (isset($sts_data['Update_time']) && !empty($sts_data['Update_time'])) {
                    ?>
                    <tr>
                        <td style="font-size: <?php echo $font_smaller; ?>" align="right"><?php echo $strStatUpdateTime . ': '; ?></td>
                        <td style="font-size: <?php echo $font_smaller; ?>" align="right"><?php echo PMA_localisedDate(strtotime($sts_data['Update_time'])); ?></td>
                    </tr>
                    <?php
                }

                if (isset($sts_data['Check_time']) && !empty($sts_data['Check_time'])) {
                    ?>
                    <tr>
                        <td style="font-size: <?php echo $font_smaller; ?>" align="right"><?php echo $strStatCheckTime . ': '; ?></td>
                        <td style="font-size: <?php echo $font_smaller; ?>" align="right"><?php echo PMA_localisedDate(strtotime($sts_data['Check_time'])); ?></td>
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
    echo "\n";
    ?>
<tr>
    <th align="center">
        &nbsp;<b><?php echo sprintf($strTables, number_format($num_tables, 0, $number_decimal_separator, $number_thousands_separator)); ?></b>&nbsp;
    </th>
    <th align="right" nowrap="nowrap">
        <b><?php echo number_format($sum_entries, 0, $number_decimal_separator, $number_thousands_separator); ?></b>
    </th>
    <th align="center">
        <b>--</b>
    </th>
    <?php
    if ($cfg['ShowStats']) {
        echo "\n";
        ?>
    <th align="right" nowrap="nowrap">
        <b><?php echo $sum_formated . ' ' . $unit; ?></b>
    </th>
        <?php
    }
    echo "\n";
    ?>
    <th>&nbsp;</th>
</tr>
</table>
    <?php
}

/**
 * Displays the footer
 */
echo "\n";
?>
<script type="text/javascript" language="javascript1.2">
<!--
function printPage()
{
    document.getElementById('print').style.visibility = 'hidden';
    // Do print the page
    if (typeof(window.print) != 'undefined') {
        window.print();
    }
    document.getElementById('print').style.visibility = '';
}
//-->
</script>
<?php
echo '<br /><br />&nbsp;<input type="button" style="visibility: ; width: 100px; height: 25px" id="print" value="' . $strPrint . '" onclick="printPage()">' . "\n";

require_once('./footer.inc.php');
?>
