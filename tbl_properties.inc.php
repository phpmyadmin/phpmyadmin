<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

// Check parameters
require_once('./libraries/common.lib.php');
PMA_checkParameters(array('db','table','action','num_fields'));


// Get available character sets
require_once('./libraries/mysql_charsets.lib.php');

?>
<?php if ($cfg['CtrlArrowsMoving']) { ?>
<!-- Set on key handler for moving using by Ctrl+arrows -->
<script type="text/javascript" language="javascript">
<!--
document.onkeydown = onKeyDownArrowsHandler;
// -->
</script>
<?php } ?>

<form method="post" action="<?php echo $action; ?>" onsubmit="return checkTableEditForm(this, <?php echo $num_fields; ?>)" >
<?php
echo PMA_generate_common_hidden_inputs($db, $table);
if ($action == 'tbl_create.php') {
    ?>
    <input type="hidden" name="reload" value="1" />
    <?php
}
else if ($action == 'tbl_addfield.php') {
    echo "\n";
    ?>
    <input type="hidden" name="after_field" value="<?php echo $after_field; ?>" />
    <?php
}
echo "\n";

if (isset($num_fields)) {
    ?>
    <input type="hidden" name="orig_num_fields" value="<?php echo $num_fields; ?>" />
    <?php
}

if (isset($after_field)) {
    ?>
    <input type="hidden" name="orig_after_field" value="<?php echo $after_field; ?>" />
    <?php
}

if (isset($selected) && is_array($selected)) {
    foreach($selected AS $o_fld_nr => $o_fld_val) {
    ?>
    <input type="hidden" name="selected[<?php echo $o_fld_nr; ?>]" value="<?php echo urlencode($o_fld_val); ?>" />
    <?php
        if (!isset($true_selected)) {
            ?>
    <input type="hidden" name="true_selected[<?php echo $o_fld_nr; ?>]" value="<?php echo urlencode($o_fld_val); ?>" />
            <?php
        }

    }

    if (isset($true_selected) && is_array($true_selected)) {
        foreach($true_selected AS $o_fld_nr => $o_fld_val) {
        ?>
        <input type="hidden" name="true_selected[<?php echo $o_fld_nr; ?>]" value="<?php echo urlencode($o_fld_val); ?>" />
        <?php
        }
    }

} elseif (isset($field)) {
    ?>
    <input type="hidden" name="orig_field" value="<?php echo urlencode($field); ?>" />
    <input type="hidden" name="true_selected[] value="<?php echo (isset($orig_field) ? $orig_field : urlencode($field)); ?>" />
    <?php
}

$is_backup = ($action != 'tbl_create.php' && $action != 'tbl_addfield.php');

$header_cells = array();
$content_cells = array();

$header_cells[] = $strField;
$header_cells[] = $strType . '<br /><span style="font-weight: normal">' . PMA_showMySQLDocu('Reference', 'Column_types') . '</span>';
$header_cells[] = $strLengthSet;
if (PMA_MYSQL_INT_VERSION >= 40100) {
    $header_cells[] = $strCharset;
}
$header_cells[] = $strAttr;
$header_cells[] = $strNull;
$header_cells[] = $strDefault . '**';
$header_cells[] = $strExtra;

require_once('./libraries/relation.lib.php');
require_once('./libraries/transformations.lib.php');
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

// garvin: workaround for field_fulltext, because its submitted indizes contain
//         the index as a value, not a key. Inserted here for easier maintaineance
//         and less code to change in existing files.
if (isset($field_fulltext) && is_array($field_fulltext)) {
    foreach($field_fulltext AS $fulltext_nr => $fulltext_indexkey) {
        $submit_fulltext[$fulltext_indexkey] = $fulltext_indexkey;
    }
}

for ($i = 0 ; $i < $num_fields; $i++) {
    $submit_null = FALSE;
    if (isset($regenerate) && $regenerate == TRUE) {
        // An error happened with previous inputs, so we will restore the data
        // to embed it once again in this form.

        $row['Field']     = (isset($field_name) && isset($field_name[$i]) ? $field_name[$i] : FALSE);
        $row['Type']      = (isset($field_type) && isset($field_type[$i]) ? $field_type[$i] : FALSE);
        $row['Null']      = (isset($field_null) && isset($field_null[$i]) ? $field_null[$i] : '');
        if ($row['Null'] == '') {
            $submit_null = TRUE;
        }

        if (isset(${'field_key_' . $i}) && ${'field_key_' . $i} == 'primary_' . $i) {
            $row['Key'] = 'PRI';
        } elseif (isset(${'field_key_' . $i}) && ${'field_key_' . $i} == 'index_' . $i) {
            $row['Key'] = 'MUL';
        } elseif (isset(${'field_key_' . $i}) && ${'field_key_' . $i} == 'unique_' . $i) {
            $row['Key'] = 'UNI';
        } else {
            $row['Key'] = '';
        }

        $row['Default']   = (isset($field_default) && isset($field_default[$i]) ? $field_default[$i] : FALSE);
        $row['Extra']     = (isset($field_extra) && isset($field_extra[$i]) ? $field_extra[$i] : FALSE);
        $row['Comment']   = (isset($submit_fulltext) && isset($submit_fulltext[$i]) && ($submit_fulltext[$i] == $i) ? 'FULLTEXT' : FALSE);

        $submit_length    = (isset($field_length) && isset($field_length[$i]) ? $field_length[$i] : FALSE);
        $submit_attribute = (isset($field_attribute) && isset($field_attribute[$i]) ? $field_attribute[$i] : FALSE);

        if (isset($field_comments) && isset($field_comments[$i])) {
            $comments_map[$row['Field']] = $field_comments[$i];
        }

        if (isset($field_mimetype) && isset($field_mimetype[$i])) {
            $mime_map[$row['Field']]['mimetype'] = $field_mimetype[$i];
        }

        if (isset($field_transformation) && isset($field_transformation[$i])) {
            $mime_map[$row['Field']]['transformation'] = $field_transformation[$i];
        }

        if (isset($field_transformation_options) && isset($field_transformation_options[$i])) {
            $mime_map[$row['Field']]['transformation_options'] = $field_transformation_options[$i];
        }

    } elseif (isset($fields_meta)) {
        $row = $fields_meta[$i];
    }

    $bgcolor = ($i % 2) ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo'];

    // Cell index: If certain fields get left out, the counter shouldn't chage.
    $ci = 0;
    // Everytime a cell shall be left out the STRG-jumping feature, $ci_offset
    // has to be incremented ($ci_offset++)
    $ci_offset = -1;

    if ($is_backup) {
        $backup_field = (isset($true_selected) && $true_selected[$i] ? $true_selected[$i] : (isset($row) && isset($row['Field']) ? urlencode($row['Field']) : ''));
        $content_cells[$i][$ci] = "\n" . '<input type="hidden" name="field_orig[]" value="' . $backup_field . '" />' . "\n";
    } else {
        $content_cells[$i][$ci] = '';
    }

    $content_cells[$i][$ci] .= "\n" . '<input id="field_' . $i . '_' . ($ci - $ci_offset) . '" type="text" name="field_name[]" size="10" maxlength="64" value="' . (isset($row) && isset($row['Field']) ? str_replace('"', '&quot;', $row['Field']) : '') . '" class="textfield" />';
    $ci++;
    $content_cells[$i][$ci] = '<select name="field_type[]" id="field_' . $i . '_' . ($ci - $ci_offset) . '">' . "\n";

    if (empty($row['Type'])) {
        $row['Type'] = '';
        $type        = '';
    }
    else {
        $type        = $row['Type'];
    }
    // set or enum types: slashes single quotes inside options
    if (preg_match('@^(set|enum)\((.+)\)$@i', $type, $tmp)) {
        $type   = $tmp[1];
        $length = substr(preg_replace('@([^,])\'\'@', '\\1\\\'', ',' . $tmp[2]), 1);
    } else {
        $type   = preg_replace('@BINARY@i', '', $type);
        $type   = preg_replace('@ZEROFILL@i', '', $type);
        $type   = preg_replace('@UNSIGNED@i', '', $type);

        if (strpos($type, '(')) {
            $length = chop(substr($type, (strpos($type, '(') + 1), (strpos($type, ')') - strpos($type, '(') - 1)));
            $type = chop(substr($type, 0, strpos($type, '(')));
        } else {
            $length = '';
        }
    } // end if else

    // some types, for example longtext, are reported as
    // "longtext character set latin7" when not latin1
    if (PMA_MYSQL_INT_VERSION >= 40100) {
        $tmp = strpos($type, 'character set');
        if ($tmp) {
            $type = substr($type, 0, $tmp-1);
        }
    }

    if (isset($submit_length) && $submit_length != FALSE) {
        $length = $submit_length;
    }

    $cnt_column_types = count($cfg['ColumnTypes']);
    for ($j = 0; $j < $cnt_column_types; $j++) {
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

    $content_cells[$i][$ci] .= "\n" . '<input id="field_' . $i . '_' . ($ci - $ci_offset) . '" type="text" name="field_length[]" size="8" value="' . str_replace('"', '&quot;', $length) . '" class="textfield" />' . "\n";
    $ci++;

    if (preg_match('@^(set|enum)$@i', $type)) {
        $binary           = 0;
        $unsigned         = 0;
        $zerofill         = 0;
    } else {
        $binary           = stristr($row['Type'], 'binary');
        $unsigned         = stristr($row['Type'], 'unsigned');
        $zerofill         = stristr($row['Type'], 'zerofill');
    }

    if (PMA_MYSQL_INT_VERSION >= 40100) {
        $content_cells[$i][$ci] = '<select name="field_charset[]" id="field_' . $i . '_' . ($ci - $ci_offset) . '">' . "\n"
                                . '    <option value=""></option>' . "\n";
        if (!empty($row['Collation']) && (
            strtolower(substr($type, 0, 4)) == 'char'
            || strtolower(substr($type, 0, 7)) == 'varchar'
            || strtolower(substr($type, 0, 4)) == 'text'
            || strtolower(substr($type, 0, 8)) == 'tinytext'
            || strtolower(substr($type, 0, 10)) == 'mediumtext'
            || strtolower(substr($type, 0, 8)) == 'longtext'
            || strtolower(substr($type, 0, 3)) == 'set'
            || strtolower(substr($type, 0, 4)) == 'enum'
            ) && !$binary) {
            $real_charset = strpos($row['Collation'], '_') ? substr($row['Collation'], 0, strpos($row['Collation'], '_')) : $row['Collation'];
        } else {
            $real_charset = '';
        }
        for ($j = 0; isset($mysql_charsets[$j]); $j++) {
            $content_cells[$i][$ci] .= '    <option value="' . $mysql_charsets[$j] . '"' . ($mysql_charsets[$j] == $real_charset ? ' selected="selected"' : '') . '>' . $mysql_charsets[$j] . '</option>' . "\n";
        }
        unset($j);
        unset($real_charset);
        $content_cells[$i][$ci] .= '</select>' . "\n";
        $ci++;
    }

    $content_cells[$i][$ci] = '<select name="field_attribute[]" id="field_' . $i . '_' . ($ci - $ci_offset) . '">' . "\n";

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

    if (isset($submit_attribute) && $submit_attribute != FALSE) {
        $strAttribute = $submit_attribute;
    }

    $cnt_attribute_types = count($cfg['AttributeTypes']);
    for ($j = 0;$j < $cnt_attribute_types; $j++) {
        $content_cells[$i][$ci] .= '                <option value="'. $cfg['AttributeTypes'][$j] . '"';
        if (strtoupper($strAttribute) == strtoupper($cfg['AttributeTypes'][$j])) {
            $content_cells[$i][$ci] .= ' selected="selected"';
        }
        $content_cells[$i][$ci] .= '>' . $cfg['AttributeTypes'][$j] . '</option>' . "\n";
    }

    $content_cells[$i][$ci] .= '</select>';
    $ci++;

    $content_cells[$i][$ci] = '<select name="field_null[]" id="field_' . $i . '_' . ($ci - $ci_offset) . '">';

    if ((!isset($row) || empty($row['Null']) || $row['Null'] == 'NOT NULL') && $submit_null == FALSE) {
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
        $content_cells[$i][$ci] = "\n" . '<input type="hidden" name="field_default_orig[]" size="8" value="' . (isset($row) && isset($row['Default']) ? urlencode($row['Default']) : '') . '" />';
    } else {
        $content_cells[$i][$ci] = "\n";
    }

    $content_cells[$i][$ci] .= '<input id="field_' . $i . '_' . ($ci - $ci_offset) . '" type="text" name="field_default[]" size="8" value="' . (isset($row) && isset($row['Default']) ? str_replace('"', '&quot;', $row['Default']) : '') . '" class="textfield" />';
    $ci++;

    $content_cells[$i][$ci] = '<select name="field_extra[]" id="field_' . $i . '_' . ($ci - $ci_offset) . '">';

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
        $content_cells[$i][$ci] = '<input id="field_' . $i . '_' . ($ci - $ci_offset) . '" type="text" name="field_comments[]" size="8" value="' . (isset($row) && isset($row['Field']) && is_array($comments_map) && isset($comments_map[$row['Field']]) ?  htmlspecialchars($comments_map[$row['Field']]) : '') . '" class="textfield" />';
        $ci++;
    }

    // garvin: MIME-types
    if ($cfgRelation['mimework'] && $cfg['BrowseMIME'] && $cfgRelation['commwork']) {
        $content_cells[$i][$ci] = '<select id="field_' . $i . '_' . ($ci - $ci_offset) . '" size="1" name="field_mimetype[]">' . "\n";
        $content_cells[$i][$ci] .= '    <option value=""></option>' . "\n";
        $content_cells[$i][$ci] .= '    <option value="auto">auto-detect</option>' . "\n";

        if (is_array($available_mime['mimetype'])) {
            foreach($available_mime['mimetype'] AS $mimekey => $mimetype) {
                $checked = (isset($row) && isset($row['Field']) && isset($mime_map[$row['Field']]['mimetype']) && ($mime_map[$row['Field']]['mimetype'] == str_replace('/', '_', $mimetype)) ? 'selected ' : '');
                $content_cells[$i][$ci] .= '    <option value="' . str_replace('/', '_', $mimetype) . '" ' . $checked . '>' . htmlspecialchars($mimetype) . '</option>';
            }
        }

        $content_cells[$i][$ci] .= '</select>';
        $ci++;

        $content_cells[$i][$ci] = '<select id="field_' . $i . '_' . ($ci - $ci_offset) . '" size="1" name="field_transformation[]">' . "\n";
        $content_cells[$i][$ci] .= '    <option value="" title="' . $strNone . '"></option>' . "\n";
        if (is_array($available_mime['transformation'])) {
            foreach($available_mime['transformation'] AS $mimekey => $transform) {
                $checked = (isset($row) && isset($row['Field']) && isset($mime_map[$row['Field']]['transformation']) && (preg_match('@' . preg_quote($available_mime['transformation_file'][$mimekey]) . '3?@i', $mime_map[$row['Field']]['transformation'])) ? 'selected ' : '');
                $tooltip = 'strTransformation_' . strtolower(preg_replace('@(\.inc\.php3?)$@', '', $available_mime['transformation_file'][$mimekey]));
                $tooltip = isset($$tooltip) ? $$tooltip : sprintf(str_replace('<br />', ' ', $strMIME_nodescription), 'PMA_transformation_' . $tooltip . '()');
                $content_cells[$i][$ci] .= '<option value="' . $available_mime['transformation_file'][$mimekey] . '" ' . $checked . ' title="' . htmlspecialchars($tooltip) . '">' . htmlspecialchars($transform) . '</option>' . "\n";
            }
        }

        $content_cells[$i][$ci] .= '</select>';
        $ci++;

        $content_cells[$i][$ci] = '<input id="field_' . $i . '_' . ($ci - $ci_offset) . '" type="text" name="field_transformation_options[]" size="8" value="' . (isset($row) && isset($row['Field']) && isset($mime_map[$row['Field']]['transformation_options']) ?  htmlspecialchars($mime_map[$row['Field']]['transformation_options']) : '') . '" class="textfield" />';
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
        } else {
            $checked_none = '';
        }

        if ((isset($row) && isset($row['Comment']) && $row['Comment'] == 'FULLTEXT')) {
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

        $content_cells[$i][$ci] = '<input type="checkbox" name="field_fulltext[]" value="' . $i . '"' . $checked_fulltext . ' />';
    } // end if ($action ==...)
} // end for

if ($cfg['DefaultPropDisplay'] == 'horizontal') {
?>
    <table border="<?php echo $cfg['Border']; ?>">
    <tr>
<?php
    if (is_array($header_cells)) {
        foreach($header_cells AS $header_nr => $header_val) {
?>
        <th><?php echo $header_val; ?></th>
<?php
        }
    }
?>
    </tr>
<?php
    if (is_array($content_cells)) {
        $i = 0;
        foreach($content_cells AS $content_nr => $content_row) {
            $i++;
            echo "\n" . '<tr>' . "\n";

            $bgcolor = ($i % 2) ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo'];

            if (is_array($content_row)) {
                foreach($content_row AS $content_row_nr => $content_row_val) {
?>
        <td bgcolor="<?php echo $bgcolor; ?>"><?php echo $content_row_val; ?></td>
<?php
                }
            }

            echo "\n" . '</tr>' . "\n";
        }
    }
?>
    </table>
    <br />
<?php
} else {
?>
    <table border="<?php echo $cfg['Border']; ?>">
<?php
    if (is_array($header_cells)) {
        $i = 0;
        foreach($header_cells AS $header_nr => $header_val) {
            echo "\n" . '<tr>' . "\n";
?>
        <th align="right"><?php echo $header_val; ?></th>
<?php
    $cnt_content_cells = count($content_cells);
    for ($j = 0; $j < $cnt_content_cells; $j++) {
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
    }
?>
    </table>
    <br />
<?php
}

if ($action == 'tbl_create.php') {
    echo "\n";
    ?>
    <table>
    <tr valign="top">
        <td><?php echo $strTableComments; ?>&nbsp;:</td>
    <?php
    if ($action == 'tbl_create.php') {
        echo "\n";
        ?>
        <td width="25">&nbsp;</td>
        <td><?php echo $strTableType; ?>&nbsp;:</td>
        <?php
        if (PMA_MYSQL_INT_VERSION >= 40100) {
            echo '        <td width="25">&nbsp;</td>' . "\n"
               . '        <td>' . $strCharset . '&nbsp;:</td>' . "\n";
        }
    }
    echo "\n";
    ?>
    </tr>
    <tr>
        <td>
            <input type="text" name="comment" size="40" maxlength="80" value="<?php echo (isset($comment) ? $comment : ''); ?>" class="textfield" />
        </td>
    <?php
    // BEGIN - Table Type - 2 May 2001 - Robbat2
    // change by staybyte - 11 June 2001
    if ($action == 'tbl_create.php') {
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
                <option <?php echo (isset($tbl_type) && $tbl_type == 'Default' ? 'selected="checked"' : ''); ?> value="Default"><?php echo $strDefault; ?></option>
                <option <?php echo (isset($tbl_type) && $tbl_type == 'MYISAM' ? 'selected="checked"' : ''); ?> value="MYISAM">MyISAM</option>
                <option <?php echo (isset($tbl_type) && $tbl_type == 'HEAP' ? 'selected="checked"' : ''); ?> value="HEAP">Heap</option>
                <option <?php echo (isset($tbl_type) && $tbl_type == 'MERGE' ? 'selected="checked"' : ''); ?> value="MERGE">Merge</option>
                <?php if (isset($tbl_bdb)) { ?><option <?php echo (isset($tbl_type) && $tbl_type == 'BDB' ? 'selected="checked"' : ''); ?> value="BDB">Berkeley DB</option><?php } ?>
                <?php if (isset($tbl_gemini)) { ?><option <?php echo (isset($tbl_type) && $tbl_type == 'GEMINI' ? 'selected="checked"' : ''); ?> value="GEMINI">Gemini</option><?php } ?>
                <?php if (isset($tbl_innodb)) { ?><option <?php echo (isset($tbl_type) && $tbl_type == 'INNO DB' ? 'selected="checked"' : ''); ?> value="InnoDB">INNO DB</option><?php } ?>
                <?php if (isset($tbl_isam)) { ?><option <?php echo (isset($tbl_type) && $tbl_type == 'ISAM' ? 'selected="checked"' : ''); ?> value="ISAM">ISAM</option><?php } ?>
            </select>
        </td>
        <?php
        if (PMA_MYSQL_INT_VERSION >= 40100) {
            echo '        <td width="25">&nbsp;</td>' . "\n"
               . '        <td>' . "\n"
               . '            <select name="tbl_charset">' . "\n";
            for ($i = 0; isset($mysql_charsets[$i]); $i++) {
                echo '                <option value="' . $mysql_charsets[$i] . '"' . ($mysql_charsets[$i] == 'latin1' ? ' selected="selected"' : '') . '>' . $mysql_charsets[$i] . '</option>' . "\n";
            }
            unset($i);
            echo '            </select>' . "\n"
               . '        </td>' . "\n";
        }
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
        <?php echo sprintf($strMIME_transformation_note, '<a href="libraries/transformations/overview.php?' . PMA_generate_common_url($db, $table) . '" target="_blank">', '</a>') . "\n"; ?>
    </td>
</tr>
<?php
}
?>

</table>
<br />

<center><?php echo PMA_showMySQLDocu('Reference', 'CREATE_TABLE'); ?></center>
