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
require_once './libraries/mysql_charsets.inc.php';
require_once './libraries/StorageEngine.class.php';

/**
 * Class for partition management
 */
require_once './libraries/Partition.class.php';

require_once './libraries/tbl_columns_definition_form.lib.php';

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

$_form_params = PMA_getFormsParameters(
    $db, $table, $action, isset($num_fields) ? $num_fields : null,
    isset($selected) ? $selected : null
);

$is_backup = ($action != 'tbl_create.php' && $action != 'tbl_addfield.php');

require_once './libraries/transformations.lib.php';
$cfgRelation = PMA_getRelationsParam();

$comments_map = array();
$mime_map = array();
$available_mime = array();

$comments_map = PMA_getComments($db, $table);

if (isset($fields_meta)) {
    $move_columns = PMA_getMoveColumns($db, $table);
}

if ($cfgRelation['mimework'] && $GLOBALS['cfg']['BrowseMIME']) {
    $mime_map = PMA_getMIME($db, $table);
    $available_mime = PMA_getAvailableMIMEtypes();
}

$header_cells = PMA_getHeaderCells(
    $is_backup, isset($fields_meta) ? $fields_meta : null,
    $cfgRelation['mimework'], $db, $table
);

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
        list($row, $submit_length, $submit_attribute,
            $submit_default_current_timestamp, $comments_map, $mime_map)
                = PMA_handleRegeneration(
                    isset($available_mime) ? $mime_map : null,
                    $comments_map, $mime_map
                ); 
    } elseif (isset($fields_meta[$i])) {
        $row = PMA_getRowDataForFieldsMetaSet(
            $fields_meta[$i], isset($analyzed_sql[0]['create_table_fields']
            [$fields_meta[$i]['Field']]['default_value'])
        );
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
        if (isset($row['Field'])) {
            $_form_params['field_orig[' . $i . ']'] = $row['Field'];
        } else {
            $_form_params['field_orig[' . $i . ']'] = '';
        }
    }

    // column name
    $content_cells[$i][$ci] = PMA_getHtmlForColumnName(
        $i, $ci, $ci_offset, isset($row) ? $row : null
    );
    
    $ci++;

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
    
    // column type    
    $content_cells[$i][$ci] = PMA_getHtmlForColumnType(
        $i, $ci, $ci_offset, $type_upper
    );
    
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
    $content_cells[$i][$ci] = PMA_getHtmlForColumnComment(
        $i, $ci, $ci_offset, isset($row) ? $row : null, $comments_map
    );
    $ci++;

    // move column
    if (isset($fields_meta)) {
        $content_cells[$i][$ci] = PMA_getHtmlForMoveColumn(
            $i, $ci, $ci_offset, $move_columns, $row
        );
        $ci++;
    }

    if ($cfgRelation['mimework']
        && $GLOBALS['cfg']['BrowseMIME']
        && $cfgRelation['commwork']
    ) {
        // Column Mime-type
        $content_cells[$i][$ci] = PMA_getHtmlForMimeType(
            $i, $ci, $ci_offset, $available_mime, $row, $mime_map
        );
        $ci++;

        // Column Browser transformation
        $content_cells[$i][$ci] = PMA_getHtmlForBrowserTransformation(
            $i, $ci, $ci_offset, $available_mime, $row, $mime_map
        );
        $ci++;

        // column Transformation options
        $content_cells[$i][$ci] = PMA_getHtmlForTransformationOption(
                $i, $ci, $ci_offset, isset($row) ? $row : null,
                isset($mime_map) ? $mime_map : null
        );
    }
} // end for

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

$html .= PMA_getHtmlForTableCreateOrAddField(
    $action, $_form_params, $content_cells, $header_cells
);

unset($_form_params);

PMA_Response::getInstance()->addHTML($html);
?>
