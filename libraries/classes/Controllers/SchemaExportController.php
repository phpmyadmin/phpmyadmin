<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Core;
use PhpMyAdmin\Dbal\DatabaseName;
use PhpMyAdmin\Exceptions\ExportException;
use PhpMyAdmin\Export;
use PhpMyAdmin\Html\MySQLDocumentation;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;

use function __;
use function is_string;
use function mb_strlen;

/**
 * Schema export handler
 */
class SchemaExportController
{
    public function __construct(private Export $export, private ResponseRenderer $response)
    {
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
            $exportInfo = $this->export->getExportSchemaInfo($db, $exportType);
        } catch (ExportException $exception) {
            $this->response->setRequestStatus(false);
            $this->response->addHTML(Message::error($exception->getMessage())->getDisplay());

            return;
        }

        $this->response->disable();
        Core::downloadHeader(
            $exportInfo['fileName'],
            $exportInfo['mediaType'],
            mb_strlen($exportInfo['fileData'], '8bit'),
        );
        echo $exportInfo['fileData'];
    }
}
