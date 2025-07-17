<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\InvalidIdentifier;
use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use Webmozart\Assert\Assert;
use Webmozart\Assert\InvalidArgumentException;

use function __;

#[Route('/table/structure/drop-confirm', ['POST'])]
final class DropColumnConfirmationController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly DbTableExists $dbTableExists,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $fields = $request->getParsedBodyParam('selected_fld');
        try {
            $db = DatabaseName::from($request->getParsedBodyParam('db'));
            $table = TableName::from($request->getParsedBodyParam('table'));
            Assert::allStringNotEmpty($fields);
        } catch (InvalidIdentifier $exception) {
            $this->sendErrorResponse($exception->getMessage());

            return $this->response->response();
        } catch (InvalidArgumentException) {
            $this->sendErrorResponse(__('No column selected.'));

            return $this->response->response();
        }

        if (! $this->dbTableExists->selectDatabase($db)) {
            if ($request->isAjax()) {
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', Message::error(__('No databases selected.')));

                return $this->response->response();
            }

            return $this->response->redirectToRoute('/', ['reload' => true, 'message' => __('No databases selected.')]);
        }

        if (! $this->dbTableExists->hasTable($db, $table)) {
            if ($request->isAjax()) {
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', Message::error(__('No table selected.')));

                return $this->response->response();
            }

            return $this->response->redirectToRoute('/', ['reload' => true, 'message' => __('No table selected.')]);
        }

        $this->response->render('table/structure/drop_confirm', [
            'db' => $db->getName(),
            'table' => $table->getName(),
            'fields' => $fields,
        ]);

        return $this->response->response();
    }

    private function sendErrorResponse(string $message): void
    {
        $this->response->setStatusCode(StatusCodeInterface::STATUS_BAD_REQUEST);
        $this->response->setRequestStatus(false);

        if ($this->response->isAjax()) {
            $this->response->addJSON('isErrorResponse', true);
            $this->response->addJSON('message', $message);

            return;
        }

        $this->response->addHTML(Message::error($message)->getDisplay());
    }
}
