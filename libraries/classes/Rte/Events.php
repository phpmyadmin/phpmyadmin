<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Functions for event management.
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin\Rte;

use PhpMyAdmin\Message;
use PhpMyAdmin\Response;
use PhpMyAdmin\Rte\Export;
use PhpMyAdmin\Rte\Footer;
use PhpMyAdmin\Rte\General;
use PhpMyAdmin\Rte\RteList;
use PhpMyAdmin\Rte\Words;
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
     * Sets required globals
     *
     * @return void
     */
    public static function setGlobals()
    {
        global $event_status, $event_type, $event_interval;

        $event_status        = array(
                                   'query'   => array('ENABLE',
                                                      'DISABLE',
                                                      'DISABLE ON SLAVE'),
                                   'display' => array('ENABLED',
                                                      'DISABLED',
                                                      'SLAVESIDE_DISABLED')
                               );
        $event_type          = array('RECURRING',
                                     'ONE TIME');
        $event_interval      = array('YEAR',
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
                                     'MINUTE_SECOND');
    }

    /**
     * Main function for the events functionality
     *
     * @return void
     */
    public static function main()
    {
        global $db;

        self::setGlobals();
        /**
         * Process all requests
         */
        self::handleEditor();
        Export::events();
        /**
         * Display a list of available events
         */
        $items = $GLOBALS['dbi']->getEvents($db);
        echo RteList::get('event', $items);
        /**
         * Display a link for adding a new event, if
         * the user has the privileges and a link to
         * toggle the state of the event scheduler.
         */
        echo Footer::events();
    } // end self::main()

    /**
     * Handles editor requests for adding or editing an item
     *
     * @return void
     */
    public static function handleEditor()
    {
        global $_REQUEST, $_POST, $errors, $db;

        if (! empty($_REQUEST['editor_process_add'])
            || ! empty($_REQUEST['editor_process_edit'])
        ) {
            $sql_query = '';

            $item_query = self::getQueryFromRequest();

            if (! count($errors)) { // set by PhpMyAdmin\Rte\Routines::getQueryFromRequest()
                // Execute the created query
                if (! empty($_REQUEST['editor_process_edit'])) {
                    // Backup the old trigger, in case something goes wrong
                    $create_item = $GLOBALS['dbi']->getDefinition(
                        $db,
                        'EVENT',
                        $_REQUEST['item_original_name']
                    );
                    $drop_item = "DROP EVENT "
                        . Util::backquote($_REQUEST['item_original_name'])
                        . ";\n";
                    $result = $GLOBALS['dbi']->tryQuery($drop_item);
                    if (! $result) {
                        $errors[] = sprintf(
                            __('The following query has failed: "%s"'),
                            htmlspecialchars($drop_item)
                        )
                        . '<br />'
                        . __('MySQL said: ') . $GLOBALS['dbi']->getError(null);
                    } else {
                        $result = $GLOBALS['dbi']->tryQuery($item_query);
                        if (! $result) {
                            $errors[] = sprintf(
                                __('The following query has failed: "%s"'),
                                htmlspecialchars($item_query)
                            )
                            . '<br />'
                            . __('MySQL said: ') . $GLOBALS['dbi']->getError(null);
                            // We dropped the old item, but were unable to create
                            // the new one. Try to restore the backup query
                            $result = $GLOBALS['dbi']->tryQuery($create_item);
                            $errors = General::checkResult(
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
                                Util::backquote($_REQUEST['item_name'])
                            );
                            $sql_query = $drop_item . $item_query;
                        }
                    }
                } else {
                    // 'Add a new item' mode
                    $result = $GLOBALS['dbi']->tryQuery($item_query);
                    if (! $result) {
                        $errors[] = sprintf(
                            __('The following query has failed: "%s"'),
                            htmlspecialchars($item_query)
                        )
                        . '<br /><br />'
                        . __('MySQL said: ') . $GLOBALS['dbi']->getError(null);
                    } else {
                        $message = Message::success(
                            __('Event %1$s has been created.')
                        );
                        $message->addParam(
                            Util::backquote($_REQUEST['item_name'])
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
                    $events = $GLOBALS['dbi']->getEvents($db, $_REQUEST['item_name']);
                    $event = $events[0];
                    $response->addJSON(
                        'name',
                        htmlspecialchars(
                            mb_strtoupper($_REQUEST['item_name'])
                        )
                    );
                    if (! empty($event)) {
                        $response->addJSON('new_row', RteList::getEventRow($event));
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
            || (empty($_REQUEST['editor_process_add'])
            && empty($_REQUEST['editor_process_edit'])
            && (! empty($_REQUEST['add_item'])
            || ! empty($_REQUEST['edit_item'])
            || ! empty($_REQUEST['item_changetype'])))
        ) { // FIXME: this must be simpler than that
            $operation = '';
            if (! empty($_REQUEST['item_changetype'])) {
                $operation = 'change';
            }
            // Get the data for the form (if any)
            if (! empty($_REQUEST['add_item'])) {
                $title = Words::get('add');
                $item = self::getDataFromRequest();
                $mode = 'add';
            } elseif (! empty($_REQUEST['edit_item'])) {
                $title = __("Edit event");
                if (! empty($_REQUEST['item_name'])
                    && empty($_REQUEST['editor_process_edit'])
                    && empty($_REQUEST['item_changetype'])
                ) {
                    $item = self::getDataFromName($_REQUEST['item_name']);
                    if ($item !== false) {
                        $item['item_original_name'] = $item['item_name'];
                    }
                } else {
                    $item = self::getDataFromRequest();
                }
                $mode = 'edit';
            }
            General::sendEditor('EVN', $mode, $item, $title, $db, $operation);
        }
    } // end self::handleEditor()

    /**
     * This function will generate the values that are required to for the editor
     *
     * @return array    Data necessary to create the editor.
     */
    public static function getDataFromRequest()
    {
        $retval = array();
        $indices = array('item_name',
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
                         'item_definer');
        foreach ($indices as $index) {
            $retval[$index] = isset($_REQUEST[$index]) ? $_REQUEST[$index] : '';
        }
        $retval['item_type']        = 'ONE TIME';
        $retval['item_type_toggle'] = 'RECURRING';
        if (isset($_REQUEST['item_type']) && $_REQUEST['item_type'] == 'RECURRING') {
            $retval['item_type']        = 'RECURRING';
            $retval['item_type_toggle'] = 'ONE TIME';
        }
        return $retval;
    } // end self::getDataFromRequest()

    /**
     * This function will generate the values that are required to complete
     * the "Edit event" form given the name of a event.
     *
     * @param string $name The name of the event.
     *
     * @return array Data necessary to create the editor.
     */
    public static function getDataFromName($name)
    {
        global $db;

        $retval = array();
        $columns = "`EVENT_NAME`, `STATUS`, `EVENT_TYPE`, `EXECUTE_AT`, "
                 . "`INTERVAL_VALUE`, `INTERVAL_FIELD`, `STARTS`, `ENDS`, "
                 . "`EVENT_DEFINITION`, `ON_COMPLETION`, `DEFINER`, `EVENT_COMMENT`";
        $where   = "EVENT_SCHEMA " . Util::getCollateForIS() . "="
                 . "'" . $GLOBALS['dbi']->escapeString($db) . "' "
                 . "AND EVENT_NAME='" . $GLOBALS['dbi']->escapeString($name) . "'";
        $query   = "SELECT $columns FROM `INFORMATION_SCHEMA`.`EVENTS` WHERE $where;";
        $item    = $GLOBALS['dbi']->fetchSingleRow($query);
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
    } // end self::getDataFromName()

    /**
     * Displays a form used to add/edit an event
     *
     * @param string $mode      If the editor will be used to edit an event
     *                              or add a new one: 'edit' or 'add'.
     * @param string $operation If the editor was previously invoked with
     *                              JS turned off, this will hold the name of
     *                              the current operation
     * @param array  $item      Data for the event returned by
     *                              self::getDataFromRequest() or
     *                              self::getDataFromName()
     *
     * @return string   HTML code for the editor.
     */
    public static function getEditorForm($mode, $operation, array $item)
    {
        global $db, $table, $event_status, $event_type, $event_interval;

        $modeToUpper = mb_strtoupper($mode);

        $response = Response::getInstance();

        // Escape special characters
        $need_escape = array(
                           'item_original_name',
                           'item_name',
                           'item_type',
                           'item_execute_at',
                           'item_interval_value',
                           'item_starts',
                           'item_ends',
                           'item_definition',
                           'item_definer',
                           'item_comment'
                       );
        foreach ($need_escape as $index) {
            $item[$index] = htmlentities($item[$index], ENT_QUOTES);
        }
        $original_data = '';
        if ($mode == 'edit') {
            $original_data = "<input name='item_original_name' "
                           . "type='hidden' value='{$item['item_original_name']}'/>\n";
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
        $retval .= "<input name='{$mode}_item' type='hidden' value='1' />\n";
        $retval .= $original_data;
        $retval .= Url::getHiddenInputs($db, $table) . "\n";
        $retval .= "<fieldset>\n";
        $retval .= "<legend>" . __('Details') . "</legend>\n";
        $retval .= "<table class='rte_table' style='width: 100%'>\n";
        $retval .= "<tr>\n";
        $retval .= "    <td style='width: 20%;'>" . __('Event name') . "</td>\n";
        $retval .= "    <td><input type='text' name='item_name' \n";
        $retval .= "               value='{$item['item_name']}'\n";
        $retval .= "               maxlength='64' /></td>\n";
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
            $retval .= "               value='{$item['item_type']}' />\n";
            $retval .= "        <div class='floatleft' style='width: 49%; "
                . "text-align: center; font-weight: bold;'>\n";
            $retval .= "            {$item['item_type']}\n";
            $retval .= "        </div>\n";
            $retval .= "        <input style='width: 49%;' type='submit'\n";
            $retval .= "               name='item_changetype'\n";
            $retval .= "               value='";
            $retval .= sprintf(__('Change to %s'), $item['item_type_toggle']);
            $retval .= "' />\n";
        }
        $retval .= "    </td>\n";
        $retval .= "</tr>\n";
        $retval .= "<tr class='onetime_event_row $isonetime_class'>\n";
        $retval .= "    <td>" . __('Execute at') . "</td>\n";
        $retval .= "    <td class='nowrap'>\n";
        $retval .= "        <input type='text' name='item_execute_at'\n";
        $retval .= "               value='{$item['item_execute_at']}'\n";
        $retval .= "               class='datetimefield' />\n";
        $retval .= "    </td>\n";
        $retval .= "</tr>\n";
        $retval .= "<tr class='recurring_event_row $isrecurring_class'>\n";
        $retval .= "    <td>" . __('Execute every') . "</td>\n";
        $retval .= "    <td>\n";
        $retval .= "        <input style='width: 49%;' type='text'\n";
        $retval .= "               name='item_interval_value'\n";
        $retval .= "               value='{$item['item_interval_value']}' />\n";
        $retval .= "        <select style='width: 49%;' name='item_interval_field'>";
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
        $retval .= "               class='datetimefield' />\n";
        $retval .= "    </td>\n";
        $retval .= "</tr>\n";
        $retval .= "<tr class='recurring_event_row$isrecurring_class'>\n";
        $retval .= "    <td>" . _pgettext('End of recurring event', 'End') . "</td>\n";
        $retval .= "    <td class='nowrap'>\n";
        $retval .= "        <input type='text' name='item_ends'\n";
        $retval .= "               value='{$item['item_ends']}'\n";
        $retval .= "               class='datetimefield' />\n";
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
        $retval .= "             name='item_preserve'{$item['item_preserve']} /></td>\n";
        $retval .= "</tr>\n";
        $retval .= "<tr>\n";
        $retval .= "    <td>" . __('Definer') . "</td>\n";
        $retval .= "    <td><input type='text' name='item_definer'\n";
        $retval .= "               value='{$item['item_definer']}' /></td>\n";
        $retval .= "</tr>\n";
        $retval .= "<tr>\n";
        $retval .= "    <td>" . __('Comment') . "</td>\n";
        $retval .= "    <td><input type='text' name='item_comment' maxlength='64'\n";
        $retval .= "               value='{$item['item_comment']}' /></td>\n";
        $retval .= "</tr>\n";
        $retval .= "</table>\n";
        $retval .= "</fieldset>\n";
        if ($response->isAjax()) {
            $retval .= "<input type='hidden' name='editor_process_{$mode}'\n";
            $retval .= "       value='true' />\n";
            $retval .= "<input type='hidden' name='ajax_request' value='true' />\n";
        } else {
            $retval .= "<fieldset class='tblFooters'>\n";
            $retval .= "    <input type='submit' name='editor_process_{$mode}'\n";
            $retval .= "           value='" . __('Go') . "' />\n";
            $retval .= "</fieldset>\n";
        }
        $retval .= "</form>\n\n";
        $retval .= "<!-- END " . $modeToUpper . " EVENT FORM -->\n\n";

        return $retval;
    } // end self::getEditorForm()

    /**
     * Composes the query necessary to create an event from an HTTP request.
     *
     * @return string  The CREATE EVENT query.
     */
    public static function getQueryFromRequest()
    {
        global $_REQUEST, $errors, $event_status, $event_type, $event_interval;

        $query = 'CREATE ';
        if (! empty($_REQUEST['item_definer'])) {
            if (mb_strpos($_REQUEST['item_definer'], '@') !== false
            ) {
                $arr = explode('@', $_REQUEST['item_definer']);
                $query .= 'DEFINER=' . Util::backquote($arr[0]);
                $query .= '@' . Util::backquote($arr[1]) . ' ';
            } else {
                $errors[] = __('The definer must be in the "username@hostname" format!');
            }
        }
        $query .= 'EVENT ';
        if (! empty($_REQUEST['item_name'])) {
            $query .= Util::backquote($_REQUEST['item_name']) . ' ';
        } else {
            $errors[] = __('You must provide an event name!');
        }
        $query .= 'ON SCHEDULE ';
        if (! empty($_REQUEST['item_type'])
            && in_array($_REQUEST['item_type'], $event_type)
        ) {
            if ($_REQUEST['item_type'] == 'RECURRING') {
                if (! empty($_REQUEST['item_interval_value'])
                    && !empty($_REQUEST['item_interval_field'])
                    && in_array($_REQUEST['item_interval_field'], $event_interval)
                ) {
                    $query .= 'EVERY ' . intval($_REQUEST['item_interval_value']) . ' ';
                    $query .= $_REQUEST['item_interval_field'] . ' ';
                } else {
                    $errors[]
                        = __('You must provide a valid interval value for the event.');
                }
                if (! empty($_REQUEST['item_starts'])) {
                    $query .= "STARTS '"
                        . $GLOBALS['dbi']->escapeString($_REQUEST['item_starts'])
                        . "' ";
                }
                if (! empty($_REQUEST['item_ends'])) {
                    $query .= "ENDS '"
                        . $GLOBALS['dbi']->escapeString($_REQUEST['item_ends'])
                        . "' ";
                }
            } else {
                if (! empty($_REQUEST['item_execute_at'])) {
                    $query .= "AT '"
                        . $GLOBALS['dbi']->escapeString($_REQUEST['item_execute_at'])
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
        if (empty($_REQUEST['item_preserve'])) {
            $query .= 'NOT ';
        }
        $query .= 'PRESERVE ';
        if (! empty($_REQUEST['item_status'])) {
            foreach ($event_status['display'] as $key => $value) {
                if ($value == $_REQUEST['item_status']) {
                    $query .= $event_status['query'][$key] . ' ';
                    break;
                }
            }
        }
        if (! empty($_REQUEST['item_comment'])) {
            $query .= "COMMENT '" . $GLOBALS['dbi']->escapeString(
                $_REQUEST['item_comment']
            ) . "' ";
        }
        $query .= 'DO ';
        if (! empty($_REQUEST['item_definition'])) {
            $query .= $_REQUEST['item_definition'];
        } else {
            $errors[] = __('You must provide an event definition.');
        }

        return $query;
    } // end self::getQueryFromRequest()
}
