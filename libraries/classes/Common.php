<?php
/**
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin;

/**
 * Shared code for server pages.
 *
 * @package PhpMyAdmin
 */
final class Common
{
    /**
     * @return void
     */
    public static function server(): void
    {
        global $db, $table, $url_query, $viewing_mode, $err_url, $is_grantuser, $is_createuser, $dbi;

        /**
         * Handles some variables that may have been sent by the calling script
         * Note: this can be called also from the db panel to get the privileges of
         *       a db, in which case we want to keep displaying the tabs of
         *       the Database panel
         */
        if (empty($viewing_mode)) {
            $db = '';
            $table = '';
        }

        /**
         * Set parameters for links
         */
        $url_query = Url::getCommon();

        /**
         * Defines the urls to return to in case of error in a sql statement
         */
        $err_url = Url::getFromRoute('/');

        $is_grantuser = $dbi->isUserType('grant');
        $is_createuser = $dbi->isUserType('create');

        // now, select the mysql db
        if ($dbi->isSuperuser()) {
            $dbi->selectDb('mysql');
        }
    }
}
