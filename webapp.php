<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * generate an WebApp file for Prism / WebRunner
 *
 * @package PhpMyAdmin
 * @see     http://wiki.mozilla.org/Prism
 */

/**
 * @ignore
 */
define('PMA_MINIMUM_COMMON', true);
/**
 * Gets core libraries and defines some variables
 */
require './libraries/common.inc.php';
/**
 * ZIP file handler.
 */
require './libraries/zip.lib.php';

// ini file
$parameters = array(
    'id'        => 'phpMyAdmin@' . $_SERVER['HTTP_HOST'],
    'uri'       => $GLOBALS['PMA_Config']->get('PmaAbsoluteUri'),
    'status'    => 'yes',
    'location'  => 'no',
    'sidebar'   => 'no',
    'navigation' => 'no',
    'icon'      => 'phpMyAdmin',
);

// dom script file
// none need yet

// icon
$icon = 'favicon.ico';

// name
$name = 'phpMyAdmin.webapp';

$ini_file = "[Parameters]\n";
foreach ($parameters as $key => $value) {
    $ini_file .= $key . '=' . $value . "\n";
}

PMA_downloadHeader($name, 'application/webapp', 0, false);

$zip = new ZipFile;
$zip->setDoWrite();
$zip->addFile($ini_file, 'webapp.ini');
$zip->addFile(file_get_contents($icon), 'phpMyAdmin.ico');
$zip->file();
?>
