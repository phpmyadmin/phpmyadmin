<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package PhpMyAdmin
 */

/**
 *
 */
define('IS_TRANSFORMATION_WRAPPER', true);

/**
 * Gets a core script and starts output buffering work
 */
require_once './libraries/common.inc.php';
require_once './libraries/transformations.lib.php'; // Transformations
$cfgRelation = PMA_getRelationsParam();

/**
 * Ensures db and table are valid, else moves to the "parent" script
 */
require_once './libraries/db_table_exists.lib.php';


/**
 * Sets globals from $_REQUEST
 */
$request_params = array(
    'cn',
    'ct',
    'newHeight',
    'newWidth',
    'resize',
    'sql_query',
    'transform_key',
    'where_clause'
);
$size_params = array(
    'newHeight',
    'newWidth',
);
foreach ($request_params as $one_request_param) {
    if (isset($_REQUEST[$one_request_param])) {
        if (in_array($one_request_param, $size_params)) {
            $GLOBALS[$one_request_param] = intval($_REQUEST[$one_request_param]);
            if ($GLOBALS[$one_request_param] > 2000) {
                $GLOBALS[$one_request_param] = 2000;
            }
        } else {
            $GLOBALS[$one_request_param] = $_REQUEST[$one_request_param];
        }
    }
}


/**
 * Get the list of the fields of the current table
 */
PMA_DBI_select_db($db);
if (isset($where_clause)) {
    $result = PMA_DBI_query(
        'SELECT * FROM ' . PMA_Util::backquote($table) . ' WHERE ' . $where_clause . ';',
        null,
        PMA_DBI_QUERY_STORE
    );
    $row = PMA_DBI_fetch_assoc($result);
} else {
    $result = PMA_DBI_query(
        'SELECT * FROM ' . PMA_Util::backquote($table) . ' LIMIT 1;',
        null,
        PMA_DBI_QUERY_STORE
    );
    $row = PMA_DBI_fetch_assoc($result);
}

// No row returned
if (! $row) {
    exit;
} // end if (no record returned)

$default_ct = 'application/octet-stream';

if ($cfgRelation['commwork'] && $cfgRelation['mimework']) {
    $mime_map = PMA_getMime($db, $table);
    $mime_options = PMA_transformation_getOptions(
        isset($mime_map[$transform_key]['transformation_options'])
        ? $mime_map[$transform_key]['transformation_options'] : ''
    );

    foreach ($mime_options AS $key => $option) {
        if (substr($option, 0, 10) == '; charset=') {
            $mime_options['charset'] = $option;
        }
    }
}

// Only output the http headers
$response = PMA_Response::getInstance();
$response->getHeader()->sendHttpHeaders();

// [MIME]
if (isset($ct) && ! empty($ct)) {
    $mime_type = $ct;
} else {
    $mime_type = (!empty($mime_map[$transform_key]['mimetype'])
        ? str_replace('_', '/', $mime_map[$transform_key]['mimetype'])
        : $default_ct)
    . (isset($mime_options['charset']) ? $mime_options['charset'] : '');
}

PMA_downloadHeader($cn, $mime_type);

if (! isset($resize)) {
    if (stripos($mime_type, 'html') === false) {
        echo $row[$transform_key];
    } else {
        echo htmlspecialchars($row[$transform_key]);
    }
} else {
    // if image_*__inline.inc.php finds that we can resize,
    // it sets $resize to jpeg or png

    $srcImage = imagecreatefromstring($row[$transform_key]);
    $srcWidth = ImageSX($srcImage);
    $srcHeight = ImageSY($srcImage);

    // Check to see if the width > height or if width < height
    // if so adjust accordingly to make sure the image
    // stays smaller then the $newWidth and $newHeight

    $ratioWidth = $srcWidth/$newWidth;
    $ratioHeight = $srcHeight/$newHeight;

    if ($ratioWidth < $ratioHeight) {
        $destWidth = $srcWidth/$ratioHeight;
        $destHeight = $newHeight;
    } else {
        $destWidth = $newWidth;
        $destHeight = $srcHeight/$ratioWidth;
    }

    if ($resize) {
        $destImage = ImageCreateTrueColor($destWidth, $destHeight);
    }

    // ImageCopyResized($destImage, $srcImage, 0, 0, 0, 0,
    // $destWidth, $destHeight, $srcWidth, $srcHeight);
    // better quality but slower:
    ImageCopyResampled(
        $destImage, $srcImage, 0, 0, 0, 0, $destWidth,
        $destHeight, $srcWidth, $srcHeight
    );

    if ($resize == 'jpeg') {
        ImageJPEG($destImage, null, 75);
    }
    if ($resize == 'png') {
        ImagePNG($destImage);
    }
    ImageDestroy($srcImage);
    ImageDestroy($destImage);
}
?>
