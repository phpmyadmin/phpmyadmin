<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays form for creating a table (if user has privileges for that)
 *
 * for MySQL >= 4.1.0, we should be able to detect if user has a CREATE
 * privilege by looking at SHOW GRANTS output;
 * for < 4.1.0, it could be more difficult because the logic tries to
 * detect the current host and it might be expressed in many ways; also
 * on a shared server, the user might be unable to define a controluser
 * that has the proper rights to the "mysql" db;
 * so we give up and assume that user has the right to create a table
 *
 * Note: in this case we could even skip the following "foreach" logic
 *
 * Addendum, 2006-01-19: ok, I give up. We got some reports about servers
 * where the hostname field in mysql.user is not the same as the one
 * in mysql.db for a user. In this case, SHOW GRANTS does not return
 * the db-specific privileges. And probably, those users are on a shared
 * server, so can't set up a control user with rights to the "mysql" db.
 * We cannot reliably detect the db-specific privileges, so no more
 * warnings about the lack of privileges for CREATE TABLE. Tested
 * on MySQL 5.0.18.
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin\Display;

use PhpMyAdmin\CheckUserPrivileges;
use PhpMyAdmin\Template;

/**
 * PhpMyAdmin\Display\CreateTable class
 *
 * @package PhpMyAdmin
 */
class CreateTable
{
    /**
     * Returns the html for create table.
     *
     * @param string $db database name
     *
     * @return string
     */
    public static function getHtml($db)
    {
        $checkUserPrivileges = new CheckUserPrivileges($GLOBALS['dbi']);
        $checkUserPrivileges->getPrivileges();

        $template = new Template();
        return $template->render('database/create_table', ['db' => $db]);
    }
}
