<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Set of functions related to designer
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

require_once 'libraries/relation.lib.php';

/**
 * Function to get html for displaying the page edit/delete form
 *
 * @param string $db        databasae name
 * @param string $operation 'edit' or 'delete' depending on the operation
 *
 * @return string html content
 */
function PMA_getHtmlForEditOrDeletePages($db, $operation)
{
    $cfgRelation = PMA_getRelationsParam();
    $html  = '<form action="pmd_general.php" method="post"'
        . ' name="edit_delete_pages" id="edit_delete_pages" class="ajax">';
    $html .= PMA_URL_getHiddenInputs($db);
    $html .= '<fieldset id="page_edit_delete_options">';
    $html .= '<input type="hidden" name="operation" value="' . $operation . '" />';
    $html .= '<label for="selected_page">';
    if ($operation == 'edit') {
        $html .= __("Page to open");
    } else {
        $html .= __("Page to delete");
    }
    $html .= ': </label>';
    $html .= '<select name="selected_page" id="selected_page">';
    $html .= '<option value="0">-- ' . __('Select page').' --</option>';
    if ($cfgRelation['pdfwork']) {
        $pages = PMA_getPageIdsAndNames($db);
        foreach ($pages as $nr => $desc) {
            $html .= '<option value="' . $nr . '">';
            $html .= htmlspecialchars($desc) . '</option>';
        }
    }
    $html .= '</select>';
    $html .= '</fieldset>';
    $html .= '</form>';
    return $html;
}

/**
 * Function to get html for displaying the page save as form
 *
 * @param string $db databasae name
 *
 * @return string html content
 */
function PMA_getHtmlForPageSaveAs($db)
{
    $cfgRelation = PMA_getRelationsParam();
    $choices = array(
        'same' => __('Save to selected page'),
        'new' => __('Create a page and save to it')
    );

    $html  = '<form action="pmd_general.php" method="post"'
        . ' name="save_as_pages" id="save_as_pages" class="ajax">';
    $html .= PMA_URL_getHiddenInputs($db);
    $html .= '<fieldset id="page_save_as_options">';
    $html .= '<table><tbody>';

    $html .= '<tr>';
    $html .= '<td>';
    $html .= '<input type="hidden" name="operation" value="save" />';
    $html .= '<select name="selected_page" id="selected_page">';
    $html .= '<option value="0">-- ' . __('Select page') . ' --</option>';

    if ($cfgRelation['pdfwork']) {
        $pages = PMA_getPageIdsAndNames($db);
        foreach ($pages as $nr => $desc) {
            $html .= '<option value="' . $nr . '">';
            $html .= htmlspecialchars($desc) . '</option>';
        }
    }
    $html .= '</select>';
    $html .= '</td>';
    $html .= '</tr>';

    $html .= '<tr>';
    $html .= '<td>';
    $html .= PMA_Util::getRadioFields('save_page', $choices, 'same', true);
    $html .= '</td>';
    $html .= '</tr>';

    $html .= '<tr>';
    $html .= '<td>';
    $html .= '<label for="selected_value">' . __('New page name') . '</label>';
    $html .= '<input type="text" name="selected_value" id="selected_value" />';
    $html .= '</td>';
    $html .= '</tr>';

    $html .= '</tbody></table>';
    $html .= '</fieldset>';
    $html .= '</form>';

    return $html;
}

/**
 * Retrieve IDs and names of schema pages
 *
 * @param string $db database name
 *
 * @return array array of schema page id and names
 */
function PMA_getPageIdsAndNames($db)
{
    $cfgRelation = PMA_getRelationsParam();
    $page_query = "SELECT `page_nr`, `page_descr` FROM "
        . PMA_Util::backquote($cfgRelation['db']) . "."
        . PMA_Util::backquote($cfgRelation['pdf_pages'])
        . " WHERE db_name = '" . PMA_Util::sqlAddSlashes($db) . "'"
        . " ORDER BY `page_nr`";
    $page_rs = PMA_queryAsControlUser(
        $page_query, false, PMA_DatabaseInterface::QUERY_STORE
    );

    $result = array();
    while ($curr_page = $GLOBALS['dbi']->fetchAssoc($page_rs)) {
        $result[$curr_page['page_nr']] = $curr_page['page_descr'];
    }
    return $result;
}

/**
 * Function to get html for displaying the schema export
 *
 * @param string $db   database name
 * @param int    $page the page to be exported
 *
 * @return string
 */
function PMA_getHtmlForSchemaExport($db, $page)
{
    $htmlString = '<form method="post" action="schema_export.php"'
        . ' class="disableAjax" id="id_export_pages">'
        . '<fieldset>'
        . PMA_URL_getHiddenInputs($db)
        . '<select name="export_type" id="export_type">';

    if (file_exists(TCPDF_INC)) {
        $htmlString .= '<option value="pdf" selected="selected">PDF</option>';
    }

    $htmlString .=' <option value="svg">SVG</option>'
        . '<option value="dia">DIA</option>'
        . '<option value="eps">EPS</option>'
        . '</select>'
        . '<label>' . __('Select Export Relational Type') . '</label><br />';

    $htmlString .= '<input type="hidden" name="chpage" value="' . htmlspecialchars($page) . '" />'
        . '<input type="checkbox" name="show_grid" id="show_grid_opt" />'
        . '<label for="show_grid_opt">' . __('Show grid') . '</label><br />'
        . '<input type="checkbox" name="show_color"'
        . ' id="show_color_opt" checked="checked" />'
        . '<label for="show_color_opt">' . __('Show color') . '</label>'
        . '<br />'
        . '<input type="checkbox" name="show_table_dimension"'
        . ' id="show_table_dim_opt" />'
        . '<label for="show_table_dim_opt">'
        . __('Show dimension of tables')
        . '</label><br />'
        . '<input type="checkbox" name="all_tables_same_width"'
        . ' id="all_tables_same_width" />'
        . '<label for="all_tables_same_width">'
        . __('Same width for all tables')
        . '</label><br />'
        . '<input type="checkbox" name="with_doc"'
        . ' id="with_doc" checked="checked" />'
        . '<label for="with_doc">' . __('Data Dictionary') . '</label><br />'
        . '<input type="checkbox" name="show_keys" id="show_keys" />'
        . '<label for="show_keys">' . __('Only show keys') . '</label><br />'
        . '<select name="orientation" id="orientation_opt" class="paper-change">'
        . '<option value="L">' . __('Landscape') . '</option>'
        . '<option value="P">' . __('Portrait') . '</option>'
        . '</select>'
        . '<label for="orientation_opt">' . __('Orientation') . '</label>'
        . '<br />'
        . '<select name="paper" id="paper_opt" class="paper-change">';

    foreach ($GLOBALS['cfg']['PDFPageSizes'] as $val) {
        $htmlString .= '<option value="' . htmlspecialchars($val) . '"';
        if ($val == $GLOBALS['cfg']['PDFDefaultPageSize']) {
            $htmlString .= ' selected="selected"';
        }
        $htmlString .= '>' . htmlspecialchars($val) . '</option>' . "\n";
    }

    $htmlString  .= '</select>'
        . '<label for="paper_opt">' . __('Paper size') . '</label>'
        . '</fieldset>'
        . '</form>';

    return $htmlString;
}
?>