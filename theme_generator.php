<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Theme generator tool
 *
 * @package PhpMyAdmin
 */
use PhpMyAdmin\Message;
use PhpMyAdmin\Response;
use PhpMyAdmin\ThemeGenerator;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

require_once ROOT_PATH . 'libraries/common.inc.php';

$response = Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
if ($GLOBALS['cfg']['ThemeGenerator']) {
    $scripts->addFile('theme_generator/color_picker.js');
    $scripts->addFile('theme_generator/preview.js');

    $theme = new ThemeGenerator();

    $response->addHTML($theme->testWritableThemeDirectory());
    $response->addHTML($theme->colorPicker());
    $response->addHTML($theme->form());
    $response->addHTML($theme->tablePreview());
    $response->addHTML($theme->groupPreview());
    if (isset($_POST['Base_Colour'])) {
        $output = $theme->createFileStructure($_POST);
        if ($output['json'] && $output['layout']) {
            $response->addHTML(
                Message::success(
                    sprintf(
                        __('Theme saved, go to the %smain page%s to try it'),
                        '<a href="index.php">',
                        '</a>'
                    )
                )
            );
        }
    }
}
