<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Triggers;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\Identifiers\TriggerName;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Template;
use PhpMyAdmin\Triggers\Trigger;
use PhpMyAdmin\Triggers\Triggers;
use PhpMyAdmin\Util;

use function __;
use function mb_strtoupper;
use function sprintf;
use function trim;

/**
 * Triggers management.
 */
#[Route('/triggers', ['GET', 'POST'])]
final readonly class IndexController implements InvocableController
{
    public function __construct(
        private ResponseRenderer $response,
        private Template $template,
        private DatabaseInterface $dbi,
        private Triggers $triggers,
        private DbTableExists $dbTableExists,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        if (Current::$database === '') {
            return $this->response->missingParameterError('db');
        }

        $databaseName = DatabaseName::tryFrom($request->getParam('db'));
        // selectDatabase is necessary because we run unqualified queries against the selected database
        if ($databaseName === null || ! $this->dbTableExists->selectDatabase($databaseName)) {
            $message = __('No database selected.');
            if (! $request->isAjax()) {
                return $this->response->redirectToRoute(
                    '/',
                    ['reload' => true, 'message' => $message],
                );
            }

            $this->response->addJSON('message', $message);
            $this->response->setRequestStatus(false);

            return $this->response->response();
        }

        $tableName = TableName::tryFrom($request->getParam('table'));
        $table = '';
        if ($tableName !== null) {
            if (! $this->dbTableExists->hasTable($databaseName, $tableName)) {
                $message = __('No table selected.');
                if (! $request->isAjax()) {
                    return $this->response->redirectToRoute(
                        '/',
                        ['reload' => true, 'message' => $message],
                    );
                }

                $this->response->addJSON('message', $message);
                $this->response->setRequestStatus(false);

                return $this->response->response();
            }

            $table = $tableName->getName();
        }

        $this->response->addScriptFiles(['triggers.js', 'sql.js']);

        if ($this->isFormSubmitted($request)) {
            $itemQuery = $this->triggers->getQueryFromRequest($request, $databaseName);
            $sqlQuery = $this->triggers->handleEditor($itemQuery, $request, $databaseName, $table);

            if ($request->isAjax()) {
                if (Current::$message instanceof Message && Current::$message->isSuccess()) {
                    $itemName = $request->getParsedBodyParamAsString('item_name');
                    $trigger = $this->triggers->getTriggerByName($databaseName, $table, $itemName);

                    if (
                        $tableName === null
                        || ($trigger !== null && $tableName->getName() === $trigger->table->getName())
                    ) {
                        $hasTriggerPrivilege = $this->triggers->hasTriggerPrivilege($databaseName, $table);

                        $this->response->addJSON(
                            'new_row',
                            $this->template->render('triggers/row', [
                                'db' => $databaseName->getName(),
                                'table' => $table,
                                'trigger' => $trigger,
                                'has_drop_privilege' => $hasTriggerPrivilege,
                                'has_edit_privilege' => $hasTriggerPrivilege,
                            ]),
                        );
                        $this->response->addJSON('name', mb_strtoupper($itemName));
                    }

                    $this->response->addJSON('message', Generator::getMessage(Current::$message, $sqlQuery));
                } else {
                    $this->response->addJSON('message', Current::$message);
                    $this->response->setRequestStatus(false);
                }

                return $this->response->response();
            }
        }

        /**
         * Display a form used to add/edit a trigger, if necessary
         */
        if (
            $this->triggers->getErrorCount() > 0
            || ! $this->isFormSubmitted($request) && $this->isEditorFormRequested($request)
        ) {
            if ($request->has('add_item')) {
                $title = __('Add trigger');
                $item = $this->getDataFromRequest($request);
                $mode = 'add';
            } else {
                $title = __('Edit trigger');
                if (! $request->hasBodyParam('editor_process_edit')) {
                    $triggerName = TriggerName::from($request->getQueryParam('item_name'));
                    $trigger = $this->triggers->getTriggerByName(
                        $databaseName,
                        $table,
                        $triggerName->getName(),
                    );
                    if ($trigger === null) {
                        if ($request->isAjax()) {
                            $this->response->setRequestStatus(false);
                            $this->response->addJSON(
                                'message',
                                $this->getMessageForMissingItem($databaseName, $triggerName),
                            );

                            return $this->response->response();
                        }

                        $this->response->addHTML($this->getMessageForMissingItem($databaseName, $triggerName));

                        return $this->showIndexPage($databaseName, $table);
                    }

                    $item = $this->getDataFromTrigger($trigger);
                } else {
                    $item = $this->getDataFromRequest($request);
                }

                $mode = 'edit';
            }

            $tables = $this->triggers->getTables($databaseName->getName());
            $editor = $this->template->render('triggers/editor_form', [
                'db' => $databaseName->getName(),
                'table' => $table,
                'is_edit' => $mode === 'edit',
                'item' => $item,
                'tables' => $tables,
                'time' => Triggers::TIMING,
                'events' => Triggers::EVENTS,
                'is_ajax' => $request->isAjax(),
            ]);
            if ($request->isAjax()) {
                $this->response->addJSON('message', $editor);
                $this->response->addJSON('title', $title);

                return $this->response->response();
            }

            $this->response->addHTML("\n\n<h2>" . $title . "</h2>\n\n" . $editor);

            return $this->response->response();
        }

        $triggerName = TriggerName::tryFrom($request->getQueryParam('item_name'));
        if ($request->hasQueryParam('export_item') && $triggerName !== null) {
            $exportData = $this->triggers->getTriggerByName(
                $databaseName,
                $table,
                $triggerName->getName(),
            )?->getCreateSql('$$');
            if ($exportData === null) {
                $message = $this->getMessageForMissingItem($databaseName, $triggerName);
                if ($request->isAjax()) {
                    $this->response->setRequestStatus(false);
                    $this->response->addJSON('message', $message);

                    return $this->response->response();
                }

                return $this->showIndexPage($databaseName, $table, $message);
            }

            if ($request->isAjax()) {
                $title = sprintf(__('Export of trigger %s'), Util::backquote($triggerName));
                $this->response->addJSON('title', $title);
                $this->response->addJSON('message', trim("DELIMITER $$\n" . $exportData));

                return $this->response->response();
            }

            $this->response->render('triggers/export', [
                'data' => "DELIMITER $$\n" . $exportData,
                'item_name' => $triggerName->getName(),
            ]);

            return $this->response->response();
        }

        return $this->showIndexPage($databaseName, $table);
    }

    /**
     * This function will generate the values that are required to for the editor
     *
     * @return array<string, string> Data necessary to create the editor.
     */
    private function getDataFromRequest(ServerRequest $request): array
    {
        return [
            'item_name' => $request->getParsedBodyParamAsString('item_name', ''),
            'item_table' => $request->getParsedBodyParamAsString('item_table', ''),
            'item_original_name' => $request->getParsedBodyParamAsString('item_original_name', ''),
            'item_action_timing' => $request->getParsedBodyParamAsString('item_action_timing', ''),
            'item_event_manipulation' => $request->getParsedBodyParamAsString('item_event_manipulation', ''),
            'item_definition' => $request->getParsedBodyParamAsString('item_definition', ''),
            'item_definer' => $request->getParsedBodyParamAsString('item_definer', ''),
        ];
    }

    /**
     * This function will generate the values that are required to complete
     * the "Edit trigger" form given the trigger.
     *
     * @return array<string, string> Data necessary to create the editor.
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

    private function isFormSubmitted(ServerRequest $request): bool
    {
        return $request->hasBodyParam('editor_process_add') || $request->hasBodyParam('editor_process_edit');
    }

    private function isEditorFormRequested(ServerRequest $request): bool
    {
        return $request->has('add_item') || $request->has('edit_item');
    }

    private function getMessageForMissingItem(DatabaseName $databaseName, TriggerName $triggerName): Message
    {
        return Message::error(
            __('Error in processing request:') . ' ' . __('No trigger with name %1$s found in database %2$s.'),
            [
                Util::backquote($triggerName->getName()),
                Util::backquote($databaseName->getName()),
            ],
        );
    }

    private function showIndexPage(
        DatabaseName $databaseName,
        string $table,
        Message|null $message = null,
    ): Response {
        $triggers = Triggers::getDetails($this->dbi, $databaseName->getName(), $table);
        $hasTriggerPrivilege = $this->triggers->hasTriggerPrivilege($databaseName, $table);

        $this->response->render('triggers/list', [
            'db' => $databaseName->getName(),
            'table' => $table,
            'triggers' => $triggers,
            'has_privilege' => $hasTriggerPrivilege,
            'error_message' => $message?->getDisplay() ?? '',
        ]);

        return $this->response->response();
    }
}
