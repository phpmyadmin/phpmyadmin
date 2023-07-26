<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Database\Events;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
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
use function strlen;
use function trim;

final class EventsController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private Events $events,
        private DatabaseInterface $dbi,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['errors'] ??= null;
        $GLOBALS['text_dir'] ??= null;
        $GLOBALS['errorUrl'] ??= null;

        $this->addScriptFiles(['database/events.js']);

        if (! $request->isAjax()) {
            $this->checkParameters(['db']);

            $GLOBALS['errorUrl'] = Util::getScriptNameForOption($GLOBALS['cfg']['DefaultTabDatabase'], 'database');
            $GLOBALS['errorUrl'] .= Url::getCommon(['db' => $GLOBALS['db']], '&');

            if (! $this->hasDatabase()) {
                return;
            }
        } elseif (strlen($GLOBALS['db']) > 0) {
            $this->dbi->selectDb($GLOBALS['db']);
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
                    $events = $this->events->getDetails($GLOBALS['db'], $_POST['item_name']);
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
                    'db' => $GLOBALS['db'],
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
                htmlspecialchars(Util::backquote($GLOBALS['db'])),
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
            $exportData = Events::getDefinition($this->dbi, $GLOBALS['db'], $itemName);

            if (! $exportData) {
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
                    htmlspecialchars(Util::backquote($GLOBALS['db'])),
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

        $items = $this->events->getDetails($GLOBALS['db']);

        $this->render('database/events/index', [
            'db' => $GLOBALS['db'],
            'items' => $items,
            'has_privilege' => Util::currentUserHasPrivilege('EVENT', $GLOBALS['db']),
            'scheduler_state' => $this->events->getEventSchedulerStatus(),
            'text_dir' => $GLOBALS['text_dir'],
            'is_ajax' => $request->isAjax() && empty($_REQUEST['ajax_page_request']),
        ]);
    }
}
