<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Common;
use PhpMyAdmin\Database\Events;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use function strlen;

final class EventsController extends AbstractController
{
    /** @var Events */
    private $events;

    /**
     * @param Response          $response Response instance.
     * @param DatabaseInterface $dbi      DatabaseInterface instance.
     * @param Template          $template Template instance.
     * @param string            $db       Database name.
     * @param Events            $events   Events instance.
     */
    public function __construct($response, $dbi, Template $template, $db, Events $events)
    {
        parent::__construct($response, $dbi, $template, $db);
        $this->events = $events;
    }

    public function index(): void
    {
        global $db, $tables, $num_tables, $total_num_tables, $sub_part, $errors, $pmaThemeImage, $text_dir;
        global $is_show_stats, $db_is_system_schema, $tooltip_truename, $tooltip_aliasname, $pos, $url_query;

        if (! $this->response->isAjax()) {
            Common::database();

            [
                $tables,
                $num_tables,
                $total_num_tables,
                $sub_part,
                $is_show_stats,
                $db_is_system_schema,
                $tooltip_truename,
                $tooltip_aliasname,
                $pos,
            ] = Util::getDbInfo($db, $sub_part ?? '');
        } elseif (strlen($db) > 0) {
            $this->dbi->selectDb($db);
            $url_query = $url_query ?? Url::getCommon(['db' => $db]);
        }

        /**
         * Keep a list of errors that occurred while
         * processing an 'Add' or 'Edit' operation.
         */
        $errors = [];

        $this->events->handleEditor();
        $this->events->export();

        $items = $this->dbi->getEvents($db);

        $this->render('database/events/index', [
            'db' => $db,
            'items' => $items,
            'select_all_arrow_src' => $pmaThemeImage . 'arrow_' . $text_dir . '.png',
            'has_privilege' => Util::currentUserHasPrivilege('EVENT', $db),
            'toggle_button' => $this->events->getFooterToggleButton(),
            'is_ajax' => $this->response->isAjax() && empty($_REQUEST['ajax_page_request']),
        ]);
    }
}
