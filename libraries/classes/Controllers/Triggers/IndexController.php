<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Triggers;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\TriggerName;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Triggers\Triggers;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
use function htmlspecialchars;
use function in_array;
use function sprintf;
use function strlen;
use function trim;

/**
 * Triggers management.
 */
final class IndexController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private DatabaseInterface $dbi,
        private Triggers $triggers,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['errors'] ??= null;
        $GLOBALS['urlParams'] ??= null;
        $GLOBALS['errorUrl'] ??= null;

        $this->addScriptFiles(['triggers.js']);

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

        $this->triggers->handleEditor();

        $message = null;
        $triggerName = TriggerName::tryFrom($request->getQueryParam('item_name'));
        if ($request->hasQueryParam('export_item') && $triggerName !== null) {
            $exportData = $this->triggers->getExportData($GLOBALS['db'], $GLOBALS['table'], $triggerName);
            if ($exportData !== null && $this->response->isAjax()) {
                $title = sprintf(__('Export of trigger %s'), htmlspecialchars(Util::backquote($triggerName)));
                $this->response->addJSON('title', $title);
                $this->response->addJSON('message', htmlspecialchars(trim($exportData)));

                return;
            }

            if ($exportData !== null) {
                $this->render('triggers/export', ['data' => $exportData, 'item_name' => $triggerName->getName()]);

                return;
            }

            $message = Message::error(sprintf(
                __('Error in processing request: No trigger with name %1$s found in database %2$s.'),
                htmlspecialchars(Util::backquote($triggerName)),
                htmlspecialchars(Util::backquote($GLOBALS['db'])),
            ));
            if ($this->response->isAjax()) {
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', $message);

                return;
            }
        }

        $triggers = Triggers::getDetails($this->dbi, $GLOBALS['db'], $GLOBALS['table']);
        $hasTriggerPrivilege = Util::currentUserHasPrivilege('TRIGGER', $GLOBALS['db'], $GLOBALS['table']);
        $isAjax = $this->response->isAjax() && empty($request->getParam('ajax_page_request'));

        $this->render('triggers/list', [
            'db' => $GLOBALS['db'],
            'table' => $GLOBALS['table'],
            'triggers' => $triggers,
            'has_privilege' => $hasTriggerPrivilege,
            'is_ajax' => $isAjax,
            'error_message' => $message?->getDisplay() ?? '',
        ]);
    }
}
