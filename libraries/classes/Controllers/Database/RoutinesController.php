<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\CheckUserPrivileges;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Database\Routines;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
use function htmlspecialchars;
use function in_array;
use function sprintf;
use function strlen;
use function trim;

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

        /** @var mixed $routineType */
        $routineType = $request->getQueryParam('item_type');
        if (
            ! empty($_GET['export_item'])
            && ! empty($_GET['item_name'])
            && in_array($routineType, ['FUNCTION', 'PROCEDURE'], true)
        ) {
            if ($routineType === 'FUNCTION') {
                $routineDefinition = Routines::getFunctionDefinition($this->dbi, $GLOBALS['db'], $_GET['item_name']);
            } else {
                $routineDefinition = Routines::getProcedureDefinition($this->dbi, $GLOBALS['db'], $_GET['item_name']);
            }

            $exportData = false;

            if ($routineDefinition !== null) {
                $exportData = "DELIMITER $$\n" . $routineDefinition . "$$\nDELIMITER ;\n";
            }

            $itemName = htmlspecialchars(Util::backquote($_GET['item_name']));
            if ($exportData !== false) {
                $exportData = htmlspecialchars(trim($exportData));
                $title = sprintf(__('Export of routine %s'), $itemName);

                if ($this->response->isAjax()) {
                    $this->response->addJSON('message', $exportData);
                    $this->response->addJSON('title', $title);

                    return;
                }

                $output = '<div class="container">';
                $output .= '<h2>' . $title . '</h2>';
                $output .= '<div class="card"><div class="card-body">';
                $output .= '<textarea rows="15" class="form-control">' . $exportData . '</textarea>';
                $output .= '</div></div></div>';

                $this->response->addHTML($output);
            } else {
                $message = sprintf(
                    __(
                        'Error in processing request: No routine with name %1$s found in database %2$s.'
                        . ' You might be lacking the necessary privileges to view/export this routine.',
                    ),
                    $itemName,
                    htmlspecialchars(Util::backquote($GLOBALS['db'])),
                );
                $message = Message::error($message);

                if ($this->response->isAjax()) {
                    $this->response->setRequestStatus(false);
                    $this->response->addJSON('message', $message);

                    return;
                }

                $this->response->addHTML($message->getDisplay());
            }
        }

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
