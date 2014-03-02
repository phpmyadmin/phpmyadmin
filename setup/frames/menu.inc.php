<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Menu items
 *
 * @package PhpMyAdmin-Setup
 */

if (!defined('PHPMYADMIN')) {
    exit;
}

$separator = PMA_URL_getArgSeparator('html');
echo '<ul>';
echo '<li><a href="index.php">' . __('Overview') . '</a></li>';
echo '<li><a href="?page=form' . $separator . 'formset=Features">'
    . __('Features') . '</a></li>';
echo '<li><a href="?page=form' . $separator . 'formset=Sql_queries">'
    . __('SQL queries') . '</a></li>';
echo '<li><a href="?page=form' . $separator . 'formset=Navi_panel">'
    . __('Navigation panel') . '</a></li>';
echo '<li><a href="?page=form' . $separator . 'formset=Main_panel">'
    . __('Main panel') . '</a></li>';
echo '<li><a href="?page=form' . $separator . 'formset=Import">'
    . __('Import') . '</a></li>';
echo '<li><a href="?page=form' . $separator . 'formset=Export">'
    . __('Export') . '</a></li>';
echo '</ul>';
