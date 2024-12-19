<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Sql;

use PhpMyAdmin\Bookmarks\BookmarkRepository;
use PhpMyAdmin\Config;
use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Core;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ParseAnalyze;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Sql;
use PhpMyAdmin\Url;
use PhpMyAdmin\UrlParams;
use PhpMyAdmin\Util;

use function __;
use function is_string;
use function mb_strpos;
use function str_contains;
use function urlencode;

class SqlController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly Sql $sql,
        private readonly DatabaseInterface $dbi,
        private readonly PageSettings $pageSettings,
        private readonly BookmarkRepository $bookmarkRepository,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $GLOBALS['display_query'] ??= null;
        $GLOBALS['ajax_reload'] ??= null;
        $GLOBALS['unlim_num_rows'] ??= null;
        $GLOBALS['import_text'] ??= null;
        $GLOBALS['disp_query'] ??= null;
        $GLOBALS['message_to_show'] ??= null;
        $GLOBALS['disp_message'] ??= null;
        $GLOBALS['complete_query'] ??= null;

        $this->pageSettings->init('Browse');
        $this->response->addHTML($this->pageSettings->getErrorHTML());
        $this->response->addHTML($this->pageSettings->getHTML());

        $this->response->addScriptFiles([
            'vendor/jquery/jquery.uitablefilter.js',
            'table/change.js',
            'gis_data_editor.js',
            'multi_column_sort.js',
        ]);

        /**
         * Set ajax_reload in the response if it was already set
         */
        if (isset($GLOBALS['ajax_reload']) && $GLOBALS['ajax_reload']['reload'] === true) {
            $this->response->addJSON('ajax_reload', $GLOBALS['ajax_reload']);
        }

        /**
         * Defines the url to return to in case of error in a sql statement
         */
        $isGotofile = true;
        $config = Config::getInstance();
        if (UrlParams::$goto === '') {
            if (Current::$table === '') {
                UrlParams::$goto = Util::getScriptNameForOption($config->settings['DefaultTabDatabase'], 'database');
            } else {
                UrlParams::$goto = Util::getScriptNameForOption($config->settings['DefaultTabTable'], 'table');
            }
        }

        $errorUrl = UrlParams::$back !== '' ? UrlParams::$back : UrlParams::$goto;
        $errorUrl .= Url::getCommon(
            ['db' => Current::$database],
            ! str_contains($errorUrl, '?') ? '?' : '&',
        );
        if (
            (mb_strpos(' ' . $errorUrl, 'db_') !== 1
                || ! str_contains($errorUrl, '?route=/database/'))
            && Current::$table !== ''
        ) {
            $errorUrl .= '&amp;table=' . urlencode(Current::$table);
        }

        /** @var array<string>|null $bkmFields */
        $bkmFields = $request->getParsedBodyParam('bkm_fields');
        $sqlQuery = $request->getParsedBodyParamAsStringOrNull('sql_query');

        // Coming from a bookmark dialog
        if ($bkmFields !== null && $bkmFields['bkm_sql_query'] != null) {
            Current::$sqlQuery = $bkmFields['bkm_sql_query'];
        } elseif ($sqlQuery !== null) {
            Current::$sqlQuery = $sqlQuery;
        } elseif ($request->hasQueryParam('sql_query') && $request->hasQueryParam('sql_signature')) {
            $sqlQuery = $request->getQueryParam('sql_query');
            if (
                is_string($sqlQuery)
                && Core::checkSqlQuerySignature($sqlQuery, $request->getQueryParam('sql_signature'))
            ) {
                Current::$sqlQuery = $sqlQuery;
            }
        }

        // This one is just to fill $db
        if ($bkmFields !== null && $bkmFields['bkm_database'] != null) {
            Current::$database = $bkmFields['bkm_database'];
        }

        // Default to browse if no query set and we have table
        // (needed for browsing from DefaultTabTable)
        if (Current::$sqlQuery === '' && Current::$table !== '' && Current::$database !== '') {
            Current::$sqlQuery = $this->sql->getDefaultSqlQueryForBrowse(Current::$database, Current::$table);

            // set $goto to what will be displayed if query returns 0 rows
            UrlParams::$goto = '';
        } elseif (Current::$sqlQuery === '') {
            return $this->response->missingParameterError('sql_query');
        }

        /**
         * Parse and analyze the query
         */
        [$statementInfo, Current::$database, $tableFromSql] = ParseAnalyze::sqlQuery(
            Current::$sqlQuery,
            Current::$database,
        );

        if (Current::$table != $tableFromSql && $tableFromSql !== '') {
            Current::$table = $tableFromSql;
        }

        /**
         * Check rights in case of DROP DATABASE
         *
         * This test may be bypassed if $is_js_confirmed = 1 (already checked with js)
         * but since a malicious user may pass this variable by url/form, we don't take
         * into account this case.
         */
        if (
            $this->sql->hasNoRightsToDropDatabase(
                $statementInfo,
                $config->settings['AllowUserDropDatabase'],
                $this->dbi->isSuperUser(),
            )
        ) {
            Generator::mysqlDie(
                __('"DROP DATABASE" statements are disabled.'),
                '',
                false,
                $errorUrl,
            );
        }

        /**
         * Bookmark add
         */
        $storeBkm = $request->hasBodyParam('store_bkm');
        $bkmAllUsers = $request->getParsedBodyParam('bkm_all_users'); // Should this be hasBodyParam?
        if ($storeBkm && $bkmFields !== null) {
            $this->addBookmark(UrlParams::$goto, $bkmFields, (bool) $bkmAllUsers);

            return $this->response->response();
        }

        /**
         * Sets or modifies the $goto variable if required
         */
        if (UrlParams::$goto === Url::getFromRoute('/sql')) {
            $isGotofile = false;
            UrlParams::$goto = Url::getFromRoute('/sql', [
                'db' => Current::$database,
                'table' => Current::$table,
                'sql_query' => Current::$sqlQuery,
            ]);
        }

        $this->response->addHTML($this->sql->executeQueryAndSendQueryResponse(
            $statementInfo,
            $isGotofile,
            Current::$database,
            Current::$table,
            $GLOBALS['import_text'] ?? null,
            $GLOBALS['message_to_show'] ?? null,
            null,
            UrlParams::$goto,
            isset($GLOBALS['disp_query']) ? $GLOBALS['display_query'] : null,
            $GLOBALS['disp_message'] ?? null,
            Current::$sqlQuery,
            $GLOBALS['complete_query'] ?? null,
        ));

        return $this->response->response();
    }

    /** @param array<string> $bkmFields */
    private function addBookmark(string $goto, array $bkmFields, bool $bkmAllUsers): void
    {
        $bookmark = $this->bookmarkRepository->createBookmark(
            $bkmFields['bkm_sql_query'],
            $bkmFields['bkm_label'],
            $bkmFields['bkm_user'],
            $bkmFields['bkm_database'],
            $bkmAllUsers,
        );

        $result = null;
        if ($bookmark !== false) {
            $result = $bookmark->save();
        }

        if (! $this->response->isAjax()) {
            $this->response->redirect('./' . $goto . '&label=' . $bkmFields['bkm_label']);

            return;
        }

        if ($result) {
            $msg = Message::success(__('Bookmark %s has been created.'));
            $msg->addParam($bkmFields['bkm_label']);
            $this->response->addJSON('message', $msg);

            return;
        }

        $msg = Message::error(__('Bookmark not created!'));
        $this->response->setRequestStatus(false);
        $this->response->addJSON('message', $msg);
    }
}
