<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Export;
use PhpMyAdmin\Html\MySQLDocumentation;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use Throwable;

use function __;

/**
 * Schema export handler
 */
class SchemaExportController
{
    /** @var Export */
    private $export;

    /** @var ResponseRenderer */
    private $response;

    public function __construct(Export $export, ResponseRenderer $response)
    {
        $this->export = $export;
        $this->response = $response;
    }

    public function __invoke(ServerRequest $request): void
    {
        if ($request->getParsedBodyParam('export_type') === null) {
            $errorMessage = __('Missing parameter:') . ' export_type'
                . MySQLDocumentation::showDocumentation('faq', 'faqmissingparameters', true)
                . '[br]';
            $this->response->setRequestStatus(false);
            $this->response->addHTML(Message::error($errorMessage)->getDisplay());

            return;
        }

        /**
         * Include the appropriate Schema Class depending on $export_type
         * default is PDF
         */
        try {
            $this->export->processExportSchema($request->getParsedBodyParam('export_type'));
        } catch (Throwable $exception) {
            $this->response->setRequestStatus(false);
            $this->response->addHTML(Message::error($exception->getMessage())->getDisplay());
        }
    }
}
