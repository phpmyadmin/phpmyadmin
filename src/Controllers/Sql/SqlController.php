<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Sql;

use PhpMyAdmin\Bookmarks\BookmarkRepository;
use PhpMyAdmin\Config;
use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Core;
use PhpMyAdmin\Current;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Import\Import;
use PhpMyAdmin\Message;
use PhpMyAdmin\ParseAnalyze;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Sql;
use PhpMyAdmin\Url;
use PhpMyAdmin\UrlParams;

use function __;
use function is_array;
use function is_string;
use function mb_strpos;
use function str_contains;
use function urlencode;

#[Route('/sql', ['GET', 'POST'])]
readonly class SqlController implements InvocableController
{
    public function __construct(
        private ResponseRenderer $response,
        private Sql $sql,
        private PageSettings $pageSettings,
        private BookmarkRepository $bookmarkRepository,
        private Config $config,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
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
         * Defines the url to return to in case of error in a sql statement
         */
        $isGotofile = true;
        if (UrlParams::$goto === '') {
            if (Current::$table === '') {
                UrlParams::$goto = Url::getFromRoute($this->config->config->DefaultTabDatabase);
            } else {
                UrlParams::$goto = Url::getFromRoute($this->config->config->DefaultTabTable);
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

        $bookmarkFields = $this->getBookmarkFields($request->getParsedBodyParam('bkm_fields'));
        $sqlQuery = $request->getParsedBodyParamAsStringOrNull('sql_query');

        // Coming from a bookmark dialog
        if (isset($bookmarkFields['bkm_sql_query'])) {
            Current::$sqlQuery = $bookmarkFields['bkm_sql_query'];
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
        if (isset($bookmarkFields['bkm_database'])) {
            Current::$database = $bookmarkFields['bkm_database'];
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
            $request->isAjax(),
        );

        if (Current::$table !== $tableFromSql && $tableFromSql !== '') {
            Current::$table = $tableFromSql;
        }

        /**
         * Check rights in case of DROP DATABASE
         *
         * This test may be bypassed if $is_js_confirmed = 1 (already checked with js)
         * but since a malicious user may pass this variable by url/form, we don't take
         * into account this case.
         */
        if ($this->sql->hasNoRightsToDropDatabase($statementInfo)) {
            $errorMessage = Generator::mysqlDie(
                __('"DROP DATABASE" statements are disabled.'),
                '',
                false,
            );

            if ($this->response->isAjax()) {
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', $errorMessage);

                return $this->response->response();
            }

            $this->response->addHTML($errorMessage . Generator::getBackUrlHtml($errorUrl));

            return $this->response->response();
        }

        /**
         * Bookmark add
         */
        $storeBkm = $request->hasBodyParam('store_bkm');
        $bkmAllUsers = $request->getParsedBodyParam('bkm_all_users'); // Should this be hasBodyParam?
        if ($storeBkm) {
            return $this->addBookmark(UrlParams::$goto, $bookmarkFields, (bool) $bkmAllUsers);
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

        Current::$messageToShow = $request->getParsedBodyParamAsString('message_to_show', '');
        $this->response->addHTML($this->sql->executeQueryAndSendQueryResponse(
            $request,
            $statementInfo,
            $isGotofile,
            Current::$database,
            Current::$table,
            Import::$importText,
            Current::$messageToShow,
            UrlParams::$goto,
            Current::$dispQuery !== null ? Current::$displayQuery : null,
            Current::$displayMessage ?? '',
            Current::$sqlQuery,
            Current::$completeQuery ?? Current::$sqlQuery,
        ));

        return $this->response->response();
    }

    /** @param array<string> $bookmarkFields */
    private function addBookmark(string $goto, array $bookmarkFields, bool $bkmAllUsers): Response
    {
        $bookmark = $this->bookmarkRepository->createBookmark(
            $bookmarkFields['bkm_sql_query'] ?? '',
            $bookmarkFields['bkm_label'] ?? '',
            $bookmarkFields['bkm_user'] ?? '',
            $bookmarkFields['bkm_database'] ?? '',
            $bkmAllUsers,
        );

        $result = false;
        if ($bookmark !== false) {
            $result = $bookmark->save();
        }

        if (! $this->response->isAjax()) {
            $this->response->redirect('./' . $goto . '&label=' . $bookmarkFields['bkm_label']);

            return $this->response->response();
        }

        if ($result) {
            $msg = Message::success(__('Bookmark %s has been created.'));
            $msg->addParam($bookmarkFields['bkm_label']);
            $this->response->addJSON('message', $msg);

            return $this->response->response();
        }

        $msg = Message::error(__('Bookmark not created!'));
        $this->response->setRequestStatus(false);
        $this->response->addJSON('message', $msg);

        return $this->response->response();
    }

    /** @return array{bkm_label?: string, bkm_database?: string, bkm_sql_query?: string, bkm_user?: string} */
    private function getBookmarkFields(mixed $param): array
    {
        if (! is_array($param)) {
            return [];
        }

        $fields = [];
        foreach (['bkm_label', 'bkm_database', 'bkm_sql_query', 'bkm_user'] as $key) {
            if (! isset($param[$key]) || ! is_string($param[$key])) {
                continue;
            }

            $fields[$key] = $param[$key];
        }

        return $fields;
    }
}
