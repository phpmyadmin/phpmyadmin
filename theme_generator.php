<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Theme generator tool
 *
 * @package PhpMyAdmin
 */
use PhpMyAdmin\Response;

require_once 'libraries/common.inc.php';

$response = Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('theme_generator/color_picker.js');

$response->addHTML('<div id="container">');
$response->addHTML('<div id="palette" class="block">');
$response->addHTML('<div id="color-palette"></div>');
$response->addHTML('<div id="color-info">');
$response->addHTML('<div class="title"> Color Picker </div>');
$response->addHTML('</div>');
$response->addHTML('</div>');
$response->addHTML('<div id="picker" class="block">');
$response->addHTML('<div class="ui-color-picker" data-topic="picker" data-mode="HSL"></div>');
$response->addHTML('<div id="picker-samples" sample-id="master"></div>');
$response->addHTML('<div id="controls">');
$response->addHTML('</div>');
$response->addHTML('</div>');
$response->addHTML('</div>');
