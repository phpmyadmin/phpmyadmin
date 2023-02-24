<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\CheckUserPrivileges;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Database\Routines;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function in_array;
use function strlen;

/**
 * Routines management.
 */
class RoutinesController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private CheckUserPrivileges $checkUserPrivileges,
        private DatabaseInterface $dbi,
        private Routines $routines,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['errors'] ??= null;
        $GLOBALS['errorUrl'] ??= null;
        $GLOBALS['urlParams'] ??= null;

        $this->addScriptFiles(['database/routines.js']);

        $type = $_REQUEST['type'] ?? null;

        $this->checkUserPrivileges->getPrivileges();

        if (! $this->response->isAjax()) {
            /**
             * Displays the header and tabs
             */
            if (! empty($GLOBALS['table']) && in_array($GLOBALS['table'], $this->dbi->getTables($GLOBALS['db']))) {
                $this->checkParameters(['db', 'table']);

                $GLOBALS['urlParams'] = ['db' => $GLOBALS['db'], 'table' => $GLOBALS['table']];
                $GLOBALS['errorUrl'] = Util::getScriptNameForOption($GLOBALS['cfg']['DefaultTabTable'], 'table');
                $GLOBALS['errorUrl'] .= Url::getCommon($GLOBALS['urlParams'], '&');

                DbTableExists::check($GLOBALS['db'], $GLOBALS['table']);
            } else {
                $GLOBALS['table'] = '';

                $this->checkParameters(['db']);

                $GLOBALS['errorUrl'] = Util::getScriptNameForOption($GLOBALS['cfg']['DefaultTabDatabase'], 'database');
                $GLOBALS['errorUrl'] .= Url::getCommon(['db' => $GLOBALS['db']], '&');

                if (! $this->hasDatabase()) {
                    return;
                }
            }
        } elseif (strlen($GLOBALS['db']) > 0) {
            $this->dbi->selectDb($GLOBALS['db']);
        }

        /**
         * Keep a list of errors that occurred while
         * processing an 'Add' or 'Edit' operation.
         */
        $GLOBALS['errors'] = [];

        $this->routines->handleEditor();
        $this->routines->handleExecute();
        $this->routines->export();

        if (! isset($type) || ! in_array($type, ['FUNCTION', 'PROCEDURE'])) {
            $type = null;
        }

        $items = Routines::getDetails($this->dbi, $GLOBALS['db'], $type);
        $isAjax = $this->response->isAjax() && empty($_REQUEST['ajax_page_request']);

        $rows = '';
        foreach ($items as $item) {
            $rows .= $this->routines->getRow($item, $isAjax ? 'ajaxInsert hide' : '');
        }

        $this->render('database/routines/index', [
            'db' => $GLOBALS['db'],
            'table' => $GLOBALS['table'],
            'items' => $items,
            'rows' => $rows,
            'has_privilege' => Util::currentUserHasPrivilege('CREATE ROUTINE', $GLOBALS['db'], $GLOBALS['table']),
        ]);
    }
}
