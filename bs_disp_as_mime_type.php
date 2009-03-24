<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @author      Raj Kissu Rajandran
 * @version     1.0
 * @package     BLOBStreaming
 */

/**
 * Core library.
 */
require_once './libraries/common.inc.php';

// load PMA configuration
$PMA_Config = $_SESSION['PMA_Config'];

// retrieve BS server variables from PMA configuration
$bs_server = $PMA_Config->get('BLOBSTREAMING_SERVER');
if (empty($bs_server)) die('No blob streaming server configured!');

// Check URL parameters
PMA_checkParameters(array('reference', 'c_type'));

// Increase time limit, because fetching blob might take some time
set_time_limit(0);

$reference = $_REQUEST['reference'];
/*
 * FIXME: Maybe it would be better to check MIME type against whitelist as
 * this code sems to support only few MIME types (check
 * function PMA_BS_CreateReferenceLink in libraries/blobstreaming.lib.php).
 */
$c_type = preg_replace('/[^A-Za-z0-9/_-]/', '_', $_REQUEST['c_type']);

$filename = 'http://' . $bs_server . '/' . $reference;

$hdrs = get_headers($filename, 1);

if ($hdrs === FALSE) die('Failed to fetch headers');

$fHnd = fopen($filename, "rb");

if ($fHnd === FALSE) die('Failed to open remote URL');

$f_size = $hdrs['Content-Length'];

header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Content-type: $c_type");
header('Content-length: ' . $f_size);
header("Content-disposition: attachment; filename=" . basename($filename));

$pos = 0;
$content = "";

while (!feof($fHnd)) {
    $content .= fread($fHnd, $f_size);
    $pos = strlen($content);

    if ($pos >= $f_size)
        break;
}

echo $content;
flush();

fclose($fHnd);
