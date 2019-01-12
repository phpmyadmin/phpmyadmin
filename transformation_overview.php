<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Lists available transformation plugins
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Response;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Template;

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

$types = $transformations->getAvailableMimeTypes();

/**
 * Preparing all labels which are required on view template
 */
$labels = [
    'available_MIME_types' => __('Available MIME types'),
    'transformation' => __('Available browser display transformations'),
    'input_transformation' => __('Available input transformations'),
];

/**
 * Preparing labels for table Table Heading (<th>)
 */
$tableHeadLabels = [
    'transformation' => __('Browser display transformation'),
    'input_transformation' => __('Input transformation'),
];

$transformation_types = [
    'transformation' => [],
    'input_transformation' => [],
];

/**
 * Looping over transformations and preparing labels, titles, text and transformation types to prepare
 * nested array so that view template can easily get all required information.
 */
foreach (['transformation', 'input_transformation'] as $ttype) {
    // settings title, text and labels for transformation
    $transformation_types[$ttype]['title'] = $ttype;
    $transformation_types[$ttype]['_pgettext'] = _pgettext('for MIME transformation', 'Description');
    $transformation_types[$ttype]['label'] = $labels[$ttype];

    $transformation_types[$ttype]['types'] = [];

    // Settings types for current transformation
    foreach ($types[$ttype] as $key => $transform) {
        $transformation_types[$ttype]['types'][] = [
            'transform' => $transform,
            'description' => $transformations->getDescription($types[$ttype . '_file'][$key]),
        ];
    }
}

$template = new Template();
echo $template->render('transformation_overview', [
    'label' => $labels,
    'types' => $transformations->getAvailableMimeTypes(),
    'transformation_types' => $transformation_types,
    'table_headings_label' => $tableHeadLabels,
]);
