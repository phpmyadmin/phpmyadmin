<?php
/* $Id$ */


/**
 * Prepares the tables list if the user where not redirected to this script
 * because there is no table in the database ($is_info is TRUE)
 */
if (empty($is_info)) {
   include('./db_details_common.php3');
   $url_query .= '&amp;goto=db_details_structure.php3';

   //Drop/delete multiple tables if required
   if ((!empty($submit_mult) && isset($selected_tbl))
       || isset($mult_btn)) {
        $action = 'db_details_structure.php3';
        include('./mult_submits.inc.php3');
   }

   // Gets the database structure
   $sub_part = '_structure';
   include('./db_details_db_info.php3');
   echo "\n";
}


/**
 * Displays the tables list
 */
?>

<!-- TABLE LIST -->

<?php
// 1. No tables
if ($num_tables == 0) {
    echo $strNoTablesFound . "\n";
}

// 2. Shows table informations on mysql >= 3.23.03 - staybyte - 11 June 2001
else if (PMA_MYSQL_INT_VERSION >= 32303) {
    ?>
<form method="post" action="db_details_structure.php3" name="tablesForm">
    <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
    <input type="hidden" name="server" value="<?php echo $server; ?>" />
    <input type="hidden" name="db" value="<?php echo $db; ?>" />

<table border="<?php echo $cfg['Border']; ?>">
<tr>
    <td></td>
    <th>&nbsp;<?php echo ucfirst($strTable); ?>&nbsp;</th>
    <th colspan="6"><?php echo ucfirst($strAction); ?></th>
    <th><?php echo ucfirst($strRecords); ?></th>
    <th><?php echo ucfirst($strType); ?></th>
    <?php
    if ($cfg['ShowStats']) {
        echo '<th>' . ucfirst($strSize) . '</th>';
    }
    echo "\n";
    ?>
</tr>
    <?php
    $i = $sum_entries = $sum_size = 0;
    $checked   = (!empty($checkall) ? ' checked="checked"' : '');
    while (list($keyname, $sts_data) = each($tables)) {
        $table         = $sts_data['Name'];
        $table_encoded = urlencode($table);
        $table_name    = htmlspecialchars($table);

        // Sets parameters for links
        $tbl_url_query = $url_query . '&amp;table=' . $table_encoded;
        $bgcolor       = ($i++ % 2) ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo'];
        echo "\n";
        ?>
<tr>
    <td align="center" bgcolor="<?php echo $bgcolor; ?>">
        <input type="checkbox" name="selected_tbl[]" value="<?php echo $table_encoded; ?>" id="checkbox_tbl_<?php echo $i; ?>"<?php echo $checked; ?> />
    </td>
    <td bgcolor="<?php echo $bgcolor; ?>" nowrap="nowrap">
        &nbsp;<b><label for="checkbox_tbl_<?php echo $i; ?>"><?php echo $table_name; ?></label>&nbsp;</b>&nbsp;
    </td>
    <td bgcolor="<?php echo $bgcolor; ?>">
        <?php
        if (!empty($sts_data['Rows'])) {
            echo '<a href="sql.php3?' . $tbl_url_query . '&amp;sql_query='
                 . urlencode('SELECT * FROM ' . PMA_backquote($table))
                 . '&amp;pos=0">' . $strBrowse . '</a>';
        } else {
            echo $strBrowse;
        }
        ?>
    </td>
    <td bgcolor="<?php echo $bgcolor; ?>">
        <?php
        if (!empty($sts_data['Rows'])) {
            echo '<a href="tbl_select.php3?' . $tbl_url_query . '">'
                 . $strSelect . '</a>';
        } else {
            echo $strSelect;
        }
        ?>
    </td>
    <td bgcolor="<?php echo $bgcolor; ?>">
        <a href="tbl_change.php3?<?php echo $tbl_url_query; ?>">
            <?php echo $strInsert; ?></a>
    </td>
    <td bgcolor="<?php echo $bgcolor; ?>">
        <a href="tbl_properties.php3?<?php echo $tbl_url_query; ?>">
            <?php echo $strProperties; ?></a>
    </td>
    <td bgcolor="<?php echo $bgcolor; ?>">
        <a href="sql.php3?<?php echo $tbl_url_query; ?>&amp;reload=1&amp;sql_query=<?php echo urlencode('DROP TABLE ' . PMA_backquote($table)); ?>&amp;zero_rows=<?php echo urlencode(sprintf($strTableHasBeenDropped, htmlspecialchars($table))); ?>"
            onclick="return confirmLink(this, 'DROP TABLE <?php echo PMA_jsFormat($table); ?>')">
            <?php echo $strDrop; ?></a>
    </td>
    <td bgcolor="<?php echo $bgcolor; ?>">
        <?php
        if (!empty($sts_data['Rows'])) {
            echo '<a href="sql.php3?' . $tbl_url_query
                 . '&amp;sql_query=';
            if (PMA_MYSQL_INT_VERSION >= 40000) {
                echo urlencode('TRUNCATE ' . PMA_backquote($table))
                     . '&amp;zero_rows='
                     . urlencode(sprintf($strTableHasBeenEmptied, htmlspecialchars($table)))
                     . '" onclick="return confirmLink(this, \'TRUNCATE ';
            } else {
                echo urlencode('DELETE FROM ' . PMA_backquote($table))
                     . '&amp;zero_rows='
                     . urlencode(sprintf($strTableHasBeenEmptied, htmlspecialchars($table)))
                     . '" onclick="return confirmLink(this, \'DELETE FROM ';
            }
            echo PMA_jsFormat($table) . '\')">' . $strEmpty . '</a>';
        } else {
             echo $strEmpty;
        }
        ?>
    </td>
        <?php
        echo "\n";

        // loic1: Patch from Joshua Nye <josh at boxcarmedia.com> to get valid
        //        statistics whatever is the table type
        if (isset($sts_data['Rows'])) {
            // MyISAM, ISAM or Heap table: Row count, data size and index size
            // is accurate.
            if (isset($sts_data['Type']) && ereg('^(MyISAM|ISAM|HEAP)$', $sts_data['Type'])) {
                if ($cfg['ShowStats']) {
                    $tblsize                    =  $sts_data['Data_length'] + $sts_data['Index_length'];
                    $sum_size                   += $tblsize;
                    list($formated_size, $unit) =  PMA_formatByteDown($tblsize, 3, ($tblsize > 0) ? 1 : 0);
                }
                $sum_entries                    += $sts_data['Rows'];
                $display_rows                   =  number_format($sts_data['Rows'], 0, $number_decimal_separator, $number_thousands_separator);
            }

            // InnoDB table: Row count is not accurate but data and index
            // sizes are.
            else if (isset($sts_data['Type']) && $sts_data['Type'] == 'InnoDB') {
                if ($cfg['ShowStats']) {
                    $tblsize                    =  $sts_data['Data_length'] + $sts_data['Index_length'];
                    $sum_size                   += $tblsize;
                    list($formated_size, $unit) =  PMA_formatByteDown($tblsize, 3, ($tblsize > 0) ? 1 : 0);
                }
                $display_rows                   =  '&nbsp;-&nbsp;';
            }

            // Merge or BerkleyDB table: Only row count is accurate.
            else if (isset($sts_data['Type']) && ereg('^(MRG_MyISAM|BerkeleyDB)$', $sts_data['Type'])) {
                if ($cfg['ShowStats']) {
                    $formated_size              =  '&nbsp;-&nbsp;';
                    $unit                       =  '';
                }
                $sum_entries                    += $sts_data['Rows'];
                $display_rows                   =  number_format($sts_data['Rows'], 0, $number_decimal_separator, $number_thousands_separator);
            }

            // Unknown table type.
            else {
                if ($cfg['ShowStats']) {
                    $formated_size              =  'unknown';
                    $unit                       =  '';
                }
                $display_rows                   =  'unknown';
            }
            ?>
    <td align="right" bgcolor="<?php echo $bgcolor; ?>">
            <?php
            echo "\n" . '        ' . $display_rows . "\n";
            ?>
    </td>
    <td bgcolor="<?php echo $bgcolor; ?>" nowrap="nowrap">
        &nbsp;<?php echo (isset($sts_data['Type']) ? $sts_data['Type'] : '&nbsp;'); ?>&nbsp;
    </td>
            <?php
            if ($cfg['ShowStats']) {
                echo "\n";
                ?>
    <td align="right" bgcolor="<?php echo $bgcolor; ?>" nowrap="nowrap">
        &nbsp;&nbsp;
        <a href="tbl_properties.php3?<?php echo $tbl_url_query; ?>#showusage"><?php echo $formated_size . ' ' . $unit; ?></a>
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
    <td></td>
    <th align="center" nowrap="nowrap">
        &nbsp;<b><?php echo sprintf($strTables, number_format($num_tables, 0, $number_decimal_separator, $number_thousands_separator)); ?></b>&nbsp;
    </th>
    <th colspan="6" align="center">
        <b><?php echo $strSum; ?></b>
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
        &nbsp;
        <b><?php echo $sum_formated . ' ' . $unit; ?></b>
    </th>
        <?php
    }
    echo "\n";
    ?>
</tr>

    <?php
    // Check all tables url
    $checkall_url = 'db_details_structure.php3'
                  . '?lang=' . $lang
                  . '&amp;server=' . $server
                  . '&amp;db=' . urlencode($db);
    echo "\n";
    ?>
<tr>
    <td colspan="<?php echo (($cfg['ShowStats']) ? '11' : '10'); ?>" valign="bottom">
        <img src="./images/arrow_<?php echo $text_dir; ?>.gif" border="0" width="38" height="22" alt="<?php echo $strWithChecked; ?>" />
        <a href="<?php echo $checkall_url; ?>&amp;checkall=1" onclick="setCheckboxes('tablesForm', true); return false;">
            <?php echo $strCheckAll; ?></a>
        &nbsp;/&nbsp;
        <a href="<?php echo $checkall_url; ?>" onclick="setCheckboxes('tablesForm', false); return false;">
            <?php echo $strUncheckAll; ?></a>
        &nbsp;&nbsp;&nbsp;
        <img src="./images/spacer.gif" border="0" width="38" height="1" alt="" />
        <select name="submit_mult" dir="ltr" onchange="this.form.submit();">
    <?php
    echo "\n";
    echo '            <option value="' . $strWithChecked . '" selected="selected">'
         . $strWithChecked . '</option>' . "\n";
    echo '            <option value="' . $strDrop . '" >'
         . $strDrop . '</option>' . "\n";
    echo '            <option value="' . $strEmpty . '" >'
         . $strEmpty . '</option>' . "\n";
    echo '            <option value="' . $strPrintView . '" >'
         . $strPrintView . '</option>' . "\n";
    echo '            <option value="' . $strOptimizeTable . '" >'
         . $strOptimizeTable . '</option>' . "\n";
    ?>
        </select>
        <script type="text/javascript" language="javascript">
        <!--
        // Fake js to allow the use of the <noscript> tag
        //-->
        </script>
        <noscript>
            <input type="submit" value="<?php echo $strGo; ?>" />
        </noscript>
    </td>
</tr>
</table>

</form>
    <?php
} // end case mysql >= 3.23.03

// 3. Shows tables list mysql < 3.23.03
else {
    $i = 0;
    echo "\n";
    ?>
<form action="db_details_structure.php3">
    <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
    <input type="hidden" name="server" value="<?php echo $server; ?>" />
    <input type="hidden" name="db" value="<?php echo $db; ?>" />

<table border="<?php echo $cfg['Border']; ?>">
<tr>
    <td></td>
    <th>&nbsp;<?php echo ucfirst($strTable); ?>&nbsp;</th>
    <th colspan="6"><?php echo ucfirst($strAction); ?></th>
    <th><?php echo ucfirst($strRecords); ?></th>
</tr>
    <?php
    $checked = (!empty($checkall) ? ' checked="checked"' : '');
    while ($i < $num_tables) {
        $table         = $tables[$i];
        $table_encoded = urlencode($table);
        $table_name    = htmlspecialchars($table);

        // Sets parameters for links
        $tbl_url_query = $url_query . '&amp;table=' . $table_encoded;
        $bgcolor       = ($i % 2) ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo'];
        echo "\n";
        ?>
<tr>
    <td align="center" bgcolor="<?php echo $bgcolor; ?>">
        <input type="checkbox" name="selected_tbl[]" value="<?php echo $table_encoded; ?>" id="checkbox_tbl_<?php echo $i; ?>"<?php echo $checked; ?> />
    </td>
    <td bgcolor="<?php echo $bgcolor; ?>" class="data">
        <b>&nbsp;<label for="checkbox_tbl_<?php echo $i; ?>"><?php echo $table_name; ?></label>&nbsp;</b>
    </td>
    <td bgcolor="<?php echo $bgcolor; ?>">
        <a href="sql.php3?<?php echo $tbl_url_query; ?>&amp;sql_query=<?php echo urlencode('SELECT * FROM ' . PMA_backquote($table)); ?>&amp;pos=0"><?php echo $strBrowse; ?></a>
    </td>
    <td bgcolor="<?php echo $bgcolor; ?>">
        <a href="tbl_select.php3?<?php echo $tbl_url_query; ?>"><?php echo $strSelect; ?></a>
    </td>
    <td bgcolor="<?php echo $bgcolor; ?>">
        <a href="tbl_change.php3?<?php echo $tbl_url_query; ?>"><?php echo $strInsert; ?></a>
    </td>
    <td bgcolor="<?php echo $bgcolor; ?>">
        <a href="tbl_properties.php3?<?php echo $tbl_url_query; ?>"><?php echo $strProperties; ?></a>
    </td>
    <td bgcolor="<?php echo $bgcolor; ?>">
        <a href="sql.php3?<?php echo $tbl_url_query; ?>&amp;reload=1&amp;sql_query=<?php echo urlencode('DROP TABLE ' . PMA_backquote($table)); ?>&amp;zero_rows=<?php echo urlencode(sprintf($strTableHasBeenDropped, $table_name)); ?>"><?php echo $strDrop; ?></a>
    </td>
    <td bgcolor="<?php echo $bgcolor; ?>">
        <a href="sql.php3?<?php echo $tbl_url_query; ?>&amp;sql_query=<?php echo urlencode('DELETE FROM ' . PMA_backquote($table)); ?>&amp;zero_rows=<?php echo urlencode(sprintf($strTableHasBeenEmptied, $table_name)); ?>"><?php echo $strEmpty; ?></a>
    </td>
    <td align="right" bgcolor="<?php echo $bgcolor; ?>">
        <?php PMA_countRecords($db, $table); echo "\n"; ?>
    </td>
</tr>
        <?php
        $i++;
    } // end while
    echo "\n";

    // Check all tables url
    $checkall_url = 'db_details_structure.php3'
                  . '?lang=' . $lang
                  . '&amp;server=' . $server
                  . '&amp;db=' . urlencode($db);
    ?>
<tr>
    <td colspan="9">
        <img src="./images/arrow_<?php echo $text_dir; ?>.gif" border="0" width="38" height="22" alt="<?php echo $strWithChecked; ?>" />
        <a href="<?php echo $checkall_url; ?>&amp;checkall=1" onclick="setCheckboxes('tablesForm', true); return false;">
            <?php echo $strCheckAll; ?></a>
        &nbsp;/&nbsp;
        <a href="<?php echo $checkall_url; ?>" onclick="setCheckboxes('tablesForm', false); return false;">
            <?php echo $strUncheckAll; ?></a>
    </td>
</tr>

<tr>
    <td colspan="9">
        <img src="./images/spacer.gif" border="0" width="38" height="1" alt="" />
        <i><?php echo $strWithChecked; ?></i>&nbsp;&nbsp;
        <input type="submit" name="submit_mult" value="<?php echo $strDrop; ?>" />
        &nbsp;<?php $strOr . "\n"; ?>&nbsp;
        <input type="submit" name="submit_mult" value="<?php echo $strEmpty; ?>" />
    </td>
</tr>
</table>

</form>
    <?php
} // end case mysql < 3.23.03

echo "\n";
?>
<hr />


<?php
/**
 * Work on the database
 */
?>
<!-- DATABASE WORK -->
<ul>

<?php
if ($num_tables > 0) {
    ?>
    <!-- Printable view of a table -->
    <li>
        <div style="margin-bottom: 10px"><a href="db_printview.php3?<?php echo $url_query; ?>"><?php echo $strPrintView; ?></a></div>
    </li>
    <?php
} // end if
?>

    <!-- Create a new table -->
    <li>
        <form method="post" action="tbl_create.php3"
            onsubmit="return (emptyFormElements(this, 'table') && checkFormElementInRange(this, 'num_fields', 1))">
        <input type="hidden" name="server" value="<?php echo $server; ?>" />
        <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
        <input type="hidden" name="db" value="<?php echo $db; ?>" />
<?php
echo '        ' . sprintf($strCreateNewTable, htmlspecialchars($db)) . '&nbsp;:<br />' . "\n";
echo '        ' . $strName . '&nbsp;:&nbsp;' . "\n";
echo '        ' . '<input type="text" name="table" maxlength="64" class="textfield" />' . "\n";
echo '        ' . '<br />' . "\n";
echo '        ' . $strFields . '&nbsp;:&nbsp;' . "\n";
echo '        ' . '<input type="text" name="num_fields" size="2" class="textfield" />' . "\n";
echo '        ' . '&nbsp;<input type="submit" value="' . $strGo . '" />' . "\n";
?>
        </form>
    </li>

<?php
// is this OK to check for 'class' support?
if (PMA_PHP_INT_VERSION >= 40000
    && (!empty($cfg['Server']['pdf_table_position']))) {
    ?>
    <!-- PDF schema -->
    <li>
        <form method="post" action="pdf_schema.php3">
            <input type="hidden" name="server" value="<?php echo $server; ?>" />
            <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
            <input type="hidden" name="db" value="<?php echo $db; ?>" />
            <?php echo $strDisplayPDF; ?>&nbsp;:<br />
            <?php echo $strPageNumber; ?>&nbsp;
            <input type="text" name="pdf_page_number" size="3" class="textfield" value="1" /><br />
            <input type="checkbox" name="show_grid" id="show_grid_opt" />
            <label for="show_grid_opt"><?php echo $strShowGrid; ?></label><br />
            <input type="checkbox" name="show_color" id="show_color_opt" checked="checked" />
            <label for="show_color_opt"><?php echo $strShowColor; ?></label><br />
            <input type="checkbox" name="show_table_dimension" id="show_table_dim_opt" />
            <label for="show_table_dim_opt"><?php echo $strShowTableDimension; ?></label>
            &nbsp;&nbsp;<input type="submit" value="<?php echo $strGo; ?>" />
        </form>
    </li>
    <?php
} // end if

echo "\n" . '</ul>';


/**
 * Displays the footer
 */
echo "\n";
require('./footer.inc.php3');
?>

