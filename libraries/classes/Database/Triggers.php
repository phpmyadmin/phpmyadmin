<?php

declare(strict_types=1);

namespace PhpMyAdmin\Database;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Message;
use PhpMyAdmin\Query\Generator as QueryGenerator;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;

use function __;
use function array_column;
use function array_multisort;
use function count;
use function explode;
use function htmlspecialchars;
use function in_array;
use function mb_strtoupper;
use function sprintf;
use function str_contains;
use function trim;

use const SORT_ASC;

/**
 * Functions for trigger management.
 */
class Triggers
{
    /** @var array<int, string> */
    private array $time = ['BEFORE', 'AFTER'];

    /** @var array<int, string> */
    private array $event = ['INSERT', 'UPDATE', 'DELETE'];

    public function __construct(
        private DatabaseInterface $dbi,
        private Template $template,
        private ResponseRenderer $response,
    ) {
    }

    /**
     * Main function for the triggers functionality
     */
    public function main(): void
    {
        /**
         * Process all requests
         */
        $this->handleEditor();
        $this->export();

        $items = self::getDetails($this->dbi, $GLOBALS['db'], $GLOBALS['table']);
        $hasTriggerPrivilege = Util::currentUserHasPrivilege('TRIGGER', $GLOBALS['db'], $GLOBALS['table']);
        $isAjax = $this->response->isAjax() && empty($_REQUEST['ajax_page_request']);

        $rows = '';
        foreach ($items as $item) {
            $rows .= $this->template->render('database/triggers/row', [
                'db' => $GLOBALS['db'],
                'table' => $GLOBALS['table'],
                'trigger' => $item,
                'has_drop_privilege' => $hasTriggerPrivilege,
                'has_edit_privilege' => $hasTriggerPrivilege,
                'row_class' => $isAjax ? 'ajaxInsert hide' : '',
            ]);
        }

        echo $this->template->render('database/triggers/list', [
            'db' => $GLOBALS['db'],
            'table' => $GLOBALS['table'],
            'items' => $items,
            'rows' => $rows,
            'has_privilege' => $hasTriggerPrivilege,
        ]);
    }

    /**
     * Handles editor requests for adding or editing an item
     */
    public function handleEditor(): void
    {
        $GLOBALS['errors'] ??= null;
        $GLOBALS['message'] ??= null;

        if (! empty($_POST['editor_process_add']) || ! empty($_POST['editor_process_edit'])) {
            $sqlQuery = '';

            $itemQuery = $this->getQueryFromRequest();

            // set by getQueryFromRequest()
            if (! count($GLOBALS['errors'])) {
                // Execute the created query
                if (! empty($_POST['editor_process_edit'])) {
                    // Backup the old trigger, in case something goes wrong
                    $trigger = $this->getDataFromName($_POST['item_original_name']);
                    $createItem = $trigger['create'];
                    $dropItem = $trigger['drop'] . ';';
                    $result = $this->dbi->tryQuery($dropItem);
                    if (! $result) {
                        $GLOBALS['errors'][] = sprintf(
                            __('The following query has failed: "%s"'),
                            htmlspecialchars($dropItem),
                        )
                        . '<br>'
                        . __('MySQL said: ') . $this->dbi->getError();
                    } else {
                        $result = $this->dbi->tryQuery($itemQuery);
                        if (! $result) {
                            $GLOBALS['errors'][] = sprintf(
                                __('The following query has failed: "%s"'),
                                htmlspecialchars($itemQuery),
                            )
                            . '<br>'
                            . __('MySQL said: ') . $this->dbi->getError();
                            // We dropped the old item, but were unable to create the
                            // new one. Try to restore the backup query.
                            $result = $this->dbi->tryQuery($createItem);

                            if (! $result) {
                                $GLOBALS['errors'] = $this->checkResult($createItem, $GLOBALS['errors']);
                            }
                        } else {
                            $GLOBALS['message'] = Message::success(
                                __('Trigger %1$s has been modified.'),
                            );
                            $GLOBALS['message']->addParam(
                                Util::backquote($_POST['item_name']),
                            );
                            $sqlQuery = $dropItem . $itemQuery;
                        }
                    }
                } else {
                    // 'Add a new item' mode
                    $result = $this->dbi->tryQuery($itemQuery);
                    if (! $result) {
                        $GLOBALS['errors'][] = sprintf(
                            __('The following query has failed: "%s"'),
                            htmlspecialchars($itemQuery),
                        )
                        . '<br><br>'
                        . __('MySQL said: ') . $this->dbi->getError();
                    } else {
                        $GLOBALS['message'] = Message::success(
                            __('Trigger %1$s has been created.'),
                        );
                        $GLOBALS['message']->addParam(
                            Util::backquote($_POST['item_name']),
                        );
                        $sqlQuery = $itemQuery;
                    }
                }
            }

            if (count($GLOBALS['errors'])) {
                $GLOBALS['message'] = Message::error(
                    '<b>'
                    . __(
                        'One or more errors have occurred while processing your request:',
                    )
                    . '</b>',
                );
                $GLOBALS['message']->addHtml('<ul>');
                foreach ($GLOBALS['errors'] as $string) {
                    $GLOBALS['message']->addHtml('<li>' . $string . '</li>');
                }

                $GLOBALS['message']->addHtml('</ul>');
            }

            $output = Generator::getMessage($GLOBALS['message'], $sqlQuery);

            if ($this->response->isAjax()) {
                if ($GLOBALS['message']->isSuccess()) {
                    $items = self::getDetails($this->dbi, $GLOBALS['db'], $GLOBALS['table'], '');
                    $trigger = false;
                    foreach ($items as $value) {
                        if ($value['name'] != $_POST['item_name']) {
                            continue;
                        }

                        $trigger = $value;
                    }

                    $insert = false;
                    if (empty($GLOBALS['table']) || ($trigger !== false && $GLOBALS['table'] == $trigger['table'])) {
                        $insert = true;
                        $hasTriggerPrivilege = Util::currentUserHasPrivilege(
                            'TRIGGER',
                            $GLOBALS['db'],
                            $GLOBALS['table'],
                        );
                        $this->response->addJSON(
                            'new_row',
                            $this->template->render('database/triggers/row', [
                                'db' => $GLOBALS['db'],
                                'table' => $GLOBALS['table'],
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
                exit;
            }
        }

        /**
         * Display a form used to add/edit a trigger, if necessary
         */
        if (
            ! count($GLOBALS['errors'])
            && (! empty($_POST['editor_process_add'])
            || ! empty($_POST['editor_process_edit'])
            || (empty($_REQUEST['add_item'])
            && empty($_REQUEST['edit_item']))) // FIXME: this must be simpler than that
        ) {
            return;
        }

        $mode = '';
        $item = null;
        $title = '';
        // Get the data for the form (if any)
        if (! empty($_REQUEST['add_item'])) {
            $title = __('Add trigger');
            $item = $this->getDataFromRequest();
            $mode = 'add';
        } elseif (! empty($_REQUEST['edit_item'])) {
            $title = __('Edit trigger');
            if (! empty($_REQUEST['item_name']) && empty($_POST['editor_process_edit'])) {
                $item = $this->getDataFromName($_REQUEST['item_name']);
                if ($item !== null) {
                    $item['item_original_name'] = $item['item_name'];
                }
            } else {
                $item = $this->getDataFromRequest();
            }

            $mode = 'edit';
        }

        $this->sendEditor($mode, $item, $title, $GLOBALS['db'], $GLOBALS['table']);
    }

    /**
     * This function will generate the values that are required to for the editor
     *
     * @return mixed[]    Data necessary to create the editor.
     */
    public function getDataFromRequest(): array
    {
        $retval = [];
        $indices = [
            'item_name',
            'item_table',
            'item_original_name',
            'item_action_timing',
            'item_event_manipulation',
            'item_definition',
            'item_definer',
        ];
        foreach ($indices as $index) {
            $retval[$index] = $_POST[$index] ?? '';
        }

        return $retval;
    }

    /**
     * This function will generate the values that are required to complete
     * the "Edit trigger" form given the name of a trigger.
     *
     * @param string $name The name of the trigger.
     *
     * @return mixed[]|null Data necessary to create the editor.
     */
    public function getDataFromName(string $name): array|null
    {
        $temp = [];
        $items = self::getDetails($this->dbi, $GLOBALS['db'], $GLOBALS['table'], '');
        foreach ($items as $value) {
            if ($value['name'] != $name) {
                continue;
            }

            $temp = $value;
        }

        if (empty($temp)) {
            return null;
        }

        $retval = [];
        $retval['create'] = $temp['create'];
        $retval['drop'] = $temp['drop'];
        $retval['item_name'] = $temp['name'];
        $retval['item_table'] = $temp['table'];
        $retval['item_action_timing'] = $temp['action_timing'];
        $retval['item_event_manipulation'] = $temp['event_manipulation'];
        $retval['item_definition'] = $temp['definition'];
        $retval['item_definer'] = $temp['definer'];

        return $retval;
    }

    /**
     * Displays a form used to add/edit a trigger
     *
     * @param string  $mode If the editor will be used to edit a trigger or add a new one: 'edit' or 'add'.
     * @param mixed[] $item Data for the trigger returned by getDataFromRequest() or getDataFromName()
     */
    public function getEditorForm(string $db, string $table, string $mode, array $item): string
    {
        $query = 'SELECT `TABLE_NAME` FROM `INFORMATION_SCHEMA`.`TABLES` ';
        $query .= 'WHERE `TABLE_SCHEMA`=\'' . $this->dbi->escapeString($db) . '\' ';
        $query .= 'AND `TABLE_TYPE` IN (\'BASE TABLE\', \'SYSTEM VERSIONED\')';
        $tables = $this->dbi->fetchResult($query);

        return $this->template->render('database/triggers/editor_form', [
            'db' => $db,
            'table' => $table,
            'is_edit' => $mode === 'edit',
            'item' => $item,
            'tables' => $tables,
            'time' => $this->time,
            'events' => $this->event,
            'is_ajax' => $this->response->isAjax(),
        ]);
    }

    /**
     * Composes the query necessary to create a trigger from an HTTP request.
     *
     * @return string  The CREATE TRIGGER query.
     */
    public function getQueryFromRequest(): string
    {
        $GLOBALS['errors'] ??= null;

        $query = 'CREATE ';
        if (! empty($_POST['item_definer'])) {
            if (str_contains($_POST['item_definer'], '@')) {
                $arr = explode('@', $_POST['item_definer']);
                $query .= 'DEFINER=' . Util::backquote($arr[0]);
                $query .= '@' . Util::backquote($arr[1]) . ' ';
            } else {
                $GLOBALS['errors'][] = __('The definer must be in the "username@hostname" format!');
            }
        }

        $query .= 'TRIGGER ';
        if (! empty($_POST['item_name'])) {
            $query .= Util::backquote($_POST['item_name']) . ' ';
        } else {
            $GLOBALS['errors'][] = __('You must provide a trigger name!');
        }

        if (! empty($_POST['item_timing']) && in_array($_POST['item_timing'], $this->time)) {
            $query .= $_POST['item_timing'] . ' ';
        } else {
            $GLOBALS['errors'][] = __('You must provide a valid timing for the trigger!');
        }

        if (! empty($_POST['item_event']) && in_array($_POST['item_event'], $this->event)) {
            $query .= $_POST['item_event'] . ' ';
        } else {
            $GLOBALS['errors'][] = __('You must provide a valid event for the trigger!');
        }

        $query .= 'ON ';
        if (! empty($_POST['item_table']) && in_array($_POST['item_table'], $this->dbi->getTables($GLOBALS['db']))) {
            $query .= Util::backquote($_POST['item_table']);
        } else {
            $GLOBALS['errors'][] = __('You must provide a valid table name!');
        }

        $query .= ' FOR EACH ROW ';
        if (! empty($_POST['item_definition'])) {
            $query .= $_POST['item_definition'];
        } else {
            $GLOBALS['errors'][] = __('You must provide a trigger definition.');
        }

        return $query;
    }

    /**
     * @param string  $createStatement Query
     * @param mixed[] $errors          Errors
     *
     * @return mixed[]
     */
    private function checkResult(string $createStatement, array $errors): array
    {
        // OMG, this is really bad! We dropped the query,
        // failed to create a new one
        // and now even the backup query does not execute!
        // This should not happen, but we better handle
        // this just in case.
        $errors[] = __('Sorry, we failed to restore the dropped trigger.') . '<br>'
            . __('The backed up query was:')
            . '"' . htmlspecialchars($createStatement) . '"<br>'
            . __('MySQL said: ') . $this->dbi->getError();

        return $errors;
    }

    /**
     * Send editor via ajax or by echoing.
     *
     * @param string       $mode  Editor mode 'add' or 'edit'
     * @param mixed[]|null $item  Data necessary to create the editor
     * @param string       $title Title of the editor
     * @param string       $db    Database
     * @param string       $table Table
     */
    private function sendEditor(string $mode, array|null $item, string $title, string $db, string $table): void
    {
        if ($item !== null) {
            $editor = $this->getEditorForm($db, $table, $mode, $item);
            if ($this->response->isAjax()) {
                $this->response->addJSON('message', $editor);
                $this->response->addJSON('title', $title);
            } else {
                echo "\n\n<h2>" . $title . "</h2>\n\n" . $editor;
                unset($_POST);
            }

            exit;
        }

        $message = __('Error in processing request:') . ' ';
        $message .= sprintf(
            __('No trigger with name %1$s found in database %2$s.'),
            htmlspecialchars(Util::backquote($_REQUEST['item_name'])),
            htmlspecialchars(Util::backquote($db)),
        );
        $message = Message::error($message);
        if ($this->response->isAjax()) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', $message);
            exit;
        }

        echo $message->getDisplay();
    }

    private function export(): void
    {
        if (empty($_GET['export_item']) || empty($_GET['item_name'])) {
            return;
        }

        $itemName = $_GET['item_name'];
        $triggers = self::getDetails($this->dbi, $GLOBALS['db'], $GLOBALS['table'], '');
        $exportData = false;

        foreach ($triggers as $trigger) {
            if ($trigger['name'] === $itemName) {
                $exportData = $trigger['create'];
                break;
            }
        }

        if ($exportData !== false) {
            $title = sprintf(__('Export of trigger %s'), htmlspecialchars(Util::backquote($itemName)));

            if ($this->response->isAjax()) {
                $this->response->addJSON('message', htmlspecialchars(trim($exportData)));
                $this->response->addJSON('title', $title);

                exit;
            }

            $this->response->addHTML($this->template->render('database/triggers/export', [
                'data' => $exportData,
                'item_name' => $itemName,
            ]));

            return;
        }

        $message = sprintf(
            __('Error in processing request: No trigger with name %1$s found in database %2$s.'),
            htmlspecialchars(Util::backquote($itemName)),
            htmlspecialchars(Util::backquote($GLOBALS['db'])),
        );
        $message = Message::error($message);

        if ($this->response->isAjax()) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', $message);

            exit;
        }

        $this->response->addHTML($message->getDisplay());
    }

    /**
     * Returns details about the TRIGGERs for a specific table or database.
     *
     * @param string $db        db name
     * @param string $table     table name
     * @param string $delimiter the delimiter to use (may be empty)
     *
     * @return mixed[] information about triggers (may be empty)
     */
    public static function getDetails(
        DatabaseInterface $dbi,
        string $db,
        string $table = '',
        string $delimiter = '//',
    ): array {
        $result = [];
        if (! $GLOBALS['cfg']['Server']['DisableIS']) {
            $query = QueryGenerator::getInformationSchemaTriggersRequest(
                $dbi->escapeString($db),
                $table === '' ? null : $dbi->escapeString($table),
            );
        } else {
            $query = 'SHOW TRIGGERS FROM ' . Util::backquote($db);
            if ($table !== '') {
                $query .= " LIKE '" . $dbi->escapeString($table) . "';";
            }
        }

        $triggers = $dbi->fetchResult($query);

        foreach ($triggers as $trigger) {
            if ($GLOBALS['cfg']['Server']['DisableIS']) {
                $trigger['TRIGGER_NAME'] = $trigger['Trigger'];
                $trigger['ACTION_TIMING'] = $trigger['Timing'];
                $trigger['EVENT_MANIPULATION'] = $trigger['Event'];
                $trigger['EVENT_OBJECT_TABLE'] = $trigger['Table'];
                $trigger['ACTION_STATEMENT'] = $trigger['Statement'];
                $trigger['DEFINER'] = $trigger['Definer'];
            }

            $oneResult = [];
            $oneResult['name'] = $trigger['TRIGGER_NAME'];
            $oneResult['table'] = $trigger['EVENT_OBJECT_TABLE'];
            $oneResult['action_timing'] = $trigger['ACTION_TIMING'];
            $oneResult['event_manipulation'] = $trigger['EVENT_MANIPULATION'];
            $oneResult['definition'] = $trigger['ACTION_STATEMENT'];
            $oneResult['definer'] = $trigger['DEFINER'];

            // do not prepend the schema name; this way, importing the
            // definition into another schema will work
            $oneResult['full_trigger_name'] = Util::backquote($trigger['TRIGGER_NAME']);
            $oneResult['drop'] = 'DROP TRIGGER IF EXISTS '
                . $oneResult['full_trigger_name'];
            $oneResult['create'] = 'CREATE TRIGGER '
                . $oneResult['full_trigger_name'] . ' '
                . $trigger['ACTION_TIMING'] . ' '
                . $trigger['EVENT_MANIPULATION']
                . ' ON ' . Util::backquote($trigger['EVENT_OBJECT_TABLE'])
                . "\n" . ' FOR EACH ROW '
                . $trigger['ACTION_STATEMENT'] . "\n" . $delimiter . "\n";

            $result[] = $oneResult;
        }

        // Sort results by name
        $name = array_column($result, 'name');
        array_multisort($name, SORT_ASC, $result);

        return $result;
    }
}
