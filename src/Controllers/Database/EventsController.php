<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Database\Events;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\LanguageManager;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
use function count;
use function htmlspecialchars;
use function mb_strtoupper;
use function sprintf;
use function trim;

final class EventsController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private Events $events,
        private DatabaseInterface $dbi,
        private readonly DbTableExists $dbTableExists,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['errors'] ??= null;
        $GLOBALS['errorUrl'] ??= null;

        $this->addScriptFiles(['database/events.js', 'sql.js']);

        if (! $request->isAjax()) {
            if (! $this->checkParameters(['db'])) {
                return;
            }

            $GLOBALS['errorUrl'] = Util::getScriptNameForOption(
                Config::getInstance()->settings['DefaultTabDatabase'],
                'database',
            );
            $GLOBALS['errorUrl'] .= Url::getCommon(['db' => Current::$database], '&');

            $databaseName = DatabaseName::tryFrom($request->getParam('db'));
            if ($databaseName === null || ! $this->dbTableExists->selectDatabase($databaseName)) {
                $this->redirect('/', ['reload' => true, 'message' => __('No databases selected.')]);

                return;
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
            $output = $this->events->handleEditor();

            if ($request->isAjax()) {
                if ($GLOBALS['message']->isSuccess()) {
                    $events = $this->events->getDetails(Current::$database, $_POST['item_name']);
                    $event = $events[0];
                    $this->response->addJSON(
                        'name',
                        htmlspecialchars(
                            mb_strtoupper($_POST['item_name']),
                        ),
                    );
                    $sqlDrop = sprintf(
                        'DROP EVENT IF EXISTS %s',
                        Util::backquote($event['name']),
                    );
                    $this->response->addJSON(
                        'new_row',
                        $this->template->render('database/events/row', [
                            'db' => Current::$database,
                            'table' => Current::$table,
                            'event' => $event,
                            'has_privilege' => Util::currentUserHasPrivilege('EVENT', Current::$database),
                            'sql_drop' => $sqlDrop,
                            'row_class' => '',
                        ]),
                    );

                    $this->response->addJSON('insert', true);
                    $this->response->addJSON('message', $output);
                } else {
                    $this->response->setRequestStatus(false);
                    $this->response->addJSON('message', $GLOBALS['message']);
                }

                $this->response->addJSON('tableType', 'events');

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
            && (
                ! empty($_REQUEST['add_item'])
                || ! empty($_REQUEST['edit_item'])
                || ! empty($_POST['item_changetype'])
            )
        ) {
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
                $item = $this->events->getDataFromRequest();
                $mode = 'add';
            } elseif (! empty($_REQUEST['edit_item'])) {
                $title = __('Edit event');
                if (
                    ! empty($_REQUEST['item_name'])
                    && empty($_POST['editor_process_edit'])
                    && empty($_POST['item_changetype'])
                ) {
                    $item = $this->events->getDataFromName($_REQUEST['item_name']);
                    if ($item !== null) {
                        $item['item_original_name'] = $item['item_name'];
                    }
                } else {
                    $item = $this->events->getDataFromRequest();
                }

                $mode = 'edit';
            }

            if ($item !== null) {
                if ($operation === 'change') {
                    if ($item['item_type'] === 'RECURRING') {
                        $item['item_type'] = 'ONE TIME';
                        $item['item_type_toggle'] = 'RECURRING';
                    } else {
                        $item['item_type'] = 'RECURRING';
                        $item['item_type_toggle'] = 'ONE TIME';
                    }
                }

                $editor = $this->template->render('database/events/editor_form', [
                    'db' => Current::$database,
                    'event' => $item,
                    'mode' => $mode,
                    'is_ajax' => $request->isAjax(),
                    'status_display' => $this->events->status['display'],
                    'event_type' => $this->events->type,
                    'event_interval' => $this->events->interval,
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
                __('No event with name %1$s found in database %2$s.'),
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

        if (! empty($_GET['export_item']) && ! empty($_GET['item_name'])) {
            $itemName = $_GET['item_name'];
            $exportData = Events::getDefinition($this->dbi, Current::$database, $itemName);

            if ($exportData === null || $exportData === '') {
                $exportData = false;
            }

            $itemName = htmlspecialchars(Util::backquote($itemName));
            if ($exportData !== false) {
                $exportData = htmlspecialchars(trim($exportData));
                $title = sprintf(__('Export of event %s'), $itemName);

                if ($request->isAjax()) {
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
                    __('Error in processing request: No event with name %1$s found in database %2$s.'),
                    $itemName,
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
        }

        $items = $this->events->getDetails(Current::$database);

        $this->render('database/events/index', [
            'db' => Current::$database,
            'items' => $items,
            'has_privilege' => Util::currentUserHasPrivilege('EVENT', Current::$database),
            'scheduler_state' => $this->events->getEventSchedulerStatus(),
            'text_dir' => LanguageManager::$textDir,
            'is_ajax' => $request->isAjax() && empty($_REQUEST['ajax_page_request']),
        ]);
    }
}
