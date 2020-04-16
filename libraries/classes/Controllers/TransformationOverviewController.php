<?php
/**
 * Holds the PhpMyAdmin\Controllers\TransformationOverviewController
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;
use PhpMyAdmin\Transformations;
use function array_keys;

/**
 * Lists available transformation plugins
 */
class TransformationOverviewController extends AbstractController
{
    /** @var Transformations */
    private $transformations;

    /**
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

    public function index(): void
    {
        $header = $this->response->getHeader();
        $header->disableMenuAndConsole();

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

        $this->render('transformation_overview', [
            'mime_types' => $mimeTypes,
            'transformations' => $transformations,
        ]);
    }
}
