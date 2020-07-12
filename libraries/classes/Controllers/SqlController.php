<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\CheckUserPrivileges;
use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Message;
use PhpMyAdmin\ParseAnalyze;
use PhpMyAdmin\Response;
use PhpMyAdmin\Sql;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use function mb_strpos;
use function strlen;
use function strpos;
use function urlencode;

class SqlController extends AbstractController
{
    /** @var Sql */
    private $sql;

    /** @var CheckUserPrivileges */
    private $checkUserPrivileges;

    /**
     * @param Response            $response            A Response instance.
     * @param DatabaseInterface   $dbi                 A DatabaseInterface instance.
     * @param Template            $template            A Template instance.
     * @param Sql                 $sql                 An Sql instance.
     * @param CheckUserPrivileges $checkUserPrivileges A CheckUserPrivileges instance.
     */
    public function __construct(
        $response,
        $dbi,
        Template $template,
        Sql $sql,
        CheckUserPrivileges $checkUserPrivileges
    ) {
        parent::__construct($response, $dbi, $template);
        $this->sql = $sql;
        $this->checkUserPrivileges = $checkUserPrivileges;
    }

    public function index(): void
    {
        global $cfg, $db, $display_query, $pmaThemeImage, $sql_query, $table, $message;
        global $ajax_reload, $goto, $err_url, $find_real_end, $unlim_num_rows, $import_text, $disp_query;
        global $extra_data, $message_to_show, $sql_data, $disp_message, $query_type, $selected, $complete_query;
        global $is_gotofile, $back, $table_from_sql;

        $this->checkUserPrivileges->getPrivileges();

        $pageSettings = new PageSettings('Browse');
        $this->response->addHTML($pageSettings->getErrorHTML());
        $this->response->addHTML($pageSettings->getHTML());

        $this->addScriptFiles([
            'vendor/jquery/jquery.uitablefilter.js',
            'table/change.js',
            'indexes.js',
            'gis_data_editor.js',
            'multi_column_sort.js',
        ]);

        /**
         * Set ajax_reload in the response if it was already set
         */
        if (isset($ajax_reload) && $ajax_reload['reload'] === true) {
            $this->response->addJSON('ajax_reload', $ajax_reload);
        }

        /**
         * Defines the url to return to in case of error in a sql statement
         */
        $is_gotofile  = true;
        if (empty($goto)) {
            if (empty($table)) {
                $goto = Util::getScriptNameForOption(
                    $cfg['DefaultTabDatabase'],
                    'database'
                );
            } else {
                $goto = Util::getScriptNameForOption(
                    $cfg['DefaultTabTable'],
                    'table'
                );
            }
        }

        if (! isset($err_url)) {
            $err_url = ! empty($back) ? $back : $goto;
            $err_url .= Url::getCommon(
                ['db' => $GLOBALS['db']],
                strpos($err_url, '?') === false ? '?' : '&'
            );
            if ((mb_strpos(' ' . $err_url, 'db_') !== 1 || mb_strpos($err_url, '?route=/database/') === false)
                && strlen($table) > 0
            ) {
                $err_url .= '&amp;table=' . urlencode($table);
            }
        }

        // Coming from a bookmark dialog
        if (isset($_POST['bkm_fields']['bkm_sql_query'])) {
            $sql_query = $_POST['bkm_fields']['bkm_sql_query'];
        } elseif (isset($_POST['sql_query'])) {
            $sql_query = $_POST['sql_query'];
        } elseif (isset($_GET['sql_query'], $_GET['sql_signature'])) {
            if (Core::checkSqlQuerySignature($_GET['sql_query'], $_GET['sql_signature'])) {
                $sql_query = $_GET['sql_query'];
            }
        }

        // This one is just to fill $db
        if (isset($_POST['bkm_fields']['bkm_database'])) {
            $db = $_POST['bkm_fields']['bkm_database'];
        }

        // Default to browse if no query set and we have table
        // (needed for browsing from DefaultTabTable)
        if (empty($sql_query) && strlen($table) > 0 && strlen($db) > 0) {
            $sql_query = $this->sql->getDefaultSqlQueryForBrowse($db, $table);

            // set $goto to what will be displayed if query returns 0 rows
            $goto = '';
        } else {
            // Now we can check the parameters
            Util::checkParameters(['sql_query']);
        }

        /**
         * Parse and analyze the query
         */
        [
            $analyzed_sql_results,
            $db,
            $table_from_sql,
        ] = ParseAnalyze::sqlQuery($sql_query, $db);

        if ($table != $table_from_sql && ! empty($table_from_sql)) {
            $table = $table_from_sql;
        }

        /**
         * Check rights in case of DROP DATABASE
         *
         * This test may be bypassed if $is_js_confirmed = 1 (already checked with js)
         * but since a malicious user may pass this variable by url/form, we don't take
         * into account this case.
         */
        if ($this->sql->hasNoRightsToDropDatabase(
            $analyzed_sql_results,
            $cfg['AllowUserDropDatabase'],
            $this->dbi->isSuperuser()
        )) {
            Generator::mysqlDie(
                __('"DROP DATABASE" statements are disabled.'),
                '',
                false,
                $err_url
            );
        }

        /**
         * Need to find the real end of rows?
         */
        if (isset($find_real_end) && $find_real_end) {
            $unlim_num_rows = $this->sql->findRealEndOfRows($db, $table);
        }

        /**
         * Bookmark add
         */
        if (isset($_POST['store_bkm'])) {
            $this->sql->addBookmark($goto);

            return;
        }

        /**
         * Sets or modifies the $goto variable if required
         */
        if ($goto === Url::getFromRoute('/sql')) {
            $is_gotofile = false;
            $goto = Url::getFromRoute('/sql', [
                'db' => $db,
                'table' => $table,
                'sql_query' => $sql_query,
            ]);
        }

        $this->response->addHTML($this->sql->executeQueryAndSendQueryResponse(
            $analyzed_sql_results,
            $is_gotofile,
            $db,
            $table,
            $find_real_end ?? null,
            $import_text ?? null,
            $extra_data ?? null,
            $message_to_show ?? null,
            $message ?? null,
            $sql_data ?? null,
            $goto,
            $pmaThemeImage,
            isset($disp_query) ? $display_query : null,
            $disp_message ?? null,
            $query_type ?? null,
            $sql_query,
            $selected ?? null,
            $complete_query ?? null
        ));
    }

    /**
     * Get values for the relational columns
     *
     * During grid edit, if we have a relational field, show the dropdown for it.
     */
    public function getRelationalValues(): void
    {
        global $db, $table;

        $this->checkUserPrivileges->getPrivileges();

        $column = $_POST['column'];
        if ($_SESSION['tmpval']['relational_display'] === 'D'
            && isset($_POST['relation_key_or_display_column'])
            && $_POST['relation_key_or_display_column']
        ) {
            $curr_value = $_POST['relation_key_or_display_column'];
        } else {
            $curr_value = $_POST['curr_value'];
        }
        $dropdown = $this->sql->getHtmlForRelationalColumnDropdown(
            $db,
            $table,
            $column,
            $curr_value
        );
        $this->response->addJSON('dropdown', $dropdown);
    }

    /**
     * Get possible values for enum fields during grid edit.
     */
    public function getEnumValues(): void
    {
        global $db, $table;

        $this->checkUserPrivileges->getPrivileges();

        $column = $_POST['column'];
        $curr_value = $_POST['curr_value'];
        $values = $this->sql->getValuesForColumn($db, $table, $column);
        $dropdown = $this->template->render('sql/enum_column_dropdown', [
            'values' => $values,
            'selected_values' => [$curr_value],
        ]);

        $this->response->addJSON('dropdown', $dropdown);
    }

    /**
     * Get possible values for SET fields during grid edit.
     */
    public function getSetValues(): void
    {
        global $db, $table;

        $this->checkUserPrivileges->getPrivileges();

        $column = $_POST['column'];
        $curr_value = $_POST['curr_value'];
        $select = $this->sql->getHtmlForSetColumn(
            $db,
            $table,
            $column,
            $curr_value
        );

        $this->response->addJSON('select', $select);
    }

    public function getDefaultForeignKeyCheckValue(): void
    {
        $this->checkUserPrivileges->getPrivileges();

        $this->response->addJSON(
            'default_fk_check_value',
            Util::isForeignKeyCheck()
        );
    }

    public function setColumnOrderOrVisibility(): void
    {
        global $db, $table;

        $this->checkUserPrivileges->getPrivileges();

        $tableObject = $this->dbi->getTable($db, $table);
        $status = false;

        // set column order
        if (isset($_POST['col_order'])) {
            $status = $this->sql->setColumnProperty($tableObject, 'col_order');
        }

        // set column visibility
        if ($status === true && isset($_POST['col_visib'])) {
            $status = $this->sql->setColumnProperty($tableObject, 'col_visib');
        }

        if ($status instanceof Message) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', $status->getString());

            return;
        }

        $this->response->setRequestStatus($status === true);
    }
}
