<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @package     BLOBStreaming
 */

/**
 * Core library.
 */
require_once './libraries/common.inc.php';

// Check URL parameters
PMA_checkParameters(array('reference', 'c_type'));

// Increase time limit, because fetching blob might take some time
@set_time_limit(0);

$reference = $_REQUEST['reference'];
/*
 * FIXME: Maybe it would be better to check MIME type against whitelist as
 * this code sems to support only few MIME types (check
 * function PMA_BS_CreateReferenceLink in libraries/blobstreaming.lib.php).
 */
$c_type = preg_replace('/[^A-Za-z0-9/_-]/', '_', $_REQUEST['c_type']);

// Get the blob streaming URL
$filename = PMA_BS_getURL($reference);
if (empty($filename)) {
    die(__('No blob streaming server configured!'));
}

$hdrs = get_headers($filename, 1);

if ($hdrs === false) {
    die(__('Failed to fetch headers'));
}

$fHnd = fopen($filename, "rb");

if ($fHnd === false) {
    die(__('Failed to open remote URL'));
}

$f_size = $hdrs['Content-Length'];

PMA_download_header(basename($filename), $c_type, $f_size);

$pos = 0;
$content = "";

while (!feof($fHnd)) {
    $content .= fread($fHnd, $f_size);
    $pos = strlen($content);

    if ($pos >= $f_size) {
        break;
    }
}

echo $content;
flush();

fclose($fHnd);
