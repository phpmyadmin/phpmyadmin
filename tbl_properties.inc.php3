<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


?>
<!-- Set on key handler for moving using by Ctrl+arrows -->
<script type="text/javascript" language="javascript">
<!--
document.onkeydown = onKeyDownArrowsHandler;
// -->
</script>

<form method="post" action="<?php echo $action; ?>">
<?php
echo PMA_generate_common_hidden_inputs($db, $table);
if ($action == 'tbl_create.php3') {
    ?>
    <input type="hidden" name="reload" value="1" />
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

$header_cells = array();
$content_cells = array();

$header_cells[] = $strField;
$header_cells[] = $strType . '<br /><span style="font-weight: normal">' . PMA_showMySQLDocu('Reference', 'Column_types') . '</span>';
$header_cells[] = $strLengthSet;
$header_cells[] = $strAttr;
$header_cells[] = $strNull;
$header_cells[] = $strDefault . '**';
$header_cells[] = $strExtra;

require('./libraries/relation.lib.php3');
require('./libraries/transformations.lib.php3');
$cfgRelation = PMA_getRelationsParam();

$comments_map = array();
$mime_map = array();
$available_mime = array();

if ($cfgRelation['commwork']) {
    $comments_map = PMA_getComments($db, $table);
    $header_cells[] = $strComments;

    if ($cfgRelation['mimework'] && $cfg['BrowseMIME']) {
        $mime_map = PMA_getMIME($db, $table);
        $available_mime = PMA_getAvailableMIMEtypes();

        $header_cells[] = $strMIME_MIMEtype;
        $header_cells[] = $strMIME_transformation;
        $header_cells[] = $strMIME_transformation_options . '***';
    }
}


// lem9: We could remove this 'if' and let the key information be shown and
// editable. However, for this to work, tbl_alter must be modified to use the
// key fields, as tbl_addfield does.

if (!$is_backup) {
    $header_cells[] = $strPrimary;
    $header_cells[] = $strIndex;
    $header_cells[] = $strUnique;
    $header_cells[] = '---';
    $header_cells[] = $strIdxFulltext;
}

for ($i = 0 ; $i < $num_fields; $i++) {
    if (isset($fields_meta)) {
        $row = $fields_meta[$i];
    }
    $bgcolor = ($i % 2) ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo'];

    // Cell index: If certain fields get left out, the counter shouldn't chage.
    $ci = 0;
    
    if ($is_backup) {
        $content_cells[$i][$ci] = "\n" . '<input type="hidden" name="field_orig[]" value="' . (isset($row) && isset($row['Field']) ? urlencode($row['Field']) : '') . '" />' . "\n";
    } else {
        $content_cells[$i][$ci] = '';
    }
    
    $content_cells[$i][$ci] .= "\n" . '<input id="field_' . $i . '_1" type="text" name="field_name[]" size="10" maxlength="64" value="' . (isset($row) && isset($row['Field']) ? str_replace('"', '&quot;', $row['Field']) : '') . '" class="textfield" />';
    $ci++;
    $content_cells[$i][$ci] = '<select name="field_type[]" id="field_' . $i . '_2">' . "\n";

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
    // set or enum types: slashes single quotes inside options
    if (eregi('^(set|enum)\((.+)\)$', $type, $tmp)) {
        $type   = $tmp[1];
        $length = substr(ereg_replace('([^,])\'\'', '\\1\\\'', ',' . $tmp[2]), 1);
    } else {
        $type   = eregi_replace('BINARY', '', $type);
        $type   = eregi_replace('ZEROFILL', '', $type);
        $type   = eregi_replace('UNSIGNED', '', $type);

        $length = $type;
        $type   = chop(eregi_replace('\\(.*\\)', '', $type));
        if (!empty($type)) {
            $length = eregi_replace("^$type\(", '', $length);
            $length = eregi_replace('\)$', '', trim($length));
        }
        if ($length == $type) {
            $length = '';
        }
    } // end if else

    for ($j = 0; $j < count($cfg['ColumnTypes']); $j++) {
        $content_cells[$i][$ci] .= '                <option value="'. $cfg['ColumnTypes'][$j] . '"';
        if (strtoupper($type) == strtoupper($cfg['ColumnTypes'][$j])) {
            $content_cells[$i][$ci] .= ' selected="selected"';
        }
        $content_cells[$i][$ci] .= '>' . $cfg['ColumnTypes'][$j] . '</option>' . "\n";
    } // end for
    
    $content_cells[$i][$ci] .= '    </select>';
    $ci++;
    
    if ($is_backup) {
        $content_cells[$i][$ci] = "\n" . '<input type="hidden" name="field_length_orig[]" value="' . urlencode($length) . '" />';
    } else {
        $content_cells[$i][$ci] = '';
    }
    
    $content_cells[$i][$ci] .= "\n" . '<input id="field_' . $i . '_3" type="text" name="field_length[]" size="8" value="' . str_replace('"', '&quot;', $length) . '" class="textfield" />' . "\n";
    $ci++;
    
    $content_cells[$i][$ci] = '<select name="field_attribute[]" id="field_' . $i . '_4">' . "\n";

    if (eregi('^(set|enum)$', $type)) {
        $binary           = 0;
        $unsigned         = 0;
        $zerofill         = 0;
    } else {
        $binary           = eregi('BINARY', $row['Type'], $test_attribute1);
        $unsigned         = eregi('UNSIGNED', $row['Type'], $test_attribute2);
        $zerofill         = eregi('ZEROFILL', $row['Type'], $test_attribute3);
    }
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
    for ($j = 0;$j < count($cfg['AttributeTypes']); $j++) {
        $content_cells[$i][3] .= '                <option value="'. $cfg['AttributeTypes'][$j] . '"';
        if (strtoupper($strAttribute) == strtoupper($cfg['AttributeTypes'][$j])) {
            $content_cells[$i][3] .= ' selected="selected"';
        }
        $content_cells[$i][3] .= '>' . $cfg['AttributeTypes'][$j] . '</option>' . "\n";
    }
    
    $content_cells[$i][$ci] .= '</select>';
    $ci++;
    
    $content_cells[$i][$ci] = '<select name="field_null[]" id="field_' . $i . '_5">';

    if (!isset($row) || empty($row['Null'])) {
        $content_cells[$i][$ci] .= "\n";
        $content_cells[$i][$ci] .= '    <option value="NOT NULL">not null</option>' . "\n";
        $content_cells[$i][$ci] .= '    <option value="">null</option>' . "\n";
    } else {
        $content_cells[$i][$ci] .= "\n";
        $content_cells[$i][$ci] .= '    <option value="">null</option>' . "\n";
        $content_cells[$i][$ci] .= '    <option value="NOT NULL">not null</option>' . "\n";
    }

    $content_cells[$i][$ci] .= "\n" . '</select>';
    $ci++;
    
    if (isset($row)
        && !isset($row['Default']) && !empty($row['Null'])) {
        $row['Default'] = 'NULL';
    }

    if ($is_backup) {
        $content_cells[$i][5] = "\n" . '<input type="hidden" name="field_default_orig[]" size="8" value="' . (isset($row) && isset($row['Default']) ? urlencode($row['Default']) : '') . '" />';
    } else {
        $content_cells[$i][5] = "\n";
    }
    
    $content_cells[$i][$ci] .= '<input id="field_' . $i . '_6" type="text" name="field_default[]" size="8" value="' . (isset($row) && isset($row['Default']) ? str_replace('"', '&quot;', $row['Default']) : '') . '" class="textfield" />';
    $ci++;
    
    $content_cells[$i][$ci] = '<select name="field_extra[]" id="field_' . $i . '_7">';

    if(!isset($row) || empty($row['Extra'])) {
        $content_cells[$i][$ci] .= "\n";
        $content_cells[$i][$ci] .= '<option value=""></option>' . "\n";
        $content_cells[$i][$ci] .= '<option value="AUTO_INCREMENT">auto_increment</option>' . "\n";
    } else {
        $content_cells[$i][$ci] .= "\n";
        $content_cells[$i][$ci] .= '<option value="AUTO_INCREMENT">auto_increment</option>' . "\n";
        $content_cells[$i][$ci] .= '<option value=""></option>' . "\n";
    }
    
    $content_cells[$i][$ci] .= "\n" . '</select>';
    $ci++;

    // garvin: comments
    if ($cfgRelation['commwork']) {
        $content_cells[$i][$ci] = '<input id="field_' . $i . '_7" type="text" name="field_comments[]" size="8" value="' . (isset($row) && isset($row['Field']) && is_array($comments_map) && isset($comments_map[$row['Field']]) ?  htmlspecialchars($comments_map[$row['Field']]) : '') . '" class="textfield" />';
        $ci++;
    }

    // garvin: MIME-types
    if ($cfgRelation['mimework'] && $cfg['BrowseMIME'] && $cfgRelation['commwork']) {
        $content_cells[$i][$ci] = '<select id="field_' . $i . '_8" size="1" name="field_mimetype[]">' . "\n";
        $content_cells[$i][$ci] .= '    <option value=""></option>' . "\n";
        $content_cells[$i][$ci] .= '    <option value="auto">auto-detect</option>' . "\n";

        if (is_array($available_mime['mimetype'])) {
            @reset($available_mime['mimetype']);
            while(list($mimekey, $mimetype) = each($available_mime['mimetype'])) {
                $checked = (isset($row) && isset($row['Field']) && isset($mime_map[$row['Field']]['mimetype']) && ($mime_map[$row['Field']]['mimetype'] == str_replace('/', '_', $mimetype)) ? 'selected ' : '');
                $content_cells[$i][$ci] .= '    <option value="' . str_replace('/', '_', $mimetype) . '" ' . $checked . '>' . htmlspecialchars($mimetype) . '</option>';
            }
        }
        
        $content_cells[$i][$ci] .= '</select>';
        $ci++;

        $content_cells[$i][$ci] = '<select id="field_' . $i . '_9" size="1" name="field_transformation[]">' . "\n";
        $content_cells[$i][$ci] .= '    <option value=""></option>' . "\n";
        if (is_array($available_mime['transformation'])) {
            @reset($available_mime['transformation']);
            while(list($mimekey, $transform) = each($available_mime['transformation'])) {
                $checked = (isset($row) && isset($row['Field']) && isset($mime_map[$row['Field']]['transformation']) && ($mime_map[$row['Field']]['transformation'] == $available_mime['transformation_file'][$mimekey]) ? 'selected ' : '');
                $content_cells[$i][$ci] .= '<option value="' . $available_mime['transformation_file'][$mimekey] . '" ' . $checked . '>' . htmlspecialchars($transform) . '</option>' . "\n";
            }
        }
        
        $content_cells[$i][$ci] .= '</select>';
        $ci++;

        $content_cells[$i][$ci] = '<input id="field_' . $i . '_10" type="text" name="field_transformation_options[]" size="8" value="' . (isset($row) && isset($row['Field']) && isset($mime_map[$row['Field']]['transformation_options']) ?  htmlspecialchars($mime_map[$row['Field']]['transformation_options']) : '') . '" class="textfield" />';
        $ci++;
    }

    // lem9: See my other comment about removing this 'if'.
    if (!$is_backup) {
        if (isset($row) && isset($row['Key']) && $row['Key'] == 'PRI') {
            $checked_primary = ' checked="checked"';
        } else {
            $checked_primary = '';
        }
        if (isset($row) && isset($row['Key']) && $row['Key'] == 'MUL') {
            $checked_index   = ' checked="checked"';
        } else {
            $checked_index   = '';
        }
        if (isset($row) && isset($row['Key']) && $row['Key'] == 'UNI') {
            $checked_unique   = ' checked="checked"';
        } else {
            $checked_unique   = '';
        }
        if (empty($checked_primary)
            && empty($checked_index)
            && empty($checked_unique)) {
            $checked_none = ' checked="checked"';
        }
        if (PMA_MYSQL_INT_VERSION >= 32323
            &&(isset($row) && isset($row['Comment']) && $row['Comment'] == 'FULLTEXT')) {
            $checked_fulltext = ' checked="checked"';
        } else {
            $checked_fulltext = '';
        }
        
        $content_cells[$i][$ci] = "\n" . '<input type="radio" name="field_key_' . $i . '" value="primary_' . $i . '"' . $checked_primary . ' />';
        $ci++;
        
        $content_cells[$i][$ci] = "\n" . '<input type="radio" name="field_key_' . $i . '" value="index_' . $i . '"' .  $checked_index . ' />';
        $ci++;
        
        $content_cells[$i][$ci] = "\n" . '<input type="radio" name="field_key_' . $i . '" value="unique_' . $i . '"' .  $checked_unique . ' />';
        $ci++;
        
        $content_cells[$i][$ci] = "\n" . '<input type="radio" name="field_key_' . $i . '" value="none_' . $i . '"' .  $checked_none . ' />';
        $ci++;
        
        if (PMA_MYSQL_INT_VERSION >= 32323) {
            $content_cells[$i][$ci] = '<input type="checkbox" name="field_fulltext[]" value="' . $i . '"' . $checked_fulltext . ' />';
        } // end if (PMA_MYSQL_INT_VERSION >= 32323)
    } // end if ($action ==...)
} // end for

if ($cfg['DefaultPropDisplay'] == 'horizontal') {
?>
    <table border="<?php echo $cfg['Border']; ?>">
    <tr>
<?php
@reset($header_cells);
while(@list($header_nr, $header_val) = @each($header_cells)) {
?>
        <th><?php echo $header_val; ?></th>
<?php
}
?>
    </tr>
<?php
@reset($content_cells);
$i = 0;
while(@list($content_nr, $content_row) = @each($content_cells)) {
    $i++;
    echo "\n" . '<tr>' . "\n";

    $bgcolor = ($i % 2) ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo'];
    
    while(list($content_row_nr, $content_row_val) = @each($content_row)) {
?>
        <td bgcolor="<?php echo $bgcolor; ?>"><?php echo $content_row_val; ?></td>
<?php
    }
    echo "\n" . '</tr>' . "\n";
}
?>
    </table>
    <br />
<?php
} else {
?>
    <table border="<?php echo $cfg['Border']; ?>">
<?php
@reset($header_cells);
$i = 0;
while(@list($header_nr, $header_val) = @each($header_cells)) {
    echo "\n" . '<tr>' . "\n";
?>
        <th align="right"><?php echo $header_val; ?></th>
<?php
    for ($j = 0; $j < count($content_cells); $j++) {
        if (isset($content_cells[$j][$i]) && $content_cells[$j][$i] != '') {
            $bgcolor = ($j % 2) ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo'];
    ?>
        <td bgcolor="<?php echo $bgcolor; ?>"><?php echo $content_cells[$j][$i]; ?></td>
    <?php
        }
    }

    echo "\n" . '</tr>' . "\n";
    $i++;
}
?>
    </table>
    <br />
<?php
}

if ($action == 'tbl_create.php3' && PMA_MYSQL_INT_VERSION >= 32300) {
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
            <input type="text" name="comment" size="40" maxlength="80" class="textfield" />
        </td>
    <?php
    // BEGIN - Table Type - 2 May 2001 - Robbat2
    // change by staybyte - 11 June 2001
    if ($action == 'tbl_create.php3') {
        // find mysql capability - staybyte - 11. June 2001
        $query = 'SHOW VARIABLES LIKE \'have_%\'';
        $result = PMA_mysql_query($query);
        if ($result != FALSE && mysql_num_rows($result) > 0) {
            while ($tmp = PMA_mysql_fetch_array($result)) {
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
        mysql_free_result($result);

        echo "\n";
        ?>
        <td width="25">&nbsp;</td>
        <td>
            <select name="tbl_type">
                <option value="Default"><?php echo $strDefault; ?></option>
                <option value="MYISAM">MyISAM</option>
                <option value="HEAP">Heap</option>
                <option value="MERGE">Merge</option>
                <?php if (isset($tbl_bdb)) { ?><option value="BDB">Berkeley DB</option><?php } ?>
                <?php if (isset($tbl_gemini)) { ?><option value="GEMINI">Gemini</option><?php } ?>
                <?php if (isset($tbl_innodb)) { ?><option value="InnoDB">INNO DB</option><?php } ?>
                <?php if (isset($tbl_isam)) { ?><option value="ISAM">ISAM</option><?php } ?>
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
<tr>
    <td valign="top">**&nbsp;</td>
    <td>
        <?php echo $strDefaultValueHelp . "\n"; ?>
    </td>
</tr>

<?php
if ($cfgRelation['commwork'] && $cfgRelation['mimework'] && $cfg['BrowseMIME']) {
?>
<tr>
    <td valign="top" rowspan="2">***&nbsp;</td>
    <td>
        <?php echo $strMIME_transformation_options_note  . "\n"; ?>
    </td>
</tr>

<tr>
    <td>
        <?php echo sprintf($strMIME_transformation_note, '<a href="libraries/transformations/overview.php3?' . PMA_generate_common_url($db, $table) . '" target="_new">', '</a>') . "\n"; ?>
    </td>
</tr>
<?php
}
?>

</table>
<br />

<center><?php echo PMA_showMySQLDocu('Reference', 'CREATE_TABLE'); ?></center>
