<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Transformation;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Transformations;

use function array_keys;

/**
 * Lists available transformation plugins
 */
#[Route('/transformation/overview', ['GET', 'POST'])]
final class OverviewController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly Transformations $transformations,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $header = $this->response->getHeader();
        $header->disableMenuAndConsole();

        $types = $this->transformations->getAvailableMimeTypes();

        $mimeTypes = [];
        foreach ($types['mimetype'] as $mimeType) {
            $mimeTypes[] = ['name' => $mimeType, 'is_empty' => isset($types['empty_mimetype'][$mimeType])];
        }

        $transformations = ['transformation' => [], 'input_transformation' => []];

        foreach (array_keys($transformations) as $type) {
            foreach ($types[$type] as $key => $transformation) {
                $transformations[$type][] = [
                    'name' => $transformation,
                    'description' => $this->transformations->getDescription($types[$type . '_file'][$key]),
                ];
            }
        }

        $this->response->render('transformation_overview', [
            'mime_types' => $mimeTypes,
            'transformations' => $transformations,
        ]);

        return $this->response->response();
    }
}
