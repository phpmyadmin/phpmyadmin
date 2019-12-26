<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PhpMyAdmin\Controllers\TransformationOverviewController
 *
 * @package PhpMyAdmin\Controllers
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;
use PhpMyAdmin\Transformations;

/**
 * Lists available transformation plugins
 *
 * @package PhpMyAdmin\Controllers
 */
class TransformationOverviewController extends AbstractController
{
    /**
     * @var Transformations
     */
    private $transformations;

    /**
     * TransformationOverviewController constructor.
     *
     * @param Response          $response        Response object
     * @param DatabaseInterface $dbi             DatabaseInterface object
     * @param Template          $template        Template object
     * @param Transformations   $transformations Transformations object
     */
    public function __construct($response, $dbi, Template $template, $transformations)
    {
        parent::__construct($response, $dbi, $template);

        $this->transformations = $transformations;
    }

    /**
     * @return string HTML
     */
    public function indexAction(): string
    {
        $types = $this->transformations->getAvailableMimeTypes();

        $mimeTypes = [];
        foreach ($types['mimetype'] as $mimeType) {
            $mimeTypes[] = [
                'name' => $mimeType,
                'is_empty' => isset($types['empty_mimetype'][$mimeType]),
            ];
        }

        $transformations = [
            'transformation' => [],
            'input_transformation' => [],
        ];

        foreach (array_keys($transformations) as $type) {
            foreach ($types[$type] as $key => $transformation) {
                $transformations[$type][] = [
                    'name' => $transformation,
                    'description' => $this->transformations->getDescription(
                        $types[$type . '_file'][$key]
                    ),
                ];
            }
        }

        return $this->template->render('transformation_overview', [
            'mime_types' => $mimeTypes,
            'transformations' => $transformations,
        ]);
    }
}
