<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Form edit view
 *
 * @package PhpMyAdmin-Setup
 */

use PhpMyAdmin\Config\Descriptions;
use PhpMyAdmin\Config\Forms\Setup\SetupFormList;
use PhpMyAdmin\Core;

if (!defined('PHPMYADMIN')) {
    exit;
}

/**
 * Core libraries.
 */
require_once './setup/lib/form_processing.lib.php';

$formset_id = Core::isValid($_GET['formset'], 'scalar') ? $_GET['formset'] : null;
$mode = isset($_GET['mode']) ? $_GET['mode'] : null;
if (! SetupFormList::isValid($formset_id)) {
    Core::fatalError(__('Incorrect formset, check $formsets array in setup/frames/form.inc.php!'));
}

$form_title = Descriptions::get('Formset_' . $formset_id);
if (! is_null($form_title)) {
    echo '<h2>' , $form_title , '</h2>';
}
$form_class = SetupFormList::get($formset_id);
$form_display = new $form_class($GLOBALS['ConfigFile']);
PMA_Process_formset($form_display);
