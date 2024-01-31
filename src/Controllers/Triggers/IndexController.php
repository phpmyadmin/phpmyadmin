<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Triggers;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Current;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\Identifiers\TriggerName;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Triggers\Trigger;
use PhpMyAdmin\Triggers\Triggers;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
use function count;
use function htmlspecialchars;
use function in_array;
use function mb_strtoupper;
use function sprintf;
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
        private readonly DbTableExists $dbTableExists,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['errors'] ??= null;
        $GLOBALS['urlParams'] ??= null;
        $GLOBALS['errorUrl'] ??= null;

        $this->addScriptFiles(['triggers.js', 'sql.js']);

        if (! $request->isAjax()) {
            $config = Config::getInstance();
            /**
             * Displays the header and tabs
             */
            if (Current::$table !== '' && in_array(Current::$table, $this->dbi->getTables(Current::$database), true)) {
                if (! $this->checkParameters(['db', 'table'])) {
                    return;
                }

                $GLOBALS['urlParams'] = ['db' => Current::$database, 'table' => Current::$table];
                $GLOBALS['errorUrl'] = Util::getScriptNameForOption($config->settings['DefaultTabTable'], 'table');
                $GLOBALS['errorUrl'] .= Url::getCommon($GLOBALS['urlParams'], '&');

                $databaseName = DatabaseName::tryFrom($request->getParam('db'));
                if ($databaseName === null || ! $this->dbTableExists->selectDatabase($databaseName)) {
                    $this->redirect('/', ['reload' => true, 'message' => __('No databases selected.')]);

                    return;
                }

                $tableName = TableName::tryFrom($request->getParam('table'));
                if ($tableName === null || ! $this->dbTableExists->hasTable($databaseName, $tableName)) {
                    $this->redirect('/', ['reload' => true, 'message' => __('No table selected.')]);

                    return;
                }
            } else {
                Current::$table = '';

                if (! $this->checkParameters(['db'])) {
                    return;
                }

                $GLOBALS['errorUrl'] = Util::getScriptNameForOption(
                    $config->settings['DefaultTabDatabase'],
                    'database',
                );
                $GLOBALS['errorUrl'] .= Url::getCommon(['db' => Current::$database], '&');

                $databaseName = DatabaseName::tryFrom($request->getParam('db'));
                if ($databaseName === null || ! $this->dbTableExists->selectDatabase($databaseName)) {
                    $this->redirect('/', ['reload' => true, 'message' => __('No databases selected.')]);

                    return;
                }
            }
        } elseif (Current::$database !== '') {
            $this->dbi->selectDb(Current::$database);
        }

        /**
         * Keep a list of errors that occurred while
         * processing an 'Add' or 'Edit' operation.
         */
        $GLOBALS['errors'] = [];
        $GLOBALS['message'] ??= null;

        if (! empty($_POST['editor_process_add']) || ! empty($_POST['editor_process_edit'])) {
            $output = $this->triggers->handleEditor();

            if ($request->isAjax()) {
                if ($GLOBALS['message']->isSuccess()) {
                    $trigger = $this->triggers->getTriggerByName(
                        Current::$database,
                        Current::$table,
                        $_POST['item_name'],
                    );

                    $insert = false;
                    if (
                        Current::$table === ''
                        || ($trigger !== null && Current::$table === $trigger->table->getName())
                    ) {
                        $insert = true;
                        $hasTriggerPrivilege = Util::currentUserHasPrivilege(
                            'TRIGGER',
                            Current::$database,
                            Current::$table,
                        );
                        $this->response->addJSON(
                            'new_row',
                            $this->template->render('triggers/row', [
                                'db' => Current::$database,
                                'table' => Current::$table,
                                'trigger' => $trigger,
                                'has_drop_privilege' => $hasTriggerPrivilege,
                                'has_edit_privilege' => $hasTriggerPrivilege,
                                'row_class' => '',
                            ]),
                        );
                        $this->response->addJSON(
                            'name',
                            htmlspecialchars(
                                mb_strtoupper(
                                    $_POST['item_name'],
                                ),
                            ),
                        );
                    }

                    $this->response->addJSON('insert', $insert);
                    $this->response->addJSON('message', $output);
                } else {
                    $this->response->addJSON('message', $GLOBALS['message']);
                    $this->response->setRequestStatus(false);
                }

                $this->response->addJSON('tableType', 'triggers');

                return;
            }
        }

        /**
         * Display a form used to add/edit a trigger, if necessary
         */
        if (
            count($GLOBALS['errors'])
            || empty($_POST['editor_process_add'])
            && empty($_POST['editor_process_edit'])
            && (! empty($_REQUEST['add_item']) || ! empty($_REQUEST['edit_item'])) // FIXME: must be simpler than that
        ) {
            $mode = '';
            $item = null;
            $title = '';
            // Get the data for the form (if any)
            if (! empty($_REQUEST['add_item'])) {
                $title = __('Add trigger');
                $item = $this->getDataFromRequest($request);
                $mode = 'add';
            } elseif (! empty($_REQUEST['edit_item'])) {
                $title = __('Edit trigger');
                if (! empty($_REQUEST['item_name']) && empty($_POST['editor_process_edit'])) {
                    $item = $this->getDataFromTrigger(
                        $this->triggers->getTriggerByName(
                            Current::$database,
                            Current::$table,
                            $_REQUEST['item_name'],
                        ),
                    );
                } else {
                    $item = $this->getDataFromRequest($request);
                }

                $mode = 'edit';
            }

            if ($item !== null) {
                $tables = $this->triggers->getTables(Current::$database);
                $editor = $this->template->render('triggers/editor_form', [
                    'db' => Current::$database,
                    'table' => Current::$table,
                    'is_edit' => $mode === 'edit',
                    'item' => $item,
                    'tables' => $tables,
                    'time' => ['BEFORE', 'AFTER'],
                    'events' => ['INSERT', 'UPDATE', 'DELETE'],
                    'is_ajax' => $request->isAjax(),
                ]);
                if ($request->isAjax()) {
                    $this->response->addJSON('message', $editor);
                    $this->response->addJSON('title', $title);

                    return;
                }

                $this->response->addHTML("\n\n<h2>" . $title . "</h2>\n\n" . $editor);

                return;
            }

            $message = __('Error in processing request:') . ' ';
            $message .= sprintf(
                __('No trigger with name %1$s found in database %2$s.'),
                htmlspecialchars(Util::backquote($_REQUEST['item_name'])),
                htmlspecialchars(Util::backquote(Current::$database)),
            );
            $message = Message::error($message);
            if ($request->isAjax()) {
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', $message);

                return;
            }

            $this->response->addHTML($message->getDisplay());
        }

        $message = null;
        $triggerName = TriggerName::tryFrom($request->getQueryParam('item_name'));
        if ($request->hasQueryParam('export_item') && $triggerName !== null) {
            $exportData = $this->triggers->getTriggerByName(
                Current::$database,
                Current::$table,
                $triggerName->getName(),
            )?->getCreateSql('');
            if ($exportData !== null && $request->isAjax()) {
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
                htmlspecialchars(Util::backquote(Current::$database)),
            ));
            if ($request->isAjax()) {
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', $message);

                return;
            }
        }

        $triggers = Triggers::getDetails($this->dbi, Current::$database, Current::$table);
        $hasTriggerPrivilege = Util::currentUserHasPrivilege('TRIGGER', Current::$database, Current::$table);
        $isAjax = $request->isAjax() && empty($request->getParam('ajax_page_request'));

        $this->render('triggers/list', [
            'db' => Current::$database,
            'table' => Current::$table,
            'triggers' => $triggers,
            'has_privilege' => $hasTriggerPrivilege,
            'is_ajax' => $isAjax,
            'error_message' => $message?->getDisplay() ?? '',
        ]);
    }

    /**
     * This function will generate the values that are required to for the editor
     *
     * @return mixed[]    Data necessary to create the editor.
     */
    private function getDataFromRequest(ServerRequest $request): array
    {
        return [
            'item_name' => $request->getParsedBodyParam('item_name', ''),
            'item_table' => $request->getParsedBodyParam('item_table', ''),
            'item_original_name' => $request->getParsedBodyParam('item_original_name', ''),
            'item_action_timing' => $request->getParsedBodyParam('item_action_timing', ''),
            'item_event_manipulation' => $request->getParsedBodyParam('item_event_manipulation', ''),
            'item_definition' => $request->getParsedBodyParam('item_definition', ''),
            'item_definer' => $request->getParsedBodyParam('item_definer', ''),
        ];
    }

    /**
     * This function will generate the values that are required to complete
     * the "Edit trigger" form given the trigger.
     *
     * @return mixed[]    Data necessary to create the editor.
     */
    private function getDataFromTrigger(Trigger $trigger): array
    {
        return [
            'create' => $trigger->getCreateSql(''),
            'drop' => $trigger->getDropSql(),
            'item_name' => $trigger->name->getName(),
            'item_table' => $trigger->table->getName(),
            'item_action_timing' => $trigger->timing->value,
            'item_event_manipulation' => $trigger->event->value,
            'item_definition' => $trigger->statement,
            'item_definer' => $trigger->definer,
            'item_original_name' => $trigger->name->getName(),
        ];
    }
}
