<?php

/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * set of functions with the operations section in pma
 *
 * @package PhpMyAdmin
 */

if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Get HTML output for database comment
 * 
 * @return string $html_output
 */
function PMA_getHtmlForDatabaseComment()
{
    $html_output = '<div class="operations_half_width">'
        . '<form method="post" action="db_operatins.php">'
        . PMA_generate_common_hidden_inputs($GLOBALS['db'])
        . '<fieldset>'
        . '<legend>';
    if ($GLOBALS['cfg']['PropertiesIconic']) {
        $html_output .= '<img class="icon ic_b_comment" src="themes/dot.gif" alt="" />';
    }
    $html_output .=  __('Database comment: ');
    $html_output .= '</legend>';
    $html_output .= '<input type="text" name="comment" class="textfield" size="30"'
        . 'value="' . htmlspecialchars(PMA_getDBComment($GLOBALS['db'])) . '" />'
        . '</fieldset>';
    $html_output .= '<fieldset class="tblFooters">'
        . '<input type="submit" value="' . __('Go') . '" />'
        . '</fieldset>'
        . '</form>'
        . '</div>';
    
    return $html_output;
}

/**
 * Get HTML output for rename database
 * 
 * @return string $html_output
 */
function PMA_getHtmlForRenameDatabase()
{
    $html_output = '<div class="operations_half_width">'
        . '<form id="rename_db_form" ' . ($GLOBALS['cfg']['AjaxEnable'] ? ' class="ajax" ' : '')
        . 'method="post" action="db_operations.php"'
        . 'onsubmit="return emptyFormElements(this, ' . "'newname'" . ')">';
    if (isset($_REQUEST['db_collation'])) {
        $html_output .= '<input type="hidden" name="db_collation" value="' . $_REQUEST['db_collation']
            .'" />' . "\n";
    }
    $html_output .= '<input type="hidden" name="what" value="data" />'
        . '<input type="hidden" name="db_rename" value="true" />'
        . PMA_generate_common_hidden_inputs($GLOBALS['db'])
        . '<fieldset>'
        . '<legend>';

    if ($GLOBALS['cfg']['PropertiesIconic']) {
        $html_output .= PMA_CommonFunctions::getInstance()->getImage('b_edit.png');
    }
    $html_output .= __('Rename database to') . ':'
        . '</legend>';
    
    $html_output .= '<input id="new_db_name" type="text" name="newname" ' 
        . 'size="30" class="textfield" value="" />'
        . '</fieldset>'
        . '<fieldset class="tblFooters">'
        . '<input id="rename_db_input" type="submit" value="' . __('Go') . '" />'
        . '</fieldset>'
        . '</form>'
        . '</div>';

    return $html_output;
}

?>
