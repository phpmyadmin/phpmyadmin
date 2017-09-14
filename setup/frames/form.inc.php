<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Form edit view
 *
 * @package PhpMyAdmin-Setup
 */

use PhpMyAdmin\Config\Forms\Setup\SetupFormList;
use PhpMyAdmin\Core;
use PhpMyAdmin\Setup\FormProcessing;

if (!defined('PHPMYADMIN')) {
    exit;
}

$formset_id = Core::isValid($_GET['formset'], 'scalar') ? $_GET['formset'] : null;
$mode = isset($_GET['mode']) ? $_GET['mode'] : null;
$form_class = SetupFormList::get($formset_id);
if (is_null($form_class)) {
    Core::fatalError(__('Incorrect form specified!'));
}
echo '<h2>' , $form_class::getName() , '</h2>';
$form_display = new $form_class($GLOBALS['ConfigFile']);
FormProcessing::process($form_display);
