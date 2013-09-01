<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Display the form to edit/create an index
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

if (isset($_REQUEST['index']) && is_array($_REQUEST['index'])) {
    // coming already from form
    $add_fields
        = count($_REQUEST['index']['columns']['names']) - $index->getColumnCount();
    if (isset($_REQUEST['add_fields'])) {
        $add_fields += $_REQUEST['added_fields'];
    }
} elseif (isset($_REQUEST['create_index'])) {
    $add_fields = $_REQUEST['added_fields'];
} else {
    $add_fields = 1;
}// end preparing form values

$html = "";
$html .= '<form action="tbl_indexes.php" method="post" name="index_frm" id="index_frm" class="ajax"'
    . 'onsubmit="if (typeof(this.elements[\'index[Key_name]\'].disabled) != \'undefined\') {'
    . 'this.elements[\'index[Key_name]\'].disabled = false}">';

$form_params = array(
    'db'    => $db,
    'table' => $table,
);

if (isset($_REQUEST['create_index'])) {
    $form_params['create_index'] = 1;
} elseif (isset($_REQUEST['old_index'])) {
    $form_params['old_index'] = $_REQUEST['old_index'];
} elseif (isset($_REQUEST['index'])) {
    $form_params['old_index'] = $_REQUEST['index'];
}

$html .= PMA_URL_getHiddenInputs($form_params);

$html .= '<fieldset id="index_edit_fields">';

if ($GLOBALS['is_ajax_request'] != true) {
    $html .= '<legend>';
    if (isset($_REQUEST['create_index'])) {
        $html .= __('Add index');
    } else {
        $html .= __('Edit index');
    }
    $html .= '</legend>';
}

$html .= '<div class="index_info">';

$html .= '<div>'
    . '<div class="label">'
    . '<strong>'
    . '<label for="input_index_name">'
    . __('Index name:')
    . PMA_Util::showHint(
        PMA_Message::notice(
            __('"PRIMARY" <b>must</b> be the name of'
                . ' and <b>only of</b> a primary key!'
            )
        )
    )
    . '</label>'
    . '</strong>'
    . '</div>'
    . '<input type="text" name="index[Key_name]" id="input_index_name" size="25"'
    . 'value="' . htmlspecialchars($index->getName()) . '"'
    . 'onfocus="this.select()" />'
    . '</div>';

if (PMA_MYSQL_INT_VERSION > 50500) {
    $html .= '<div>'
        . '<div class="label">'
        . '<strong>'
        . '<label for="input_index_comment">'
        . __('Comment:')
        . '</label>'
        . '</strong>'
        . '</div>'
        . '<input type="text" name="index[Index_comment]" id="input_index_comment" size="30"'
        . 'value="' . htmlspecialchars($index->getComment()) . '"'
        . 'onfocus="this.select()" />'
        . '</div>';
}

$html .= '<div>'
    . '<div class="label">'
    . '<strong>'
    . '<label for="select_index_type">'
    . __('Index type:')
    . PMA_Util::showMySQLDocu('ALTER_TABLE')
    . '</label>'
    . '</strong>'
    . '</div>'
    . '<select name="index[Index_type]" id="select_index_type" >'
    . $index->generateIndexSelector()
    . '</select>'
    . '</div>';

$html .= '<div class="clearfloat"></div>';

$html .= '</div>';

$html .= '<table id="index_columns">';

$html .= '<thead>'
    . '<tr>'
    . '<th>' . __('Column') . '</th>'
    . '<th>' . __('Size') . '</th>'
    . '</tr>'
    . '</thead>';

$odd_row = true;
$spatial_types = array(
    'geometry', 'point', 'linestring', 'polygon', 'multipoint',
    'multilinestring', 'multipolygon', 'geomtrycollection'
);
$html .= '<tbody>';
foreach ($index->getColumns() as $column) {
    $html .= '<tr class="';
    $html .= $odd_row ? 'odd' : 'even';
    $html .= 'noclick">';
    $html .= '<td>';
    $html .= '<select name="index[columns][names][]">';
    $html .= '<option value="">-- ' . __('Ignore') . ' --</option>';
    foreach ($fields as $field_name => $field_type) {
        if (($index->getType() != 'FULLTEXT'
            || preg_match('/(char|text)/i', $field_type))
            && ($index->getType() != 'SPATIAL'
            || in_array($field_type, $spatial_types))
        ) {
            $html .= '<option value="' . htmlspecialchars($field_name) . '"'
                . (($field_name == $column->getName())
                ? ' selected="selected"'
                : '') . '>'
                . htmlspecialchars($field_name) . ' ['
                . htmlspecialchars($field_type) . ']'
                . '</option>' . "\n";
        }
    } // end foreach $fields
    $html .= '</select>';
    $html .= '</td>';
    $html .= '<td>';
    $html .= '<input type="text" size="5" onfocus="this.select()"'
        . 'name="index[columns][sub_parts][]" value="';
    if ($index->getType() != 'SPATIAL') {
        $html .= $column->getSubPart();
    }
    $html .= '"/>';
    $html .= '</td>';
    $html .= '</tr>';
    $odd_row = !$odd_row;
} // end foreach $edited_index_info['Sequences']

for ($i = 0; $i < $add_fields; $i++) {
    $html .= '<tr class="';
    $html .= $odd_row ? 'odd' : 'even';
    $html .= 'noclick">';
    $html .= '<td>';
    $html .= '<select name="index[columns][names][]">';
    $html .= '<option value="">-- ' . __('Ignore') . ' --</option>';
    foreach ($fields as $field_name => $field_type) {
        $html .= '<option value="' . htmlspecialchars($field_name) . '">'
            . htmlspecialchars($field_name) . ' ['
            . htmlspecialchars($field_type) . ']'
            . '</option>' . "\n";
    } // end foreach $fields
    $html .= '</select>';
    $html .= '</td>';
    $html .= '<td>'
        . '<input type="text" size="5" onfocus="this.select()"'
        . 'name="index[columns][sub_parts][]" value="" />'
        . '</td>';
    $html .= '</tr>';
    $odd_row = !$odd_row;
} // end foreach $edited_index_info['Sequences']

$html .= '</tbody>';

$html .= '</table>';

$html .= '</fieldset>';

$html .= '<fieldset class="tblFooters">';
if ($GLOBALS['is_ajax_request'] != true || ! empty($_REQUEST['ajax_page_request'])) {
    $html .= '<input type="submit" name="do_save_data" value="' . __('Save') . '" />';
    $html .= '<span id="addMoreColumns">';
    $html .= __('Or') . ' ';
    $html .= printf(
        __('Add %s column(s) to index') . "\n",
        '<input type="text" name="added_fields" size="2" value="1" />'
    );
    $html .= '<input type="submit" name="add_fields" value="' . __('Go') . '" />' . "\n";
    $html .= '</span>';
} else {
    $btn_value = sprintf(__('Add %s column(s) to index'), 1);
    $html .= '<div class="slider"></div>';
    $html .= '<div class="add_fields">';
    $html .= '<input type="submit" value="' . $btn_value . '" />';
    $html .= '</div>';
}
$html .= '</fieldset>';

$html .= '</form>';

$response = PMA_Response::getInstance();
$response->addHTML($html);
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('indexes.js');
?>
