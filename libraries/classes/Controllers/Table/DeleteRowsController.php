<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationCleanup;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Operations;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Sql;
use PhpMyAdmin\Template;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use PhpMyAdmin\Utils\ForeignKey;

use function __;
use function sprintf;

final class DeleteRowsController extends AbstractController
{
    /** @var DatabaseInterface */
    private $dbi;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        DatabaseInterface $dbi
    ) {
        parent::__construct($response, $template);
        $this->dbi = $dbi;
    }

    public function __invoke(): void
    {
        $GLOBALS['goto'] = $GLOBALS['goto'] ?? null;
        $GLOBALS['disp_message'] = $GLOBALS['disp_message'] ?? null;
        $GLOBALS['disp_query'] = $GLOBALS['disp_query'] ?? null;
        $GLOBALS['active_page'] = $GLOBALS['active_page'] ?? null;

        $mult_btn = $_POST['mult_btn'] ?? '';
        $original_sql_query = $_POST['original_sql_query'] ?? '';
        $selected = $_POST['selected'] ?? [];

        $relation = new Relation($this->dbi);
        $sql = new Sql(
            $this->dbi,
            $relation,
            new RelationCleanup($this->dbi, $relation),
            new Operations($this->dbi, $relation),
            new Transformations(),
            $this->template
        );

        if ($mult_btn === __('Yes')) {
            $default_fk_check_value = ForeignKey::handleDisableCheckInit();
            $GLOBALS['sql_query'] = '';

            foreach ($selected as $row) {
                $query = sprintf(
                    'DELETE FROM %s WHERE %s LIMIT 1;',
                    Util::backquote($GLOBALS['table']),
                    $row
                );
                $GLOBALS['sql_query'] .= $query . "\n";
                $this->dbi->selectDb($GLOBALS['db']);
                $this->dbi->query($query);
            }

            if (! empty($_REQUEST['pos'])) {
                $_REQUEST['pos'] = $sql->calculatePosForLastPage($GLOBALS['db'], $GLOBALS['table'], $_REQUEST['pos']);
            }

            ForeignKey::handleDisableCheckCleanup($default_fk_check_value);

            $GLOBALS['disp_message'] = __('Your SQL query has been executed successfully.');
            $GLOBALS['disp_query'] = $GLOBALS['sql_query'];
        }

        $_url_params = $GLOBALS['urlParams'];
        $_url_params['goto'] = Url::getFromRoute('/table/sql');

        if (isset($original_sql_query)) {
            $GLOBALS['sql_query'] = $original_sql_query;
        }

        $GLOBALS['active_page'] = Url::getFromRoute('/sql');

        $this->response->addHTML($sql->executeQueryAndSendQueryResponse(
            null,
            false,
            $GLOBALS['db'],
            $GLOBALS['table'],
            null,
            null,
            null,
            null,
            null,
            $GLOBALS['goto'],
            $GLOBALS['disp_query'] ?? null,
            $GLOBALS['disp_message'] ?? null,
            $GLOBALS['sql_query'],
            null
        ));
    }
}
