<?php
/* $Id$ */


?>
<form method="post" action="<?php echo $action; ?>">
    <input type="hidden" name="server" value="<?php echo $server; ?>" />
    <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
    <input type="hidden" name="db" value="<?php echo $db; ?>" />
    <input type="hidden" name="table" value="<?php echo $table; ?>" />
<?php
if ($action == 'tbl_create.php3') {
    ?>
    <input type="hidden" name="reload" value="true" />
    <?php
}
else if ($action == 'tbl_addfield.php3') {
    echo "\n";
    ?>
    <input type="hidden" name="after_field" value="<?php echo $after_field; ?>" />
    <?php
}
echo "\n";
$is_backup = ($action != 'tbl_create.php3' && $action != 'tbl_addfield.php3');
?>

    <table border="<?php echo $cfgBorder; ?>">
    <tr>
        <th><?php echo $strField; ?></th>
        <th><?php echo $strType; ?></th>
        <th><?php echo $strLengthSet; ?></th>
        <th><?php echo $strAttr; ?></th>
        <th><?php echo $strNull; ?></th>
        <th><?php echo $strDefault; ?></th>
        <th><?php echo $strExtra; ?></th>
<?php
if (!$is_backup) {
    if (empty($num_indexes)) {
        echo "        <th>$strPrimary</th>\n";
        echo "        <th>$strIndex</th>\n";
        echo "        <th>$strUnique</th>\n";
    } else {
        for ($i = 0; $i < $num_indexes; $i++) {
            echo "        <th>$strSequence</th>\n";
            echo "        <th>$strLength</th>\n";
        } // end for
    } // end if
}
?>
    </tr>

<?php
for ($i = 0 ; $i < $num_fields; $i++) {
    if (isset($result)) {
        $row = mysql_fetch_array($result);
    }
    $bgcolor = ($i % 2) ? $cfgBgcolorOne : $cfgBgcolorTwo;
    ?>
    <tr bgcolor="<?php echo $bgcolor;?>">
        <td>
    <?php
    if ($is_backup) {
        echo "\n";
        ?>
            <input type="hidden" name="field_orig[]" value="<?php if (isset($row) && isset($row['Field'])) echo urlencode($row['Field']); ?>" />
        <?php
    }
    echo "\n";
    ?>
            <input type="text" name="field_name[]" size="10" value="<?php if (isset($row) && isset($row['Field'])) echo str_replace('"', '&quot;', $row['Field']); ?>" />
        </td>
        <td>
            <select name="field_type[]">
    <?php
    echo "\n";
    if (empty($row['Type'])) {
        $row['Type'] = '';
        $type        = '';
    }
    else if (get_magic_quotes_gpc()) {
        $type        = stripslashes($row['Type']);
    }
    else {
        $type        = $row['Type'];
    }
    $type   = eregi_replace('BINARY', '', $type);
    $type   = eregi_replace('ZEROFILL', '', $type);
    $type   = eregi_replace('UNSIGNED', '', $type);
    $length = $type;
    $type   = chop(eregi_replace('\\(.*\\)', '', $type));
    if (!empty($type)) {
        $length = eregi_replace("^$type\(", '', $length);
        $length = eregi_replace('\)$', '', trim($length));
    }
    // Removes automatic MySQL escape format
    $length = str_replace('\'\'', '\\\'', $length);
    if ($length == $type) {
        $length = '';
    }
    for ($j = 0; $j < count($cfgColumnTypes); $j++) {
        echo '                <option value="'. $cfgColumnTypes[$j] . '"';
        if (strtoupper($type) == strtoupper($cfgColumnTypes[$j])) {
            echo ' selected="selected"';
        }
        echo ">$cfgColumnTypes[$j]</option>\n";
    }
    ?>
            </select>
        </td>
        <td>
    <?php
    if ($is_backup) {
        echo "\n";
        ?>
            <input type="hidden" name="field_length_orig[]" value="<?php echo urlencode($length); ?>" />
        <?php
    }
    echo "\n";
    ?>
            <input type="text" name="field_length[]" size="8" value="<?php echo str_replace('"', '&quot;', $length); ?>" />
        </td>
        <td>
            <select name="field_attribute[]">
    <?php
    echo "\n";
    $binary           = eregi('BINARY', $row['Type'], $test_attribute1);
    $unsigned         = eregi('UNSIGNED', $row['Type'], $test_attribute2);
    $zerofill         = eregi('ZEROFILL', $row['Type'], $test_attribute3);
    $strAttribute     = '';
    if ($binary) {
        $strAttribute = 'BINARY';
    }
    if ($unsigned) {
        $strAttribute = 'UNSIGNED';
    }
    if ($zerofill) {
        $strAttribute = 'UNSIGNED ZEROFILL';
    }
    for ($j = 0;$j < count($cfgAttributeTypes); $j++) {
        echo '                <option value="'. $cfgAttributeTypes[$j] . '"';
        if (strtoupper($strAttribute) == strtoupper($cfgAttributeTypes[$j])) {
            echo ' selected="selected"';
        }
        echo ">$cfgAttributeTypes[$j]</option>\n";
    }
    ?>
            </select>
        </td>
        <td>
            <select name="field_null[]">
    <?php
    if (!isset($row) || empty($row['Null'])) {
        echo "\n";
        ?>
                <option value="NOT NULL">not null</option>
                <option value="">null</option>
        <?php
    } else {
        echo "\n";
        ?>
                <option value="">null</option>
                <option value="NOT NULL">not null</option>
        <?php
    }
    echo "\n";
    ?>
            </select>
        </td>
    <?php
    if (isset($row)
        && !isset($row['Default']) && !empty($row['Null'])) {
        $row['Default'] = 'NULL';
    }
    echo "\n";
    ?>
        <td>
    <?php
    if ($is_backup) {
        echo "\n";
        ?>
            <input type="hidden" name="field_default_orig[]" size="8" value="<?php if(isset($row) && isset($row['Default'])) echo urlencode($row['Default']); ?>" />
        <?php
    }
    echo "\n";
    ?>
            <input type="text" name="field_default[]" size="8" value="<?php if(isset($row) && isset($row['Default'])) echo str_replace('"', '&quot;', $row['Default']); ?>" />
        </td>
        <td>
            <select name="field_extra[]">
    <?php
    if(!isset($row) || empty($row['Extra'])) {
        echo "\n";
        ?>
                <option value=""></option>
                <option value="AUTO_INCREMENT">auto_increment</option>
        <?php
    } else {
        echo "\n";
        ?>
                <option value="AUTO_INCREMENT">auto_increment</option>
                <option value=""></option>
        <?php
    }
    echo "\n";
    ?>
            </select>
        </td>
    <?php
    if (!$is_backup) {
        if (empty($num_indexes)) {
            if (isset($row) && isset($row['Key']) && $row['Key'] == 'PRI') {
                $checked_primary = ' checked="checked"';
            } else {
                $checked_primary = '';
            }
            if (isset($row) && isset($row['Key']) && $row['Key'] == 'MUL') {
                $checked_index = ' checked="checked"';
            } else {
                $checked_index = '';
            }
            if (isset($row) && isset($row['Key']) && $row['Key'] == 'UNI') {
                $checked_unique = ' checked="checked"';
            } else {
                $checked_unique = '';
            }
            echo "\n";
            ?>
        <td align="center">
            <input type="checkbox" name="field_primary[]" value="<?php echo $i; ?>"<?php echo $checked_primary; ?> />
        </td>
        <td align="center">
            <input type="checkbox" name="field_index[]" value="<?php echo $i; ?>"<?php echo $checked_index; ?> />
        </td>
        <td align="center">
            <input type="checkbox" name="field_unique[]" value="<?php echo $i; ?>"<?php echo $checked_unique; ?> />
        </td>
            <?php
        } // end if (empty($num_indexes))
    } // end if ($action ==...)
    echo "\n";
    ?>
    </tr>
    <?php
    echo "\n";
} // end for
?>
    </table>
    <br />

<?php
if ($action == 'tbl_create.php3' && MYSQL_INT_VERSION >= 32300) {
    echo "\n";
    ?>
    <table>
    <tr valign="top">
        <td><?php echo $strTableComments; ?>&nbsp;:</td>
    <?php
    if ($action == 'tbl_create.php3') {
        echo "\n";
        ?>
        <td width="25">&nbsp;</td>
        <td><?php echo $strTableType; ?>&nbsp;:</td>
        <?php
    }
    echo "\n";
    ?>
    </tr>
    <tr>
        <td>
            <input type="text" name="comment" size="40" maxlength="80" />
        </td>
    <?php
    // BEGIN - Table Type - 2 May 2001 - Robbat2
    // change by staybyte - 11 June 2001
    if ($action == 'tbl_create.php3') {
        // find mysql capability - staybyte - 11. June 2001
        $query = 'SHOW VARIABLES LIKE \'have_%\'';
        $result = mysql_query($query);
        if ($result != FALSE && mysql_num_rows($result) > 0) {
            while ($tmp = mysql_fetch_array($result)) {
                if (isset($tmp['Variable_name'])) {
                    switch ($tmp['Variable_name']) {
                        case 'have_bdb':
                            if (isset($tmp['Variable_name']) && $tmp['Value'] == 'YES') {
                                $tbl_bdb    = TRUE;
                            }
                            break;
                        case 'have_gemini':
                            if (isset($tmp['Variable_name']) && $tmp['Value'] == 'YES') {
                                $tbl_gemini = TRUE;
                            }
                            break;
                        case 'have_innodb':
                            if (isset($tmp['Variable_name']) && $tmp['Value'] == 'YES') {
                                $tbl_innodb = TRUE;
                            }
                            break;
                        case 'have_isam':
                            if (isset($tmp['Variable_name']) && $tmp['Value'] == 'YES') {
                                $tbl_isam   = TRUE;
                            }
                            break;
                    } // end switch
                } // end if
            } // end while
        } // end if
        mysql_free_result($tables);

        echo "\n";
        ?>
        <td width="25">&nbsp;</td>
        <td>
            <select name="tbl_type">
                <option value="Default"><?php echo $strDefault; ?></option>
                <option value="MYISAM">MyISAM</option>
                <option value="HEAP">Heap</option>
                <option value="MERGE">Merge</option>
                <?php if (isset($tbl_bdb)) { ?><option value="BDB">Berkeley DB</option><?php }?> 
                <?php if (isset($tbl_gemini)) { ?><option value="GEMINI">Gemini</option><?php }?> 
                <?php if (isset($tbl_innodb)) { ?><option value="InnoDB">INNO DB</option><?php }?> 
                <?php if (isset($tbl_isam)) { ?><option value="ISAM">ISAM</option><?php }?> 
            </select>
        </td>
        <?php
    }
    echo "\n";
    ?>
        </tr>
    </table>
    <br />
    <?php
}
echo "\n";
// END - Table Type - 2 May 2001 - Robbat2
?>

<input type="submit" name="submit" value="<?php echo $strSave; ?>" />
</form>

<table>
<tr>
    <td valign="top">*&nbsp;</td>
    <td>
        <?php echo $strSetEnumVal . "\n"; ?>
    </td>
</tr>
</table>
<br />

<center><?php echo show_docu('manual_Reference.html#CREATE_TABLE'); ?></center>
