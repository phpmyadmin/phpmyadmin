<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Menu items
 *
 * @package PhpMyAdmin-Setup
 */
use PhpMyAdmin\Url;
use PhpMyAdmin\Config\Forms\Setup\SetupFormList;

if (!defined('PHPMYADMIN')) {
    exit;
}

$formset_id = isset($_GET['formset']) ? $_GET['formset'] : null;

echo '<ul>';
echo '<li><a href="index.php' , Url::getCommon() , '"'
    , ($formset_id === null ? ' class="active' : '')
    , '">' , __('Overview') , '</a></li>';

$ignored = array('Config', 'Servers');
foreach (SetupFormList::getAll() as $formset) {
    if (in_array($formset, $ignored)) {
        continue;
    }
    $form_class = SetupFormList::get($formset);
    echo '<li><a href="index.php' , Url::getCommon(array('page' => 'form', 'formset' => $formset)) , '" '
        , ($formset_id === $formset ? ' class="active' : '')
        , '">' , $form_class::getName() , '</a></li>';
}

echo '</ul>';
