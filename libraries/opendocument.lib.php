<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Simple interface for creating OASIS OpenDocument files.
 */

require_once('./libraries/zip.lib.php');

/**
 * Minimalistic creator of OASIS OpenDocument
 *
 * @param   string      desired MIME type
 * @param   string      document content
 *
 * @return  string      OASIS OpenDocument data
 *
 * @access  public
 */
function PMA_createOpenDocument($mime, $data) {
    $zipfile = new zipfile();
    $zipfile -> addFile($mime, 'mimetype');
    $zipfile -> addFile($data, 'content.xml');
    $zipfile -> addFile('<?xml version="1.0" encoding="UTF-8"?>'
        . '<manifest:manifest xmlns:manifest="urn:oasis:names:tc:opendocument:xmlns:manifest:1.0">'
        . '<manifest:file-entry manifest:media-type="' . $mime . '" manifest:full-path="/"/>'
        . '<manifest:file-entry manifest:media-type="text/xml" manifest:full-path="content.xml"/>'
        . '</manifest:manifest>'
        , 'META-INF/manifest.xml');
    return $zipfile -> file();
}
?>
