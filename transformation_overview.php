<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Lists available transformation plugins
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Response;
use PhpMyAdmin\Template;
use PhpMyAdmin\Transformations;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

/**
 * Gets some core libraries and displays a top message if required
 */
require_once ROOT_PATH . 'libraries/common.inc.php';

$response = Response::getInstance();
$header = $response->getHeader();
$header->disableMenuAndConsole();

$transformations = new Transformations();
$template = new Template();

$types = $transformations->getAvailableMimeTypes();

$mimeTypes = [];
foreach ($types['mimetype'] as $mimeType) {
    $mimeTypes[] = [
        'name' => $mimeType,
        'is_empty' => isset($types['empty_mimetype'][$mimeType]),
    ];
}

$transformationTypes = [
    'transformation' => [],
    'input_transformation' => [],
];

foreach (array_keys($transformationTypes) as $type) {
    foreach ($types[$type] as $key => $transformation) {
        $transformationTypes[$type][] = [
            'name' => $transformation,
            'description' => $transformations->getDescription($types[$type . '_file'][$key]),
        ];
    }
}

$response->addHTML($template->render('transformation_overview', [
    'mime_types' => $mimeTypes,
    'transformations' => $transformationTypes,
]));
