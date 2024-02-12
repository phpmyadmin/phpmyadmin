<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Bookmarks\BookmarkRepository;
use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationCleanup;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Current;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Operations;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Sql;
use PhpMyAdmin\Template;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Util;
use PhpMyAdmin\Utils\ForeignKey;

use function __;
use function sprintf;

final class DeleteRowsController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private DatabaseInterface $dbi,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['goto'] ??= null;
        $GLOBALS['disp_message'] ??= null;
        $GLOBALS['disp_query'] ??= null;

        $multBtn = $_POST['mult_btn'] ?? '';
        $selected = $_POST['selected'] ?? [];

        $relation = new Relation($this->dbi);
        $sql = new Sql(
            $this->dbi,
            $relation,
            new RelationCleanup($this->dbi, $relation),
            new Operations($this->dbi, $relation),
            new Transformations(),
            $this->template,
            new BookmarkRepository($this->dbi, $relation),
            Config::getInstance(),
        );

        if ($multBtn === __('Yes')) {
            $defaultFkCheckValue = ForeignKey::handleDisableCheckInit();
            $GLOBALS['sql_query'] = '';

            $this->dbi->selectDb(Current::$database);

            foreach ($selected as $row) {
                $query = sprintf(
                    'DELETE FROM %s WHERE %s LIMIT 1;',
                    Util::backquote(Current::$table),
                    $row,
                );
                $GLOBALS['sql_query'] .= $query . "\n";
                $this->dbi->query($query);
            }

            if (! empty($_REQUEST['pos'])) {
                $_REQUEST['pos'] = $sql->calculatePosForLastPage(Current::$database, Current::$table, $_REQUEST['pos']);
            }

            ForeignKey::handleDisableCheckCleanup($defaultFkCheckValue);

            $GLOBALS['disp_message'] = __('Your SQL query has been executed successfully.');
            $GLOBALS['disp_query'] = $GLOBALS['sql_query'];
        }

        if ($request->hasBodyParam('original_sql_query')) {
            $GLOBALS['sql_query'] = $request->getParsedBodyParam('original_sql_query', '');
        }

        $this->response->addHTML($sql->executeQueryAndSendQueryResponse(
            null,
            false,
            Current::$database,
            Current::$table,
            null,
            null,
            null,
            $GLOBALS['goto'] ?? '',
            $GLOBALS['disp_query'] ?? null,
            $GLOBALS['disp_message'] ?? null,
            $GLOBALS['sql_query'],
            null,
        ));
    }
}
