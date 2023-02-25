<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\Controllers\Database\SqlController;

use function __;
use function defined;

final class DbTableExists
{
    /**
     * Ensure the database and the table exist (else move to the "parent" script) and display headers.
     */
    public static function check(string $db, string $table, bool $isTransformationWrapper = false): void
    {
        self::checkDatabase($db, $isTransformationWrapper);
        self::checkTable($db, $table, $isTransformationWrapper);
    }

    private static function checkDatabase(string $db, bool $isTransformationWrapper): void
    {
        $GLOBALS['message'] ??= null;
        $GLOBALS['show_as_php'] ??= null;

        if (! empty($GLOBALS['is_db'])) {
            return;
        }

        $GLOBALS['is_db'] = false;
        if ($db !== '') {
            $GLOBALS['is_db'] = @$GLOBALS['dbi']->selectDb($db);
        }

        if ($GLOBALS['is_db'] || $isTransformationWrapper) {
            return;
        }

        $response = ResponseRenderer::getInstance();
        if ($response->isAjax()) {
            $response->setRequestStatus(false);
            $response->addJSON(
                'message',
                Message::error(__('No databases selected.')),
            );

            exit;
        }

        $urlParams = ['reload' => 1];

        if (isset($GLOBALS['message'])) {
            $urlParams['message'] = $GLOBALS['message'];
        }

        if (! empty($GLOBALS['sql_query'])) {
            $urlParams['sql_query'] = $GLOBALS['sql_query'];
        }

        if (isset($GLOBALS['show_as_php'])) {
            $urlParams['show_as_php'] = $GLOBALS['show_as_php'];
        }

        Core::sendHeaderLocation('./index.php?route=/' . Url::getCommonRaw($urlParams, '&'));

        exit;
    }

    private static function checkTable(string $db, string $table, bool $isTransformationWrapper): void
    {
        if (! empty($GLOBALS['is_table']) || defined('PMA_SUBMIT_MULT') || defined('TABLE_MAY_BE_ABSENT')) {
            return;
        }

        $GLOBALS['is_table'] = false;
        if ($table !== '') {
            $GLOBALS['is_table'] = $GLOBALS['dbi']->getCache()->getCachedTableContent([$db, $table], false);
            if ($GLOBALS['is_table']) {
                return;
            }

            $result = $GLOBALS['dbi']->tryQuery('SHOW TABLES LIKE \'' . $GLOBALS['dbi']->escapeString($table) . '\';');
            $GLOBALS['is_table'] = $result && $result->numRows();
        }

        if ($GLOBALS['is_table']) {
            return;
        }

        if ($isTransformationWrapper) {
            exit;
        }

        if ($table !== '') {
            /**
             * SHOW TABLES doesn't show temporary tables, so try select
             * (as it can happen just in case temporary table, it should be fast):
             */
            $result = $GLOBALS['dbi']->tryQuery('SELECT COUNT(*) FROM ' . Util::backquote($table) . ';');
            $GLOBALS['is_table'] = $result && $result->numRows();
        }

        if ($GLOBALS['is_table']) {
            return;
        }

        /** @var SqlController $controller */
        $controller = Core::getContainerBuilder()->get(SqlController::class);
        $controller(Common::getRequest());

        exit;
    }
}
