<?php

/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * set of functions for structure section in pma
 *
 * @package PhpMyAdmin
 */
if (!defined('PHPMYADMIN')) {
    exit;
}

require_once 'libraries/Index.class.php';

/**
 * Get HTML for display indexes
 *
 * @return string $html_output
 */
function PMA_getHtmlForDisplayIndexes()
{
    $html_output = '<div id="index_div" class="ajax';
    if ($GLOBALS['cfg']['InitialSlidersState'] != 'disabled') {
        $html_output .= ' print_ignore';
    }
    $html_output .= '" >';

    $html_output .= PMA_Util::getDivForSliderEffect(
        'indexes', __('Indexes')
    );
    $html_output .= PMA_Index::getHtmlForIndexes($GLOBALS['table'], $GLOBALS['db']);
    $html_output .= '<fieldset class="tblFooters print_ignore" style="text-align: '
        . 'left;"><form action="tbl_indexes.php" method="post">';
    $html_output .= PMA_URL_getHiddenInputs(
        $GLOBALS['db'], $GLOBALS['table']
    );
    $html_output .= sprintf(
        __('Create an index on &nbsp;%s&nbsp;columns'),
        '<input type="number" name="added_fields" value="1" '
        . 'min="1" required="required" />'
    );
    $html_output .= '<input type="hidden" name="create_index" value="1" />'
        . '<input class="add_index ajax"'
        . ' type="submit" value="' . __('Go') . '" />';

    $html_output .= '</form>'
        . '</fieldset>'
        . '</div>'
        . '</div>';

    return $html_output;
}
