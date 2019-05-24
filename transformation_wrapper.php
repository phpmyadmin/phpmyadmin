<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Wrapper script for rendering transformations
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Response;
use PhpMyAdmin\Transformations;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

define('IS_TRANSFORMATION_WRAPPER', true);

global $db, $table;

require_once ROOT_PATH . 'libraries/common.inc.php';

/** @var Response $response */
$response = $containerBuilder->get(Response::class);

/** @var DatabaseInterface $dbi */
$dbi = $containerBuilder->get(DatabaseInterface::class);

/** @var Transformations $transformations */
$transformations = $containerBuilder->get('transformations');
/** @var Relation $relation */
$relation = $containerBuilder->get('relation');
$cfgRelation = $relation->getRelationsParam();

/**
 * Ensures db and table are valid, else moves to the "parent" script
 */
require_once ROOT_PATH . 'libraries/db_table_exists.inc.php';


/**
 * Sets globals from $_REQUEST
 */
$request_params = [
    'cn',
    'ct',
    'sql_query',
    'transform_key',
    'where_clause',
];
$size_params = [
    'newHeight',
    'newWidth',
];
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
$dbi->selectDb($db);
if (isset($where_clause)) {
    $result = $dbi->query(
        'SELECT * FROM ' . PhpMyAdmin\Util::backquote($table)
        . ' WHERE ' . $where_clause . ';',
        PhpMyAdmin\DatabaseInterface::CONNECT_USER,
        PhpMyAdmin\DatabaseInterface::QUERY_STORE
    );
    $row = $dbi->fetchAssoc($result);
} else {
    $result = $dbi->query(
        'SELECT * FROM ' . PhpMyAdmin\Util::backquote($table) . ' LIMIT 1;',
        PhpMyAdmin\DatabaseInterface::CONNECT_USER,
        PhpMyAdmin\DatabaseInterface::QUERY_STORE
    );
    $row = $dbi->fetchAssoc($result);
}

// No row returned
if (! $row) {
    exit;
} // end if (no record returned)

$default_ct = 'application/octet-stream';

if ($cfgRelation['commwork'] && $cfgRelation['mimework']) {
    $mime_map = $transformations->getMime($db, $table);
    $mime_options = $transformations->getOptions(
        isset($mime_map[$transform_key]['transformation_options'])
        ? $mime_map[$transform_key]['transformation_options'] : ''
    );

    foreach ($mime_options as $key => $option) {
        if (substr($option, 0, 10) == '; charset=') {
            $mime_options['charset'] = $option;
        }
    }
}

$response->getHeader()->sendHttpHeaders();

// [MIME]
if (isset($ct) && ! empty($ct)) {
    $mime_type = $ct;
} else {
    $mime_type = (! empty($mime_map[$transform_key]['mimetype'])
        ? str_replace('_', '/', $mime_map[$transform_key]['mimetype'])
        : $default_ct)
    . (isset($mime_options['charset']) ? $mime_options['charset'] : '');
}

Core::downloadHeader($cn, $mime_type);

if (! isset($_REQUEST['resize'])) {
    if (stripos($mime_type, 'html') === false) {
        echo $row[$transform_key];
    } else {
        echo htmlspecialchars($row[$transform_key]);
    }
} else {
    // if image_*__inline.inc.php finds that we can resize,
    // it sets the resize parameter to jpeg or png

    $srcImage = imagecreatefromstring($row[$transform_key]);
    $srcWidth = imagesx($srcImage);
    $srcHeight = imagesy($srcImage);

    // Check to see if the width > height or if width < height
    // if so adjust accordingly to make sure the image
    // stays smaller than the new width and new height

    $ratioWidth = $srcWidth / $_REQUEST['newWidth'];
    $ratioHeight = $srcHeight / $_REQUEST['newHeight'];

    if ($ratioWidth < $ratioHeight) {
        $destWidth = $srcWidth / $ratioHeight;
        $destHeight = $_REQUEST['newHeight'];
    } else {
        $destWidth = $_REQUEST['newWidth'];
        $destHeight = $srcHeight / $ratioWidth;
    }

    if ($_REQUEST['resize']) {
        $destImage = imagecreatetruecolor($destWidth, $destHeight);

        // ImageCopyResized($destImage, $srcImage, 0, 0, 0, 0,
        // $destWidth, $destHeight, $srcWidth, $srcHeight);
        // better quality but slower:
        imagecopyresampled(
            $destImage,
            $srcImage,
            0,
            0,
            0,
            0,
            $destWidth,
            $destHeight,
            $srcWidth,
            $srcHeight
        );
        if ($_REQUEST['resize'] == 'jpeg') {
            imagejpeg($destImage, null, 75);
        }
        if ($_REQUEST['resize'] == 'png') {
            imagepng($destImage);
        }
        imagedestroy($destImage);
    }
    imagedestroy($srcImage);
}
