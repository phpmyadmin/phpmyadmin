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

/**
 * Function to get html for displaying the page edit/delete form
 *
 * @param string $operation 'edit' or 'delete' depending on the operation
 *
 * @return string html content
 */
function PMA_getHtmlForEditOrDeletePages($operation)
{
    $cfgRelation = PMA_getRelationsParam();
    $page_query = "SELECT * FROM " . PMA_Util::backquote($cfgRelation['db'])
        . "." . PMA_Util::backquote($cfgRelation['pdf_pages'])
        . " WHERE db_name = '" . $GLOBALS['db'] . "'"
        . " ORDER BY `page_nr`";
    $page_rs = PMA_queryAsControlUser(
        $page_query, false, PMA_DatabaseInterface::QUERY_STORE
    );

    $html  = '<form action="pmd_general.php" method="post"'
        . ' name="edit_delete_pages" id="edit_delete_pages" class="ajax">';
    $html .= PMA_URL_getHiddenInputs($GLOBALS['db']);
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
    while ($curr_page = $GLOBALS['dbi']->fetchAssoc($page_rs)) {
        $html .= '<option value="' . $curr_page['page_nr'] . '">';
        $html .= htmlspecialchars($curr_page['page_descr']) . '</option>';
    }
    $html .= '</select>';
    $html .= '</fieldset>';
    $html .= '</form>';
    return $html;
}

/**
 * Function to get html for displaying the page save as form
 *
 * @return string html content
 */
function PMA_getHtmlForPageSaveAs()
{
    $cfgRelation = PMA_getRelationsParam();
    $page_query = "SELECT * FROM " . PMA_Util::backquote($cfgRelation['db'])
        . "." . PMA_Util::backquote($cfgRelation['pdf_pages'])
        . " WHERE db_name = '" . $GLOBALS['db'] . "'"
        . " ORDER BY `page_nr`";
    $page_rs = PMA_queryAsControlUser(
        $page_query, false, PMA_DatabaseInterface::QUERY_STORE
    );

    $choices = array(
        'same' => __('Save to selected page'),
        'new' => __('Create a page and save to it')
    );

    $html  = '<form action="pmd_general.php" method="post"'
        . ' name="save_as_pages" id="save_as_pages" class="ajax">';
    $html .= PMA_URL_getHiddenInputs($GLOBALS['db']);
    $html .= '<fieldset id="page_save_as_options">';
    $html .= '<table><tbody>';

    $html .= '<tr>';
    $html .= '<td>';
    $html .= '<input type="hidden" name="operation" value="save" />';
    $html .= '<select name="selected_page" id="selected_page">';
    $html .= '<option value="0">' . __('Select page') . '</option>';

    while ($curr_page = $GLOBALS['dbi']->fetchAssoc($page_rs)) {
        $html .= '<option value="' . $curr_page['page_nr'] . '">';
        $html .= htmlspecialchars($curr_page['page_descr']) . '</option>';
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
    $html .= '<label for="id_newpage">' . __('New page name') . '</label>';
    $html .= '<input type="text" name="selected_value" id="selected_value"'
        . ' size="20" maxlength="50" />';
    $html .= '</td>';
    $html .= '</tr>';

    $html .= '</tbody></table>';
    $html .= '</fieldset>';
    $html .= '</form>';

    return $html;
}
?>