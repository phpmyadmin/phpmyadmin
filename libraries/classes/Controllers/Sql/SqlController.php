<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Sql;

use PhpMyAdmin\Bookmark;
use PhpMyAdmin\CheckUserPrivileges;
use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Core;
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
use function strlen;
use function urlencode;

class SqlController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private Sql $sql,
        private CheckUserPrivileges $checkUserPrivileges,
        private DatabaseInterface $dbi,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['display_query'] ??= null;
        $GLOBALS['ajax_reload'] ??= null;
        $GLOBALS['goto'] ??= null;
        $GLOBALS['errorUrl'] ??= null;
        $GLOBALS['find_real_end'] ??= null;
        $GLOBALS['unlim_num_rows'] ??= null;
        $GLOBALS['import_text'] ??= null;
        $GLOBALS['disp_query'] ??= null;
        $GLOBALS['extra_data'] ??= null;
        $GLOBALS['message_to_show'] ??= null;
        $GLOBALS['disp_message'] ??= null;
        $GLOBALS['complete_query'] ??= null;
        $GLOBALS['is_gotofile'] ??= null;
        $GLOBALS['back'] ??= null;
        $GLOBALS['table_from_sql'] ??= null;

        $this->checkUserPrivileges->getPrivileges();

        $pageSettings = new PageSettings('Browse');
        $this->response->addHTML($pageSettings->getErrorHTML());
        $this->response->addHTML($pageSettings->getHTML());

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
        $GLOBALS['is_gotofile'] = true;
        if (empty($GLOBALS['goto'])) {
            if (empty($GLOBALS['table'])) {
                $GLOBALS['goto'] = Util::getScriptNameForOption($GLOBALS['cfg']['DefaultTabDatabase'], 'database');
            } else {
                $GLOBALS['goto'] = Util::getScriptNameForOption($GLOBALS['cfg']['DefaultTabTable'], 'table');
            }
        }

        if (! isset($GLOBALS['errorUrl'])) {
            $GLOBALS['errorUrl'] = ! empty($GLOBALS['back']) ? $GLOBALS['back'] : $GLOBALS['goto'];
            $GLOBALS['errorUrl'] .= Url::getCommon(
                ['db' => $GLOBALS['db']],
                ! str_contains($GLOBALS['errorUrl'], '?') ? '?' : '&',
            );
            if (
                (mb_strpos(' ' . $GLOBALS['errorUrl'], 'db_') !== 1
                    || ! str_contains($GLOBALS['errorUrl'], '?route=/database/'))
                && strlen($GLOBALS['table']) > 0
            ) {
                $GLOBALS['errorUrl'] .= '&amp;table=' . urlencode($GLOBALS['table']);
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
            $GLOBALS['db'] = $bkmFields['bkm_database'];
        }

        // Default to browse if no query set and we have table
        // (needed for browsing from DefaultTabTable)
        if (empty($GLOBALS['sql_query']) && strlen($GLOBALS['table']) > 0 && strlen($GLOBALS['db']) > 0) {
            $GLOBALS['sql_query'] = $this->sql->getDefaultSqlQueryForBrowse($GLOBALS['db'], $GLOBALS['table']);

            // set $goto to what will be displayed if query returns 0 rows
            $GLOBALS['goto'] = '';
        } else {
            $this->checkParameters(['sql_query']);
        }

        /**
         * Parse and analyze the query
         */
        [$statementInfo, $GLOBALS['db'], $GLOBALS['table_from_sql']] = ParseAnalyze::sqlQuery(
            $GLOBALS['sql_query'],
            $GLOBALS['db'],
        );

        if ($GLOBALS['table'] != $GLOBALS['table_from_sql'] && ! empty($GLOBALS['table_from_sql'])) {
            $GLOBALS['table'] = $GLOBALS['table_from_sql'];
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
                $GLOBALS['cfg']['AllowUserDropDatabase'],
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
         * Need to find the real end of rows?
         */
        if (isset($GLOBALS['find_real_end']) && $GLOBALS['find_real_end']) {
            $GLOBALS['unlim_num_rows'] = $this->sql->findRealEndOfRows($GLOBALS['db'], $GLOBALS['table']);
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
            $GLOBALS['is_gotofile'] = false;
            $GLOBALS['goto'] = Url::getFromRoute('/sql', [
                'db' => $GLOBALS['db'],
                'table' => $GLOBALS['table'],
                'sql_query' => $GLOBALS['sql_query'],
            ]);
        }

        $this->response->addHTML($this->sql->executeQueryAndSendQueryResponse(
            $statementInfo,
            $GLOBALS['is_gotofile'],
            $GLOBALS['db'],
            $GLOBALS['table'],
            $GLOBALS['find_real_end'] ?? null,
            $GLOBALS['import_text'] ?? null,
            $GLOBALS['extra_data'] ?? null,
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
        $bookmark = Bookmark::createBookmark($this->dbi, $bkmFields, $bkmAllUsers);

        $result = null;
        if ($bookmark instanceof Bookmark) {
            $result = $bookmark->save();
        }

        if (! $this->response->isAjax()) {
            Core::sendHeaderLocation('./' . $goto . '&label=' . $bkmFields['bkm_label']);

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
