<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Display form for changing/adding table field/columns
 *
 * @version $Id$
 */

/**
 * Check parameters
 */
require_once './libraries/common.inc.php';
PMA_checkParameters(array('db', 'table', 'action', 'num_fields'));


// Get available character sets and storage engines
require_once './libraries/mysql_charsets.lib.php';
require_once './libraries/StorageEngine.class.php';

/**
 * Class for partition management
 */
require_once './libraries/Partition.class.php';

if (is_int($cfg['DefaultPropDisplay'])) {
    if ($num_fields <= $cfg['DefaultPropDisplay']) {
        $display_type = 'vertical';
    } else {
        $display_type = 'horizontal';
    }
} else {
    $display_type = $cfg['DefaultPropDisplay'];
}

$_form_aprams = array(
    'db' => $db,
    'table' => $table,
);

if ($action == 'tbl_create.php') {
    $_form_aprams['reload'] = 1;
} elseif ($action == 'tbl_addfield.php') {
    $_form_aprams['field_where'] = $field_where;
    $_form_aprams['after_field'] = $after_field;
}

if (isset($num_fields)) {
    $_form_aprams['orig_num_fields'] = $num_fields;
}

if (isset($field_where)) {
    $_form_aprams['orig_field_where'] = $field_where;
}

if (isset($after_field)) {
    $_form_aprams['orig_after_field'] = $after_field;
}

if (isset($selected) && is_array($selected)) {
    foreach ($selected as $o_fld_nr => $o_fld_val) {
        $_form_aprams['selected[' . $o_fld_nr . ']'] = $o_fld_val;
        if (! isset($true_selected)) {
            $_form_aprams['true_selected[' . $o_fld_nr . ']'] = $o_fld_val;
        }
    }

    if (isset($true_selected) && is_array($true_selected)) {
        foreach ($true_selected as $o_fld_nr => $o_fld_val) {
            $_form_aprams['true_selected[' . $o_fld_nr . ']'] = $o_fld_val;
        }
    }
} elseif (isset($field)) {
    $_form_aprams['orig_field'] = $field;
    if (isset($orig_field)) {
        $_form_aprams['true_selected[]'] = $orig_field;
    } else {
        $_form_aprams['true_selected[]'] = $field;
    }
}

$is_backup = ($action != 'tbl_create.php' && $action != 'tbl_addfield.php');

$header_cells = array();
$content_cells = array();

$header_cells[] = $strField;
$header_cells[] = $strType . ($GLOBALS['cfg']['ReplaceHelpImg'] ? PMA_showMySQLDocu('SQL-Syntax', 'data-types') : '<br /><span style="font-weight: normal">' . PMA_showMySQLDocu('SQL-Syntax', 'data-types') . '</span>');
$header_cells[] = $strLengthSet . PMA_showHint($strSetEnumVal);
$header_cells[] = $strCollation;
$header_cells[] = $strAttr;
$header_cells[] = $strNull;
$header_cells[] = $strDefault . PMA_showHint($strDefaultValueHelp);
$header_cells[] = $strExtra;



// lem9: We could remove this 'if' and let the key information be shown and
// editable. However, for this to work, tbl_alter must be modified to use the
// key fields, as tbl_addfield does.

if (!$is_backup) {
    $header_cells[] = $cfg['PropertiesIconic'] ? '<img class="icon" src="' . $pmaThemeImage . 'b_primary.png" width="16" height="16" alt="' . $strPrimary . '" title="' . $strPrimary . '" />' : $strPrimary;
    $header_cells[] = $cfg['PropertiesIconic'] ? '<img class="icon" src="' . $pmaThemeImage . 'b_index.png" width="16" height="16" alt="' . $strIndex . '" title="' . $strIndex . '" />' : $strIndex;
    $header_cells[] = $cfg['PropertiesIconic'] ? '<img class="icon" src="' . $pmaThemeImage . 'b_unique.png" width="16" height="16" alt="' . $strUnique . '" title="' . $strUnique . '" />' : $strUnique;
    $header_cells[] = '---';
    $header_cells[] = $cfg['PropertiesIconic'] ? '<img class="icon" src="' . $pmaThemeImage . 'b_ftext.png" width="16" height="16" alt="' . $strIdxFulltext . '" title="' . $strIdxFulltext . '" />' : $strIdxFulltext;
}

require_once './libraries/relation.lib.php';
require_once './libraries/transformations.lib.php';
$cfgRelation = PMA_getRelationsParam();

$comments_map = array();
$mime_map = array();
$available_mime = array();

$comments_map = PMA_getComments($db, $table);
$header_cells[] = $strComments;

if ($cfgRelation['mimework'] && $cfg['BrowseMIME']) {
    $mime_map = PMA_getMIME($db, $table);
    $available_mime = PMA_getAvailableMIMEtypes();

    $hint = '<p>' .
        sprintf($strMIME_transformation_note,
            '<a href="transformation_overview.php?'
            . PMA_generate_common_url($db, $table) . '" target="_blank">',
            '</a>') . '</p>';


    $header_cells[] = $strMIME_MIMEtype;
    $header_cells[] = $strMIME_transformation;
    $header_cells[] = $strMIME_transformation_options . PMA_showHint($strMIME_transformation_options_note . $hint);
}

// garvin: workaround for field_fulltext, because its submitted indizes contain
//         the index as a value, not a key. Inserted here for easier maintaineance
//         and less code to change in existing files.
if (isset($field_fulltext) && is_array($field_fulltext)) {
    foreach ($field_fulltext AS $fulltext_nr => $fulltext_indexkey) {
        $submit_fulltext[$fulltext_indexkey] = $fulltext_indexkey;
    }
}

for ($i = 0; $i <= $num_fields; $i++) {
    if (! empty($regenerate)) {
        // An error happened with previous inputs, so we will restore the data
        // to embed it once again in this form.

        $row['Field']     = (isset($field_name[$i]) ? $field_name[$i] : FALSE);
        $row['Type']      = (isset($field_type[$i]) ? $field_type[$i] : FALSE);
        $row['Collation'] = (isset($field_collation[$i]) ? $field_collation[$i] : '');
        $row['Null']      = (isset($field_null[$i]) ? $field_null[$i] : '');

        if (isset(${'field_key_' . $i}) && ${'field_key_' . $i} == 'primary_' . $i) {
            $row['Key'] = 'PRI';
        } elseif (isset(${'field_key_' . $i}) && ${'field_key_' . $i} == 'index_' . $i) {
            $row['Key'] = 'MUL';
        } elseif (isset(${'field_key_' . $i}) && ${'field_key_' . $i} == 'unique_' . $i) {
            $row['Key'] = 'UNI';
        } else {
            $row['Key'] = '';
        }

        $row['Default']   = (isset($field_default[$i]) ? $field_default[$i] : FALSE);
        $row['Extra']     = (isset($field_extra[$i]) ? $field_extra[$i] : FALSE);
        $row['Comment']   = (isset($submit_fulltext[$i]) && ($submit_fulltext[$i] == $i) ? 'FULLTEXT' : FALSE);

        $submit_length    = (isset($field_length[$i]) ? $field_length[$i] : FALSE);
        $submit_attribute = (isset($field_attribute[$i]) ? $field_attribute[$i] : FALSE);

        $submit_default_current_timestamp = (isset($field_default_current_timestamp[$i]) ? TRUE : FALSE);

        if (isset($field_comments[$i])) {
            $comments_map[$row['Field']] = $field_comments[$i];
        }

        if (isset($field_mimetype[$i])) {
            $mime_map[$row['Field']]['mimetype'] = $field_mimetype[$i];
        }

        if (isset($field_transformation[$i])) {
            $mime_map[$row['Field']]['transformation'] = $field_transformation[$i];
        }

        if (isset($field_transformation_options[$i])) {
            $mime_map[$row['Field']]['transformation_options'] = $field_transformation_options[$i];
        }

    } elseif (isset($fields_meta[$i])) {
        $row = $fields_meta[$i];
    }

    // Cell index: If certain fields get left out, the counter shouldn't chage.
    $ci = 0;
    // Everytime a cell shall be left out the STRG-jumping feature, $ci_offset
    // has to be incremented ($ci_offset++)
    $ci_offset = -1;

    if ($is_backup) {
        $backup_field = (isset($true_selected) && isset($true_selected[$i]) && $true_selected[$i] ? $true_selected[$i] : (isset($row) && isset($row['Field']) ? urlencode($row['Field']) : ''));
        $content_cells[$i][$ci] = "\n" . '<input type="hidden" name="field_orig[]" value="' . $backup_field . '" />' . "\n";
    } else {
        $content_cells[$i][$ci] = '';
    }

    $content_cells[$i][$ci] .= "\n" . '<input id="field_' . $i . '_' . ($ci - $ci_offset) . '" type="text" name="field_name[' . $i . ']" size="' . ($GLOBALS['cfg']['DefaultPropDisplay'] == 'horizontal' ? '10' : '30') . '" maxlength="64" value="' . (isset($row) && isset($row['Field']) ? str_replace('"', '&quot;', $row['Field']) : '') . '" class="textfield" title="' . $strField . '" />';
    $ci++;
    $content_cells[$i][$ci] = '<select name="field_type[' . $i . ']" id="field_' . $i . '_' . ($ci - $ci_offset) . '" ';
    $content_cells[$i][$ci] .= 'onchange="display_field_options(this.options[this.selectedIndex].value,' . $i .')" ';
    $content_cells[$i][$ci] .= '>' . "\n";

    if (empty($row['Type'])) {
        $row['Type'] = '';
        $type        = '';
    } else {
        $type        = $row['Type'];
    }
    // set or enum types: slashes single quotes inside options
    if (preg_match('@^(set|enum)\((.+)\)$@i', $type, $tmp)) {
        $type   = $tmp[1];
        $length = substr(preg_replace('@([^,])\'\'@', '\\1\\\'', ',' . $tmp[2]), 1);
    } else {
        // strip the "BINARY" attribute, except if we find "BINARY(" because
        // this would be a BINARY or VARBINARY field type
        $type   = preg_replace('@BINARY([^\(])@i', '', $type);
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
    // "longtext character set latin7" when their charset and / or collation
    // differs from the ones of the corresponding database.
    $tmp = strpos($type, 'character set');
    if ($tmp) {
        $type = substr($type, 0, $tmp - 1);
    }

    if (isset($submit_length) && $submit_length != FALSE) {
        $length = $submit_length;
    }

    // rtrim the type, for cases like "float unsigned"
    $type = rtrim($type);
    $type_upper = strtoupper($type);

    foreach ($cfg['ColumnTypes'] as $col_goup => $column_type) {
        if (is_array($column_type)) {
            $content_cells[$i][$ci] .= '<optgroup label="' . htmlspecialchars($col_goup) . '">';
            foreach ($column_type as $col_group_type) {
                $content_cells[$i][$ci] .= '<option value="'. $col_group_type . '"';
                if ($type_upper == strtoupper($col_group_type)) {
                    $content_cells[$i][$ci] .= ' selected="selected"';
                }
                $content_cells[$i][$ci] .= '>' . $col_group_type . '</option>' . "\n";
            }
            $content_cells[$i][$ci] .= '</optgroup>';
            continue;
        }

        $content_cells[$i][$ci] .= '<option value="'. $column_type . '"';
        if ($type_upper == strtoupper($column_type)) {
            $content_cells[$i][$ci] .= ' selected="selected"';
        }
        $content_cells[$i][$ci] .= '>' . $column_type . '</option>' . "\n";
    } // end for

    $content_cells[$i][$ci] .= '    </select>';
    $ci++;

    if ($is_backup) {
        $content_cells[$i][$ci] = "\n" . '<input type="hidden" name="field_length_orig[' . $i . ']" value="' . urlencode($length) . '" />';
    } else {
        $content_cells[$i][$ci] = '';
    }

    if (preg_match('@^(set|enum)$@i', $type)) {
        $binary           = 0;
        $unsigned         = 0;
        $zerofill         = 0;
        $length_to_display = htmlspecialchars($length);
    } else {
        $length_to_display = $length;
        $binary           = FALSE;
        $unsigned         = stristr($row['Type'], 'unsigned');
        $zerofill         = stristr($row['Type'], 'zerofill');
    }

    $content_cells[$i][$ci] .= "\n" . '<input id="field_' . $i . '_' . ($ci - $ci_offset) . '" type="text" name="field_length[' . $i . ']" size="8" value="' . str_replace('"', '&quot;', $length_to_display) . '" class="textfield" />' . "\n";
    $ci++;

    $tmp_collation          = empty($row['Collation']) ? null : $row['Collation'];
    $content_cells[$i][$ci] = PMA_generateCharsetDropdownBox(PMA_CSDROPDOWN_COLLATION, 'field_collation[' . $i . ']', 'field_' . $i . '_' . ($ci - $ci_offset), $tmp_collation, FALSE);
    unset($tmp_collation);
    $ci++;

    $content_cells[$i][$ci] = '<select style="font-size: 70%;" name="field_attribute[' . $i . ']" id="field_' . $i . '_' . ($ci - $ci_offset) . '">' . "\n";

    $attribute     = '';
    if ($binary) {
        $attribute = 'BINARY';
    }
    if ($unsigned) {
        $attribute = 'UNSIGNED';
    }
    if ($zerofill) {
        $attribute = 'UNSIGNED ZEROFILL';
    }

    if (isset($submit_attribute) && $submit_attribute != FALSE) {
        $attribute = $submit_attribute;
    }

    // here, we have a TIMESTAMP that SHOW FULL FIELDS reports as having the
    // NULL attribute, but SHOW CREATE TABLE says the contrary. Believe
    // the latter.
    if (PMA_MYSQL_INT_VERSION < 50025
     && isset($row['Field'])
     && isset($analyzed_sql[0]['create_table_fields'][$row['Field']]['type'])
     && $analyzed_sql[0]['create_table_fields'][$row['Field']]['type'] == 'TIMESTAMP'
     && $analyzed_sql[0]['create_table_fields'][$row['Field']]['timestamp_not_null'] == true) {
        $row['Null'] = '';
    }

    // MySQL 4.1.2+ TIMESTAMP options
    // (if on_update_current_timestamp is set, then it's TRUE)
    if (isset($row['Field'])
     && isset($analyzed_sql[0]['create_table_fields'][$row['Field']]['on_update_current_timestamp'])) {
        $attribute = 'ON UPDATE CURRENT_TIMESTAMP';
    }
    if ((isset($row['Field'])
      && isset($analyzed_sql[0]['create_table_fields'][$row['Field']]['default_current_timestamp']))
     || (isset($submit_default_current_timestamp) && $submit_default_current_timestamp)) {
        $default_current_timestamp = TRUE;
    } else {
        $default_current_timestamp = FALSE;
    }

    // Dynamically add ON UPDATE CURRENT_TIMESTAMP to the possible attributes
    if (! in_array('ON UPDATE CURRENT_TIMESTAMP', $cfg['AttributeTypes'])) {
        $cfg['AttributeTypes'][] = 'ON UPDATE CURRENT_TIMESTAMP';
    }


    $cnt_attribute_types = count($cfg['AttributeTypes']);
    for ($j = 0;$j < $cnt_attribute_types; $j++) {
        if ($cfg['AttributeTypes'][$j] == 'BINARY') {
            continue;
        }
        $content_cells[$i][$ci] .= '                <option value="'. $cfg['AttributeTypes'][$j] . '"';
        if (strtoupper($attribute) == strtoupper($cfg['AttributeTypes'][$j])) {
            $content_cells[$i][$ci] .= ' selected="selected"';
        }
        $content_cells[$i][$ci] .= '>' . $cfg['AttributeTypes'][$j] . '</option>' . "\n";
    }

    $content_cells[$i][$ci] .= '</select>';
    $ci++;

    $content_cells[$i][$ci] = '<input name="field_null[' . $i . ']"'
        . ' id="field_' . $i . '_' . ($ci - $ci_offset) . '"';

    if (! empty($row['Null']) && $row['Null'] != 'NO' && $row['Null'] != 'NOT NULL') {
        $content_cells[$i][$ci] .= ' checked="checked"';
    }

    $content_cells[$i][$ci] .= ' type="checkbox" value="NULL" />';
    $ci++;

    if (isset($row)
      && !isset($row['Default']) && isset($row['Null']) && $row['Null'] == 'YES') {
        $row['Default'] = 'NULL';
    }

    if ($is_backup) {
        $content_cells[$i][$ci] = "\n" . '<input type="hidden" name="field_default_orig[' . $i . ']" size="8" value="' . (isset($row) && isset($row['Default']) ? urlencode($row['Default']) : '') . '" />';
    } else {
        $content_cells[$i][$ci] = "\n";
    }

    // for a TIMESTAMP, do not show CURRENT_TIMESTAMP as a default value
    if ($type_upper == 'TIMESTAMP'
     && $default_current_timestamp
     && isset($row['Default'])) {
        $row['Default'] = '';
    }

    $content_cells[$i][$ci] .= '<input id="field_' . $i . '_' . ($ci - $ci_offset) . '" type="text" name="field_default[' . $i . ']" size="12" value="' . (isset($row) && isset($row['Default']) ? str_replace('"', '&quot;', $row['Default']) : '') . '" class="textfield" />';
    if ($type_upper == 'TIMESTAMP') {
        $tmp_display_type = 'block';
    } else {
        $tmp_display_type = 'none';
        $default_current_timestamp = FALSE;
    }
    $content_cells[$i][$ci] .= '<br /><div id="div_' . $i . '_' . ($ci - $ci_offset) . '" style="white-space: nowrap; display: ' . $tmp_display_type . '"><input id="field_' . $i . '_' . ($ci - $ci_offset) . 'a" type="checkbox" name="field_default_current_timestamp[' . $i . ']"';
    if ($default_current_timestamp) {
        $content_cells[$i][$ci] .= ' checked="checked" ';
    }
    $content_cells[$i][$ci] .= ' /><label for="field_' . $i . '_' . ($ci - $ci_offset) . 'a" style="font-size: 70%;">CURRENT_TIMESTAMP</label></div>';
    $ci++;

    $content_cells[$i][$ci] = '<select name="field_extra[' . $i . ']" id="field_' . $i . '_' . ($ci - $ci_offset) . '">';

    if (!isset($row) || empty($row['Extra'])) {
        $content_cells[$i][$ci] .= "\n";
        $content_cells[$i][$ci] .= '<option value="">&nbsp;</option>' . "\n";
        $content_cells[$i][$ci] .= '<option value="AUTO_INCREMENT">auto_increment</option>' . "\n";
    } else {
        $content_cells[$i][$ci] .= "\n";
        $content_cells[$i][$ci] .= '<option value="AUTO_INCREMENT">auto_increment</option>' . "\n";
        $content_cells[$i][$ci] .= '<option value="">&nbsp;</option>' . "\n";
    }

    $content_cells[$i][$ci] .= "\n" . '</select>';
    $ci++;


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

        $content_cells[$i][$ci] = "\n" . '<input type="radio" name="field_key_' . $i . '" value="primary_' . $i . '"' . $checked_primary . ' title="' . $strPrimary . '" />';
        $ci++;

        $content_cells[$i][$ci] = "\n" . '<input type="radio" name="field_key_' . $i . '" value="index_' . $i . '"' .  $checked_index . ' title="' . $strIndex . '" />';
        $ci++;

        $content_cells[$i][$ci] = "\n" . '<input type="radio" name="field_key_' . $i . '" value="unique_' . $i . '"' .  $checked_unique . ' title="' . $strUnique . '" />';
        $ci++;

        $content_cells[$i][$ci] = "\n" . '<input type="radio" name="field_key_' . $i . '" value="none_' . $i . '"' .  $checked_none . ' title="---" />';
        $ci++;

        $content_cells[$i][$ci] = '<input type="checkbox" name="field_fulltext[' . $i . ']" value="' . $i . '"' . $checked_fulltext . ' title="' . $strIdxFulltext . '" />';
        $ci++;
    } // end if ($action ==...)

    // garvin: comments
    $content_cells[$i][$ci] = '<input id="field_' . $i . '_' . ($ci - $ci_offset) . '" type="text" name="field_comments[' . $i . ']" size="12" value="' . (isset($row) && isset($row['Field']) && is_array($comments_map) && isset($comments_map[$row['Field']]) ?  htmlspecialchars($comments_map[$row['Field']]) : '') . '" class="textfield" />';
    $ci++;

    // garvin: MIME-types
    if ($cfgRelation['mimework'] && $cfg['BrowseMIME'] && $cfgRelation['commwork']) {
        $content_cells[$i][$ci] = '<select id="field_' . $i . '_' . ($ci - $ci_offset) . '" size="1" name="field_mimetype[' . $i . ']">' . "\n";
        $content_cells[$i][$ci] .= '    <option value="">&nbsp;</option>' . "\n";

        if (is_array($available_mime['mimetype'])) {
            foreach ($available_mime['mimetype'] AS $mimekey => $mimetype) {
                $checked = (isset($row) && isset($row['Field']) && isset($mime_map[$row['Field']]['mimetype']) && ($mime_map[$row['Field']]['mimetype'] == str_replace('/', '_', $mimetype)) ? 'selected ' : '');
                $content_cells[$i][$ci] .= '    <option value="' . str_replace('/', '_', $mimetype) . '" ' . $checked . '>' . htmlspecialchars($mimetype) . '</option>';
            }
        }

        $content_cells[$i][$ci] .= '</select>';
        $ci++;

        $content_cells[$i][$ci] = '<select id="field_' . $i . '_' . ($ci - $ci_offset) . '" size="1" name="field_transformation[' . $i . ']">' . "\n";
        $content_cells[$i][$ci] .= '    <option value="" title="' . $strNone . '"></option>' . "\n";
        if (is_array($available_mime['transformation'])) {
            foreach ($available_mime['transformation'] AS $mimekey => $transform) {
                $checked = (isset($row) && isset($row['Field']) && isset($mime_map[$row['Field']]['transformation']) && (preg_match('@' . preg_quote($available_mime['transformation_file'][$mimekey]) . '3?@i', $mime_map[$row['Field']]['transformation'])) ? 'selected ' : '');
                $tooltip = 'strTransformation_' . strtolower(str_replace('.inc.php', '', $available_mime['transformation_file'][$mimekey]));
                $tooltip = isset($$tooltip) ? $$tooltip : sprintf(str_replace('<br />', ' ', $strMIME_nodescription), 'PMA_transformation_' . $tooltip . '()');
                $content_cells[$i][$ci] .= '<option value="' . $available_mime['transformation_file'][$mimekey] . '" ' . $checked . ' title="' . htmlspecialchars($tooltip) . '">' . htmlspecialchars($transform) . '</option>' . "\n";
            }
        }

        $content_cells[$i][$ci] .= '</select>';
        $ci++;

        $content_cells[$i][$ci] = '<input id="field_' . $i . '_' . ($ci - $ci_offset) . '" type="text" name="field_transformation_options[' . $i . ']" size="16" value="' . (isset($row) && isset($row['Field']) && isset($mime_map[$row['Field']]['transformation_options']) ?  htmlspecialchars($mime_map[$row['Field']]['transformation_options']) : '') . '" class="textfield" />';
        //$ci++;
    }
} // end for

if ($cfg['CtrlArrowsMoving']) {
    ?>
<script src="./js/keyhandler.js" type="text/javascript"></script>
<script type="text/javascript">
// <![CDATA[
var switch_movement = <?php echo $display_type == 'horizontal' ? '0' : '1'; ?>;
document.onkeydown = onKeyDownArrowsHandler;
// ]]>
</script>
    <?php
}
// here, the div_x_7 represents a div id which contains
// the default CURRENT TIMESTAMP checkbox and label
// and, field_x_7a represents the checkbox itself

?>
<script type="text/javascript">
// <![CDATA[
function display_field_options(field_type, i) {
    if (field_type == 'TIMESTAMP') {
        getElement('div_' + i + '_7').style.display = 'block';
    } else {
        getElement('div_' + i + '_7').style.display = 'none';
        getElement('field_' + i + '_7a').checked = false;
    }
    return true;
}
// ]]>
</script>

<form method="post" action="<?php echo $action; ?>">
<?php
echo PMA_generate_common_hidden_inputs($_form_aprams);

if (is_array($content_cells) && is_array($header_cells)) {
    // last row is for javascript insert
    $empty_row = array_pop($content_cells);

    echo '<table id="table_columns">';
    if ($display_type == 'horizontal') {
        ?>
<tr>
        <?php foreach ($header_cells as $header_val) { ?>
    <th><?php echo $header_val; ?></th>
        <?php } ?>
</tr>
        <?php

        $odd_row = true;
        foreach ($content_cells as $content_row) {
            echo '<tr class="' . ($odd_row ? 'odd' : 'even') . ' noclick">';
            $odd_row = ! $odd_row;

            if (is_array($content_row)) {
                foreach ($content_row as $content_row_val) {
                    ?>
    <td align="center"><?php echo $content_row_val; ?></td>
                    <?php
                }
            }
            echo '</tr>';
        }
    } else {
        $i = 0;
        $odd_row = true;
        foreach ($header_cells as $header_val) {
            echo '<tr class="' . ($odd_row ? 'odd' : 'even') . ' noclick">';
            $odd_row = ! $odd_row;
            ?>
    <th><?php echo $header_val; ?></th>
            <?php
            foreach ($content_cells as $content_cell) {
                if (isset($content_cell[$i]) && $content_cell[$i] != '') {
                    ?>
    <td><?php echo $content_cell[$i]; ?></td>
                    <?php
                }
            }
            echo '</tr>';
            $i++;
        }
    }
    ?>
</table>
<br />
    <?php
}

/**
 * needs to be finished
 *
 *
if ($display_type == 'horizontal') {
    $new_field = '';
    foreach ($empty_row as $content_row_val) {
        $new_field .= '<td align="center">' . $content_row_val . '</td>';
    }
    ?>
<script type="text/javascript">
// <![CDATA[
var odd_row = <?php echo $odd_row; ?>;

function addField() {
    var new_fields = document.getElementById('added_fields').value;
    var new_field_container = document.getElementById('table_columns');
    var new_field = '<?php echo preg_replace('|\s+|', ' ', preg_replace('|\'|', '\\\'', $new_field)); ?>';
    var i = 0;
    for (i = 0; i < new_fields; i++) {
        if (odd_row) {
            new_field_container.innerHTML += '<tr class="odd">' + new_field + '</tr>';
        } else {
            new_field_container.innerHTML += '<tr class="even">' + new_field + '</tr>';
        }
        odd_row = ! odd_row;
    }

    return true;
}
// ]]>
</script>
    <?php
}
 */

if ($action == 'tbl_create.php') {
    ?>
    <table>
    <tr valign="top">
        <th><?php echo $strTableComments; ?>:&nbsp;</th>
        <td width="25">&nbsp;</td>
        <th><?php echo $strStorageEngine; ?>:
            <?php echo PMA_showMySQLDocu('Storage_engines', 'Storage_engines'); ?>
        </th>
        <td width="25">&nbsp;</td>
        <th><?php echo $strCollation ;?>:&nbsp;</th>
    </tr>
    <tr><td><input type="text" name="comment" size="40" maxlength="80"
                value="<?php echo (isset($comment) ? $comment : ''); ?>"
                class="textfield" />
        </td>
        <td width="25">&nbsp;</td>
        <td>
    <?php
    echo PMA_StorageEngine::getHtmlSelect('tbl_type', null,
        (isset($GLOBALS['tbl_type']) ? $GLOBALS['tbl_type'] : null));
    ?>
        </td>
        <td width="25">&nbsp;</td>
        <td>
    <?php
    echo PMA_generateCharsetDropdownBox(PMA_CSDROPDOWN_COLLATION, 'tbl_collation',
        null, (isset($tbl_collation) ? $tbl_collation : null), FALSE, 3);
    ?>
        </td>
    </tr>
    <?php
    if (PMA_Partition::havePartitioning()) {
        ?>
    <tr valign="top">
        <th><?php echo $strPartitionDefinition; ?>:&nbsp;<?php echo PMA_showMySQLDocu('Partitioning', 'Partitioning'); ?>
        </th>
    </tr>
    <tr>
        <td>
            <textarea name="partition_definition" id="partitiondefinition" cols="<?php echo $GLOBALS['cfg']['TextareaCols'];?>" rows="<?php echo $GLOBALS['cfg']['TextareaRows'];?>" dir="<?php echo $GLOBALS['text_dir'];?>"></textarea>
        </td>
    </tr>
        <?php
    }
    ?>
    </table>
    <br />
    <?php
} // end if ($action == 'tbl_create.php')
?>

<fieldset class="tblFooters">
    <input type="submit" name="do_save_data" value="<?php echo $strSave; ?>" onclick="return checkTableEditForm(this.form, <?php echo $num_fields; ?>)" />
<?php if ($action == 'tbl_create.php' || $action == 'tbl_addfield.php') { ?>
    <?php echo $GLOBALS['strOr']; ?>
    <?php echo sprintf($strAddFields, '<input type="text" id="added_fields" name="added_fields" size="2" value="1" onfocus="this.select()" />'); ?>
    <input type="submit" name="submit_num_fields"
        value="<?php echo $GLOBALS['strGo']; ?>"
<?php /*        onclick="if (addField()) return false;" */ ?>
        onclick="return checkFormElementInRange(this.form, 'added_fields', '<?php echo str_replace('\'', '\\\'', $GLOBALS['strInvalidFieldAddCount']); ?>', 1)"
        />
<?php } ?>
</fieldset>

</form>

<center><?php echo PMA_showMySQLDocu('SQL-Syntax', 'CREATE_TABLE'); ?></center>
