<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Events;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Events\Events;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;

use function __;
use function htmlspecialchars;
use function mb_strtoupper;
use function sprintf;

#[Route('/events', ['GET', 'POST'])]
final readonly class IndexController implements InvocableController
{
    public function __construct(
        private ResponseRenderer $response,
        private Template $template,
        private Events $events,
        private DbTableExists $dbTableExists,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $databaseName = DatabaseName::tryFrom($request->getParam('db'));
        if ($databaseName === null || ! $this->dbTableExists->selectDatabase($databaseName)) {
            $message = __('No database selected.');
            if (! $request->isAjax()) {
                return $this->response->redirectToRoute('/', ['reload' => true, 'message' => $message]);
            }

            $this->response->addJSON('message', $message);
            $this->response->setRequestStatus(false);

            return $this->response->response();
        }

        $this->response->addScriptFiles(['events.js', 'sql.js']);

        $hasPrivilege = $this->events->hasEventPrivilege($databaseName);

        if (! empty($_POST['editor_process_add']) || ! empty($_POST['editor_process_edit'])) {
            $output = $this->events->handleEditor($this->events->getQueryFromRequest($request));

            if ($request->isAjax()) {
                if (Current::$message instanceof Message && Current::$message->isSuccess()) {
                    $events = $this->events->getDetails($databaseName->getName(), $_POST['item_name']);
                    if ($events === []) {
                        $this->response->setRequestStatus(false);
                        $this->response->addJSON(
                            'message',
                            __('The event was dropped immediately after creation.'),
                        );
                        $this->response->addJSON('tableType', 'events');

                        return $this->response->response();
                    }

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
                        $this->template->render('events/row', [
                            'db' => $databaseName->getName(),
                            'table' => Current::$table,
                            'event' => $event,
                            'has_privilege' => $hasPrivilege,
                            'sql_drop' => $sqlDrop,
                            'row_class' => '',
                        ]),
                    );

                    $this->response->addJSON('insert', true);
                    $this->response->addJSON('message', $output);
                } else {
                    $this->response->setRequestStatus(false);
                    $this->response->addJSON('message', Current::$message);
                }

                $this->response->addJSON('tableType', 'events');

                return $this->response->response();
            }
        }

        /**
         * Display a form used to add/edit a trigger, if necessary
         */
        if (
            $this->events->getErrorCount() > 0
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

                $editor = $this->template->render('events/editor-form', [
                    'db' => $databaseName->getName(),
                    'event' => $item,
                    'mode' => $mode,
                    'is_ajax' => $request->isAjax(),
                    'status_display' => $this->events::STATUS['display'],
                    'event_type' => $this->events::TYPE,
                    'event_interval' => $this->events::INTERVAL,
                ]);
                if ($request->isAjax()) {
                    $this->response->addJSON('message', $editor);
                    $this->response->addJSON('title', $title);

                    return $this->response->response();
                }

                $this->response->addHTML('<h2>' . $title . "</h2>\n\n" . $editor);

                return $this->response->response();
            }

            $message = __('Error in processing request:') . ' ';
            $message .= sprintf(
                __('No event with name %1$s found in database %2$s.'),
                htmlspecialchars(Util::backquote($_REQUEST['item_name'])),
                htmlspecialchars(Util::backquote($databaseName->getName())),
            );
            $message = Message::error($message);
            if ($request->isAjax()) {
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', $message);

                return $this->response->response();
            }

            $this->response->addHTML($message);
        }

        $items = $this->events->getDetails($databaseName->getName());

        $this->response->render('events/index', [
            'db' => $databaseName->getName(),
            'items' => $items,
            'has_privilege' => $hasPrivilege,
            'scheduler_state' => $this->events->getEventSchedulerStatus(),
            'is_ajax' => $request->isAjax() && empty($_REQUEST['ajax_page_request']),
        ]);

        return $this->response->response();
    }
}
