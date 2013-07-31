<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * set of functions used by tbl_columns_definitions_form.inc.php
 *
 * @package PhpMyAdmin
 */
if (!defined('PHPMYADMIN')) {
    exit;
}

/**
 * Function to get form parameters
 * 
 * @param string $db            database
 * @param string $table         table
 * @param string $action        action
 * @param int    $num_fields    number of fields
 * @param bool   $selected      selected
 * 
 * @return array $form_params form parameters
 */
function PMA_getFormsParameters($db, $table, $action, $num_fields, $selected)
{
    $form_params = array(
        'db' => $db
    );

    if ($action == 'tbl_create.php') {
        $form_params['reload'] = 1;
    } elseif ($action == 'tbl_addfield.php') {
        $form_params['field_where'] = $_REQUEST['field_where'];
        $form_params['after_field'] = $_REQUEST['after_field'];
        $form_params['table'] = $table;
    } else {
        $form_params['table'] = $table;
    }

    if (isset($num_fields)) {
        $form_params['orig_num_fields'] = $num_fields;
    }

    if (isset($_REQUEST['field_where'])) {
        $form_params['orig_field_where'] = $_REQUEST['field_where'];
    }

    if (isset($_REQUEST['after_field'])) {
        $form_params['orig_after_field'] = $_REQUEST['after_field'];
    }

    if (isset($selected) && is_array($selected)) {
        foreach ($selected as $o_fld_nr => $o_fld_val) {
            $form_params['selected[' . $o_fld_nr . ']'] = $o_fld_val;
        }
    }
    
    return $form_params;
}

/**
 * Function to get html for table comments, storage engine, collation and 
 * partition definition
 * 
 * @return string
 */
function PMA_getHtmlForTableConfigurations()
{
    $html = '<table>'
        . '<tr class="vtop">'
        . '<th>' . __('Table comments:') . '</th>'
        . '<td width="25">&nbsp;</td>'
        . '<th>' . __('Storage Engine:')
        . PMA_Util::showMySQLDocu('Storage_engines', 'Storage_engines')
        . '</th>'
        . '<td width="25">&nbsp;</td>'
        . '<th>' . __('Collation:') . '</th>'
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
            . '<th>' . __('PARTITION definition:') . '&nbsp;'
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
    
    return $html;
}

/**
 * Function to get html for the footer
 * 
 * @return string
 */
function PMA_getHtmlForFooter()
{
    $html = '<fieldset class="tblFooters">'
        . '<input type="submit" name="do_save_data" value="' . __('Save') . '" />'
        . '</fieldset>'
        . '<div id="properties_message"></div>'
        . '</form>';

    $html .= '<div id="popup_background"></div>';
    
    return $html;
}

/**
 * Function to get html for table create table name and number of fields
 * 
 * @return string
 */
function PMA_getHtmlForTableNameAndNoOfColumns()
{
    $html = '<table>'
        . '<tr class="vmiddle">'
        . '<td>' . __('Table name')
        . ':&nbsp;<input type="text" name="table" size="40" maxlength="80"'
        . ' value="'
        . (isset($_REQUEST['table']) ? htmlspecialchars($_REQUEST['table']) : '')
        . '" class="textfield" autofocus />'
        . '</td>'
        . '<td>';
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
        
    $html .= '</td>'
        . '</tr>'
        . '</table>';
    
    return $html;
}

/**
 * Function to get html for table field definitions
 * 
 * @param array $header_cells
 * @param array $content_cells
 * 
 * @return string
 */
function PMA_getHtmlForTableFieldDefinitions($header_cells, $content_cells)
{
    $html = '<table id="table_columns" class="noclick">';
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
    
    return $html;
}

/**
 * Function to get html for the create table or field add view
 * 
 * @param string $action        action
 * @param array  $form_params   forms parameters
 * @param array  $content_cells content cells
 * @param array  $header_cells  header cells
 * 
 * @return string
 */
function PMA_getHtmlForTableCreateOrAddField($action, $form_params, $content_cells,
    $header_cells
) {    
    $html = '<form method="post" action="' . $action  . '" class="'
        . ($action == 'tbl_create.php' ? 'create_table' : 'append_fields')
        . '_form ajax">';
    $html .= PMA_generate_common_hidden_inputs($form_params);
    
    if ($action == 'tbl_create.php') {
        $html .= PMA_getHtmlForTableNameAndNoOfColumns();
    }
    
    if (is_array($content_cells) && is_array($header_cells)) {
        $html .= PMA_getHtmlForTableFieldDefinitions($header_cells, $content_cells);
    }
    
    if ($action == 'tbl_create.php') {
        $html .= PMA_getHtmlForTableConfigurations();
    }    
    
    $html .= PMA_getHtmlForFooter();

    return $html;
}
?>
