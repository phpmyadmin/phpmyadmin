<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PhpMyAdmin\Controllers\Database\TriggersController
 *
 * @package PhpMyAdmin\Controllers\Database
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Rte\Triggers;
use PhpMyAdmin\Util;

/**
 * Triggers management.
 *
 * @package PhpMyAdmin\Controllers\Database
 */
class TriggersController extends AbstractController
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

        $triggers = new Triggers($this->dbi);
        $triggers->main();
    }
}
