<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Display form for changing/adding table fields/columns.
 * Included by tbl_addfield.php and tbl_create.php
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Check parameters
 */
require_once './libraries/Util.class.php';

PMA_Util::checkParameters(array('db', 'table', 'action', 'num_fields'));


// Get available character sets and storage engines
require_once './libraries/mysql_charsets.lib.php';
require_once './libraries/StorageEngine.class.php';

/**
 * Class for partition management
 */
require_once './libraries/Partition.class.php';

/**
 * We are in transition between old-style echo and new-style PMA_Response
 * so this script generates $html and at the bottom, either echos it
 * or uses addHTML on it. 
 *
 * Initialize $html in case this variable was used by a caller 
 * (yes, this script should be refactored into functions)
 */
$html = '';

$length_values_input_size = 8;

$_form_params = array(
    'db' => $db
);

if ($action == 'tbl_create.php') {
    $_form_params['reload'] = 1;
} elseif ($action == 'tbl_addfield.php') {
    $_form_params['field_where'] = $_REQUEST['field_where'];
    $_form_params['after_field'] = $_REQUEST['after_field'];
    $_form_params['table'] = $table;
} else {
    $_form_params['table'] = $table;
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

$header_cells[] = __('Name');
$header_cells[] = __('Type')
    . PMA_Util::showMySQLDocu('SQL-Syntax', 'data-types');
$header_cells[] = __('Length/Values')
    . PMA_Util::showHint(
        __(
            'If column type is "enum" or "set", please enter the values using'
            . ' this format: \'a\',\'b\',\'c\'…<br />If you ever need to put'
            . ' a backslash ("\") or a single quote ("\'") amongst those values,'
            . ' precede it with a backslash (for example \'\\\\xyz\' or \'a\\\'b\').'
        )
    );
$header_cells[] = __('Default')
    . PMA_Util::showHint(
        __(
            'For default values, please enter just a single value,'
            . ' without backslash escaping or quotes, using this format: a'
        )
    );
$header_cells[] = __('Collation');
$header_cells[] = __('Attributes');
$header_cells[] = __('Null');

// We could remove this 'if' and let the key information be shown and
// editable. However, for this to work, structure.lib.php must be modified 
// to use the key fields, as tbl_addfield does.

if (! $is_backup) {
    $header_cells[] = __('Index');
}

$header_cells[] = '<abbr title="AUTO_INCREMENT">A_I</abbr>';

require_once './libraries/transformations.lib.php';
$cfgRelation = PMA_getRelationsParam();

$comments_map = array();
$mime_map = array();
$available_mime = array();

$comments_map = PMA_getComments($db, $table);
$header_cells[] = __('Comments');

if (isset($fields_meta)) {
    // for moving, load all available column names
    $move_columns_sql_query    = 'SELECT * FROM ' 
        . PMA_Util::backquote($db)
        . '.'
        . PMA_Util::backquote($table)
        . ' LIMIT 1';
    $move_columns_sql_result = PMA_DBI_try_query($move_columns_sql_query);
    $move_columns = PMA_DBI_get_fields_meta($move_columns_sql_result);
    unset($move_columns_sql_query, $move_columns_sql_result);

    $header_cells[] = __('Move column');
}

if ($cfgRelation['mimework'] && $GLOBALS['cfg']['BrowseMIME']) {
    $mime_map = PMA_getMIME($db, $table);
    $available_mime = PMA_getAvailableMIMEtypes();

    $hint = '<br />'
        . sprintf(
            __(
                'For a list of available transformation options and their MIME'
                . ' type transformations, click on %stransformation descriptions%s'
            ),
            '<a href="transformation_overview.php?'
            . PMA_generate_common_url($db, $table)
            . '" target="_blank">',
            '</a>'
        );


    $header_cells[] = __('MIME type');
    $header_cells[] = __('Browser transformation');
    $header_cells[] = __('Transformation options')
        . PMA_Util::showHint(
            __(
                'Please enter the values for transformation options using this'
                . ' format: \'a\', 100, b,\'c\'…<br />If you ever need to put'
                . ' a backslash ("\") or a single quote ("\'") amongst those'
                . ' values, precede it with a backslash (for example \'\\\\xyz\''
                . ' or \'a\\\'b\').'
            )
            . $hint
        );
}

//  workaround for field_fulltext, because its submitted indices contain
//  the index as a value, not a key. Inserted here for easier maintaineance
//  and less code to change in existing files.
if (isset($field_fulltext) && is_array($field_fulltext)) {
    foreach ($field_fulltext as $fulltext_nr => $fulltext_indexkey) {
        $submit_fulltext[$fulltext_indexkey] = $fulltext_indexkey;
    }
}

for ($i = 0; $i < $num_fields; $i++) {
    if (! empty($regenerate)) {
        // An error happened with previous inputs, so we will restore the data
        // to embed it once again in this form.

        $row['Field'] = isset($_REQUEST['field_name'][$i])
            ? $_REQUEST['field_name'][$i]
            : false;
        $row['Type'] = isset($_REQUEST['field_type'][$i])
            ? $_REQUEST['field_type'][$i]
            : false;
        $row['Collation'] = isset($_REQUEST['field_collation'][$i])
            ? $_REQUEST['field_collation'][$i]
            : '';
        $row['Null'] = isset($_REQUEST['field_null'][$i])
            ? $_REQUEST['field_null'][$i]
            : '';

        if (isset($_REQUEST['field_key'][$i])
            && $_REQUEST['field_key'][$i] == 'primary_' . $i
        ) {
            $row['Key'] = 'PRI';
        } elseif (isset($_REQUEST['field_key'][$i])
            && $_REQUEST['field_key'][$i] == 'index_' . $i
        ) {
            $row['Key'] = 'MUL';
        } elseif (isset($_REQUEST['field_key'][$i])
            && $_REQUEST['field_key'][$i] == 'unique_' . $i
        ) {
            $row['Key'] = 'UNI';
        } elseif (isset($_REQUEST['field_key'][$i])
            && $_REQUEST['field_key'][$i] == 'fulltext_' . $i
        ) {
            $row['Key'] = 'FULLTEXT';
        } else {
            $row['Key'] = '';
        }

        // put None in the drop-down for Default, when someone adds a field
        $row['DefaultType'] = isset($_REQUEST['field_default_type'][$i])
            ? $_REQUEST['field_default_type'][$i]
            : 'NONE';
        $row['DefaultValue'] = isset($_REQUEST['field_default_value'][$i])
            ? $_REQUEST['field_default_value'][$i]
            : '';

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

        $row['Extra']
            = (isset($_REQUEST['field_extra'][$i])
            ? $_REQUEST['field_extra'][$i]
            : false);
        $row['Comment']
            = (isset($submit_fulltext[$i])
                && ($submit_fulltext[$i] == $i)
            ? 'FULLTEXT'
            : false);

        $submit_length
            = (isset($_REQUEST['field_length'][$i])
            ? $_REQUEST['field_length'][$i]
            : false);
        $submit_attribute
            = (isset($_REQUEST['field_attribute'][$i])
            ? $_REQUEST['field_attribute'][$i]
            : false);

        $submit_default_current_timestamp
            = (isset($_REQUEST['field_default_current_timestamp'][$i])
            ? true
            : false);

        if (isset($_REQUEST['field_comments'][$i])) {
            $comments_map[$row['Field']] = $_REQUEST['field_comments'][$i];
        }

        if (isset($_REQUEST['field_mimetype'][$i])) {
            $mime_map[$row['Field']]['mimetype'] = $_REQUEST['field_mimetype'][$i];
        }

        if (isset($_REQUEST['field_transformation'][$i])) {
            $mime_map[$row['Field']]['transformation']
                = $_REQUEST['field_transformation'][$i];
        }

        if (isset($_REQUEST['field_transformation_options'][$i])) {
            $mime_map[$row['Field']]['transformation_options']
                = $_REQUEST['field_transformation_options'][$i];
        }

    } elseif (isset($fields_meta[$i])) {
        $row = $fields_meta[$i];
        switch ($row['Default']) {
        case null:
            if ($row['Null'] == 'YES') {
                $row['DefaultType']  = 'NULL';
                $row['DefaultValue'] = '';
                // SHOW FULL COLUMNS does not report the case
                // when there is a DEFAULT value which is empty so we need to use the
                // results of SHOW CREATE TABLE
            } elseif (isset($row)
                && isset($analyzed_sql[0]['create_table_fields'][$row['Field']]
                    ['default_value'])
            ) {
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
        $extracted_columnspec = PMA_Util::extractColumnSpec($row['Type']);
        if ($extracted_columnspec['type'] == 'bit') {
            $row['Default']
                = PMA_Util::convertBitDefaultValue($row['Default']);
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
    $content_cells[$i][$ci] = '<input id="field_' . $i . '_' . ($ci - $ci_offset)
        . '"' . ' type="text" name="field_name[' . $i . ']"'
        . ' maxlength="64" class="textfield" title="' . __('Column') . '"'
        . ' size="10"'
        . ' value="' . (isset($row['Field']) ? htmlspecialchars($row['Field']) : '')
        . '"' . ' />';
    $ci++;

    // column type
    $select_id = 'field_' . $i . '_' . ($ci - $ci_offset);
    $content_cells[$i][$ci] = '<select class="column_type" name="field_type[' .
        $i . ']"' .' id="' . $select_id . '">';

    if (empty($row['Type'])) {
        // creating a column
        $row['Type'] = '';
        $type        = '';
        $length = '';
    } else {
        $type = $extracted_columnspec['type'];
        $length = $extracted_columnspec['spec_in_brackets'];
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

    $content_cells[$i][$ci]
        .= PMA_Util::getSupportedDatatypes(true, $type_upper);
    $content_cells[$i][$ci] .= '    </select>';
    $ci++;

    // old column length
    if ($is_backup) {
        $_form_params['field_length_orig[' . $i . ']'] = $length;
    }

    // column length
    $length_to_display = $length;

    $content_cells[$i][$ci] = '<input id="field_' . $i . '_' . ($ci - $ci_offset)
        . '"' . ' type="text" name="field_length[' . $i . ']" size="'
        . $length_values_input_size . '"' . ' value="' . htmlspecialchars(
            $length_to_display
        )
        . '"'
        . ' class="textfield" />'
        . '<p class="enum_notice" id="enum_notice_' . $i . '_' . ($ci - $ci_offset)
        . '">';
    $content_cells[$i][$ci] .= __('ENUM or SET data too long?')
        . '<a href="#" class="open_enum_editor"> '
        . __('Get more editing space') . '</a>'
        . '</p>';
    $ci++;

    // column default

    // old column default
    if ($is_backup) {
        $_form_params['field_default_orig[' . $i . ']']
            = (isset($row['Default']) ? $row['Default'] : '');
    }

    // here we put 'NONE' as the default value of drop-down; otherwise
    // users would have problems if they forget to enter the default
    // value (example, for an INT)
    $default_options = array(
        'NONE'              =>  _pgettext('for default', 'None'),
        'USER_DEFINED'      =>  __('As defined:'),
        'NULL'              => 'NULL',
        'CURRENT_TIMESTAMP' => 'CURRENT_TIMESTAMP',
    );

    // for a TIMESTAMP, do not show the string "CURRENT_TIMESTAMP" as a default value
    if ($type_upper == 'TIMESTAMP'
        && ! empty($default_current_timestamp)
        && isset($row['Default'])
    ) {
        $row['Default'] = '';
    }

    if ($type_upper == 'BIT') {
        $row['DefaultValue']
            = PMA_Util::convertBitDefaultValue($row['DefaultValue']);
    }

    $content_cells[$i][$ci] = '<select name="field_default_type[' . $i
        . ']" id="field_' . $i . '_' . ($ci - $ci_offset) 
        . '" class="default_type">';
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
    $content_cells[$i][$ci] .= '<input type="text"'
        . ' name="field_default_value[' . $i . ']" size="12"'
        . ' value="' . (isset($row['DefaultValue'])
            ? htmlspecialchars($row['DefaultValue'])
            : '') . '"'
        . ' class="textfield default_value" />';
    $ci++;

    // column collation
    $tmp_collation          = empty($row['Collation']) ? null : $row['Collation'];
    $content_cells[$i][$ci] = PMA_generateCharsetDropdownBox(
        PMA_CSDROPDOWN_COLLATION, 'field_collation[' . $i . ']',
        'field_' . $i . '_' . ($ci - $ci_offset), $tmp_collation, false
    );
    unset($tmp_collation);
    $ci++;

    // column attribute
    $content_cells[$i][$ci] = '<select style="font-size: 70%;"'
        . ' name="field_attribute[' . $i . ']"'
        . ' id="field_' . $i . '_' . ($ci - $ci_offset) . '">';

    $attribute     = '';
    if (isset($extracted_columnspec)) {
        $attribute = $extracted_columnspec['attribute'];
    }

    if (isset($row['Extra']) && $row['Extra'] == 'on update CURRENT_TIMESTAMP') {
        $attribute = 'on update CURRENT_TIMESTAMP';
    }

    if (isset($submit_attribute) && $submit_attribute != false) {
        $attribute = $submit_attribute;
    }

    // here, we have a TIMESTAMP that SHOW FULL COLUMNS reports as having the
    // NULL attribute, but SHOW CREATE TABLE says the contrary. Believe
    // the latter.
    if (PMA_MYSQL_INT_VERSION < 50025
        && isset($row['Field'])
        && isset($analyzed_sql[0]['create_table_fields'][$row['Field']]['type'])
        && $analyzed_sql[0]['create_table_fields'][$row['Field']]['type'] == 'TIMESTAMP'
        && $analyzed_sql[0]['create_table_fields'][$row['Field']]['timestamp_not_null'] == true
    ) {
        $row['Null'] = '';
    }

    // MySQL 4.1.2+ TIMESTAMP options
    // (if on_update_current_timestamp is set, then it's TRUE)
    if (isset($row['Field'])
        && isset($analyzed_sql[0]['create_table_fields'][$row['Field']]['on_update_current_timestamp'])
    ) {
        $attribute = 'on update CURRENT_TIMESTAMP';
    }
    if ((isset($row['Field'])
        && isset($analyzed_sql[0]['create_table_fields'][$row['Field']]['default_current_timestamp']))
        || (isset($submit_default_current_timestamp)
        && $submit_default_current_timestamp)
    ) {
        $default_current_timestamp = true;
    } else {
        $default_current_timestamp = false;
    }

    $attribute_types = $GLOBALS['PMA_Types']->getAttributes();
    $cnt_attribute_types = count($attribute_types);
    for ($j = 0; $j < $cnt_attribute_types; $j++) {
        $content_cells[$i][$ci]
            .= '                <option value="' . $attribute_types[$j] . '"';
        if (strtoupper($attribute) == strtoupper($attribute_types[$j])) {
            $content_cells[$i][$ci] .= ' selected="selected"';
        }
        $content_cells[$i][$ci] .= '>' . $attribute_types[$j] . '</option>';
    }

    $content_cells[$i][$ci] .= '</select>';
    $ci++;

    // column NULL
    $content_cells[$i][$ci] = '<input name="field_null[' . $i . ']"'
        . ' id="field_' . $i . '_' . ($ci - $ci_offset) . '"';

    if (! empty($row['Null'])
        && $row['Null'] != 'NO'
        && $row['Null'] != 'NOT NULL'
    ) {
        $content_cells[$i][$ci] .= ' checked="checked"';
    }

    $content_cells[$i][$ci] .= ' type="checkbox" value="NULL" class="allow_null"/>';
    $ci++;

    // column indexes
    // See my other comment about removing this 'if'.
    if (!$is_backup) {
        $content_cells[$i][$ci] = '<select name="field_key[' . $i . ']"'
            . ' id="field_' . $i . '_' . ($ci - $ci_offset) . '">';
        $content_cells[$i][$ci] .= '<option value="none_' . $i . '">---</option>';

        $content_cells[$i][$ci] .= '<option value="primary_' . $i . '" title="'
            . __('Primary') . '"';
        if (isset($row['Key']) && $row['Key'] == 'PRI') {
            $content_cells[$i][$ci] .= ' selected="selected"';
        }
        $content_cells[$i][$ci] .= '>PRIMARY</option>';

        $content_cells[$i][$ci] .= '<option value="unique_' . $i . '" title="'
            . __('Unique') . '"';
        if (isset($row['Key']) && $row['Key'] == 'UNI') {
            $content_cells[$i][$ci] .= ' selected="selected"';
        }
        $content_cells[$i][$ci] .= '>UNIQUE</option>';

        $content_cells[$i][$ci] .= '<option value="index_' . $i . '" title="'
            . __('Index') . '"';
        if (isset($row['Key']) && $row['Key'] == 'MUL') {
            $content_cells[$i][$ci] .= ' selected="selected"';
        }
        $content_cells[$i][$ci] .= '>INDEX</option>';

        if (!PMA_DRIZZLE) {
            $content_cells[$i][$ci] .= '<option value="fulltext_' . $i . '" title="'
                . __('Fulltext') . '"';
            if (isset($row['Key']) && $row['Key'] == 'FULLTEXT') {
                $content_cells[$i][$ci] .= ' selected="selected"';
            }
            $content_cells[$i][$ci] .= '>FULLTEXT</option>';
        }

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
    $content_cells[$i][$ci] = '<input id="field_' . $i . '_' . ($ci - $ci_offset)
        . '"' . ' type="text" name="field_comments[' . $i . ']" size="12"'
        . ' value="' . (isset($row['Field'])
                && is_array($comments_map)
                && isset($comments_map[$row['Field']])
            ?  htmlspecialchars($comments_map[$row['Field']])
            : '') . '"'
        . ' class="textfield" />';
    $ci++;

    // move column
    if (isset($fields_meta)) {
        $content_cells[$i][$ci] = '<select id="field_' . $i . '_'
            . ($ci - $ci_offset) . '"' . ' name="field_move_to[' . $i
            . ']" size="1" width="5em">'
            . '<option value="" selected="selected">&nbsp;</option>';

        // find index of current column
        $current_index = 0;
        for ($mi = 0, $cols = count($move_columns); $mi < $cols; $mi++) {
            if ($move_columns[$mi]->name == $row['Field']) {
                $current_index = $mi;
                break;
            }
        }
        $content_cells[$i][$ci] .= '<option value="-first"'
            . ($current_index == 0 ? ' disabled="disabled"' : '')
            . '>' . __('first') . '</option>';

        for ($mi = 0, $cols = count($move_columns); $mi < $cols; $mi++) {
            $content_cells[$i][$ci] .=
                '<option value="' . htmlspecialchars($move_columns[$mi]->name) . '"'
                . (($current_index == $mi || $current_index == $mi + 1)
                    ? ' disabled="disabled"'
                    : '')
                .'>'
                . sprintf(
                    __('after %s'),
                    PMA_Util::backquote(
                        htmlspecialchars(
                            $move_columns[$mi]->name
                        )
                    )
                )
                . '</option>';
        }

        $content_cells[$i][$ci] .= '</select>';
        $ci++;
    }

    // column MIME-types
    if ($cfgRelation['mimework']
        && $GLOBALS['cfg']['BrowseMIME']
        && $cfgRelation['commwork']
    ) {
        $content_cells[$i][$ci] = '<select id="field_' . $i . '_'
            . ($ci - $ci_offset) . '" size="1" name="field_mimetype[' . $i . ']">';
        $content_cells[$i][$ci] .= '    <option value="">&nbsp;</option>';

        if (is_array($available_mime['mimetype'])) {
            foreach ($available_mime['mimetype'] as $mimekey => $mimetype) {
                $checked = (isset($row['Field'])
                    && isset($mime_map[$row['Field']]['mimetype'])
                    && ($mime_map[$row['Field']]['mimetype']
                        == str_replace('/', '_', $mimetype))
                    ? 'selected '
                    : '');
                $content_cells[$i][$ci] .= '    <option value="'
                    . str_replace('/', '_', $mimetype) . '" ' . $checked . '>'
                    . htmlspecialchars($mimetype) . '</option>';
            }
        }

        $content_cells[$i][$ci] .= '</select>';
        $ci++;

        $content_cells[$i][$ci] = '<select id="field_' . $i . '_'
            . ($ci - $ci_offset) . '" size="1" name="field_transformation['
            . $i . ']">';
        $content_cells[$i][$ci] .= '    <option value="" title="' . __('None')
            . '"></option>';
        if (is_array($available_mime['transformation'])) {
            foreach ($available_mime['transformation'] as $mimekey => $transform) {
                $checked = isset($row['Field'])
                    && isset($mime_map[$row['Field']]['transformation'])
                    && preg_match(
                        '@' . preg_quote(
                            $available_mime['transformation_file'][$mimekey], '@'
                        ) . '3?@i',
                        $mime_map[$row['Field']]['transformation']
                    )
                    ? 'selected '
                    : '';
                $tooltip = PMA_getTransformationDescription(
                    $available_mime['transformation_file'][$mimekey], false
                );
                $content_cells[$i][$ci] .= '<option value="'
                    . $available_mime['transformation_file'][$mimekey] . '" '
                    . $checked . ' title="' . htmlspecialchars($tooltip) . '">'
                    . htmlspecialchars($transform) . '</option>';
            }
        }

        $content_cells[$i][$ci] .= '</select>';
        $ci++;

        $val = isset($row['Field'])
            && isset($mime_map[$row['Field']]['transformation_options'])
            ? htmlspecialchars($mime_map[$row['Field']]['transformation_options'])
            : '';
        $content_cells[$i][$ci] = '<input id="field_' . $i . '_'
            . ($ci - $ci_offset) . '"' . ' type="text" '
            . 'name="field_transformation_options[' . $i . ']"'
            . ' size="16" class="textfield"'
            . ' value="' . $val . '"'
            . ' />';
        //$ci++;
    }
} // end for

$html .= '<form method="post" action="' . $action  . '" class="'
    . ($action == 'tbl_create.php' ? 'create_table' : 'append_fields')
    . '_form ajax">';

$html .= PMA_generate_common_hidden_inputs($_form_params);
unset($_form_params);
if ($action == 'tbl_create.php') {
    $html .= '<table>'
        . '<tr class="vmiddle">'
        . '<td>' . __('Table name')
        . ':&nbsp;<input type="text" name="table" size="40" maxlength="80"'
        . ' value="'
        . (isset($_REQUEST['table']) ? htmlspecialchars($_REQUEST['table']) : '')
        . '" class="textfield" autofocus />'
        . '</td>'
        . '<td>';
    if ($action == 'tbl_create.php'
        || $action == 'tbl_addfield.php'
    ) {
        $html .= sprintf(
            __('Add %s column(s)'), '<input type="text" id="added_fields" '
            . 'name="added_fields" size="2" value="1" onfocus="this.select'
            . '()" />'
        );

        $html .= '<input type="submit" name="submit_num_fields"'
            . 'value="' . __('Go') . '"'
            . 'onclick="return'
            . ' checkFormElementInRange(this.form, \'added_fields\', \''
            . str_replace(
                '\'', '\\\'', __('You have to add at least one column.')
            ) . '\', 1)" />';
    }
    $html .= '</td>'
        . '</tr>'
        . '</table>';
}

if (is_array($content_cells) && is_array($header_cells)) {
    // last row is for javascript insert
    //$empty_row = array_pop($content_cells);

    $html .= '<table id="table_columns" class="noclick">';
    $html .= '<caption class="tblHeaders">' . __('Structure')
        . PMA_Util::showMySQLDocu('SQL-Syntax', 'CREATE_TABLE') . '</caption>';

    $html .= '<tr>';
    foreach ($header_cells as $header_val) {
        $html .= '<th>' . $header_val . '</th>';
    }
    $html .= '</tr>';

    $odd_row = true;
    foreach ($content_cells as $content_row) {
        $html .= '<tr class="' . ($odd_row ? 'odd' : 'even') . '">';
        $odd_row = ! $odd_row;

        if (is_array($content_row)) {
            foreach ($content_row as $content_row_val) {
                $html .= '<td class="center">' . $content_row_val . '</td>';
            }
        }
        $html .= '</tr>';
    }
    $html .= '</table>'
        . '<br />';
}

/**
 * needs to be finished
 *
 *
if ($display_type == 'horizontal') {
    $new_field = '';
    foreach ($empty_row as $content_row_val) {
        $new_field .= '<td class="center">' . $content_row_val . '</td>';
    }
    ?>
<script type="text/javascript">
// <![CDATA[
var odd_row = <?php echo $odd_row; ?>;

function addField()
{
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
    $html .= '<table>'
        . '<tr class="vtop">'
        . '<th>' . __('Table comments') . ':&nbsp;</th>'
        . '<td width="25">&nbsp;</td>'
        . '<th>' . __('Storage Engine') . ':'
        . PMA_Util::showMySQLDocu('Storage_engines', 'Storage_engines')
        . '</th>'
        . '<td width="25">&nbsp;</td>'
        . '<th>' . __('Collation') . ':&nbsp;</th>'
        . '</tr>'
        . '<tr><td><input type="text" name="comment" size="40" maxlength="80"'
        . 'value="'
        . (isset($_REQUEST['comment'])
        ? htmlspecialchars($_REQUEST['comment'])
        : '')
        . '" class="textfield" />'
        . '</td>'
        . '<td width="25">&nbsp;</td>'
        . '<td>'
        . PMA_StorageEngine::getHtmlSelect(
            'tbl_storage_engine', null,
            (isset($_REQUEST['tbl_storage_engine'])
                ? $_REQUEST['tbl_storage_engine']
                : null
            )
        )
        . '</td>'
        . '<td width="25">&nbsp;</td>'
        . '<td>'
        . PMA_generateCharsetDropdownBox(
            PMA_CSDROPDOWN_COLLATION, 'tbl_collation', null,
            (isset($_REQUEST['tbl_collation'])
                ? $_REQUEST['tbl_collation']
                : null
            ),
            false, 3
        )
        . '</td>'
        . '</tr>';

    if (PMA_Partition::havePartitioning()) {
        $html .= '<tr class="vtop">'
            . '<th>' . __('PARTITION definition') . ':&nbsp;'
            . PMA_Util::showMySQLDocu('Partitioning', 'Partitioning')
            . '</th>'
            . '</tr>'
            . '<tr>'
            . '<td>'
            . '<textarea name="partition_definition" id="partitiondefinition"'
            . ' cols="' . $GLOBALS['cfg']['TextareaCols'] . '"'
            . ' rows="' . $GLOBALS['cfg']['TextareaRows'] . '"'
            . ' dir="' . $GLOBALS['text_dir'] . '">'
            . (isset($_REQUEST['partition_definition'])
                ? htmlspecialchars($_REQUEST['partition_definition'])
                : '')
            . '</textarea>'
            . '</td>'
            . '</tr>';
    }
    $html .= '</table>'
        . '<br />';
} // end if ($action == 'tbl_create.php')

$html .= '<fieldset class="tblFooters">'
    . '<input type="submit" name="do_save_data" value="' . __('Save') . '" />'
    . '</fieldset>'
    . '<div id="properties_message"></div>'
    . '</form>';

$html .= '<div id="popup_background"></div>';

PMA_Response::getInstance()->addHTML($html);
?>
