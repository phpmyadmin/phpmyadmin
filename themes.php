<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays list of themes.
 *
 * @package PhpMyAdmin
 */

/**
 * get some globals
 */
require './libraries/common.inc.php';
$response = PMA_Response::getInstance();
$response->getFooter()->setMinimal();
$header = $response->getHeader();
$header->setBodyId('bodythemes');
$header->setTitle('phpMyAdmin - ' . __('Theme'));
$header->disableMenu();

$hash    = '#pma_' . preg_replace('/([0-9]*)\.([0-9]*)\..*/', '\1_\2', PMA_VERSION);
$url     = PMA_linkURL('http://www.phpmyadmin.net/home_page/themes.php') . $hash;
$output  = '<h1>phpMyAdmin - ' . __('Theme') . '</h1>';
$output .= '<p>';
$output .= '<a href="' . $url . '" class="_blank">';
$output .= __('Get more themes!');
$output .= '</a>';
$output .= '</p>';
$output .= $_SESSION['PMA_Theme_Manager']->getPrintPreviews();

$response->addHTML($output);

?>
