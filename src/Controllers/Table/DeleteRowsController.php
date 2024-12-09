<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Bookmarks\BookmarkRepository;
use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationCleanup;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Sql;
use PhpMyAdmin\Template;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\UrlParams;
use PhpMyAdmin\Util;
use PhpMyAdmin\Utils\ForeignKey;

use function __;
use function sprintf;

final class DeleteRowsController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly Template $template,
        private readonly DatabaseInterface $dbi,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $GLOBALS['disp_message'] ??= null;
        $GLOBALS['disp_query'] ??= null;

        $multBtn = $_POST['mult_btn'] ?? '';
        $selected = $_POST['selected'] ?? [];

        $relation = new Relation($this->dbi);
        $sql = new Sql(
            $this->dbi,
            $relation,
            new RelationCleanup($this->dbi, $relation),
            new Transformations(),
            $this->template,
            new BookmarkRepository($this->dbi, $relation),
            Config::getInstance(),
        );

        if ($multBtn === __('Yes')) {
            $defaultFkCheckValue = ForeignKey::handleDisableCheckInit();
            Current::$sqlQuery = '';

            $this->dbi->selectDb(Current::$database);

            foreach ($selected as $row) {
                $query = sprintf(
                    'DELETE FROM %s WHERE %s LIMIT 1;',
                    Util::backquote(Current::$table),
                    $row,
                );
                Current::$sqlQuery .= $query . "\n";
                $this->dbi->query($query);
            }

            if (! empty($_REQUEST['pos'])) {
                $_REQUEST['pos'] = $sql->calculatePosForLastPage(Current::$database, Current::$table, $_REQUEST['pos']);
            }

            ForeignKey::handleDisableCheckCleanup($defaultFkCheckValue);

            $GLOBALS['disp_message'] = __('Your SQL query has been executed successfully.');
            $GLOBALS['disp_query'] = Current::$sqlQuery;
        }

        if ($request->hasBodyParam('original_sql_query')) {
            Current::$sqlQuery = $request->getParsedBodyParamAsString('original_sql_query', '');
        }

        $this->response->addHTML($sql->executeQueryAndSendQueryResponse(
            null,
            false,
            Current::$database,
            Current::$table,
            null,
            null,
            null,
            UrlParams::$goto,
            $GLOBALS['disp_query'] ?? null,
            $GLOBALS['disp_message'] ?? null,
            Current::$sqlQuery,
            null,
        ));

        return $this->response->response();
    }
}
