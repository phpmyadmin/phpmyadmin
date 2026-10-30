<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Events;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Events\Events;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;
use Webmozart\Assert\Assert;

use function __;
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
        // selectDatabase is necessary for Events::getEventDefinition()
        if ($databaseName === null || ! $this->dbTableExists->selectDatabase($databaseName)) {
            return $this->response->respondWithErrorMessage(__('No database selected.'));
        }

        $this->response->addScriptFiles(['events.js', 'sql.js']);

        $hasPrivilege = $this->events->hasEventPrivilege($databaseName);
        $schedulerStatus = $this->events->getEventSchedulerStatus();

        if (
            $schedulerStatus !== 'DISABLED'
            && ($request->hasBodyParam('editor_process_add') || $request->hasBodyParam('editor_process_edit'))
        ) {
            $itemQuery = $this->events->getQueryFromRequest($request);
            $sqlQuery = $this->events->handleEditor($itemQuery, $request);

            if ($request->isAjax()) {
                if (Current::$message instanceof Message && Current::$message->isSuccess()) {
                    $itemName = $request->getParsedBodyParamAsString('item_name');
                    $events = $this->events->getDetails($databaseName->getName(), $itemName);
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
                    $this->response->addJSON('name', mb_strtoupper($itemName));
                    $sqlDrop = sprintf(
                        'DROP EVENT IF EXISTS %s',
                        Util::backquote($event['name']),
                    );
                    $this->response->addJSON(
                        'new_row',
                        $this->template->render('events/row', [
                            'db' => $databaseName->getName(),
                            'event' => $event,
                            'has_privilege' => $hasPrivilege,
                            'sql_drop' => $sqlDrop,
                        ]),
                    );

                    $this->response->addJSON('insert', true);
                    $this->response->addJSON('message', Generator::getMessage(Current::$message, $sqlQuery));
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
            $schedulerStatus !== 'DISABLED'
            && ($this->events->getErrorCount() > 0
            || ! $request->hasBodyParam('editor_process_add')
            && ! $request->hasBodyParam('editor_process_edit')
            && ($request->has('add_item') || $request->has('edit_item')))
        ) {
            if ($request->has('add_item')) {
                $title = __('Add event');
                $item = $this->events->getDataFromRequest($request);
                $mode = 'add';
            } else {
                $title = __('Edit event');
                if (! $request->hasBodyParam('editor_process_edit')) {
                    $itemName = $request->getQueryParam('item_name');
                    Assert::string($itemName);
                    $item = $this->events->getDataFromName($itemName);
                    if ($item === null) {
                        if ($request->isAjax()) {
                            $this->response->setRequestStatus(false);
                            $this->response->addJSON(
                                'message',
                                $this->getMessageForMissingItem($request, $databaseName),
                            );

                            return $this->response->response();
                        }

                        $this->response->addHTML($this->getMessageForMissingItem($request, $databaseName));

                        return $this->showIndexPage($databaseName, $hasPrivilege, $schedulerStatus);
                    }

                    $item['item_original_name'] = $item['item_name'];
                } else {
                    $item = $this->events->getDataFromRequest($request);
                }

                $mode = 'edit';
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

        return $this->showIndexPage($databaseName, $hasPrivilege, $schedulerStatus);
    }

    private function getMessageForMissingItem(ServerRequest $request, DatabaseName $databaseName): Message
    {
        $itemName = $request->getParam('item_name');
        Assert::string($itemName);

        return Message::error(
            __('Error in processing request:') . ' ' . __('No event with name %1$s found in database %2$s.'),
            [
                Util::backquote($itemName),
                Util::backquote($databaseName->getName()),
            ],
        );
    }

    private function showIndexPage(DatabaseName $databaseName, bool $hasPrivilege, string $schedulerStatus): Response
    {
        $items = [];
        if ($schedulerStatus !== 'DISABLED') {
            $items = $this->events->getDetails($databaseName->getName());
        }

        $this->response->render('events/index', [
            'db' => $databaseName->getName(),
            'items' => $items,
            'has_privilege' => $hasPrivilege,
            'scheduler_state' => $schedulerStatus,
        ]);

        return $this->response->response();
    }
}
