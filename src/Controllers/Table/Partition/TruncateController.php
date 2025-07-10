<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table\Partition;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\InvalidIdentifier;
use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\Message;
use PhpMyAdmin\MessageType;
use PhpMyAdmin\Partitioning\Maintenance;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use Webmozart\Assert\Assert;
use Webmozart\Assert\InvalidArgumentException;

use function __;

#[Route('/table/partition/truncate', ['POST'])]
final class TruncateController implements InvocableController
{
    public function __construct(private readonly ResponseRenderer $response, private readonly Maintenance $model)
    {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $partitionName = $request->getParsedBodyParam('partition_name');

        try {
            Assert::stringNotEmpty($partitionName, __('The partition name must be a non-empty string.'));
            $database = DatabaseName::from($request->getParam('db'));
            $table = TableName::from($request->getParam('table'));
        } catch (InvalidIdentifier | InvalidArgumentException $exception) {
            $message = Message::error($exception->getMessage());
            $this->response->addHTML($message->getDisplay());

            return $this->response->response();
        }

        [$result, $query] = $this->model->truncate($database, $table, $partitionName);

        if ($result) {
            $message = Generator::getMessage(
                __('Your SQL query has been executed successfully.'),
                $query,
                MessageType::Success,
            );
        } else {
            $message = Generator::getMessage(
                __('Error'),
                $query,
                MessageType::Error,
            );
        }

        $this->response->render('table/partition/truncate', [
            'partition_name' => $partitionName,
            'message' => $message,
        ]);

        return $this->response->response();
    }
}
