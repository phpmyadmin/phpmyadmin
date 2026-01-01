<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database\MultiTableQuery;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\ParseAnalyze;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Sql;
use PhpMyAdmin\Url;

#[Route('/database/multi-table-query/query', ['POST'])]
final class QueryController implements InvocableController
{
    public function __construct(private readonly ResponseRenderer $response, private readonly Sql $sql)
    {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $sqlQuery = $request->getParsedBodyParamAsString('sql_query');
        $db = $request->getParam('db');
        $database = DatabaseName::from($db);

        [, $db] = ParseAnalyze::sqlQuery($sqlQuery, $database->getName());

        $goto = Url::getFromRoute('/database/multi-table-query');

        $this->response->addHTML($this->sql->executeQueryAndSendQueryResponse(
            null,
            false, // is_gotofile
            $db, // db
            null, // table
            '', // sql_query_for_bookmark - see below
            '', // message_to_show
            $goto, // goto
            null, // disp_query
            '', // disp_message
            $sqlQuery, // sql_query
            $sqlQuery, // complete_query
        ));

        return $this->response->response();
    }
}
