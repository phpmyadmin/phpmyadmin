<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id$
 */

/**
 *
 */
define('IS_TRANSFORMATION_WRAPPER', true);

/**
 * Gets a core script and starts output buffering work
 */
require_once './libraries/common.inc.php';
require_once './libraries/relation.lib.php'; // foreign keys
require_once './libraries/transformations.lib.php'; // Transformations
$cfgRelation = PMA_getRelationsParam();

/**
 * Ensures db and table are valid, else moves to the "parent" script
 */
require_once './libraries/db_table_exists.lib.php';


/**
 * Get the list of the fields of the current table
 */
PMA_DBI_select_db($db);
$table_def = PMA_DBI_query('SHOW FIELDS FROM ' . PMA_backquote($table), null, PMA_DBI_QUERY_STORE);
if (isset($primary_key)) {
    $result      = PMA_DBI_query('SELECT * FROM ' . PMA_backquote($table) . ' WHERE ' . $primary_key . ';', null, PMA_DBI_QUERY_STORE);
    $row         = PMA_DBI_fetch_assoc($result);
} else {
    $result      = PMA_DBI_query('SELECT * FROM ' . PMA_backquote($table) . ' LIMIT 1;', null, PMA_DBI_QUERY_STORE);
    $row         = PMA_DBI_fetch_assoc($result);
}

// No row returned
if (!$row) {
    exit;
} // end if (no record returned)

$default_ct = 'application/octet-stream';

if ($cfgRelation['commwork'] && $cfgRelation['mimework']) {
    $mime_map = PMA_getMime($db, $table);
    $mime_options = PMA_transformation_getOptions((isset($mime_map[urldecode($transform_key)]['transformation_options']) ? $mime_map[urldecode($transform_key)]['transformation_options'] : ''));

    foreach ($mime_options AS $key => $option) {
        if (substr($option, 0, 10) == '; charset=') {
            $mime_options['charset'] = $option;
        }
    }
}

// garvin: For re-usability, moved http-headers and stylesheets
// to a seperate file. It can now be included by libraries/header.inc.php,
// querywindow.php.

require_once './libraries/header_http.inc.php';
// [MIME]
if (isset($ct) && !empty($ct)) {
    $content_type = 'Content-Type: ' . urldecode($ct);
} else {
    $content_type = 'Content-Type: ' . (isset($mime_map[urldecode($transform_key)]['mimetype']) ? str_replace('_', '/', $mime_map[urldecode($transform_key)]['mimetype']) : $default_ct) . (isset($mime_options['charset']) ? $mime_options['charset'] : '');
}
header($content_type);

if (isset($cn) && !empty($cn)) {
    header('Content-Disposition: attachment; filename=' . urldecode($cn));
}

if (!isset($resize)) {
    echo $row[urldecode($transform_key)];
} else {
    // if image_*__inline.inc.php finds that we can resize,
    // it sets $resize to jpeg or png

    $srcImage = imagecreatefromstring($row[urldecode($transform_key)]);
    $srcWidth = ImageSX($srcImage);
    $srcHeight = ImageSY($srcImage);

    // Check to see if the width > height or if width < height
    // if so adjust accordingly to make sure the image
    // stays smaller then the $newWidth and $newHeight

    $ratioWidth = $srcWidth/$newWidth;
    $ratioHeight = $srcHeight/$newHeight;

    if ($ratioWidth < $ratioHeight){
        $destWidth = $srcWidth/$ratioHeight;
        $destHeight = $newHeight;
    } else {
        $destWidth = $newWidth;
        $destHeight = $srcHeight/$ratioWidth;
    }

    if ($resize) {
        $destImage = ImageCreateTrueColor($destWidth, $destHeight);
    }

//    ImageCopyResized($destImage, $srcImage, 0, 0, 0, 0, $destWidth, $destHeight, $srcWidth, $srcHeight);
// better quality but slower:
    ImageCopyResampled($destImage, $srcImage, 0, 0, 0, 0, $destWidth, $destHeight, $srcWidth, $srcHeight);

    if ($resize == 'jpeg') {
        ImageJPEG($destImage, '', 75);
    }
    if ($resize == 'png') {
        ImagePNG($destImage);
    }
    ImageDestroy($srcImage);
    ImageDestroy($destImage);
}
?>
