<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Functions for event management.
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin\Rte;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Message;
use PhpMyAdmin\Response;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

/**
 * PhpMyAdmin\Rte\Events class
 *
 * @package PhpMyAdmin
 */
class Events
{
    /**
     * @var Export
     */
    private $export;

    /**
     * @var Footer
     */
    private $footer;

    /**
     * @var General
     */
    private $general;

    /**
     * @var RteList
     */
    private $rteList;

    /**
     * @var Words
     */
    private $words;

    /**
     * @var DatabaseInterface
     */
    private $dbi;

    /**
     * Events constructor.
     *
     * @param DatabaseInterface $dbi DatabaseInterface object
     */
    public function __construct(DatabaseInterface $dbi)
    {
        $this->dbi = $dbi;
        $this->export = new Export($this->dbi);
        $this->footer = new Footer($this->dbi);
        $this->general = new General($this->dbi);
        $this->rteList = new RteList($this->dbi);
        $this->words = new Words();
    }

    /**
     * Sets required globals
     *
     * @return void
     */
    public function setGlobals()
    {
        global $event_status, $event_type, $event_interval;

        $event_status = [
            'query' => [
                'ENABLE',
                'DISABLE',
                'DISABLE ON SLAVE',
            ],
            'display' => [
                'ENABLED',
                'DISABLED',
                'SLAVESIDE_DISABLED',
            ],
        ];
        $event_type = [
            'RECURRING',
            'ONE TIME',
        ];
        $event_interval = [
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
    }

    /**
     * Main function for the events functionality
     *
     * @return void
     */
    public function main()
    {
        global $db;

        $this->setGlobals();
        /**
         * Process all requests
         */
        $this->handleEditor();
        $this->export->events();
        /**
         * Display a list of available events
         */
        $items = $this->dbi->getEvents($db);
        echo $this->rteList->get('event', $items);
        /**
         * Display a link for adding a new event, if
         * the user has the privileges and a link to
         * toggle the state of the event scheduler.
         */
        echo $this->footer->events();
    }

    /**
     * Handles editor requests for adding or editing an item
     *
     * @return void
     */
    public function handleEditor()
    {
        global $errors, $db;

        if (! empty($_POST['editor_process_add'])
            || ! empty($_POST['editor_process_edit'])
        ) {
            $sql_query = '';

            $item_query = $this->getQueryFromRequest();

            if (! count($errors)) { // set by PhpMyAdmin\Rte\Routines::getQueryFromRequest()
                // Execute the created query
                if (! empty($_POST['editor_process_edit'])) {
                    // Backup the old trigger, in case something goes wrong
                    $create_item = $this->dbi->getDefinition(
                        $db,
                        'EVENT',
                        $_POST['item_original_name']
                    );
                    $drop_item = "DROP EVENT "
                        . Util::backquote($_POST['item_original_name'])
                        . ";\n";
                    $result = $this->dbi->tryQuery($drop_item);
                    if (! $result) {
                        $errors[] = sprintf(
                            __('The following query has failed: "%s"'),
                            htmlspecialchars($drop_item)
                        )
                        . '<br>'
                        . __('MySQL said: ') . $this->dbi->getError();
                    } else {
                        $result = $this->dbi->tryQuery($item_query);
                        if (! $result) {
                            $errors[] = sprintf(
                                __('The following query has failed: "%s"'),
                                htmlspecialchars($item_query)
                            )
                            . '<br>'
                            . __('MySQL said: ') . $this->dbi->getError();
                            // We dropped the old item, but were unable to create
                            // the new one. Try to restore the backup query
                            $result = $this->dbi->tryQuery($create_item);
                            $errors = $this->general->checkResult(
                                $result,
                                __(
                                    'Sorry, we failed to restore the dropped event.'
                                ),
                                $create_item,
                                $errors
                            );
                        } else {
                            $message = Message::success(
                                __('Event %1$s has been modified.')
                            );
                            $message->addParam(
                                Util::backquote($_POST['item_name'])
                            );
                            $sql_query = $drop_item . $item_query;
                        }
                    }
                } else {
                    // 'Add a new item' mode
                    $result = $this->dbi->tryQuery($item_query);
                    if (! $result) {
                        $errors[] = sprintf(
                            __('The following query has failed: "%s"'),
                            htmlspecialchars($item_query)
                        )
                        . '<br><br>'
                        . __('MySQL said: ') . $this->dbi->getError();
                    } else {
                        $message = Message::success(
                            __('Event %1$s has been created.')
                        );
                        $message->addParam(
                            Util::backquote($_POST['item_name'])
                        );
                        $sql_query = $item_query;
                    }
                }
            }

            if (count($errors)) {
                $message = Message::error(
                    '<b>'
                    . __(
                        'One or more errors have occurred while processing your request:'
                    )
                    . '</b>'
                );
                $message->addHtml('<ul>');
                foreach ($errors as $string) {
                    $message->addHtml('<li>' . $string . '</li>');
                }
                $message->addHtml('</ul>');
            }

            $output = Util::getMessage($message, $sql_query);
            $response = Response::getInstance();
            if ($response->isAjax()) {
                if ($message->isSuccess()) {
                    $events = $this->dbi->getEvents($db, $_POST['item_name']);
                    $event = $events[0];
                    $response->addJSON(
                        'name',
                        htmlspecialchars(
                            mb_strtoupper($_POST['item_name'])
                        )
                    );
                    if (! empty($event)) {
                        $response->addJSON('new_row', $this->rteList->getEventRow($event));
                    }
                    $response->addJSON('insert', ! empty($event));
                    $response->addJSON('message', $output);
                } else {
                    $response->setRequestStatus(false);
                    $response->addJSON('message', $message);
                }
                exit;
            }
        }
        /**
         * Display a form used to add/edit a trigger, if necessary
         */
        if (count($errors)
            || (empty($_POST['editor_process_add'])
            && empty($_POST['editor_process_edit'])
            && (! empty($_REQUEST['add_item'])
            || ! empty($_REQUEST['edit_item'])
            || ! empty($_POST['item_changetype'])))
        ) { // FIXME: this must be simpler than that
            $operation = '';
            if (! empty($_POST['item_changetype'])) {
                $operation = 'change';
            }
            // Get the data for the form (if any)
            if (! empty($_REQUEST['add_item'])) {
                $title = $this->words->get('add');
                $item = $this->getDataFromRequest();
                $mode = 'add';
            } elseif (! empty($_REQUEST['edit_item'])) {
                $title = __("Edit event");
                if (! empty($_REQUEST['item_name'])
                    && empty($_POST['editor_process_edit'])
                    && empty($_POST['item_changetype'])
                ) {
                    $item = $this->getDataFromName($_REQUEST['item_name']);
                    if ($item !== false) {
                        $item['item_original_name'] = $item['item_name'];
                    }
                } else {
                    $item = $this->getDataFromRequest();
                }
                $mode = 'edit';
            }
            $this->general->sendEditor('EVN', $mode, $item, $title, $db, $operation);
        }
    }

    /**
     * This function will generate the values that are required to for the editor
     *
     * @return array    Data necessary to create the editor.
     */
    public function getDataFromRequest()
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
            $retval[$index] = isset($_POST[$index]) ? $_POST[$index] : '';
        }
        $retval['item_type']        = 'ONE TIME';
        $retval['item_type_toggle'] = 'RECURRING';
        if (isset($_POST['item_type']) && $_POST['item_type'] == 'RECURRING') {
            $retval['item_type']        = 'RECURRING';
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
     * @return array|bool Data necessary to create the editor.
     */
    public function getDataFromName($name)
    {
        global $db;

        $retval = [];
        $columns = "`EVENT_NAME`, `STATUS`, `EVENT_TYPE`, `EXECUTE_AT`, "
                 . "`INTERVAL_VALUE`, `INTERVAL_FIELD`, `STARTS`, `ENDS`, "
                 . "`EVENT_DEFINITION`, `ON_COMPLETION`, `DEFINER`, `EVENT_COMMENT`";
        $where   = "EVENT_SCHEMA " . Util::getCollateForIS() . "="
                 . "'" . $this->dbi->escapeString($db) . "' "
                 . "AND EVENT_NAME='" . $this->dbi->escapeString($name) . "'";
        $query   = "SELECT $columns FROM `INFORMATION_SCHEMA`.`EVENTS` WHERE $where;";
        $item    = $this->dbi->fetchSingleRow($query);
        if (! $item) {
            return false;
        }
        $retval['item_name']   = $item['EVENT_NAME'];
        $retval['item_status'] = $item['STATUS'];
        $retval['item_type']   = $item['EVENT_TYPE'];
        if ($retval['item_type'] == 'RECURRING') {
            $retval['item_type_toggle'] = 'ONE TIME';
        } else {
            $retval['item_type_toggle'] = 'RECURRING';
        }
        $retval['item_execute_at']     = $item['EXECUTE_AT'];
        $retval['item_interval_value'] = $item['INTERVAL_VALUE'];
        $retval['item_interval_field'] = $item['INTERVAL_FIELD'];
        $retval['item_starts']         = $item['STARTS'];
        $retval['item_ends']           = $item['ENDS'];
        $retval['item_preserve']       = '';
        if ($item['ON_COMPLETION'] == 'PRESERVE') {
            $retval['item_preserve']   = " checked='checked'";
        }
        $retval['item_definition'] = $item['EVENT_DEFINITION'];
        $retval['item_definer']    = $item['DEFINER'];
        $retval['item_comment']    = $item['EVENT_COMMENT'];

        return $retval;
    }

    /**
     * Displays a form used to add/edit an event
     *
     * @param string $mode      If the editor will be used to edit an event
     *                          or add a new one: 'edit' or 'add'.
     * @param string $operation If the editor was previously invoked with
     *                          JS turned off, this will hold the name of
     *                          the current operation
     * @param array  $item      Data for the event returned by
     *                          getDataFromRequest() or getDataFromName()
     *
     * @return string   HTML code for the editor.
     */
    public function getEditorForm($mode, $operation, array $item)
    {
        global $db, $table, $event_status, $event_type, $event_interval;

        $modeToUpper = mb_strtoupper($mode);

        $response = Response::getInstance();

        // Escape special characters
        $need_escape = [
            'item_original_name',
            'item_name',
            'item_type',
            'item_execute_at',
            'item_interval_value',
            'item_starts',
            'item_ends',
            'item_definition',
            'item_definer',
            'item_comment',
        ];
        foreach ($need_escape as $index) {
            $item[$index] = htmlentities((string) $item[$index], ENT_QUOTES);
        }
        $original_data = '';
        if ($mode == 'edit') {
            $original_data = "<input name='item_original_name' "
                           . "type='hidden' value='{$item['item_original_name']}'>\n";
        }
        // Handle some logic first
        if ($operation == 'change') {
            if ($item['item_type'] == 'RECURRING') {
                $item['item_type']         = 'ONE TIME';
                $item['item_type_toggle']  = 'RECURRING';
            } else {
                $item['item_type']         = 'RECURRING';
                $item['item_type_toggle']  = 'ONE TIME';
            }
        }
        if ($item['item_type'] == 'ONE TIME') {
            $isrecurring_class = ' hide';
            $isonetime_class   = '';
        } else {
            $isrecurring_class = '';
            $isonetime_class   = ' hide';
        }
        // Create the output
        $retval  = "";
        $retval .= "<!-- START " . $modeToUpper . " EVENT FORM -->\n\n";
        $retval .= "<form class='rte_form' action='db_events.php' method='post'>\n";
        $retval .= "<input name='{$mode}_item' type='hidden' value='1'>\n";
        $retval .= $original_data;
        $retval .= Url::getHiddenInputs($db, $table) . "\n";
        $retval .= "<fieldset>\n";
        $retval .= "<legend>" . __('Details') . "</legend>\n";
        $retval .= "<table class='rte_table'>\n";
        $retval .= "<tr>\n";
        $retval .= "    <td>" . __('Event name') . "</td>\n";
        $retval .= "    <td><input type='text' name='item_name' \n";
        $retval .= "               value='{$item['item_name']}'\n";
        $retval .= "               maxlength='64'></td>\n";
        $retval .= "</tr>\n";
        $retval .= "<tr>\n";
        $retval .= "    <td>" . __('Status') . "</td>\n";
        $retval .= "    <td>\n";
        $retval .= "        <select name='item_status'>\n";
        foreach ($event_status['display'] as $key => $value) {
            $selected = "";
            if (! empty($item['item_status']) && $item['item_status'] == $value) {
                $selected = " selected='selected'";
            }
            $retval .= "<option$selected>$value</option>";
        }
        $retval .= "        </select>\n";
        $retval .= "    </td>\n";
        $retval .= "</tr>\n";

        $retval .= "<tr>\n";
        $retval .= "    <td>" . __('Event type') . "</td>\n";
        $retval .= "    <td>\n";
        if ($response->isAjax()) {
            $retval .= "        <select name='item_type'>";
            foreach ($event_type as $key => $value) {
                $selected = "";
                if (! empty($item['item_type']) && $item['item_type'] == $value) {
                    $selected = " selected='selected'";
                }
                $retval .= "<option$selected>$value</option>";
            }
            $retval .= "        </select>\n";
        } else {
            $retval .= "        <input name='item_type' type='hidden' \n";
            $retval .= "               value='{$item['item_type']}'>\n";
            $retval .= "        <div class='font_weight_bold center half_width'>\n";
            $retval .= "            {$item['item_type']}\n";
            $retval .= "        </div>\n";
            $retval .= "        <input type='submit'\n";
            $retval .= "               name='item_changetype' class='half_width'\n";
            $retval .= "               value='";
            $retval .= sprintf(__('Change to %s'), $item['item_type_toggle']);
            $retval .= "'>\n";
        }
        $retval .= "    </td>\n";
        $retval .= "</tr>\n";
        $retval .= "<tr class='onetime_event_row $isonetime_class'>\n";
        $retval .= "    <td>" . __('Execute at') . "</td>\n";
        $retval .= "    <td class='nowrap'>\n";
        $retval .= "        <input type='text' name='item_execute_at'\n";
        $retval .= "               value='{$item['item_execute_at']}'\n";
        $retval .= "               class='datetimefield'>\n";
        $retval .= "    </td>\n";
        $retval .= "</tr>\n";
        $retval .= "<tr class='recurring_event_row $isrecurring_class'>\n";
        $retval .= "    <td>" . __('Execute every') . "</td>\n";
        $retval .= "    <td>\n";
        $retval .= "        <input class='half_width' type='text'\n";
        $retval .= "               name='item_interval_value'\n";
        $retval .= "               value='{$item['item_interval_value']}'>\n";
        $retval .= "        <select class='half_width' name='item_interval_field'>";
        foreach ($event_interval as $key => $value) {
            $selected = "";
            if (! empty($item['item_interval_field'])
                && $item['item_interval_field'] == $value
            ) {
                $selected = " selected='selected'";
            }
            $retval .= "<option$selected>$value</option>";
        }
        $retval .= "        </select>\n";
        $retval .= "    </td>\n";
        $retval .= "</tr>\n";
        $retval .= "<tr class='recurring_event_row$isrecurring_class'>\n";
        $retval .= "    <td>" . _pgettext('Start of recurring event', 'Start');
        $retval .= "    </td>\n";
        $retval .= "    <td class='nowrap'>\n";
        $retval .= "        <input type='text'\n name='item_starts'\n";
        $retval .= "               value='{$item['item_starts']}'\n";
        $retval .= "               class='datetimefield'>\n";
        $retval .= "    </td>\n";
        $retval .= "</tr>\n";
        $retval .= "<tr class='recurring_event_row$isrecurring_class'>\n";
        $retval .= "    <td>" . _pgettext('End of recurring event', 'End') . "</td>\n";
        $retval .= "    <td class='nowrap'>\n";
        $retval .= "        <input type='text' name='item_ends'\n";
        $retval .= "               value='{$item['item_ends']}'\n";
        $retval .= "               class='datetimefield'>\n";
        $retval .= "    </td>\n";
        $retval .= "</tr>\n";
        $retval .= "<tr>\n";
        $retval .= "    <td>" . __('Definition') . "</td>\n";
        $retval .= "    <td><textarea name='item_definition' rows='15' cols='40'>";
        $retval .= $item['item_definition'];
        $retval .= "</textarea></td>\n";
        $retval .= "</tr>\n";
        $retval .= "<tr>\n";
        $retval .= "    <td>" . __('On completion preserve') . "</td>\n";
        $retval .= "    <td><input type='checkbox'\n";
        $retval .= "             name='item_preserve'{$item['item_preserve']}></td>\n";
        $retval .= "</tr>\n";
        $retval .= "<tr>\n";
        $retval .= "    <td>" . __('Definer') . "</td>\n";
        $retval .= "    <td><input type='text' name='item_definer'\n";
        $retval .= "               value='{$item['item_definer']}'></td>\n";
        $retval .= "</tr>\n";
        $retval .= "<tr>\n";
        $retval .= "    <td>" . __('Comment') . "</td>\n";
        $retval .= "    <td><input type='text' name='item_comment' maxlength='64'\n";
        $retval .= "               value='{$item['item_comment']}'></td>\n";
        $retval .= "</tr>\n";
        $retval .= "</table>\n";
        $retval .= "</fieldset>\n";
        if ($response->isAjax()) {
            $retval .= "<input type='hidden' name='editor_process_{$mode}'\n";
            $retval .= "       value='true'>\n";
            $retval .= "<input type='hidden' name='ajax_request' value='true'>\n";
        } else {
            $retval .= "<fieldset class='tblFooters'>\n";
            $retval .= "    <input type='submit' name='editor_process_{$mode}'\n";
            $retval .= "           value='" . __('Go') . "'>\n";
            $retval .= "</fieldset>\n";
        }
        $retval .= "</form>\n\n";
        $retval .= "<!-- END " . $modeToUpper . " EVENT FORM -->\n\n";

        return $retval;
    }

    /**
     * Composes the query necessary to create an event from an HTTP request.
     *
     * @return string  The CREATE EVENT query.
     */
    public function getQueryFromRequest()
    {
        global $errors, $event_status, $event_type, $event_interval;

        $query = 'CREATE ';
        if (! empty($_POST['item_definer'])) {
            if (mb_strpos($_POST['item_definer'], '@') !== false
            ) {
                $arr = explode('@', $_POST['item_definer']);
                $query .= 'DEFINER=' . Util::backquote($arr[0]);
                $query .= '@' . Util::backquote($arr[1]) . ' ';
            } else {
                $errors[] = __('The definer must be in the "username@hostname" format!');
            }
        }
        $query .= 'EVENT ';
        if (! empty($_POST['item_name'])) {
            $query .= Util::backquote($_POST['item_name']) . ' ';
        } else {
            $errors[] = __('You must provide an event name!');
        }
        $query .= 'ON SCHEDULE ';
        if (! empty($_POST['item_type'])
            && in_array($_POST['item_type'], $event_type)
        ) {
            if ($_POST['item_type'] == 'RECURRING') {
                if (! empty($_POST['item_interval_value'])
                    && ! empty($_POST['item_interval_field'])
                    && in_array($_POST['item_interval_field'], $event_interval)
                ) {
                    $query .= 'EVERY ' . intval($_POST['item_interval_value']) . ' ';
                    $query .= $_POST['item_interval_field'] . ' ';
                } else {
                    $errors[]
                        = __('You must provide a valid interval value for the event.');
                }
                if (! empty($_POST['item_starts'])) {
                    $query .= "STARTS '"
                        . $this->dbi->escapeString($_POST['item_starts'])
                        . "' ";
                }
                if (! empty($_POST['item_ends'])) {
                    $query .= "ENDS '"
                        . $this->dbi->escapeString($_POST['item_ends'])
                        . "' ";
                }
            } else {
                if (! empty($_POST['item_execute_at'])) {
                    $query .= "AT '"
                        . $this->dbi->escapeString($_POST['item_execute_at'])
                        . "' ";
                } else {
                    $errors[]
                        = __('You must provide a valid execution time for the event.');
                }
            }
        } else {
            $errors[] = __('You must provide a valid type for the event.');
        }
        $query .= 'ON COMPLETION ';
        if (empty($_POST['item_preserve'])) {
            $query .= 'NOT ';
        }
        $query .= 'PRESERVE ';
        if (! empty($_POST['item_status'])) {
            foreach ($event_status['display'] as $key => $value) {
                if ($value == $_POST['item_status']) {
                    $query .= $event_status['query'][$key] . ' ';
                    break;
                }
            }
        }
        if (! empty($_POST['item_comment'])) {
            $query .= "COMMENT '" . $this->dbi->escapeString(
                $_POST['item_comment']
            ) . "' ";
        }
        $query .= 'DO ';
        if (! empty($_POST['item_definition'])) {
            $query .= $_POST['item_definition'];
        } else {
            $errors[] = __('You must provide an event definition.');
        }

        return $query;
    }
}
