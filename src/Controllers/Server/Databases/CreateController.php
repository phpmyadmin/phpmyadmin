<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server\Databases;

use PhpMyAdmin\Charsets;
use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\MessageType;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
use function array_key_exists;
use function explode;
use function is_string;
use function mb_strtolower;
use function str_contains;

#[Route('/server/databases/create', ['POST'])]
final readonly class CreateController implements InvocableController
{
    public function __construct(
        private ResponseRenderer $response,
        private DatabaseInterface $dbi,
        private Config $config,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $newDb = $request->getParsedBodyParamAsString('new_db');
        $dbCollation = $request->getParsedBodyParamAsStringOrNull('db_collation');

        if ($newDb === '' || ! $request->isAjax()) {
            $this->response->addJSON(['message' => Message::error()]);

            return $this->response->response();
        }

        if ($this->dbi->getLowerCaseNames() === 1) {
            $newDb = mb_strtolower($newDb);
        }

        /**
         * Builds and executes the db creation sql query
         */
        $sqlQuery = 'CREATE DATABASE ' . Util::backquote($newDb);
        if (is_string($dbCollation) && $dbCollation !== '') {
            [$databaseCharset] = explode('_', $dbCollation);
            $charsets = Charsets::getCharsets($this->dbi, $this->config->selectedServer['DisableIS']);
            $collations = Charsets::getCollations($this->dbi, $this->config->selectedServer['DisableIS']);
            if (
                array_key_exists($databaseCharset, $charsets)
                && array_key_exists($dbCollation, $collations[$databaseCharset])
            ) {
                $sqlQuery .= ' DEFAULT'
                    . Util::getCharsetQueryPart($dbCollation);
            }
        }

        $sqlQuery .= ';';

        $result = $this->dbi->tryQuery($sqlQuery);

        if (! $result) {
            // avoid displaying the not-created db name in header or navi panel
            Current::$database = '';

            $message = Message::rawError($this->dbi->getError());
            $json = ['message' => $message];

            $this->response->setRequestStatus(false);
        } else {
            Current::$database = $newDb;

            $message = Message::success(__('Database %1$s has been created.'));
            $message->addParam($newDb);

            $scriptName = Url::getFromRoute($this->config->settings['DefaultTabDatabase']);

            $json = [
                'message' => $message,
                'sql_query' => Generator::getMessage('', $sqlQuery, MessageType::Success),
                'url' => $scriptName . Url::getCommon(
                    ['db' => $newDb],
                    ! str_contains($scriptName, '?') ? '?' : '&',
                ),
            ];
        }

        $this->response->addJSON($json);

        return $this->response->response();
    }
}
