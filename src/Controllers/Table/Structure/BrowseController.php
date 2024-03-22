<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table\Structure;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ParseAnalyze;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Sql;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;

use function __;
use function implode;
use function sprintf;

final class BrowseController extends AbstractController
{
    public function __construct(ResponseRenderer $response, Template $template, private Sql $sql)
    {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        if (empty($_POST['selected_fld'])) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No column selected.'));

            return;
        }

        $this->displayTableBrowseForSelectedColumns($GLOBALS['goto']);
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
                null, // sql_query_for_bookmark
                null, // message_to_show
                null, // sql_data
                $goto, // goto
                null, // disp_query
                null, // disp_message
                $sqlQuery, // sql_query
                null, // complete_query
            ),
        );
    }
}
