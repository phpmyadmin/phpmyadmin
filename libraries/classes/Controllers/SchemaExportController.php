<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Dbal\DatabaseName;
use PhpMyAdmin\Export;
use PhpMyAdmin\Html\MySQLDocumentation;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use RuntimeException;

use function __;
use function is_string;

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
        $db = DatabaseName::tryFromValue($request->getParsedBodyParam('db'));
        /** @var mixed $exportType */
        $exportType = $request->getParsedBodyParam('export_type');
        if ($db === null || ! is_string($exportType) || $exportType === '') {
            $errorMessage = __('Missing parameter:') . ($db === null ? ' db' : ' export_type')
                . MySQLDocumentation::showDocumentation('faq', 'faqmissingparameters', true)
                . '[br]';
            $this->response->setRequestStatus(false);
            $this->response->addHTML(Message::error($errorMessage)->getDisplay());

            return;
        }

        /**
         * Include the appropriate Schema Class depending on $exportType, default is PDF.
         */
        try {
            $this->export->processExportSchema($db, $exportType);
        } catch (RuntimeException $exception) {
            $this->response->setRequestStatus(false);
            $this->response->addHTML(Message::error($exception->getMessage())->getDisplay());
        }
    }
}
