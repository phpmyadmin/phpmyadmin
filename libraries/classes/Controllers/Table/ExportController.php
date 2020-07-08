<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Common;
use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Display\Export;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Message;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Response as ResponseRenderer;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\SelectStatement;
use PhpMyAdmin\SqlParser\Utils\Query;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use function implode;
use function is_array;

class ExportController extends AbstractController
{
    /** @var Export */
    private $export;

    /** @var Relation */
    private $relation;

    /**
     * @param ResponseRenderer  $response A Response instance.
     * @param DatabaseInterface $dbi      A DatabaseInterface instance.
     * @param Template          $template A Template instance.
     * @param string            $db       Database name.
     * @param string            $table    Table name.
     * @param Export            $export   An Export instance.
     * @param Relation          $relation A Relation instance.
     */
    public function __construct(
        $response,
        $dbi,
        Template $template,
        $db,
        $table,
        Export $export,
        Relation $relation
    ) {
        parent::__construct($response, $dbi, $template, $db, $table);
        $this->export = $export;
        $this->relation = $relation;
    }

    public function index(Request $request, Response $response): Response
    {
        global $db, $url_query, $url_params, $table, $export_page_title, $replaces;
        global $sql_query, $where_clause, $num_tables, $unlim_num_rows, $multi_values;

        PageSettings::showGroup('Export');

        $header = $this->response->getHeader();
        $scripts = $header->getScripts();
        $scripts->addFile('export.js');

        $cfgRelation = $this->relation->getRelationsParam();

        // handling export template actions
        if (isset($_POST['templateAction']) && $cfgRelation['exporttemplateswork']) {
            $this->export->handleTemplateActions($cfgRelation);

            return $response;
        }

        /**
         * Gets tables information and displays top links
         */
        Common::table();

        $url_params = [
            'goto' => Url::getFromRoute('/table/export'),
            'back' => Url::getFromRoute('/table/export'),
        ];
        $url_query .= Url::getCommon($url_params, '&');

        $export_page_title = __('View dump (schema) of table');

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

            echo Generator::getMessage(Message::success());
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
        if (! isset($multi_values)) {
            $multi_values = '';
        }

        $this->response->addHTML($this->export->getDisplay(
            'table',
            $db,
            $table,
            $sql_query,
            $num_tables,
            $unlim_num_rows,
            $multi_values
        ));

        return $response;
    }

    public function rows(Request $request, Response $response): Response
    {
        global $active_page, $single_table, $where_clause;

        if (isset($_POST['goto']) && (! isset($_POST['rows_to_delete']) || ! is_array($_POST['rows_to_delete']))) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No row selected.'));

            return $response;
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

        $this->index($request, $response);

        return $response;
    }
}
