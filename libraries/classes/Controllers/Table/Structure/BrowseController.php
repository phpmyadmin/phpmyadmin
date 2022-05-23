<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table\Structure;

use PhpMyAdmin\Controllers\AbstractController;
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
    /** @var Sql */
    private $sql;

    public function __construct(ResponseRenderer $response, Template $template, Sql $sql)
    {
        parent::__construct($response, $template);
        $this->sql = $sql;
    }

    public function __invoke(): void
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
    private function displayTableBrowseForSelectedColumns($goto): void
    {
        $GLOBALS['active_page'] = Url::getFromRoute('/sql');
        $fields = [];
        foreach ($_POST['selected_fld'] as $sval) {
            $fields[] = Util::backquote($sval);
        }

        $sql_query = sprintf(
            'SELECT %s FROM %s.%s',
            implode(', ', $fields),
            Util::backquote($GLOBALS['db']),
            Util::backquote($GLOBALS['table'])
        );

        // Parse and analyze the query
        [$statementInfo, $GLOBALS['db']] = ParseAnalyze::sqlQuery($sql_query, $GLOBALS['db']);

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
                $sql_query, // sql_query
                null // complete_query
            )
        );
    }
}
