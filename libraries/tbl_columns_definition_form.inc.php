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
    $content_cells[$i][$ci] = PMA_getHtmlForColumnLength(
        $i, $ci, $ci_offset, $length_values_input_size, $length
    );
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
    $content_cells[$i][$ci] = PMA_getHtmlForColumnCollation(
        $i, $ci, $ci_offset, $row
    );
    $ci++;

    // column attribute
    $content_cells[$i][$ci] = PMA_getHtmlForColumnAttribute($i, $ci, $ci_offset,
        isset($extracted_columnspec) ? $extracted_columnspec : null,
        isset($row) ? $row : null,
        isset($submit_attribute) ? $submit_attribute : null,
        isset($analyzed_sql) ? $analyzed_sql : null,
        isset($submit_default_current_timestamp)
            ? $submit_default_current_timestamp : null
    );
    $ci++;

    // column NULL
    $content_cells[$i][$ci] = PMA_getHtmlForColumnNull(
        $i, $ci, $ci_offset, isset($row) ? $row : null
    );
    $ci++;

    // column indexes
    // See my other comment about  this 'if'.
    if (!$is_backup) {
        $content_cells[$i][$ci] = PMA_getHtmlForColumnIndexes(
                $i, $ci, $ci_offset, $row
        );
        $ci++;
    } // end if ($action ==...)

    // column auto_increment
    $content_cells[$i][$ci] = PMA_getHtmlForColumnAutoIncrement(
            $i, $ci, $ci_offset, $row
    );
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
