<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database\Operations;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
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
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['errorUrl'] ??= null;

        if (! $this->response->isAjax()) {
            return;
        }

        $dbCollation = $request->getParsedBodyParam('db_collation') ?? '';
        if (empty($dbCollation)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', Message::error(__('No collation provided.')));

            return;
        }

        $this->checkParameters(['db']);

        $GLOBALS['errorUrl'] = Util::getScriptNameForOption($GLOBALS['cfg']['DefaultTabDatabase'], 'database');
        $GLOBALS['errorUrl'] .= Url::getCommon(['db' => $GLOBALS['db']], '&');

        if (! $this->hasDatabase()) {
            return;
        }

        $sqlQuery = 'ALTER DATABASE ' . Util::backquote($GLOBALS['db'])
            . ' DEFAULT' . Util::getCharsetQueryPart($dbCollation);
        $this->dbi->query($sqlQuery);
        $message = Message::success();

        /**
         * Changes tables charset if requested by the user
         */
        if ($request->getParsedBodyParam('change_all_tables_collations') === 'on') {
            [$tables] = Util::getDbInfo($request, $GLOBALS['db']);
            foreach ($tables as ['Name' => $tableName]) {
                if ($this->dbi->getTable($GLOBALS['db'], $tableName)->isView()) {
                    // Skip views, we can not change the collation of a view.
                    // issue #15283
                    continue;
                }

                $sqlQuery = 'ALTER TABLE '
                    . Util::backquote($GLOBALS['db'])
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

                $this->operations->changeAllColumnsCollation($GLOBALS['db'], $tableName, $dbCollation);
            }
        }

        $this->response->setRequestStatus($message->isSuccess());
        $this->response->addJSON('message', $message);
    }
}
