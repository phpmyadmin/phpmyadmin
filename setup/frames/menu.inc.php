<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Menu items
 *
 * @package PhpMyAdmin-Setup
 */
use PMA\libraries\URL;

if (!defined('PHPMYADMIN')) {
    exit;
}

$formset_id = isset($_GET['formset']) ? $_GET['formset'] : null;

echo '<ul>';
echo '<li><a href="index.php' , URL::getCommon() , '"'
    , ($formset_id === null ? ' class="active' : '')
    , '">' , __('Overview') , '</a></li>';

$formsets = array(
    'Features'    => __('Features'),
    'Sql_queries' => __('SQL queries'),
    'Navi_panel'  => __('Navigation panel'),
    'Main_panel'  => __('Main panel'),
    'Import'      => __('Import'),
    'Export'      => __('Export')
);

foreach ($formsets as $formset => $label) {
    echo '<li><a href="index.php' , URL::getCommon(array('page' => 'form', 'formset' => $formset)) , '" '
        , ($formset_id === $formset ? ' class="active' : '')
        , '">' , $label , '</a></li>';
}

echo '</ul>';
