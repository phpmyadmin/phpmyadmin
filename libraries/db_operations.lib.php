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


?>
