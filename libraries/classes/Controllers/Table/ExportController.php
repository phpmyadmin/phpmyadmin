<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Export\Options;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Message;
use PhpMyAdmin\Plugins;
use PhpMyAdmin\Response;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\SelectStatement;
use PhpMyAdmin\SqlParser\Utils\Query;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use function array_merge;
use function implode;
use function is_array;

class ExportController extends AbstractController
{
    /** @var Options */
    private $export;

    /**
     * @param Response $response
     * @param string   $db       Database name.
     * @param string   $table    Table name.
     */
    public function __construct(
        $response,
        Template $template,
        $db,
        $table,
        Options $export
    ) {
        parent::__construct($response, $template, $db, $table);
        $this->export = $export;
    }

    public function index(): void
    {
        global $db, $url_params, $table, $replaces, $cfg, $err_url;
        global $sql_query, $where_clause, $num_tables, $unlim_num_rows;

        $pageSettings = new PageSettings('Export');
        $pageSettingsErrorHtml = $pageSettings->getErrorHTML();
        $pageSettingsHtml = $pageSettings->getHTML();

        $this->addScriptFiles(['export.js']);

        Util::checkParameters(['db', 'table']);

        $url_params = ['db' => $db, 'table' => $table];
        $err_url = Util::getScriptNameForOption($cfg['DefaultTabTable'], 'table');
        $err_url .= Url::getCommon($url_params, '&');

        $url_params['goto'] = Url::getFromRoute('/table/export');
        $url_params['back'] = Url::getFromRoute('/table/export');

        $message = '';

        // When we have some query, we need to remove LIMIT from that and possibly
        // generate WHERE clause (if we are asked to export specific rows)

        if (! empty($sql_query)) {
            $parser = new Parser($sql_query);

            if (! empty($parser->statements[0])
                && ($parser->statements[0] instanceof SelectStatement)
            ) {
                // Checking if the WHERE clause has to be replaced.
                if (! empty($where_clause) && is_array($where_clause)) {
                    $replaces[] = [
                        'WHERE',
                        'WHERE (' . implode(') OR (', $where_clause) . ')',
                    ];
                }

                // Preparing to remove the LIMIT clause.
                $replaces[] = [
                    'LIMIT',
                    '',
                ];

                // Replacing the clauses.
                $sql_query = Query::replaceClauses(
                    $parser->statements[0],
                    $parser->list,
                    $replaces
                );
            }

            $message = Generator::getMessage(Message::success());
        }

        if (! isset($sql_query)) {
            $sql_query = '';
        }
        if (! isset($num_tables)) {
            $num_tables = 0;
        }
        if (! isset($unlim_num_rows)) {
            $unlim_num_rows = 0;
        }

        $GLOBALS['single_table'] = $_POST['single_table'] ?? $_GET['single_table'] ?? $GLOBALS['single_table'] ?? null;

        $exportList = Plugins::getExport('table', isset($GLOBALS['single_table']));

        if (empty($exportList)) {
            $this->response->addHTML(Message::error(
                __('Could not load export plugins, please check your installation!')
            )->getDisplay());

            return;
        }

        $exportType = 'table';
        $isReturnBackFromRawExport = isset($_POST['export_type']) && $_POST['export_type'] === 'raw';
        if (isset($_POST['raw_query']) || $isReturnBackFromRawExport) {
            $exportType = 'raw';
        }

        $options = $this->export->getOptions(
            $exportType,
            $db,
            $table,
            $sql_query,
            $num_tables,
            $unlim_num_rows,
            $exportList
        );

        $this->render('table/export/index', array_merge($options, [
            'export_type' => $exportType,
            'page_settings_error_html' => $pageSettingsErrorHtml,
            'page_settings_html' => $pageSettingsHtml,
            'message' => $message,
        ]));
    }

    public function rows(): void
    {
        global $active_page, $single_table, $where_clause;

        if (isset($_POST['goto']) && (! isset($_POST['rows_to_delete']) || ! is_array($_POST['rows_to_delete']))) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No row selected.'));

            return;
        }

        // Needed to allow SQL export
        $single_table = true;

        // As we got the rows to be exported from the
        // 'rows_to_delete' checkbox, we use the index of it as the
        // indicating WHERE clause. Then we build the array which is used
        // for the /table/change script.
        $where_clause = [];
        if (isset($_POST['rows_to_delete']) && is_array($_POST['rows_to_delete'])) {
            foreach ($_POST['rows_to_delete'] as $i => $i_where_clause) {
                $where_clause[] = $i_where_clause;
            }
        }

        $active_page = Url::getFromRoute('/table/export');

        $this->index();
    }
}
