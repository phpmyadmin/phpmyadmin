<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table\Structure;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ParseAnalyze;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Sql;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
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
        $GLOBALS['active_page'] = Url::getFromRoute('/sql');
        $fields = [];
        foreach ($_POST['selected_fld'] as $sval) {
            $fields[] = Util::backquote($sval);
        }

        $sqlQuery = sprintf(
            'SELECT %s FROM %s.%s',
            implode(', ', $fields),
            Util::backquote($GLOBALS['db']),
            Util::backquote($GLOBALS['table']),
        );

        // Parse and analyze the query
        [$statementInfo, $GLOBALS['db']] = ParseAnalyze::sqlQuery($sqlQuery, $GLOBALS['db']);

        $this->response->addHTML(
            $this->sql->executeQueryAndGetQueryResponse(
                $statementInfo,
                false, // is_gotofile
                $GLOBALS['db'], // db
                $GLOBALS['table'], // table
                null, // find_real_end
                null, // sql_query_for_bookmark
                null, // extra_data
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
