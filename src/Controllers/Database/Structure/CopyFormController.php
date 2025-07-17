<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database\Structure;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Template;

use function __;

#[Route('/database/structure/copy-form', ['POST'])]
final class CopyFormController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly ResponseFactory $responseFactory,
        private readonly Template $template,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        /** @var string[] $selected */
        $selected = $request->getParsedBodyParam('selected_tbl', []);

        if ($selected === []) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No table selected.'));

            return $this->response->response();
        }

        $urlParams = ['db' => Current::$database];
        foreach ($selected as $selectedValue) {
            $urlParams['selected'][] = $selectedValue;
        }

        $databasesList = DatabaseInterface::getInstance()->getDatabaseList();
        foreach ($databasesList as $key => $databaseName) {
            if ($databaseName === Current::$database) {
                $databasesList->offsetUnset($key);
                break;
            }
        }

        $response = $this->responseFactory->createResponse();
        foreach ($this->response->getHeader()->getHttpHeaders() as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        return $response->write($this->template->render('database/structure/copy_form', [
            'url_params' => $urlParams,
            'options' => $databasesList->getList(),
        ]));
    }
}
