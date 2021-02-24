<?php

declare(strict_types=1);

namespace PhpMyAdmin\Database;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Message;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use const ENT_QUOTES;
use function count;
use function explode;
use function htmlentities;
use function htmlspecialchars;
use function in_array;
use function mb_strpos;
use function mb_strtoupper;
use function sprintf;
use function trim;

/**
 * Functions for trigger management.
 */
class Triggers
{
    /** @var array<int, string> */
    private $time = ['BEFORE', 'AFTER'];

    /** @var array<int, string> */
    private $event = ['INSERT', 'UPDATE', 'DELETE'];

    /** @var DatabaseInterface */
    private $dbi;

    /** @var Template */
    private $template;

    /** @var Response */
    private $response;

    /**
     * @param DatabaseInterface $dbi      DatabaseInterface instance.
     * @param Template          $template Template instance.
     * @param Response          $response Response instance.
     */
    public function __construct(DatabaseInterface $dbi, Template $template, $response)
    {
        $this->dbi = $dbi;
        $this->template = $template;
        $this->response = $response;
    }

    /**
     * Main function for the triggers functionality
     *
     * @return void
     */
    public function main()
    {
        global $db, $table, $text_dir, $PMA_Theme;

        /**
         * Process all requests
         */
        $this->handleEditor();
        $this->export();

        $items = $this->dbi->getTriggers($db, $table);
        $hasDropPrivilege = Util::currentUserHasPrivilege('TRIGGER', $db);
        $hasEditPrivilege = Util::currentUserHasPrivilege('TRIGGER', $db, $table);
        $isAjax = $this->response->isAjax() && empty($_REQUEST['ajax_page_request']);

        $rows = '';
        foreach ($items as $item) {
            $rows .= $this->template->render('database/triggers/row', [
                'db' => $db,
                'table' => $table,
                'trigger' => $item,
                'has_drop_privilege' => $hasDropPrivilege,
                'has_edit_privilege' => $hasEditPrivilege,
                'row_class' => $isAjax ? 'ajaxInsert hide' : '',
            ]);
        }

        echo $this->template->render('database/triggers/list', [
            'db' => $db,
            'table' => $table,
            'items' => $items,
            'rows' => $rows,
            'select_all_arrow_src' => $PMA_Theme->getImgPath() . 'arrow_' . $text_dir . '.png',
        ]);

        echo $this->template->render('database/triggers/footer', [
            'db' => $db,
            'table' => $table,
            'has_privilege' => Util::currentUserHasPrivilege('TRIGGER', $db, $table),
        ]);
    }

    /**
     * Handles editor requests for adding or editing an item
     *
     * @return void
     */
    public function handleEditor()
    {
        global $db, $errors, $message, $table;

        if (! empty($_POST['editor_process_add'])
            || ! empty($_POST['editor_process_edit'])
        ) {
            $sql_query = '';

            $item_query = $this->getQueryFromRequest();

            // set by getQueryFromRequest()
            if (! count($errors)) {
                // Execute the created query
                if (! empty($_POST['editor_process_edit'])) {
                    // Backup the old trigger, in case something goes wrong
                    $trigger = $this->getDataFromName($_POST['item_original_name']);
                    $create_item = $trigger['create'];
                    $drop_item = $trigger['drop'] . ';';
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
                            // We dropped the old item, but were unable to create the
                            // new one. Try to restore the backup query.
                            $result = $this->dbi->tryQuery($create_item);

                            $errors = $this->checkResult($result, $create_item, $errors);
                        } else {
                            $message = Message::success(
                                __('Trigger %1$s has been modified.')
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
                            __('Trigger %1$s has been created.')
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

            $output = Generator::getMessage($message, $sql_query);

            if ($this->response->isAjax()) {
                if ($message->isSuccess()) {
                    $items = $this->dbi->getTriggers($db, $table, '');
                    $trigger = false;
                    foreach ($items as $value) {
                        if ($value['name'] != $_POST['item_name']) {
                            continue;
                        }

                        $trigger = $value;
                    }
                    $insert = false;
                    if (empty($table)
                        || ($trigger !== false && $table == $trigger['table'])
                    ) {
                        $insert = true;
                        $this->response->addJSON(
                            'new_row',
                            $this->template->render('database/triggers/row', [
                                'db' => $db,
                                'table' => $table,
                                'trigger' => $trigger,
                                'has_drop_privilege' => Util::currentUserHasPrivilege('TRIGGER', $db),
                                'has_edit_privilege' => Util::currentUserHasPrivilege('TRIGGER', $db, $table),
                                'row_class' => '',
                            ])
                        );
                        $this->response->addJSON(
                            'name',
                            htmlspecialchars(
                                mb_strtoupper(
                                    $_POST['item_name']
                                )
                            )
                        );
                    }
                    $this->response->addJSON('insert', $insert);
                    $this->response->addJSON('message', $output);
                } else {
                    $this->response->addJSON('message', $message);
                    $this->response->setRequestStatus(false);
                }
                exit;
            }
        }

        /**
         * Display a form used to add/edit a trigger, if necessary
         */
        if (! count($errors)
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
            if (! empty($_REQUEST['item_name'])
                && empty($_POST['editor_process_edit'])
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
        $this->sendEditor($mode, $item, $title, $db);
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
     * @return array|null Data necessary to create the editor.
     */
    public function getDataFromName($name): ?array
    {
        global $db, $table;

        $temp = [];
        $items = $this->dbi->getTriggers($db, $table, '');
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
        $retval['create']                  = $temp['create'];
        $retval['drop']                    = $temp['drop'];
        $retval['item_name']               = $temp['name'];
        $retval['item_table']              = $temp['table'];
        $retval['item_action_timing']      = $temp['action_timing'];
        $retval['item_event_manipulation'] = $temp['event_manipulation'];
        $retval['item_definition']         = $temp['definition'];
        $retval['item_definer']            = $temp['definer'];

        return $retval;
    }

    /**
     * Displays a form used to add/edit a trigger
     *
     * @param string $mode If the editor will be used to edit a trigger
     *                     or add a new one: 'edit' or 'add'.
     * @param array  $item Data for the trigger returned by getDataFromRequest()
     *                     or getDataFromName()
     *
     * @return string HTML code for the editor.
     */
    public function getEditorForm($mode, array $item)
    {
        global $db, $table;

        $modeToUpper = mb_strtoupper($mode);

        // Escape special characters
        $need_escape = [
            'item_original_name',
            'item_name',
            'item_definition',
            'item_definer',
        ];
        foreach ($need_escape as $key => $index) {
            $item[$index] = htmlentities($item[$index], ENT_QUOTES, 'UTF-8');
        }
        $original_data = '';
        if ($mode === 'edit') {
            $original_data = "<input name='item_original_name' "
                           . "type='hidden' value='" . $item['item_original_name'] . "'>\n";
        }
        $query  = 'SELECT `TABLE_NAME` FROM `INFORMATION_SCHEMA`.`TABLES` ';
        $query .= "WHERE `TABLE_SCHEMA`='" . $this->dbi->escapeString($db) . "' ";
        $query .= "AND `TABLE_TYPE` IN ('BASE TABLE', 'SYSTEM VERSIONED')";
        $tables = $this->dbi->fetchResult($query);

        // Create the output
        $retval  = '';
        $retval .= '<!-- START ' . $modeToUpper . " TRIGGER FORM -->\n\n";
        $retval .= '<form class="rte_form" action="' . Url::getFromRoute('/database/triggers')
            . '" method="post">' . "\n";
        $retval .= "<input name='" . $mode . "_item' type='hidden' value='1'>\n";
        $retval .= $original_data;
        $retval .= Url::getHiddenInputs($db, $table) . "\n";
        $retval .= "<fieldset>\n";
        $retval .= '<legend>' . __('Details') . "</legend>\n";
        $retval .= "<table class='rte_table table table-borderless table-sm'>\n";
        $retval .= "<tr>\n";
        $retval .= '    <td>' . __('Trigger name') . "</td>\n";
        $retval .= "    <td><input type='text' name='item_name' maxlength='64'\n";
        $retval .= "               value='" . $item['item_name'] . "'></td>\n";
        $retval .= "</tr>\n";
        $retval .= "<tr>\n";
        $retval .= '    <td>' . __('Table') . "</td>\n";
        $retval .= "    <td>\n";
        $retval .= "        <select name='item_table'>\n";
        foreach ($tables as $key => $value) {
            $selected = '';
            if ($mode === 'add' && $value == $table) {
                $selected = " selected='selected'";
            } elseif ($mode === 'edit' && $value == $item['item_table']) {
                $selected = " selected='selected'";
            }
            $retval .= '<option' . $selected . '>';
            $retval .= htmlspecialchars($value);
            $retval .= "</option>\n";
        }
        $retval .= "        </select>\n";
        $retval .= "    </td>\n";
        $retval .= "</tr>\n";
        $retval .= "<tr>\n";
        $retval .= '    <td>' . _pgettext('Trigger action time', 'Time') . "</td>\n";
        $retval .= "    <td><select name='item_timing'>\n";
        foreach ($this->time as $key => $value) {
            $selected = '';
            if (! empty($item['item_action_timing'])
                && $item['item_action_timing'] == $value
            ) {
                $selected = " selected='selected'";
            }
            $retval .= '<option' . $selected . '>' . $value . '</option>';
        }
        $retval .= "    </select></td>\n";
        $retval .= "</tr>\n";
        $retval .= "<tr>\n";
        $retval .= '    <td>' . __('Event') . "</td>\n";
        $retval .= "    <td><select name='item_event'>\n";
        foreach ($this->event as $key => $value) {
            $selected = '';
            if (! empty($item['item_event_manipulation'])
                && $item['item_event_manipulation'] == $value
            ) {
                $selected = " selected='selected'";
            }
            $retval .= '<option' . $selected . '>' . $value . '</option>';
        }
        $retval .= "    </select></td>\n";
        $retval .= "</tr>\n";
        $retval .= "<tr>\n";
        $retval .= '    <td>' . __('Definition') . "</td>\n";
        $retval .= "    <td><textarea name='item_definition' rows='15' cols='40'>";
        $retval .= $item['item_definition'];
        $retval .= "</textarea></td>\n";
        $retval .= "</tr>\n";
        $retval .= "<tr>\n";
        $retval .= '    <td>' . __('Definer') . "</td>\n";
        $retval .= "    <td><input type='text' name='item_definer'\n";
        $retval .= "               value='" . $item['item_definer'] . "'></td>\n";
        $retval .= "</tr>\n";
        $retval .= "</table>\n";
        $retval .= "</fieldset>\n";
        if ($this->response->isAjax()) {
            $retval .= "<input type='hidden' name='editor_process_" . $mode . "'\n";
            $retval .= "       value='true'>\n";
            $retval .= "<input type='hidden' name='ajax_request' value='true'>\n";
        } else {
            $retval .= "<fieldset class='tblFooters'>\n";
            $retval .= "    <input type='submit' name='editor_process_" . $mode . "'\n";
            $retval .= "           value='" . __('Go') . "'>\n";
            $retval .= "</fieldset>\n";
        }
        $retval .= "</form>\n\n";
        $retval .= '<!-- END ' . $modeToUpper . " TRIGGER FORM -->\n\n";

        return $retval;
    }

    /**
     * Composes the query necessary to create a trigger from an HTTP request.
     *
     * @return string  The CREATE TRIGGER query.
     */
    public function getQueryFromRequest()
    {
        global $db, $errors;

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
        $query .= 'TRIGGER ';
        if (! empty($_POST['item_name'])) {
            $query .= Util::backquote($_POST['item_name']) . ' ';
        } else {
            $errors[] = __('You must provide a trigger name!');
        }
        if (! empty($_POST['item_timing'])
            && in_array($_POST['item_timing'], $this->time)
        ) {
            $query .= $_POST['item_timing'] . ' ';
        } else {
            $errors[] = __('You must provide a valid timing for the trigger!');
        }
        if (! empty($_POST['item_event'])
            && in_array($_POST['item_event'], $this->event)
        ) {
            $query .= $_POST['item_event'] . ' ';
        } else {
            $errors[] = __('You must provide a valid event for the trigger!');
        }
        $query .= 'ON ';
        if (! empty($_POST['item_table'])
            && in_array($_POST['item_table'], $this->dbi->getTables($db))
        ) {
            $query .= Util::backquote($_POST['item_table']);
        } else {
            $errors[] = __('You must provide a valid table name!');
        }
        $query .= ' FOR EACH ROW ';
        if (! empty($_POST['item_definition'])) {
            $query .= $_POST['item_definition'];
        } else {
            $errors[] = __('You must provide a trigger definition.');
        }

        return $query;
    }

    /**
     * @param resource|bool $result          Query result
     * @param string        $createStatement Query
     * @param array         $errors          Errors
     *
     * @return array
     */
    private function checkResult($result, $createStatement, array $errors)
    {
        if ($result) {
            return $errors;
        }

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
     * @param string     $mode  Editor mode 'add' or 'edit'
     * @param array|null $item  Data necessary to create the editor
     * @param string     $title Title of the editor
     * @param string     $db    Database
     *
     * @return void
     */
    private function sendEditor($mode, ?array $item, $title, $db)
    {
        if ($item !== null) {
            $editor = $this->getEditorForm($mode, $item);
            if ($this->response->isAjax()) {
                $this->response->addJSON('message', $editor);
                $this->response->addJSON('title', $title);
            } else {
                echo "\n\n<h2>" . $title . "</h2>\n\n" . $editor;
                unset($_POST);
            }
            exit;
        }

        $message  = __('Error in processing request:') . ' ';
        $message .= sprintf(
            __('No trigger with name %1$s found in database %2$s.'),
            htmlspecialchars(Util::backquote($_REQUEST['item_name'])),
            htmlspecialchars(Util::backquote($db))
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
        global $db, $table;

        if (empty($_GET['export_item']) || empty($_GET['item_name'])) {
            return;
        }

        $itemName = $_GET['item_name'];
        $triggers = $this->dbi->getTriggers($db, $table, '');
        $exportData = false;

        foreach ($triggers as $trigger) {
            if ($trigger['name'] === $itemName) {
                $exportData = $trigger['create'];
                break;
            }
        }

        $itemName = htmlspecialchars(Util::backquote($_GET['item_name']));
        if ($exportData !== false) {
            $exportData = htmlspecialchars(trim($exportData));
            $title = sprintf(__('Export of trigger %s'), $itemName);

            if ($this->response->isAjax()) {
                $this->response->addJSON('message', $exportData);
                $this->response->addJSON('title', $title);

                exit;
            }

            $exportData = '<textarea cols="40" rows="15" style="width: 100%;">'
                . $exportData . '</textarea>';
            echo "<fieldset>\n" . '<legend>' . $title . "</legend>\n"
                . $exportData . "</fieldset>\n";

            return;
        }

        $message = sprintf(
            __('Error in processing request: No trigger with name %1$s found in database %2$s.'),
            $itemName,
            htmlspecialchars(Util::backquote($db))
        );
        $message = Message::error($message);

        if ($this->response->isAjax()) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', $message);

            exit;
        }

        echo $message->getDisplay();
    }
}
