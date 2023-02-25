<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server\Databases;

use PhpMyAdmin\Charsets;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
use function array_key_exists;
use function explode;
use function is_string;
use function mb_strtolower;
use function str_contains;

final class CreateController extends AbstractController
{
    public function __construct(ResponseRenderer $response, Template $template, private DatabaseInterface $dbi)
    {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $newDb = $request->getParsedBodyParam('new_db');
        $dbCollation = $request->getParsedBodyParam('db_collation');

        if (! is_string($newDb) || $newDb === '' || ! $this->response->isAjax()) {
            $this->response->addJSON(['message' => Message::error()]);

            return;
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
            $charsets = Charsets::getCharsets($this->dbi, $GLOBALS['cfg']['Server']['DisableIS']);
            $collations = Charsets::getCollations($this->dbi, $GLOBALS['cfg']['Server']['DisableIS']);
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
            $GLOBALS['db'] = '';

            $message = Message::rawError($this->dbi->getError());
            $json = ['message' => $message];

            $this->response->setRequestStatus(false);
        } else {
            $GLOBALS['db'] = $newDb;

            $message = Message::success(__('Database %1$s has been created.'));
            $message->addParam($newDb);

            $scriptName = Util::getScriptNameForOption($GLOBALS['cfg']['DefaultTabDatabase'], 'database');

            $json = [
                'message' => $message,
                'sql_query' => Generator::getMessage('', $sqlQuery, 'success'),
                'url' => $scriptName . Url::getCommon(
                    ['db' => $newDb],
                    ! str_contains($scriptName, '?') ? '?' : '&',
                ),
            ];
        }

        $this->response->addJSON($json);
    }
}
