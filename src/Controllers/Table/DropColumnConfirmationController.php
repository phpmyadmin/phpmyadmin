<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\InvalidIdentifier;
use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use Webmozart\Assert\Assert;
use Webmozart\Assert\InvalidArgumentException;

use function __;

final class DropColumnConfirmationController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private readonly DbTableExists $dbTableExists,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $fields = $request->getParsedBodyParam('selected_fld');
        try {
            $db = DatabaseName::from($request->getParsedBodyParam('db'));
            $table = TableName::from($request->getParsedBodyParam('table'));
            Assert::allStringNotEmpty($fields);
        } catch (InvalidIdentifier $exception) {
            $this->sendErrorResponse($exception->getMessage());

            return;
        } catch (InvalidArgumentException) {
            $this->sendErrorResponse(__('No column selected.'));

            return;
        }

        if (! $this->dbTableExists->selectDatabase($db)) {
            if ($request->isAjax()) {
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', Message::error(__('No databases selected.')));

                return;
            }

            $this->redirect('/', ['reload' => true, 'message' => __('No databases selected.')]);

            return;
        }

        if (! $this->dbTableExists->hasTable($db, $table)) {
            if ($request->isAjax()) {
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', Message::error(__('No table selected.')));

                return;
            }

            $this->redirect('/', ['reload' => true, 'message' => __('No table selected.')]);

            return;
        }

        $this->render('table/structure/drop_confirm', [
            'db' => $db->getName(),
            'table' => $table->getName(),
            'fields' => $fields,
        ]);
    }
}
