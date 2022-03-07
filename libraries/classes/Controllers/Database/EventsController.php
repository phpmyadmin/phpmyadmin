<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Database\Events;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function strlen;

final class EventsController extends AbstractController
{
    /** @var Events */
    private $events;

    /** @var DatabaseInterface */
    private $dbi;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        Events $events,
        DatabaseInterface $dbi
    ) {
        parent::__construct($response, $template);
        $this->events = $events;
        $this->dbi = $dbi;
    }

    public function __invoke(): void
    {
        $this->addScriptFiles(['database/events.js']);

        if (! $this->response->isAjax()) {
            Util::checkParameters(['db']);

            $GLOBALS['errorUrl'] = Util::getScriptNameForOption($GLOBALS['cfg']['DefaultTabDatabase'], 'database');
            $GLOBALS['errorUrl'] .= Url::getCommon(['db' => $GLOBALS['db']], '&');

            if (! $this->hasDatabase()) {
                return;
            }

            [
                $GLOBALS['tables'],
                $GLOBALS['num_tables'],
                $GLOBALS['total_num_tables'],
                $GLOBALS['sub_part'],,,
                $GLOBALS['tooltip_truename'],
                $GLOBALS['tooltip_aliasname'],
                $GLOBALS['pos'],
            ] = Util::getDbInfo($GLOBALS['db'], $GLOBALS['sub_part'] ?? '');
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

        $items = $this->dbi->getEvents($GLOBALS['db']);

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
