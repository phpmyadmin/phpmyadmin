<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Database\Events;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function strlen;

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
        $this->events->export();

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
