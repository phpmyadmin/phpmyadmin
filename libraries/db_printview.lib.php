<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Set of functions related to db printview 
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Function to get html for one of the db dates
 *
 * @param string $title the title 
 * @param string $date  which date to display
 *
 * @return string html content
 */
function PMA_getHtmlForOneDate($title, $date)
{
    $html = '<tr>'
        . '<td class="right">' . $title . '</td>'
        . '<td class="right">'
        . PMA_Util::localisedDate(strtotime($date))
        . '</td>'
        . '</tr>';
    return $html;
}
?>
