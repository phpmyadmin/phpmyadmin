<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays list of themes.
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Core;
use PhpMyAdmin\ThemeManager;
use PhpMyAdmin\Response;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
}

/**
 * get some globals
 */
include ROOT_PATH . 'libraries/common.inc.php';

$response = Response::getInstance();
$response->getFooter()->setMinimal();
$header = $response->getHeader();
$header->setBodyId('bodythemes');
$header->setTitle('phpMyAdmin - ' . __('Theme'));
$header->disableMenuAndConsole();

$hash    = '#pma_' . preg_replace('/([0-9]*)\.([0-9]*)\..*/', '\1_\2', PMA_VERSION);
$url     = Core::linkURL('https://www.phpmyadmin.net/themes/') . $hash;
$output  = '<h1>phpMyAdmin - ' . __('Theme') . '</h1>';
$output .= '<p>';
$output .= '<a href="' . $url . '" rel="noopener noreferrer" target="_blank">';
$output .= __('Get more themes!');
$output .= '</a>';
$output .= '</p>';
$output .= ThemeManager::getInstance()->getPrintPreviews();

$response->addHTML($output);
