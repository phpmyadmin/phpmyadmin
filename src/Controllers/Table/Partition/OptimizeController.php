<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table\Partition;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\InvalidIdentifier;
use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\Message;
use PhpMyAdmin\Partitioning\Maintenance;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use Webmozart\Assert\Assert;
use Webmozart\Assert\InvalidArgumentException;

use function __;

final class OptimizeController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private Maintenance $model,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $partitionName = $request->getParsedBodyParam('partition_name');

        try {
            Assert::stringNotEmpty($partitionName, __('The partition name must be a non-empty string.'));
            $database = DatabaseName::from($request->getParam('db'));
            $table = TableName::from($request->getParam('table'));
        } catch (InvalidIdentifier | InvalidArgumentException $exception) {
            $message = Message::error($exception->getMessage());
            $this->response->addHTML($message->getDisplay());

            return;
        }

        [$rows, $query] = $this->model->optimize($database, $table, $partitionName);

        $message = Generator::getMessage(
            __('Your SQL query has been executed successfully.'),
            $query,
            'success',
        );

        $this->render('table/partition/optimize', [
            'partition_name' => $partitionName,
            'message' => $message,
            'rows' => $rows,
        ]);
    }
}
