<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table\Partition;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Dbal\DatabaseName;
use PhpMyAdmin\Dbal\InvalidIdentifierName;
use PhpMyAdmin\Dbal\TableName;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\Partitioning\Maintenance;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use Webmozart\Assert\Assert;
use Webmozart\Assert\InvalidArgumentException;

use function __;

final class AnalyzeController extends AbstractController
{
    private Maintenance $model;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        Maintenance $maintenance,
    ) {
        parent::__construct($response, $template);

        $this->model = $maintenance;
    }

    public function __invoke(ServerRequest $request): void
    {
        $partitionName = $request->getParsedBodyParam('partition_name');

        try {
            Assert::stringNotEmpty($partitionName, __('The partition name must be a non-empty string.'));
            $database = DatabaseName::fromValue($request->getParam('db'));
            $table = TableName::fromValue($request->getParam('table'));
        } catch (InvalidIdentifierName | InvalidArgumentException $exception) {
            $message = Message::error($exception->getMessage());
            $this->response->addHTML($message->getDisplay());

            return;
        }

        [$rows, $query] = $this->model->analyze($database, $table, $partitionName);

        $message = Generator::getMessage(
            __('Your SQL query has been executed successfully.'),
            $query,
            'success',
        );

        $this->render('table/partition/analyze', [
            'partition_name' => $partitionName,
            'message' => $message,
            'rows' => $rows,
        ]);
    }
}
