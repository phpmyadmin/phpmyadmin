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
use function intval;
use function is_string;
use function mb_strtoupper;
use function sprintf;
use function str_contains;
use function strtoupper;
use function trim;

use const SORT_ASC;

/**
 * Functions for event management.
 */
class Events
{
    /** @var array<string, array<int, string>> */
    private array $status = [
        'query' => ['ENABLE', 'DISABLE', 'DISABLE ON SLAVE'],
        'display' => ['ENABLED', 'DISABLED', 'SLAVESIDE_DISABLED'],
    ];

    /** @var array<int, string> */
    private array $type = ['RECURRING', 'ONE TIME'];

    /** @var array<int, string> */
    private array $interval = [
        'YEAR',
        'QUARTER',
        'MONTH',
        'DAY',
        'HOUR',
        'MINUTE',
        'WEEK',
        'SECOND',
        'YEAR_MONTH',
        'DAY_HOUR',
        'DAY_MINUTE',
        'DAY_SECOND',
        'HOUR_MINUTE',
        'HOUR_SECOND',
        'MINUTE_SECOND',
    ];

    public function __construct(
        private DatabaseInterface $dbi,
        private Template $template,
        private ResponseRenderer $response,
    ) {
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
                    $createItem = self::getDefinition($this->dbi, $GLOBALS['db'], $_POST['item_original_name']);
                    $dropItem = 'DROP EVENT IF EXISTS '
                        . Util::backquote($_POST['item_original_name'])
                        . ";\n";
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
                            // We dropped the old item, but were unable to create
                            // the new one. Try to restore the backup query
                            $result = $this->dbi->tryQuery($createItem);
                            if (! $result) {
                                $GLOBALS['errors'] = $this->checkResult($createItem, $GLOBALS['errors']);
                            }
                        } else {
                            $GLOBALS['message'] = Message::success(
                                __('Event %1$s has been modified.'),
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
                            __('Event %1$s has been created.'),
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
                    $events = $this->getDetails($GLOBALS['db'], $_POST['item_name']);
                    $event = $events[0];
                    $this->response->addJSON(
                        'name',
                        htmlspecialchars(
                            mb_strtoupper($_POST['item_name']),
                        ),
                    );
                    if (! empty($event)) {
                        $sqlDrop = sprintf(
                            'DROP EVENT IF EXISTS %s',
                            Util::backquote($event['name']),
                        );
                        $this->response->addJSON(
                            'new_row',
                            $this->template->render('database/events/row', [
                                'db' => $GLOBALS['db'],
                                'table' => $GLOBALS['table'],
                                'event' => $event,
                                'has_privilege' => Util::currentUserHasPrivilege('EVENT', $GLOBALS['db']),
                                'sql_drop' => $sqlDrop,
                                'row_class' => '',
                            ]),
                        );
                    }

                    $this->response->addJSON('insert', ! empty($event));
                    $this->response->addJSON('message', $output);
                } else {
                    $this->response->setRequestStatus(false);
                    $this->response->addJSON('message', $GLOBALS['message']);
                }

                $this->response->addJSON('tableType', 'events');
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
            && empty($_REQUEST['edit_item'])
            && empty($_POST['item_changetype'])))
        ) {
            return;
        }

        // FIXME: this must be simpler than that
        $operation = '';
        $title = '';
        $item = null;
        $mode = '';
        if (! empty($_POST['item_changetype'])) {
            $operation = 'change';
        }

        // Get the data for the form (if any)
        if (! empty($_REQUEST['add_item'])) {
            $title = __('Add event');
            $item = $this->getDataFromRequest();
            $mode = 'add';
        } elseif (! empty($_REQUEST['edit_item'])) {
            $title = __('Edit event');
            if (
                ! empty($_REQUEST['item_name'])
                && empty($_POST['editor_process_edit'])
                && empty($_POST['item_changetype'])
            ) {
                $item = $this->getDataFromName($_REQUEST['item_name']);
                if ($item !== null) {
                    $item['item_original_name'] = $item['item_name'];
                }
            } else {
                $item = $this->getDataFromRequest();
            }

            $mode = 'edit';
        }

        $this->sendEditor($mode, $item, $title, $GLOBALS['db'], $operation);
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
            'item_original_name',
            'item_status',
            'item_execute_at',
            'item_interval_value',
            'item_interval_field',
            'item_starts',
            'item_ends',
            'item_definition',
            'item_preserve',
            'item_comment',
            'item_definer',
        ];
        foreach ($indices as $index) {
            $retval[$index] = $_POST[$index] ?? '';
        }

        $retval['item_type'] = 'ONE TIME';
        $retval['item_type_toggle'] = 'RECURRING';
        if (isset($_POST['item_type']) && $_POST['item_type'] === 'RECURRING') {
            $retval['item_type'] = 'RECURRING';
            $retval['item_type_toggle'] = 'ONE TIME';
        }

        return $retval;
    }

    /**
     * This function will generate the values that are required to complete
     * the "Edit event" form given the name of a event.
     *
     * @param string $name The name of the event.
     *
     * @return mixed[]|null Data necessary to create the editor.
     */
    public function getDataFromName(string $name): array|null
    {
        $retval = [];
        $columns = '`EVENT_NAME`, `STATUS`, `EVENT_TYPE`, `EXECUTE_AT`, '
                 . '`INTERVAL_VALUE`, `INTERVAL_FIELD`, `STARTS`, `ENDS`, '
                 . '`EVENT_DEFINITION`, `ON_COMPLETION`, `DEFINER`, `EVENT_COMMENT`';
        $where = 'EVENT_SCHEMA ' . Util::getCollateForIS() . '=' . $this->dbi->quoteString($GLOBALS['db'])
                 . ' AND EVENT_NAME=' . $this->dbi->quoteString($name);
        $query = 'SELECT ' . $columns . ' FROM `INFORMATION_SCHEMA`.`EVENTS` WHERE ' . $where . ';';
        $item = $this->dbi->fetchSingleRow($query);
        if (! $item) {
            return null;
        }

        $retval['item_name'] = $item['EVENT_NAME'];
        $retval['item_status'] = $item['STATUS'];
        $retval['item_type'] = $item['EVENT_TYPE'];
        if ($retval['item_type'] === 'RECURRING') {
            $retval['item_type_toggle'] = 'ONE TIME';
        } else {
            $retval['item_type_toggle'] = 'RECURRING';
        }

        $retval['item_execute_at'] = $item['EXECUTE_AT'];
        $retval['item_interval_value'] = $item['INTERVAL_VALUE'];
        $retval['item_interval_field'] = $item['INTERVAL_FIELD'];
        $retval['item_starts'] = $item['STARTS'];
        $retval['item_ends'] = $item['ENDS'];
        $retval['item_preserve'] = '';
        if ($item['ON_COMPLETION'] === 'PRESERVE') {
            $retval['item_preserve'] = " checked='checked'";
        }

        $retval['item_definition'] = $item['EVENT_DEFINITION'];
        $retval['item_definer'] = $item['DEFINER'];
        $retval['item_comment'] = $item['EVENT_COMMENT'];

        return $retval;
    }

    /**
     * Displays a form used to add/edit an event
     *
     * @param string  $mode      If the editor will be used to edit an event
     *                           or add a new one: 'edit' or 'add'.
     * @param string  $operation If the editor was previously invoked with
     *                           JS turned off, this will hold the name of
     *                           the current operation
     * @param mixed[] $item      Data for the event returned by
     *                         getDataFromRequest() or getDataFromName()
     *
     * @return string   HTML code for the editor.
     */
    public function getEditorForm(string $mode, string $operation, array $item): string
    {
        if ($operation === 'change') {
            if ($item['item_type'] === 'RECURRING') {
                $item['item_type'] = 'ONE TIME';
                $item['item_type_toggle'] = 'RECURRING';
            } else {
                $item['item_type'] = 'RECURRING';
                $item['item_type_toggle'] = 'ONE TIME';
            }
        }

        return $this->template->render('database/events/editor_form', [
            'db' => $GLOBALS['db'],
            'event' => $item,
            'mode' => $mode,
            'is_ajax' => $this->response->isAjax(),
            'status_display' => $this->status['display'],
            'event_type' => $this->type,
            'event_interval' => $this->interval,
        ]);
    }

    /**
     * Composes the query necessary to create an event from an HTTP request.
     *
     * @return string  The CREATE EVENT query.
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

        $query .= 'EVENT ';
        if (! empty($_POST['item_name'])) {
            $query .= Util::backquote($_POST['item_name']) . ' ';
        } else {
            $GLOBALS['errors'][] = __('You must provide an event name!');
        }

        $query .= 'ON SCHEDULE ';
        if (! empty($_POST['item_type']) && in_array($_POST['item_type'], $this->type)) {
            if ($_POST['item_type'] === 'RECURRING') {
                if (
                    ! empty($_POST['item_interval_value'])
                    && ! empty($_POST['item_interval_field'])
                    && in_array($_POST['item_interval_field'], $this->interval)
                ) {
                    $query .= 'EVERY ' . intval($_POST['item_interval_value']) . ' ';
                    $query .= $_POST['item_interval_field'] . ' ';
                } else {
                    $GLOBALS['errors'][] = __('You must provide a valid interval value for the event.');
                }

                if (! empty($_POST['item_starts'])) {
                    $query .= 'STARTS ' . $this->dbi->quoteString($_POST['item_starts']) . ' ';
                }

                if (! empty($_POST['item_ends'])) {
                    $query .= 'ENDS ' . $this->dbi->quoteString($_POST['item_ends']) . ' ';
                }
            } elseif (! empty($_POST['item_execute_at'])) {
                $query .= 'AT ' . $this->dbi->quoteString($_POST['item_execute_at']) . ' ';
            } else {
                $GLOBALS['errors'][] = __('You must provide a valid execution time for the event.');
            }
        } else {
            $GLOBALS['errors'][] = __('You must provide a valid type for the event.');
        }

        $query .= 'ON COMPLETION ';
        if (empty($_POST['item_preserve'])) {
            $query .= 'NOT ';
        }

        $query .= 'PRESERVE ';
        if (! empty($_POST['item_status'])) {
            foreach ($this->status['display'] as $key => $value) {
                if ($value == $_POST['item_status']) {
                    $query .= $this->status['query'][$key] . ' ';
                    break;
                }
            }
        }

        if (! empty($_POST['item_comment'])) {
            $query .= 'COMMENT ' . $this->dbi->quoteString($_POST['item_comment']) . ' ';
        }

        $query .= 'DO ';
        if (! empty($_POST['item_definition'])) {
            $query .= $_POST['item_definition'];
        } else {
            $GLOBALS['errors'][] = __('You must provide an event definition.');
        }

        return $query;
    }

    public function getEventSchedulerStatus(): bool
    {
        $state = (string) $this->dbi->fetchValue('SHOW GLOBAL VARIABLES LIKE \'event_scheduler\'', 1);

        return strtoupper($state) === 'ON' || $state === '1';
    }

    /**
     * @param string|null $createStatement Query
     * @param mixed[]     $errors          Errors
     *
     * @return mixed[]
     */
    private function checkResult(string|null $createStatement, array $errors): array
    {
        // OMG, this is really bad! We dropped the query,
        // failed to create a new one
        // and now even the backup query does not execute!
        // This should not happen, but we better handle
        // this just in case.
        $errors[] = __('Sorry, we failed to restore the dropped event.') . '<br>'
            . __('The backed up query was:')
            . '"' . htmlspecialchars((string) $createStatement) . '"<br>'
            . __('MySQL said: ') . $this->dbi->getError();

        return $errors;
    }

    /**
     * Send editor via ajax or by echoing.
     *
     * @param string       $mode      Editor mode 'add' or 'edit'
     * @param mixed[]|null $item      Data necessary to create the editor
     * @param string       $title     Title of the editor
     * @param string       $db        Database
     * @param string       $operation Operation 'change' or ''
     */
    private function sendEditor(string $mode, array|null $item, string $title, string $db, string $operation): void
    {
        if ($item !== null) {
            $editor = $this->getEditorForm($mode, $operation, $item);
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
            __('No event with name %1$s found in database %2$s.'),
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

    public function export(): void
    {
        if (empty($_GET['export_item']) || empty($_GET['item_name'])) {
            return;
        }

        $itemName = $_GET['item_name'];
        $exportData = self::getDefinition($this->dbi, $GLOBALS['db'], $itemName);

        if (! $exportData) {
            $exportData = false;
        }

        $itemName = htmlspecialchars(Util::backquote($itemName));
        if ($exportData !== false) {
            $exportData = htmlspecialchars(trim($exportData));
            $title = sprintf(__('Export of event %s'), $itemName);

            if ($this->response->isAjax()) {
                $this->response->addJSON('message', $exportData);
                $this->response->addJSON('title', $title);

                exit;
            }

            $output = '<div class="container">';
            $output .= '<h2>' . $title . '</h2>';
            $output .= '<div class="card"><div class="card-body">';
            $output .= '<textarea rows="15" class="form-control">' . $exportData . '</textarea>';
            $output .= '</div></div></div>';

            $this->response->addHTML($output);

            return;
        }

        $message = sprintf(
            __('Error in processing request: No event with name %1$s found in database %2$s.'),
            $itemName,
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
     * Returns details about the EVENTs for a specific database.
     *
     * @param string $db   db name
     * @param string $name event name
     *
     * @return mixed[] information about EVENTs
     */
    public function getDetails(string $db, string $name = ''): array
    {
        if (! $GLOBALS['cfg']['Server']['DisableIS']) {
            $query = QueryGenerator::getInformationSchemaEventsRequest(
                $this->dbi->quoteString($db),
                $name === '' ? null : $this->dbi->quoteString($name),
            );
        } else {
            $query = 'SHOW EVENTS FROM ' . Util::backquote($db);
            if ($name !== '') {
                $query .= ' WHERE `Name` = ' . $this->dbi->quoteString($name);
            }
        }

        $result = [];
        $events = $this->dbi->fetchResult($query);

        foreach ($events as $event) {
            $result[] = ['name' => $event['Name'], 'type' => $event['Type'], 'status' => $event['Status']];
        }

        // Sort results by name
        $name = array_column($result, 'name');
        array_multisort($name, SORT_ASC, $result);

        return $result;
    }

    public static function getDefinition(DatabaseInterface $dbi, string $db, string $name): string|null
    {
        $result = $dbi->fetchValue(
            'SHOW CREATE EVENT ' . Util::backquote($db) . '.' . Util::backquote($name),
            'Create Event',
        );

        return is_string($result) ? $result : null;
    }
}
