<?php
/* $Id$ */


/**
 * Gets the variables sent or posted to this script, then displays headers
 */
require('./grab_globals.inc.php3');
require('./header.inc.php3');


/**
 * Selects the database
 */
mysql_select_db($db);


/**
 * Gets table informations
 */
// The 'show table' statement works correct since 3.23.03
if (MYSQL_INT_VERSION >= 32303) {
    $local_query  = 'SHOW TABLE STATUS LIKE \'' . sql_addslashes($table, TRUE) . '\'';
    $result       = mysql_query($local_query) or mysql_die('', $local_query);
    $showtable    = mysql_fetch_array($result);
    $num_rows     = (isset($showtable['Rows']) ? $showtable['Rows'] : 0);
    $show_comment = (isset($showtable['Comment']) ? $showtable['Comment'] : '');
} else {
    $local_query  = 'SELECT COUNT(*) AS count FROM ' . backquote($table);
    $result       = mysql_query($local_query) or mysql_die('', $local_query);
    $showtable    = array();
    $num_rows     = mysql_result($result, 0, 'count');
    $show_comment = '';
} // end display comments
mysql_free_result($result);


/**
 * Gets table keys and retains them
 */
$local_query = 'SHOW KEYS FROM ' . backquote($table);
$result      = mysql_query($local_query) or mysql_die('', $local_query);
$primary     = '';
$prev_key    = '';
$prev_seq    = 0;
$i           = 0;
$pk_array    = array(); // will be use to emphasis prim. keys in the table view
while ($row = mysql_fetch_array($result)) {
    $ret_keys[]  = $row;
    // Unset the 'Seq_in_index' value if it's not a composite index - part 1
    if ($i > 0 && $row['Key_name'] != $prev_key && $prev_seq == 1) {
        unset($ret_keys[$i-1]['Seq_in_index']);
    }
    $prev_key    = $row['Key_name'];
    $prev_seq    = $row['Seq_in_index'];
    // Backups the list of primary keys
    if ($row['Key_name'] == 'PRIMARY') {
        $primary .= $row['Column_name'] . ', ';
        $pk_array[$row['Column_name']] = 1;
    }
    $i++;
} // end while
// Unset the 'Seq_in_index' value if it's not a composite index - part 2
if ($i > 0 && $row['Key_name'] != $prev_key && $prev_seq == 1) {
    unset($ret_keys[$i-1]['Seq_in_index']);
}
mysql_free_result($result);


/**
 * Gets fields properties
 */
$local_query = 'SHOW FIELDS FROM ' . backquote($table);
$result      = mysql_query($local_query) or mysql_die('', $local_query);
$fields_cnt  = mysql_num_rows($result);



/**
 * Displays the comments of the table is MySQL >= 3.23
 */
if (!empty($show_comment)) {
    echo $strTableComments . '&nbsp;:&nbsp;' . $row['Comment'];
}


/**
 * Displays the table structure
 */
?>

<!-- TABLE INFORMATIONS -->
<table border="<?php echo $cfgBorder; ?>">
<tr>
    <th><?php echo ucfirst($strField); ?></th>
    <th><?php echo ucfirst($strType); ?></th>
    <th><?php echo ucfirst($strAttr); ?></th>
    <th><?php echo ucfirst($strNull); ?></th>
    <th><?php echo ucfirst($strDefault); ?></th>
    <th><?php echo ucfirst($strExtra); ?></th>
</tr>

<?php
$i = 0;
while ($row = mysql_fetch_array($result)) {
    $bgcolor = ($i % 2) ?$cfgBgcolorOne : $cfgBgcolorTwo;
    $i++;

    $type             = $row['Type'];
    // reformat mysql query output - staybyte - 9. June 2001
    $shorttype        = substr($type, 0, 3);
    if ($shorttype == 'set' || $shorttype == 'enu') {
        $type         = eregi_replace(',', ', ', $type);
        // Removes automatic MySQL escape format
        $type         = str_replace('\'\'', '\\\'', $type);
        $type_nowrap  = '';
    } else {
        $type_nowrap  = ' nowrap="nowrap"';
    }
    $type             = eregi_replace('BINARY', '', $type);
    $type             = eregi_replace('ZEROFILL', '', $type);
    $type             = eregi_replace('UNSIGNED', '', $type);
    if (empty($type)) {
        $type         = '&nbsp;';
    }

    $binary           = eregi('BINARY', $row['Type'], $test);
    $unsigned         = eregi('UNSIGNED', $row['Type'], $test);
    $zerofill         = eregi('ZEROFILL', $row['Type'], $test);
    $strAttribute     = '&nbsp;';
    if ($binary) {
        $strAttribute = 'BINARY';
    }
    if ($unsigned) {
        $strAttribute = 'UNSIGNED';
    }
    if ($zerofill) {
        $strAttribute = 'UNSIGNED ZEROFILL';
    }
    if (!isset($row['Default'])) {
        if ($row['Null'] != '') {
            $row['Default'] = '<i>NULL</i>';
        }
    } else {
        $row['Default'] = htmlspecialchars($row['Default']);
    }
    $field_name = htmlspecialchars($row['Field']);
    if (isset($pk_array[$row['Field']])) {
        $field_name = '<u>' . $field_name . '</u>';
    }
    echo "\n";
    ?>
<tr bgcolor="<?php echo $bgcolor; ?>">
    <td nowrap="nowrap"><?php echo $field_name; ?>&nbsp;</td>
    <td<?php echo $type_nowrap; ?>><?php echo $type; ?></td>
    <td nowrap="nowrap"><?php echo $strAttribute; ?></td>
    <td><?php echo (($row['Null'] == '') ? $strNo : $strYes); ?>&nbsp;</td>
    <td nowrap="nowrap"><?php if (isset($row['Default'])) echo $row['Default']; ?>&nbsp;</td>
    <td nowrap="nowrap"><?php echo $row['Extra']; ?>&nbsp;</td>
</tr>
    <?php
} // end while
mysql_free_result($result);

echo "\n";
?>
</table>


<?php
/**
 * Displays indexes
 */
$index_count = (isset($ret_keys))
             ? count($ret_keys)
             : 0;
if ($index_count > 0) {
    ?>
<br /><br />

<!-- Indexes -->
&nbsp;<big><?php echo $strIndexes . '&nbsp;:'; ?></big>
<table border="<?php echo $cfgBorder; ?>">
<tr>
    <th><?php echo $strKeyname; ?></th>
    <th><?php echo $strUnique; ?></th>
    <?php
    if (MYSQL_INT_VERSION >= 32323) {
        echo "\n";
        ?>
    <th><?php echo $strIdxFulltext; ?></th>
        <?php
    }
    echo "\n";
    ?>
    <th><?php echo $strField; ?></th>
</tr>
    <?php
    $prev_key = '';
    $j        = 0;
    for ($i = 0 ; $i < $index_count; $i++) {
        $row     = $ret_keys[$i];
        if (isset($row['Seq_in_index'])) {
            $key_name = htmlspecialchars($row['Key_name']) . '<nobr>&nbsp;<small>-' . $row['Seq_in_index'] . '-</small></nobr>';
        } else {
            $key_name = htmlspecialchars($row['Key_name']);
        }
        if (!isset($row['Sub_part'])) {
            $row['Sub_part'] = '';
        }

        if ($row['Key_name'] != $prev_key) {
            $j++;
            $prev_key = $row['Key_name'];
        }
        $bgcolor = ($j % 2) ? $cfgBgcolorOne : $cfgBgcolorTwo;
        echo "\n";
        ?>
<tr bgcolor="<?php echo $bgcolor; ?>">
    <td><?php echo $key_name; ?></td>
    <td><?php echo (($row['Non_unique'] == '0') ? $strYes : $strNo); ?></td>
        <?php
        if (MYSQL_INT_VERSION >= 32323) {
            echo "\n";
            ?>
    <td><?php echo (($row['Comment'] == 'FULLTEXT') ? $strYes : $strNo); ?></td>
            <?php
        }
        if (!empty($row['Sub_part'])) {
            echo "\n";
            ?>
    <td><?php echo htmlspecialchars($row['Column_name']); ?></td>
    <td align="right">&nbsp;<?php echo $row['Sub_part']; ?></td>
            <?php
        } else {
            echo "\n";
            ?>
    <td colspan="2"><?php echo htmlspecialchars($row['Column_name']); ?></td>
            <?php
        }
        echo "\n";
        ?>
</tr>
        <?php
    } // end for
    echo "\n";
    ?>
</table>
    <?php
    echo "\n";
} // end display indexes


/**
 * Displays Space usage and row statistics
 *
 * staybyte - 9 June 2001
 */
$nonisam     = FALSE;
if (isset($showtable['Type']) && !eregi('ISAM|HEAP', $showtable['Type'])) {
    $nonisam = TRUE;
}
if (MYSQL_INT_VERSION >= 32303 && $nonisam == FALSE) {
    // Gets some sizes
    $mergetable     = FALSE;
    if (isset($showtable['Type']) && $showtable['Type'] == 'MRG_MyISAM') {
        $mergetable = TRUE;
    }
    list($data_size, $data_unit)         = format_byte_down($showtable['Data_length']);
    if ($mergetable == FALSE) {
        list($index_size, $index_unit)   = format_byte_down($showtable['Index_length']);
    }
    if (isset($showtable['Data_free']) && $showtable['Data_free'] > 0) {
        list($free_size, $free_unit)     = format_byte_down($showtable['Data_free']);
        list($effect_size, $effect_unit) = format_byte_down($showtable['Data_length'] + $showtable['Index_length'] - $showtable['Data_free']);
    } else {
        list($effect_size, $effect_unit) = format_byte_down($showtable['Data_length'] + $showtable['Index_length']);
    }
    list($tot_size, $tot_unit)           = format_byte_down($showtable['Data_length'] + $showtable['Index_length']);
    if ($num_rows > 0) {
        list($avg_size, $avg_unit)       = format_byte_down(($showtable['Data_length'] + $showtable['Index_length']) / $showtable['Rows'], 6, 1);
    }

    // Displays them
    ?>
<br /><br />

<table border="0" cellspacing="0" cellpadding="0">
<tr>

    <!-- Space usage -->
    <td valign="top">
        &nbsp;<big><?php echo $strSpaceUsage . '&nbsp;:'; ?></big>
        <table border="<?php echo $cfgBorder; ?>">
        <tr>
            <th><?php echo $strType; ?></th>
            <th colspan="2" align="center"><?php echo $strUsage; ?></th>
        </tr>
        <tr bgcolor="<?php echo $cfgBgcolorTwo; ?>">
            <td style="padding-right: 10px"><?php echo ucfirst($strData); ?></td>
            <td align="right" nowrap="nowrap"><?php echo $data_size; ?></td>
            <td><?php echo $data_unit; ?></td>
        </tr>
    <?php
    if (isset($index_size)) {
        echo "\n";
        ?>
        <tr bgcolor="<?php echo $cfgBgcolorTwo; ?>">
            <td style="padding-right: 10px"><?php echo ucfirst($strIndex); ?></td>
            <td align="right" nowrap="nowrap"><?php echo $index_size; ?></td>
            <td><?php echo $index_unit; ?></td>
        </tr>
        <?php
    }
    if (isset($free_size)) {
        echo "\n";
        ?>
        <tr bgcolor="<?php echo $cfgBgcolorTwo; ?>" style="color: #bb0000">
            <td style="padding-right: 10px"><?php echo ucfirst($strOverhead); ?></td>
            <td align="right" nowrap="nowrap"><?php echo $free_size; ?></td>
            <td><?php echo $free_unit; ?></td>
        </tr>
        <tr bgcolor="<?php echo $cfgBgcolorOne; ?>">
            <td style="padding-right: 10px"><?php echo ucfirst($strEffective); ?></td>
            <td align="right" nowrap="nowrap"><?php echo $effect_size; ?></td>
            <td><?php echo $effect_unit; ?></td>
        </tr>
        <?php
    }
    if (isset($tot_size) && $mergetable == FALSE) {
        echo "\n";
        ?>
        <tr bgcolor="<?php echo $cfgBgcolorOne; ?>">
            <td style="padding-right: 10px"><?php echo ucfirst($strTotal); ?></td>
            <td align="right" nowrap="nowrap"><?php echo $tot_size; ?></td>
            <td><?php echo $tot_unit; ?></td>
        </tr>
        <?php
    }
    echo "\n";
    ?>
        </table>
    </td>

    <td width="20">&nbsp;</td>

    <!-- Rows Statistic -->
    <td valign="top">
        &nbsp;<big><?php echo $strRowsStatistic . '&nbsp;:'; ?></big>
        <table border="<?php echo $cfgBorder; ?>">
        <tr>
            <th><?php echo $strStatement; ?></th>
            <th align="center"><?php echo $strValue; ?></th>
        </tr>
    <?php
    $i = 0;
    if (isset($showtable['Row_format'])) {
        echo "\n";
        ?>
        <tr bgcolor="<?php echo ((++$i%2) ? $cfgBgcolorTwo : $cfgBgcolorOne); ?>">
            <td><?php echo ucfirst($strFormat); ?></td>
            <td align="right" nowrap="nowrap">
        <?php
        echo '        ';
        if ($showtable['Row_format'] == 'Fixed') {
            echo $strFixed;
        } else if ($showtable['Row_format'] == 'Dynamic') {
            echo $strDynamic;
        } else {
            echo $showtable['Row_format'];
        }
        echo "\n";
        ?>
            </td>
        </tr>
        <?php
    }
    if (isset($showtable['Rows'])) {
        echo "\n";
        ?>
        <tr bgcolor="<?php echo ((++$i%2) ? $cfgBgcolorTwo : $cfgBgcolorOne); ?>">
            <td><?php echo ucfirst($strRows); ?></td>
            <td align="right" nowrap="nowrap">
                <?php echo number_format($showtable['Rows'], 0, $number_decimal_separator, $number_thousands_separator) . "\n"; ?>
            </td>
        </tr>
        <?php
    }
    if (isset($showtable['Avg_row_length']) && $showtable['Avg_row_length'] > 0) {
        echo "\n";
        ?>
        <tr bgcolor="<?php echo ((++$i%2) ? $cfgBgcolorTwo : $cfgBgcolorOne); ?>">
            <td><?php echo ucfirst($strRowLength); ?>&nbsp;&oslash;</td>
            <td align="right" nowrap="nowrap">
                <?php echo number_format($showtable['Avg_row_length'], 0, $number_decimal_separator, $number_thousands_separator) . "\n"; ?>
            </td>
        </tr>
        <?php
    }
    if (isset($showtable['Data_length']) && $showtable['Rows'] > 0 && $mergetable == FALSE) {
        echo "\n";
        ?>
        <tr bgcolor="<?php echo ((++$i%2) ? $cfgBgcolorTwo : $cfgBgcolorOne); ?>">
            <td><?php echo ucfirst($strRowSize); ?>&nbsp;&oslash;</td>
            <td align="right" nowrap="nowrap">
                <?php echo "$avg_size $avg_unit\n"; ?>
            </td>
        </tr>
        <?php
    }
    if (isset($showtable['Auto_increment'])) {
        echo "\n";
        ?>
        <tr bgcolor="<?php echo ((++$i%2) ? $cfgBgcolorTwo : $cfgBgcolorOne); ?>">
            <td><?php echo ucfirst($strNext); ?>&nbsp;Autoindex</td>
            <td align="right" nowrap="nowrap">
                <?php echo number_format($showtable['Auto_increment'], 0, $number_decimal_separator, $number_thousands_separator) . "\n"; ?>
            </td>
        </tr>
        <?php
    }
    echo "\n";
    ?>
        </table>
    </td>
</tr>
</table>

    <?php
} // end if (MYSQL_INT_VERSION >= 32303 && $nonisam == FALSE)


/**
 * Displays the footer
 */
echo "\n";
require('./footer.inc.php3');
?>
