<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database\Structure;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\FlashMessenger;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function mb_strlen;
use function mb_substr;

#[Route('/database/structure/replace-prefix', ['POST'])]
final class ReplacePrefixController implements InvocableController
{
    public function __construct(
        private readonly DatabaseInterface $dbi,
        private readonly ResponseFactory $responseFactory,
        private readonly FlashMessenger $flashMessenger,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        /** @var string[] $selected */
        $selected = $request->getParsedBodyParam('selected', []);
        $fromPrefix = $request->getParsedBodyParamAsString('from_prefix', '');
        $toPrefix = $request->getParsedBodyParamAsString('to_prefix', '');

        Current::$sqlQuery = '';

        $this->dbi->selectDb(Current::$database);

        foreach ($selected as $selectedValue) {
            $subFromPrefix = mb_substr($selectedValue, 0, mb_strlen($fromPrefix));

            if ($subFromPrefix === $fromPrefix) {
                $newTableName = $toPrefix . mb_substr($selectedValue, mb_strlen($fromPrefix));
            } else {
                $newTableName = $selectedValue;
            }

            $aQuery = 'ALTER TABLE ' . Util::backquote($selectedValue)
                . ' RENAME ' . Util::backquote($newTableName);

            Current::$sqlQuery .= $aQuery . ';' . "\n";
            $this->dbi->query($aQuery);
        }

        Current::$message = Message::success();

        $this->flashMessenger->addMessage('success', Current::$message->getMessage(), Current::$sqlQuery);

        return $this->responseFactory->createResponse(StatusCodeInterface::STATUS_FOUND)
            ->withHeader('Location', Url::getFromRoute('/database/structure', ['db' => Current::$database]));
    }
}
