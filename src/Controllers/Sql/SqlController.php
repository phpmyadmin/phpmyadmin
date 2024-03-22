<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Sql;

use PhpMyAdmin\Bookmarks\BookmarkRepository;
use PhpMyAdmin\Config;
use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Core;
use PhpMyAdmin\Current;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ParseAnalyze;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Sql;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
use function mb_strpos;
use function str_contains;
use function urlencode;

class SqlController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private Sql $sql,
        private DatabaseInterface $dbi,
        private PageSettings $pageSettings,
        private readonly BookmarkRepository $bookmarkRepository,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['display_query'] ??= null;
        $GLOBALS['ajax_reload'] ??= null;
        $GLOBALS['goto'] ??= null;
        $GLOBALS['errorUrl'] ??= null;
        $GLOBALS['unlim_num_rows'] ??= null;
        $GLOBALS['import_text'] ??= null;
        $GLOBALS['disp_query'] ??= null;
        $GLOBALS['message_to_show'] ??= null;
        $GLOBALS['disp_message'] ??= null;
        $GLOBALS['complete_query'] ??= null;
        $GLOBALS['back'] ??= null;

        $this->pageSettings->init('Browse');
        $this->response->addHTML($this->pageSettings->getErrorHTML());
        $this->response->addHTML($this->pageSettings->getHTML());

        $this->addScriptFiles([
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
        if (empty($GLOBALS['goto'])) {
            if (Current::$table === '') {
                $GLOBALS['goto'] = Util::getScriptNameForOption($config->settings['DefaultTabDatabase'], 'database');
            } else {
                $GLOBALS['goto'] = Util::getScriptNameForOption($config->settings['DefaultTabTable'], 'table');
            }
        }

        if (! isset($GLOBALS['errorUrl'])) {
            $GLOBALS['errorUrl'] = ! empty($GLOBALS['back']) ? $GLOBALS['back'] : $GLOBALS['goto'];
            $GLOBALS['errorUrl'] .= Url::getCommon(
                ['db' => Current::$database],
                ! str_contains($GLOBALS['errorUrl'], '?') ? '?' : '&',
            );
            if (
                (mb_strpos(' ' . $GLOBALS['errorUrl'], 'db_') !== 1
                    || ! str_contains($GLOBALS['errorUrl'], '?route=/database/'))
                && Current::$table !== ''
            ) {
                $GLOBALS['errorUrl'] .= '&amp;table=' . urlencode(Current::$table);
            }
        }

        /** @var array<string>|null $bkmFields */
        $bkmFields = $request->getParsedBodyParam('bkm_fields');
        $sqlQuery = $request->getParsedBodyParam('sql_query');

        // Coming from a bookmark dialog
        if ($bkmFields !== null && $bkmFields['bkm_sql_query'] != null) {
            $GLOBALS['sql_query'] = $bkmFields['bkm_sql_query'];
        } elseif ($sqlQuery !== null) {
            $GLOBALS['sql_query'] = $sqlQuery;
        } elseif ($request->hasQueryParam('sql_query') && $request->hasQueryParam('sql_signature')) {
            $sqlQuery = $request->getQueryParam('sql_query');
            if (Core::checkSqlQuerySignature($sqlQuery, $request->getQueryParam('sql_signature'))) {
                $GLOBALS['sql_query'] = $sqlQuery;
            }
        }

        // This one is just to fill $db
        if ($bkmFields !== null && $bkmFields['bkm_database'] != null) {
            Current::$database = $bkmFields['bkm_database'];
        }

        // Default to browse if no query set and we have table
        // (needed for browsing from DefaultTabTable)
        if (empty($GLOBALS['sql_query']) && Current::$table !== '' && Current::$database !== '') {
            $GLOBALS['sql_query'] = $this->sql->getDefaultSqlQueryForBrowse(Current::$database, Current::$table);

            // set $goto to what will be displayed if query returns 0 rows
            $GLOBALS['goto'] = '';
        } elseif (! $this->checkParameters(['sql_query'])) {
            return;
        }

        /**
         * Parse and analyze the query
         */
        [$statementInfo, Current::$database, $tableFromSql] = ParseAnalyze::sqlQuery(
            $GLOBALS['sql_query'],
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
                $GLOBALS['errorUrl'],
            );
        }

        /**
         * Bookmark add
         */
        $storeBkm = $request->hasBodyParam('store_bkm');
        $bkmAllUsers = $request->getParsedBodyParam('bkm_all_users'); // Should this be hasBodyParam?
        if ($storeBkm && $bkmFields !== null) {
            $this->addBookmark($GLOBALS['goto'], $bkmFields, (bool) $bkmAllUsers);

            return;
        }

        /**
         * Sets or modifies the $goto variable if required
         */
        if ($GLOBALS['goto'] === Url::getFromRoute('/sql')) {
            $isGotofile = false;
            $GLOBALS['goto'] = Url::getFromRoute('/sql', [
                'db' => Current::$database,
                'table' => Current::$table,
                'sql_query' => $GLOBALS['sql_query'],
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
            $GLOBALS['goto'],
            isset($GLOBALS['disp_query']) ? $GLOBALS['display_query'] : null,
            $GLOBALS['disp_message'] ?? null,
            $GLOBALS['sql_query'],
            $GLOBALS['complete_query'] ?? null,
        ));
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
