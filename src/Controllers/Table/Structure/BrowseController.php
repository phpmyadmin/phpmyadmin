<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table\Structure;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ParseAnalyze;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Sql;
use PhpMyAdmin\UrlParams;
use PhpMyAdmin\Util;

use function __;
use function implode;
use function sprintf;

#[Route('/table/structure/browse', ['POST'])]
final class BrowseController implements InvocableController
{
    public function __construct(private readonly ResponseRenderer $response, private readonly Sql $sql)
    {
    }

    public function __invoke(ServerRequest $request): Response
    {
        if (empty($_POST['selected_fld'])) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No column selected.'));

            return $this->response->response();
        }

        $this->displayTableBrowseForSelectedColumns(UrlParams::$goto);

        return $this->response->response();
    }

    /**
     * Function to display table browse for selected columns
     *
     * @param string $goto goto page url
     */
    private function displayTableBrowseForSelectedColumns(string $goto): void
    {
        $fields = [];
        foreach ($_POST['selected_fld'] as $sval) {
            $fields[] = Util::backquote($sval);
        }

        $sqlQuery = sprintf(
            'SELECT %s FROM %s.%s',
            implode(', ', $fields),
            Util::backquote(Current::$database),
            Util::backquote(Current::$table),
        );

        // Parse and analyze the query
        [$statementInfo, Current::$database] = ParseAnalyze::sqlQuery($sqlQuery, Current::$database);

        $this->response->addHTML(
            $this->sql->executeQueryAndGetQueryResponse(
                $statementInfo,
                false, // is_gotofile
                Current::$database, // db
                Current::$table, // table
                '', // sql_query_for_bookmark
                '', // message_to_show
                $goto, // goto
                null, // disp_query
                '', // disp_message
                $sqlQuery, // sql_query
                $sqlQuery, // complete_query
            ),
        );
    }
}
