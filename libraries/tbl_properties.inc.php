<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Display form for changing/adding table fields/columns
 *
 * included by tbl_addfield.php, -_alter.php, -_create.php
 * @version $Id$
 * @package phpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

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

if ('horizontal' == $display_type) {
    $length_values_input_size = 8;
} else {
    $length_values_input_size = 30;
}

$_form_params = array(
    'db' => $db,
    'table' => $table,
);

if ($action == 'tbl_create.php') {
    $_form_params['reload'] = 1;
} elseif ($action == 'tbl_addfield.php') {
    $_form_params['field_where'] = $_REQUEST['field_where'];
    $_form_params['after_field'] = $_REQUEST['after_field'];
}

if (isset($num_fields)) {
    $_form_params['orig_num_fields'] = $num_fields;
}

if (isset($_REQUEST['field_where'])) {
    $_form_params['orig_field_where'] = $_REQUEST['field_where'];
}

if (isset($_REQUEST['after_field'])) {
    $_form_params['orig_after_field'] = $_REQUEST['after_field'];
}

if (isset($selected) && is_array($selected)) {
    foreach ($selected as $o_fld_nr => $o_fld_val) {
        $_form_params['selected[' . $o_fld_nr . ']'] = $o_fld_val;
        if (! isset($true_selected)) {
            $_form_params['true_selected[' . $o_fld_nr . ']'] = $o_fld_val;
        }
    }

    if (isset($true_selected) && is_array($true_selected)) {
        foreach ($true_selected as $o_fld_nr => $o_fld_val) {
            $_form_params['true_selected[' . $o_fld_nr . ']'] = $o_fld_val;
        }
    }
} elseif (isset($_REQUEST['field'])) {
    $_form_params['orig_field'] = $_REQUEST['field'];
    if (isset($orig_field)) {
        $_form_params['true_selected[]'] = $orig_field;
    } else {
        $_form_params['true_selected[]'] = $_REQUEST['field'];
    }
}

$is_backup = ($action != 'tbl_create.php' && $action != 'tbl_addfield.php');

$header_cells = array();
$content_cells = array();

$header_cells[] = $strField;
$header_cells[] = $strType
     . ($GLOBALS['cfg']['ReplaceHelpImg']
        ? PMA_showMySQLDocu('SQL-Syntax', 'data-types')
        : '<br /><span style="font-weight: normal">' . PMA_showMySQLDocu('SQL-Syntax', 'data-types')
     . '</span>');
$header_cells[] = $strLengthSet . PMA_showHint($strSetEnumVal);
$header_cells[] = $strDefault . PMA_showHint($strDefaultValueHelp);
$header_cells[] = $strCollation;
$header_cells[] = $strAttr;
$header_cells[] = $strNull;

// lem9: We could remove this 'if' and let the key information be shown and
// editable. However, for this to work, tbl_alter must be modified to use the
// key fields, as tbl_addfield does.

if (!$is_backup) {
    $header_cells[] = $strIndex;
}

$header_cells[] = '<abbr title="AUTO_INCREMENT">' . ($display_type == 'horizontal' ? 'A_I' : 'AUTO_INCREMENT') . '</abbr>';

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

    $hint = '<br />' .
        sprintf($strMIME_transformation_note,
            '<a href="transformation_overview.php?'
            . PMA_generate_common_url($db, $table) . '" target="_blank">',
            '</a>');


    $header_cells[] = $strMIME_MIMEtype;
    $header_cells[] = $strMIME_transformation;
    $header_cells[] = $strMIME_transformation_options
        . PMA_showHint($strMIME_transformation_options_note . $hint);
}

// garvin: workaround for field_fulltext, because its submitted indizes contain
//         the index as a value, not a key. Inserted here for easier maintaineance
//         and less code to change in existing files.
if (isset($field_fulltext) && is_array($field_fulltext)) {
    foreach ($field_fulltext as $fulltext_nr => $fulltext_indexkey) {
        $submit_fulltext[$fulltext_indexkey] = $fulltext_indexkey;
    }
}

for ($i = 0; $i < $num_fields; $i++) {
    if (! empty($regenerate)) {
        // An error happened with previous inputs, so we will restore the data
        // to embed it once again in this form.

        $row['Field']     = (isset($_REQUEST['field_name'][$i]) ? $_REQUEST['field_name'][$i] : false);
        $row['Type']      = (isset($_REQUEST['field_type'][$i]) ? $_REQUEST['field_type'][$i] : false);
        $row['Collation'] = (isset($_REQUEST['field_collation'][$i]) ? $_REQUEST['field_collation'][$i] : '');
        $row['Null']      = (isset($_REQUEST['field_null'][$i]) ? $_REQUEST['field_null'][$i] : '');

        if (isset($_REQUEST['field_key'][$i]) && $_REQUEST['field_key'][$i] == 'primary_' . $i) {
            $row['Key'] = 'PRI';
        } elseif (isset($_REQUEST['field_key'][$i]) && $_REQUEST['field_key'][$i] == 'index_' . $i) {
            $row['Key'] = 'MUL';
        } elseif (isset($_REQUEST['field_key'][$i]) && $_REQUEST['field_key'][$i] == 'unique_' . $i) {
            $row['Key'] = 'UNI';
        } elseif (isset($_REQUEST['field_key'][$i]) && $_REQUEST['field_key'][$i] == 'fulltext_' . $i) {
            $row['Key'] = 'FULLTEXT';
        } else {
            $row['Key'] = '';
        }

        // put None in the drop-down for Default, when someone adds a field
        $row['DefaultType']  = (isset($_REQUEST['field_default_type'][$i]) ? $_REQUEST['field_default_type'][$i] : 'NONE');
        $row['DefaultValue'] = (isset($_REQUEST['field_default_value'][$i]) ? $_REQUEST['field_default_value'][$i] : '');

        switch ($row['DefaultType']) {
            case 'NONE' :
                $row['Default'] = null;
                break;
            case 'USER_DEFINED' :
                $row['Default'] = $row['DefaultValue'];
                break;
            case 'NULL' :
            case 'CURRENT_TIMESTAMP' :
                $row['Default'] = $row['DefaultType'];
                break;
        }

        $row['Extra']     = (isset($_REQUEST['field_extra'][$i]) ? $_REQUEST['field_extra'][$i] : false);
        $row['Comment']   = (isset($submit_fulltext[$i]) && ($submit_fulltext[$i] == $i) ? 'FULLTEXT' : false);

        $submit_length    = (isset($_REQUEST['field_length'][$i]) ? $_REQUEST['field_length'][$i] : false);
        $submit_attribute = (isset($_REQUEST['field_attribute'][$i]) ? $_REQUEST['field_attribute'][$i] : false);

        $submit_default_current_timestamp = (isset($_REQUEST['field_default_current_timestamp'][$i]) ? true : false);

        if (isset($_REQUEST['field_comments'][$i])) {
            $comments_map[$row['Field']] = $_REQUEST['field_comments'][$i];
        }

        if (isset($_REQUEST['field_mimetype'][$i])) {
            $mime_map[$row['Field']]['mimetype'] = $_REQUEST['field_mimetype'][$i];
        }

        if (isset($_REQUEST['field_transformation'][$i])) {
            $mime_map[$row['Field']]['transformation'] = $_REQUEST['field_transformation'][$i];
        }

        if (isset($_REQUEST['field_transformation_options'][$i])) {
            $mime_map[$row['Field']]['transformation_options'] = $_REQUEST['field_transformation_options'][$i];
        }

    } elseif (isset($fields_meta[$i])) {
        $row = $fields_meta[$i];
        switch ($row['Default']) {
            case null:
                if ($row['Null'] == 'YES') {
                    $row['DefaultType']  = 'NULL';
                    $row['DefaultValue'] = '';
    // SHOW FULL FIELDS does not report the case when there is a DEFAULT value
    // which is empty so we need to use the results of SHOW CREATE TABLE
                } elseif (isset($row) && isset($analyzed_sql[0]['create_table_fields'][$row['Field']]['default_value'])) {
                    $row['DefaultType']  = 'USER_DEFINED';
                    $row['DefaultValue'] = $row['Default'];
                } else {
                    $row['DefaultType']  = 'NONE';
                    $row['DefaultValue'] = '';
                }
                break;
            case 'CURRENT_TIMESTAMP':
                $row['DefaultType']  = 'CURRENT_TIMESTAMP';
                $row['DefaultValue'] = '';
                break;
            default:
                $row['DefaultType']  = 'USER_DEFINED';
                $row['DefaultValue'] = $row['Default'];
                break;
        }
    }

    if (isset($row['Type'])) {
        $extracted_fieldspec = PMA_extractFieldSpec($row['Type']);
        if ($extracted_fieldspec['type'] == 'bit') {
            $row['Default'] = PMA_convert_bit_default_value($row['Default']);
        }
    }
    // Cell index: If certain fields get left out, the counter shouldn't change.
    $ci = 0;
    // Everytime a cell shall be left out the STRG-jumping feature, $ci_offset
    // has to be incremented ($ci_offset++)
    $ci_offset = -1;

    // old column name
    if ($is_backup) {
        if (! empty($true_selected[$i])) {
            $_form_params['field_orig[' . $i . ']'] = $true_selected[$i];
        } elseif (isset($row['Field'])) {
            $_form_params['field_orig[' . $i . ']'] = $row['Field'];
        } else {
            $_form_params['field_orig[' . $i . ']'] = '';
        }
    }

    // column name
    $content_cells[$i][$ci] = '<input id="field_' . $i . '_' . ($ci - $ci_offset) . '"'
        . ' type="text" name="field_name[' . $i . ']"'
        . ' maxlength="64" class="textfield" title="' . $strField . '"'
        . ' size="' . ($GLOBALS['cfg']['DefaultPropDisplay'] == 'horizontal' ? '10' : '30') . '"'
        . ' value="' . (isset($row['Field']) ? htmlspecialchars($row['Field']) : '') . '"'
        . ' />';
    $ci++;

    // column type
    $content_cells[$i][$ci] = '<select name="field_type[' . $i . ']"'
        .' id="field_' . $i . '_' . ($ci - $ci_offset) . '" >';

    if (empty($row['Type'])) {
        // creating a column
        $row['Type'] = '';
        $type        = '';
    } else {
        $type        = $row['Type'];
    }

    if (! empty($row['Type'])) {
        $type = $extracted_fieldspec['type'];
        if ('set' == $extracted_fieldspec['type'] || 'enum' == $extracted_fieldspec['type']) {
            $length = $extracted_fieldspec['spec_in_brackets'];
        } else {
            // strip the "BINARY" attribute, except if we find "BINARY(" because
            // this would be a BINARY or VARBINARY field type
            $type   = preg_replace('@BINARY([^\(])@i', '', $type);
            $type   = preg_replace('@ZEROFILL@i', '', $type);
            $type   = preg_replace('@UNSIGNED@i', '', $type);
            $length = $extracted_fieldspec['spec_in_brackets'];
        } // end if else
    } else {
        // creating a column
        $length = '';
    }

    // some types, for example longtext, are reported as
    // "longtext character set latin7" when their charset and / or collation
    // differs from the ones of the corresponding database.
    $tmp = strpos($type, 'character set');
    if ($tmp) {
        $type = substr($type, 0, $tmp - 1);
    }

    if (isset($submit_length) && $submit_length != false) {
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
                $content_cells[$i][$ci] .= '>' . $col_group_type . '</option>';
            }
            $content_cells[$i][$ci] .= '</optgroup>';
            continue;
        }

        $content_cells[$i][$ci] .= '<option value="'. $column_type . '"';
        if ($type_upper == strtoupper($column_type)) {
            $content_cells[$i][$ci] .= ' selected="selected"';
        }
        $content_cells[$i][$ci] .= '>' . $column_type . '</option>';
    } // end for

    $content_cells[$i][$ci] .= '    </select>';
    $ci++;

    // old column length
    if ($is_backup) {
        $_form_params['field_length_orig[' . $i . ']'] = $length;
    }

    // column length
    if (isset($extracted_fieldspec) && ('set' == $extracted_fieldspec['type'] || 'enum' == $extracted_fieldspec['type'])) {
        $binary           = 0;
        $unsigned         = 0;
        $zerofill         = 0;
    } else {
        $binary           = false;
        $unsigned         = stristr($row['Type'], 'unsigned');
        $zerofill         = stristr($row['Type'], 'zerofill');
    }
    $length_to_display = $length;

    $content_cells[$i][$ci] = '<input id="field_' . $i . '_' . ($ci - $ci_offset) . '"'
        . ' type="text" name="field_length[' . $i . ']" size="' . $length_values_input_size . '"'
        . ' value="' . htmlspecialchars($length_to_display) . '"'
        . ' class="textfield" />';
    $ci++;

    // column default
    /**
     * having NULL enabled does not implicit having Default with NULL
     *
    if (isset($row)
      && !isset($row['Default']) && isset($row['Null']) && $row['Null'] == 'YES') {
        $row['Default'] = 'NULL';
    }
     */

    // old column default
    if ($is_backup) {
        $_form_params['field_default_orig[' . $i . ']'] =
            (isset($row['Default']) ? $row['Default'] : '');
    }

    // here we put 'NONE' as the default value of drop-down; otherwise
    // users would have problems if they forget to enter the default
    // value (example, for an INT)
    $default_options = array(
        'NONE'              =>  $strNoneDefault,
        'USER_DEFINED'      =>  $strAsDefined,
        'NULL'              => 'NULL',
        'CURRENT_TIMESTAMP' => 'CURRENT_TIMESTAMP',
    );

    // for a TIMESTAMP, do not show the string "CURRENT_TIMESTAMP" as a default value
    if ($type_upper == 'TIMESTAMP'
     && ! empty($default_current_timestamp)
     && isset($row['Default'])) {
        $row['Default'] = '';
    }

    if ($type_upper == 'BIT') {
        $row['DefaultValue'] = PMA_convert_bit_default_value($row['DefaultValue']);
    } 

    $content_cells[$i][$ci] = '<select name="field_default_type[' . $i . ']">';
    foreach ($default_options as $key => $value) {
        $content_cells[$i][$ci] .= '<option value="' . $key . '"';
        // is only set when we go back to edit a field's structure
        if (isset($row['DefaultType']) && $row['DefaultType'] == $key) {
            $content_cells[$i][$ci] .= ' selected="selected"';
        }
        $content_cells[$i][$ci] .= ' >' . $value . '</option>';
    }
    $content_cells[$i][$ci] .= '</select>';
    $content_cells[$i][$ci] .= '<br />';
    $content_cells[$i][$ci] .= '<input id="field_' . $i . '_' . ($ci - $ci_offset) . '"'
        . ' type="text" name="field_default_value[' . $i . ']" size="12"'
        . ' value="' . (isset($row['DefaultValue']) ? htmlspecialchars($row['DefaultValue']) : '') . '"'
        . ' class="textfield" />';
    $ci++;

    // column collation
    $tmp_collation          = empty($row['Collation']) ? null : $row['Collation'];
    $content_cells[$i][$ci] = PMA_generateCharsetDropdownBox(
        PMA_CSDROPDOWN_COLLATION, 'field_collation[' . $i . ']',
        'field_' . $i . '_' . ($ci - $ci_offset), $tmp_collation, false);
    unset($tmp_collation);
    $ci++;

    // column attribute
    $content_cells[$i][$ci] = '<select style="font-size: 70%;"'
        . ' name="field_attribute[' . $i . ']"'
        . ' id="field_' . $i . '_' . ($ci - $ci_offset) . '">';

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
    if (isset($row['Extra']) && $row['Extra'] == 'on update CURRENT_TIMESTAMP') {
        $attribute = 'on update CURRENT_TIMESTAMP';
    }

    if (isset($submit_attribute) && $submit_attribute != false) {
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
        $attribute = 'on update CURRENT_TIMESTAMP';
    }
    if ((isset($row['Field'])
      && isset($analyzed_sql[0]['create_table_fields'][$row['Field']]['default_current_timestamp']))
     || (isset($submit_default_current_timestamp) && $submit_default_current_timestamp)) {
        $default_current_timestamp = true;
    } else {
        $default_current_timestamp = false;
    }

    $cnt_attribute_types = count($cfg['AttributeTypes']);
    for ($j = 0; $j < $cnt_attribute_types; $j++) {
        $content_cells[$i][$ci] .= '                <option value="'. $cfg['AttributeTypes'][$j] . '"';
        if (strtoupper($attribute) == strtoupper($cfg['AttributeTypes'][$j])) {
            $content_cells[$i][$ci] .= ' selected="selected"';
        }
        $content_cells[$i][$ci] .= '>' . $cfg['AttributeTypes'][$j] . '</option>';
    }

    $content_cells[$i][$ci] .= '</select>';
    $ci++;

    // column NULL
    $content_cells[$i][$ci] = '<input name="field_null[' . $i . ']"'
        . ' id="field_' . $i . '_' . ($ci - $ci_offset) . '"';

    if (! empty($row['Null']) && $row['Null'] != 'NO' && $row['Null'] != 'NOT NULL') {
        $content_cells[$i][$ci] .= ' checked="checked"';
    }

    $content_cells[$i][$ci] .= ' type="checkbox" value="NULL" />';
    $ci++;

    // column indexes
    // lem9: See my other comment about removing this 'if'.
    if (!$is_backup) {
        $content_cells[$i][$ci] = '<select name="field_key[' . $i . ']"'
            . ' id="field_' . $i . '_' . ($ci - $ci_offset) . '">';
        $content_cells[$i][$ci] .= '<option value="none_' . $i . '">---</option>';

        $content_cells[$i][$ci] .= '<option value="primary_' . $i . '" title="' . $strPrimary . '"';
        if (isset($row['Key']) && $row['Key'] == 'PRI') {
            $content_cells[$i][$ci] .= ' selected="selected"';
        }
        $content_cells[$i][$ci] .= '>PRIMARY</option>';

        $content_cells[$i][$ci] .= '<option value="unique_' . $i . '" title="' . $strUnique . '"';
        if (isset($row['Key']) && $row['Key'] == 'UNI') {
            $content_cells[$i][$ci] .= ' selected="selected"';
        }
        $content_cells[$i][$ci] .= '>UNIQUE</option>';

        $content_cells[$i][$ci] .= '<option value="index_' . $i . '" title="' . $strIndex . '"';
        if (isset($row['Key']) && $row['Key'] == 'MUL') {
            $content_cells[$i][$ci] .= ' selected="selected"';
        }
        $content_cells[$i][$ci] .= '>INDEX</option>';

        $content_cells[$i][$ci] .= '<option value="fulltext_' . $i . '" title="' . $strIdxFulltext . '"';
        if (isset($row['Key']) && $row['Key'] == 'FULLTEXT') {
            $content_cells[$i][$ci] .= ' selected="selected"';
        }
        $content_cells[$i][$ci] .= '>FULLTEXT</option>';

        $content_cells[$i][$ci] .= '</select>';
        $ci++;
    } // end if ($action ==...)

    // column auto_increment
    $content_cells[$i][$ci] = '<input name="field_extra[' . $i . ']"'
        . ' id="field_' . $i . '_' . ($ci - $ci_offset) . '"';

    if (isset($row['Extra']) && strtolower($row['Extra']) == 'auto_increment') {
        $content_cells[$i][$ci] .= ' checked="checked"';
    }

    $content_cells[$i][$ci] .= ' type="checkbox" value="AUTO_INCREMENT" />';
    $ci++;

    // column comments
    $content_cells[$i][$ci] = '<input id="field_' . $i . '_' . ($ci - $ci_offset) . '"'
        . ' type="text" name="field_comments[' . $i . ']" size="12"'
        . ' value="' . (isset($row['Field']) && is_array($comments_map) && isset($comments_map[$row['Field']]) ?  htmlspecialchars($comments_map[$row['Field']]) : '') . '"'
        . ' class="textfield" />';
    $ci++;

    // column MIME-types
    if ($cfgRelation['mimework'] && $cfg['BrowseMIME'] && $cfgRelation['commwork']) {
        $content_cells[$i][$ci] = '<select id="field_' . $i . '_' . ($ci - $ci_offset) . '" size="1" name="field_mimetype[' . $i . ']">';
        $content_cells[$i][$ci] .= '    <option value="">&nbsp;</option>';

        if (is_array($available_mime['mimetype'])) {
            foreach ($available_mime['mimetype'] AS $mimekey => $mimetype) {
                $checked = (isset($row['Field']) && isset($mime_map[$row['Field']]['mimetype']) && ($mime_map[$row['Field']]['mimetype'] == str_replace('/', '_', $mimetype)) ? 'selected ' : '');
                $content_cells[$i][$ci] .= '    <option value="' . str_replace('/', '_', $mimetype) . '" ' . $checked . '>' . htmlspecialchars($mimetype) . '</option>';
            }
        }

        $content_cells[$i][$ci] .= '</select>';
        $ci++;

        $content_cells[$i][$ci] = '<select id="field_' . $i . '_' . ($ci - $ci_offset) . '" size="1" name="field_transformation[' . $i . ']">';
        $content_cells[$i][$ci] .= '    <option value="" title="' . $strNone . '"></option>';
        if (is_array($available_mime['transformation'])) {
            foreach ($available_mime['transformation'] AS $mimekey => $transform) {
                $checked = (isset($row['Field']) && isset($mime_map[$row['Field']]['transformation']) && (preg_match('@' . preg_quote($available_mime['transformation_file'][$mimekey]) . '3?@i', $mime_map[$row['Field']]['transformation'])) ? 'selected ' : '');
                $tooltip = 'strTransformation_' . strtolower(str_replace('.inc.php', '', $available_mime['transformation_file'][$mimekey]));
                $tooltip = isset($$tooltip) ? $$tooltip : sprintf(str_replace('<br />', ' ', $strMIME_nodescription), 'PMA_transformation_' . $tooltip . '()');
                $content_cells[$i][$ci] .= '<option value="' . $available_mime['transformation_file'][$mimekey] . '" ' . $checked . ' title="' . htmlspecialchars($tooltip) . '">' . htmlspecialchars($transform) . '</option>';
            }
        }

        $content_cells[$i][$ci] .= '</select>';
        $ci++;

        $content_cells[$i][$ci] = '<input id="field_' . $i . '_' . ($ci - $ci_offset) . '"'
            . ' type="text" name="field_transformation_options[' . $i . ']"'
            . ' size="16" class="textfield"'
            . ' value="' . (isset($row['Field']) && isset($mime_map[$row['Field']]['transformation_options']) ?  htmlspecialchars($mime_map[$row['Field']]['transformation_options']) : '') . '"'
            . ' />';
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
?>

<form method="post" action="<?php echo $action; ?>">
<?php
echo PMA_generate_common_hidden_inputs($_form_params);
unset($_form_params);

if (is_array($content_cells) && is_array($header_cells)) {
    // last row is for javascript insert
    //$empty_row = array_pop($content_cells);

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
                value="<?php echo (isset($_REQUEST['comment']) ? htmlspecialchars($_REQUEST['comment']) : ''); ?>"
                class="textfield" />
        </td>
        <td width="25">&nbsp;</td>
        <td>
    <?php
    echo PMA_StorageEngine::getHtmlSelect('tbl_type', null,
        (isset($_REQUEST['tbl_type']) ? $_REQUEST['tbl_type'] : null));
    ?>
        </td>
        <td width="25">&nbsp;</td>
        <td>
    <?php
    echo PMA_generateCharsetDropdownBox(PMA_CSDROPDOWN_COLLATION, 'tbl_collation',
        null, (isset($_REQUEST['tbl_collation']) ? $_REQUEST['tbl_collation'] : null), false, 3);
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
            <textarea name="partition_definition" id="partitiondefinition"
                cols="<?php echo $GLOBALS['cfg']['TextareaCols'];?>"
                rows="<?php echo $GLOBALS['cfg']['TextareaRows'];?>"
                dir="<?php echo $GLOBALS['text_dir'];?>"><?php echo (isset($_REQUEST['partition_definition']) ? htmlspecialchars($_REQUEST['partition_definition']) : ''); ?></textarea>
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
    <input type="submit" name="do_save_data" value="<?php echo $strSave; ?>"
        onclick="return checkTableEditForm(this.form, <?php echo $num_fields; ?>)" />
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
