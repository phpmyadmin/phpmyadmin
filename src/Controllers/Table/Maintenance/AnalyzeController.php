<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table\Maintenance;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\InvalidIdentifier;
use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\Message;
use PhpMyAdmin\MessageType;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Table\Maintenance;
use Webmozart\Assert\Assert;
use Webmozart\Assert\InvalidArgumentException;

use function __;
use function count;

#[Route('/table/maintenance/analyze', ['POST'])]
final class AnalyzeController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly Maintenance $model,
        private readonly Config $config,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $selectedTablesParam = $request->getParsedBodyParam('selected_tbl');

        try {
            Assert::isArray($selectedTablesParam);
            Assert::notEmpty($selectedTablesParam);
            Assert::allStringNotEmpty($selectedTablesParam);
        } catch (InvalidArgumentException) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No table selected.'));

            return $this->response->response();
        }

        try {
            $database = DatabaseName::from($request->getParam('db'));
            $selectedTables = [];
            foreach ($selectedTablesParam as $table) {
                $selectedTables[] = TableName::from($table);
            }
        } catch (InvalidIdentifier $exception) {
            $message = Message::error($exception->getMessage());
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', $message->getDisplay());

            return $this->response->response();
        }

        if ($this->config->config->DisableMultiTableMaintenance && count($selectedTables) > 1) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('Maintenance operations on multiple tables are disabled.'));

            return $this->response->response();
        }

        [$rows, $query] = $this->model->getAnalyzeTableRows($database, $selectedTables);

        $message = Generator::getMessage(
            __('Your SQL query has been executed successfully.'),
            $query,
            MessageType::Success,
        );

        $this->response->render('table/maintenance/analyze', ['message' => $message, 'rows' => $rows]);

        return $this->response->response();
    }
}
