<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PhpMyAdmin\Controllers\Database\RoutinesController
 *
 * @package PhpMyAdmin\Controllers\Database
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Rte\Routines;
use PhpMyAdmin\Util;

/**
 * Routines management.
 *
 * @package PhpMyAdmin\Controllers\Database
 */
class RoutinesController extends AbstractController
{
    /**
     * @param array $params Request parameters
     * @return void
     */
    public function index(array $params): void
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

        $routines = new Routines($this->dbi);
        $routines->main($params['type']);
    }
}
