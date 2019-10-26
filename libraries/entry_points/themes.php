<?php
/**
 * Displays list of themes.
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Response;
use PhpMyAdmin\Template;
use PhpMyAdmin\ThemeManager;

if (! defined('PHPMYADMIN')) {
    exit;
}

global $containerBuilder;

/** @var Template $template */
$template = $containerBuilder->get('template');

$response = Response::getInstance();
$response->getFooter()->setMinimal();
$header = $response->getHeader();
$header->setBodyId('bodythemes');
$header->setTitle('phpMyAdmin - ' . __('Theme'));
$header->disableMenuAndConsole();

$response->addHTML($template->render('themes', [
    'version' => preg_replace(
        '/([0-9]*)\.([0-9]*)\..*/',
        '\1_\2',
        PMA_VERSION
    ),
    'previews' => ThemeManager::getInstance()->getPrintPreviews(),
]));
