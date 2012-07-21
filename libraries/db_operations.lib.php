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

/**
 * Get HTML for database drop link
 * 
 * @return string $html_output
 */
function PMA_getHtmlForDropDatabaseLink()
{
    $common_functions = PMA_CommonFunctions::getInstance();
    
    $this_sql_query = 'DROP DATABASE ' . $common_functions->backquote($GLOBALS['db']);
    $this_url_params = array(
            'sql_query' => $this_sql_query,
            'back' => 'db_operations.php',
            'goto' => 'main.php',
            'reload' => '1',
            'purge' => '1',
            'message_to_show' => sprintf(
                __('Database %s has been dropped.')
                , htmlspecialchars($common_functions->backquote($GLOBALS['db']))
            ),
            'db' => null,
        );
    
    $html_output = '<div class="operations_half_width">'
        . '<fieldset class="caution">';
    $html_output .= '<legend>';
    if ($GLOBALS['cfg']['PropertiesIconic']) {
        $html_output .= $common_functions->getImage('b_deltbl.png');
    }
    $html_output .= __('Remove database')
        . '</legend>';
    $html_output .= '<ul>';
    $html_output .= '<li>' 
        . '<a href="sql.php' . PMA_generate_common_url($this_url_params) . '"' 
        . ($GLOBALS['cfg']['AjaxEnable'] ? 'id="drop_db_anchor"' : '') . '>'
        . __('Drop the database (DROP)')
        . '</a>'
        .  $common_functions->showMySQLDocu('SQL-Syntax', 'DROP_DATABASE')
        . '</li>'
        . '</ul></fieldset>'
        . '</div>';
    
    return $html_output;
}

?>
