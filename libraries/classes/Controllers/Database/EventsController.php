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
use function htmlspecialchars;
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

        if (! $this->response->isAjax()) {
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

        $this->events->handleEditor();

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

                if ($this->response->isAjax()) {
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

                if ($this->response->isAjax()) {
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
            'is_ajax' => $this->response->isAjax() && empty($_REQUEST['ajax_page_request']),
        ]);
    }
}
