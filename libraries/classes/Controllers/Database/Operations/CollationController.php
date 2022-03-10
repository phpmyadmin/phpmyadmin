<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database\Operations;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Message;
use PhpMyAdmin\Operations;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;

final class CollationController extends AbstractController
{
    /** @var Operations */
    private $operations;

    /** @var DatabaseInterface */
    private $dbi;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        Operations $operations,
        DatabaseInterface $dbi
    ) {
        parent::__construct($response, $template);
        $this->operations = $operations;
        $this->dbi = $dbi;
    }

    public function __invoke(): void
    {
        $GLOBALS['errorUrl'] = $GLOBALS['errorUrl'] ?? null;

        if (! $this->response->isAjax()) {
            return;
        }

        if (empty($_POST['db_collation'])) {
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

        $sql_query = 'ALTER DATABASE ' . Util::backquote($GLOBALS['db'])
            . ' DEFAULT' . Util::getCharsetQueryPart($_POST['db_collation'] ?? '');
        $this->dbi->query($sql_query);
        $message = Message::success();

        /**
         * Changes tables charset if requested by the user
         */
        if (isset($_POST['change_all_tables_collations']) && $_POST['change_all_tables_collations'] === 'on') {
            [$tables] = Util::getDbInfo($GLOBALS['db'], '');
            foreach ($tables as ['Name' => $tableName]) {
                if ($this->dbi->getTable($GLOBALS['db'], $tableName)->isView()) {
                    // Skip views, we can not change the collation of a view.
                    // issue #15283
                    continue;
                }

                $sql_query = 'ALTER TABLE '
                    . Util::backquote($GLOBALS['db'])
                    . '.'
                    . Util::backquote($tableName)
                    . ' DEFAULT '
                    . Util::getCharsetQueryPart($_POST['db_collation'] ?? '');
                $this->dbi->query($sql_query);

                /**
                 * Changes columns charset if requested by the user
                 */
                if (
                    ! isset($_POST['change_all_tables_columns_collations']) ||
                    $_POST['change_all_tables_columns_collations'] !== 'on'
                ) {
                    continue;
                }

                $this->operations->changeAllColumnsCollation($GLOBALS['db'], $tableName, $_POST['db_collation']);
            }
        }

        $this->response->setRequestStatus($message->isSuccess());
        $this->response->addJSON('message', $message);
    }
}
