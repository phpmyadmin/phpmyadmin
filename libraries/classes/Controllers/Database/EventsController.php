<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PhpMyAdmin\Controllers\Database\EventsController
 *
 * @package PhpMyAdmin\Controllers\Database
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Rte\Events;
use PhpMyAdmin\Util;

/**
 * Events management.
 *
 * @package PhpMyAdmin\Controllers\Database
 */
class EventsController extends AbstractController
{
    /**
     * @return void
     */
    public function index(): void
    {
        global $errors, $titles;

        /**
         * Create labels for the list
         */
        $titles = Util::buildActionTitles();

        /**
         * Keep a list of errors that occurred while
         * processing an 'Add' or 'Edit' operation.
         */
        $errors = [];

        $events = new Events($this->dbi);
        $events->main();
    }
}
