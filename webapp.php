<?php
/**
 * generate an WebApp file for Prism / WebRunner
 *
 * @see http://wiki.mozilla.org/Prism
 * @todo send zip file without saving
 * @todo use own zip class and make use of PHP zip function in there if available
 */

/**
 *
 */
define('PMA_MINIMUM_COMMON', true);
require_once './libraries/common.inc.php';

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

$zip = new ZipArchive();
$filename = './' . $name;

if ($zip->open($filename, ZIPARCHIVE::CREATE) !== true) {
    exit("cannot open <$filename>\n");
}

$zip->addFromString("webapp.ini", $ini_file);
$zip->addFile($icon, 'phpMyAdmin.ico');
$zip->close();

header('Location: ' . $_SESSION['PMA_Config']->get('PmaAbsoluteUri') . $filename);
?>
