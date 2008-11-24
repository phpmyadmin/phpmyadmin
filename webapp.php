<?php
/**
 * generate an WebApp file for Prism / WebRunner
 *
 * @see http://wiki.mozilla.org/Prism
 * @package phpMyAdmin
 */

/**
 * @ignore
 */
define('PMA_MINIMUM_COMMON', true);
/**
 * Gets core libraries and defines some variables
 */
require_once './libraries/common.inc.php';
/**
 * ZIP file handler.
 */
require_once './libraries/zip.lib.php';

// ini file
$parameters = array(
    'id'        => 'phpMyAdmin@' . $_SERVER['HTTP_HOST'],
    'uri'       => $_SESSION['PMA_Config']->get('PmaAbsoluteUri'),
    'status'    => 'yes',
    'location'  => 'no',
    'sidebar'   => 'no',
    'navigation' => 'no',
    'icon'      => 'phpMyAdmin',
);

// dom sript file
// none need yet

// icon
$icon = 'favicon.ico';

// name
$name = 'phpMyAdmin.webapp';

$ini_file = "[Parameters]\n";
foreach ($parameters as $key => $value) {
    $ini_file .= $key . '=' . $value . "\n";
}

$zip = new zipfile;
$zip->addFile($ini_file, 'webapp.ini');
$zip->addFile(file_get_contents($icon), 'phpMyAdmin.ico');

header('Content-Type: application/webapp');
header('Content-Disposition: attachment; filename="' . $name . '"');
echo $zip->file();
?>
