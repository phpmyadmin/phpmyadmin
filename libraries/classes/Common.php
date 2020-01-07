<?php
/**
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin;

/**
 * Shared code for server and database level pages.
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

    /**
     * @return void
     */
    public static function database(): void
    {
        global $cfg, $db, $is_show_stats, $db_is_system_schema, $err_url;
        global $message, $dbi, $url_query, $errno, $is_db, $err_url_0;

        Util::checkParameters(['db']);

        $response = Response::getInstance();
        $is_show_stats = $cfg['ShowStats'];

        $db_is_system_schema = $dbi->isSystemSchema($db);
        if ($db_is_system_schema) {
            $is_show_stats = false;
        }

        $relation = new Relation($dbi);
        $operations = new Operations($dbi, $relation);

        /**
         * Defines the urls to return to in case of error in a sql statement
         */
        $err_url_0 = Url::getFromRoute('/');

        $err_url = Util::getScriptNameForOption(
            $cfg['DefaultTabDatabase'],
            'database'
        );
        $err_url .= Url::getCommon(['db' => $db], strpos($err_url, '?') === false ? '?' : '&');

        /**
         * Ensures the database exists (else move to the "parent" script) and displays
         * headers
         */
        if (! isset($is_db) || ! $is_db) {
            if (strlen($db) > 0) {
                $is_db = $dbi->selectDb($db);
                // This "Command out of sync" 2014 error may happen, for example
                // after calling a MySQL procedure; at this point we can't select
                // the db but it's not necessarily wrong
                if ($dbi->getError() && $errno == 2014) {
                    $is_db = true;
                    unset($errno);
                }
            } else {
                $is_db = false;
            }
            // Not a valid db name -> back to the welcome page
            $params = ['reload' => '1'];
            if (isset($message)) {
                $params['message'] = $message;
            }
            $uri = './index.php?route=/' . Url::getCommonRaw($params, '&');
            if (strlen($db) === 0 || ! $is_db) {
                if ($response->isAjax()) {
                    $response->setRequestStatus(false);
                    $response->addJSON(
                        'message',
                        Message::error(__('No databases selected.'))
                    );
                } else {
                    Core::sendHeaderLocation($uri);
                }
                exit;
            }
        } // end if (ensures db exists)

        /**
         * Changes database charset if requested by the user
         */
        if (isset($_POST['submitcollation'], $_POST['db_collation']) && ! empty($_POST['db_collation'])) {
            [$db_charset] = explode('_', $_POST['db_collation']);
            $sql_query = 'ALTER DATABASE ' . Util::backquote($db)
                . ' DEFAULT' . Util::getCharsetQueryPart($_POST['db_collation']);
            $result = $dbi->query($sql_query);
            $message = Message::success();

            /**
             * Changes tables charset if requested by the user
             */
            if (isset($_POST['change_all_tables_collations']) &&
                $_POST['change_all_tables_collations'] === 'on'
            ) {
                list($tables, , , , , , , ,) = Util::getDbInfo($db, null);
                foreach ($tables as $tableName => $data) {
                    if ($dbi->getTable($db, $tableName)->isView()) {
                        // Skip views, we can not change the collation of a view.
                        // issue #15283
                        continue;
                    }
                    $sql_query = 'ALTER TABLE '
                        . Util::backquote($db)
                        . '.'
                        . Util::backquote($tableName)
                        . ' DEFAULT '
                        . Util::getCharsetQueryPart($_POST['db_collation']);
                    $dbi->query($sql_query);

                    /**
                     * Changes columns charset if requested by the user
                     */
                    if (isset($_POST['change_all_tables_columns_collations']) &&
                        $_POST['change_all_tables_columns_collations'] === 'on'
                    ) {
                        $operations->changeAllColumnsCollation($db, $tableName, $_POST['db_collation']);
                    }
                }
            }
            unset($db_charset);

            /**
             * If we are in an Ajax request, let us stop the execution here. Necessary for
             * db charset change action on /database/operations. If this causes a bug on
             * other pages, we might have to move this to a different location.
             */
            if ($response->isAjax()) {
                $response->setRequestStatus($message->isSuccess());
                $response->addJSON('message', $message);
                exit;
            }
        } elseif (isset($_POST['submitcollation'], $_POST['db_collation']) && empty($_POST['db_collation'])) {
            if ($response->isAjax()) {
                $response->setRequestStatus(false);
                $response->addJSON(
                    'message',
                    Message::error(__('No collation provided.'))
                );
            }
        }

        /**
         * Set parameters for links
         */
        $url_query = Url::getCommon(['db' => $db]);
    }
}
