<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Operations\Database;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Current;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Message;
use PhpMyAdmin\Operations;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;

final class CollationController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private Operations $operations,
        private DatabaseInterface $dbi,
        private readonly DbTableExists $dbTableExists,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['errorUrl'] ??= null;

        if (! $request->isAjax()) {
            return;
        }

        $dbCollation = $request->getParsedBodyParam('db_collation') ?? '';
        if (empty($dbCollation)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', Message::error(__('No collation provided.')));

            return;
        }

        if (! $this->checkParameters(['db'])) {
            return;
        }

        $GLOBALS['errorUrl'] = Util::getScriptNameForOption(
            Config::getInstance()->settings['DefaultTabDatabase'],
            'database',
        );
        $GLOBALS['errorUrl'] .= Url::getCommon(['db' => Current::$database], '&');

        $databaseName = DatabaseName::tryFrom($request->getParam('db'));
        if ($databaseName === null || ! $this->dbTableExists->selectDatabase($databaseName)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', Message::error(__('No databases selected.')));

            return;
        }

        $sqlQuery = 'ALTER DATABASE ' . Util::backquote(Current::$database)
            . ' DEFAULT' . Util::getCharsetQueryPart($dbCollation);
        $this->dbi->query($sqlQuery);
        $message = Message::success();

        /**
         * Changes tables charset if requested by the user
         */
        if ($request->getParsedBodyParam('change_all_tables_collations') === 'on') {
            foreach ($this->dbi->getTables(Current::$database) as $tableName) {
                if ($this->dbi->getTable(Current::$database, $tableName)->isView()) {
                    // Skip views, we can not change the collation of a view.
                    // issue #15283
                    continue;
                }

                $sqlQuery = 'ALTER TABLE '
                    . Util::backquote(Current::$database)
                    . '.'
                    . Util::backquote($tableName)
                    . ' DEFAULT '
                    . Util::getCharsetQueryPart($dbCollation);
                $this->dbi->query($sqlQuery);

                /**
                 * Changes columns charset if requested by the user
                 */
                if ($request->getParsedBodyParam('change_all_tables_columns_collations') !== 'on') {
                    continue;
                }

                $this->operations->changeAllColumnsCollation(Current::$database, $tableName, $dbCollation);
            }
        }

        $this->response->setRequestStatus($message->isSuccess());
        $this->response->addJSON('message', $message);
    }
}
