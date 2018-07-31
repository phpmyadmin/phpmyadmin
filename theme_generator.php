<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Theme generator tool
 *
 * @package PhpMyAdmin
 */
use PhpMyAdmin\Response;
use PhpMyAdmin\ThemeGenerator;

require_once 'libraries/common.inc.php';

$response = Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('theme_generator/color_picker.js');
$scripts->addFile('theme_generator/preview.js');

$theme = new ThemeGenerator();

$response->addHTML($theme->testWritableThemeDirectory());
$response->addHTML($theme->colorPicker());
$response->addHTML($theme->form());
$response->addHTML($theme->tablePreview());
$response->addHTML($theme->groupPreview());
if (isset($_POST['Base_Colour'])) {
    $theme->createFileStructure($_POST);
}
