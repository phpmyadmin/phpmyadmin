<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\CheckUserPrivileges;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationCleanup;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Server\Plugins;
use PhpMyAdmin\Server\Privileges;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
use function header;
use function implode;
use function is_array;
use function is_string;
use function str_replace;
use function urlencode;

/**
 * Server privileges and users manipulations.
 */
class PrivilegesController extends AbstractController
{
    /** @var Relation */
    private $relation;

    /** @var DatabaseInterface */
    private $dbi;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        Relation $relation,
        DatabaseInterface $dbi
    ) {
        parent::__construct($response, $template);
        $this->relation = $relation;
        $this->dbi = $dbi;
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['errorUrl'] = $GLOBALS['errorUrl'] ?? null;
        $GLOBALS['message'] = $GLOBALS['message'] ?? null;
        $GLOBALS['text_dir'] = $GLOBALS['text_dir'] ?? null;
        $GLOBALS['post_patterns'] = $GLOBALS['post_patterns'] ?? null;
        $GLOBALS['username'] = $GLOBALS['username'] ?? null;
        $GLOBALS['hostname'] = $GLOBALS['hostname'] ?? null;
        $GLOBALS['dbname'] = $GLOBALS['dbname'] ?? null;
        $GLOBALS['tablename'] = $GLOBALS['tablename'] ?? null;
        $GLOBALS['routinename'] = $GLOBALS['routinename'] ?? null;
        $GLOBALS['db_and_table'] = $GLOBALS['db_and_table'] ?? null;
        $GLOBALS['dbname_is_wildcard'] = $GLOBALS['dbname_is_wildcard'] ?? null;
        $GLOBALS['queries'] = $GLOBALS['queries'] ?? null;
        $GLOBALS['password'] = $GLOBALS['password'] ?? null;
        $GLOBALS['ret_message'] = $GLOBALS['ret_message'] ?? null;
        $GLOBALS['ret_queries'] = $GLOBALS['ret_queries'] ?? null;
        $GLOBALS['queries_for_display'] = $GLOBALS['queries_for_display'] ?? null;
        $GLOBALS['_add_user_error'] = $GLOBALS['_add_user_error'] ?? null;
        $GLOBALS['itemType'] = $GLOBALS['itemType'] ?? null;
        $GLOBALS['tables'] = $GLOBALS['tables'] ?? null;
        $GLOBALS['num_tables'] = $GLOBALS['num_tables'] ?? null;
        $GLOBALS['total_num_tables'] = $GLOBALS['total_num_tables'] ?? null;
        $GLOBALS['sub_part'] = $GLOBALS['sub_part'] ?? null;
        $GLOBALS['tooltip_truename'] = $GLOBALS['tooltip_truename'] ?? null;
        $GLOBALS['tooltip_aliasname'] = $GLOBALS['tooltip_aliasname'] ?? null;
        $GLOBALS['pos'] = $GLOBALS['pos'] ?? null;
        $GLOBALS['title'] = $GLOBALS['title'] ?? null;
        $GLOBALS['export'] = $GLOBALS['export'] ?? null;
        $GLOBALS['grants'] = $GLOBALS['grants'] ?? null;
        $GLOBALS['one_grant'] = $GLOBALS['one_grant'] ?? null;
        $GLOBALS['url_dbname'] = $GLOBALS['url_dbname'] ?? null;

        $checkUserPrivileges = new CheckUserPrivileges($this->dbi);
        $checkUserPrivileges->getPrivileges();

        $relationParameters = $this->relation->getRelationParameters();

        $this->addScriptFiles(['server/privileges.js', 'vendor/zxcvbn-ts.js']);

        $relationCleanup = new RelationCleanup($this->dbi, $this->relation);
        $serverPrivileges = new Privileges(
            $this->template,
            $this->dbi,
            $this->relation,
            $relationCleanup,
            new Plugins($this->dbi)
        );

        if ($relationParameters->configurableMenusFeature !== null) {
            $this->response->addHTML('<div class="container-fluid">');
            $this->render('server/privileges/subnav', [
                'active' => 'privileges',
                'is_super_user' => $this->dbi->isSuperUser(),
            ]);
        }

        /**
         * Sets globals from $_POST patterns, for privileges and max_* vars
         */
        $GLOBALS['post_patterns'] = [
            '/_priv$/i',
            '/^max_/i',
        ];

        Core::setPostAsGlobal($GLOBALS['post_patterns']);

        $GLOBALS['errorUrl'] = Url::getFromRoute('/');

        if ($this->dbi->isSuperUser()) {
            $this->dbi->selectDb('mysql');
        }

        $GLOBALS['_add_user_error'] = false;
        /**
         * Get DB information: username, hostname, dbname,
         * tablename, db_and_table, dbname_is_wildcard
         */
        [
            $GLOBALS['username'],
            $GLOBALS['hostname'],
            $GLOBALS['dbname'],
            $GLOBALS['tablename'],
            $GLOBALS['routinename'],
            $GLOBALS['db_and_table'],
            $GLOBALS['dbname_is_wildcard'],
        ] = $serverPrivileges->getDataForDBInfo();

        /**
         * Checks if the user is allowed to do what they try to...
         */
        $isGrantUser = $this->dbi->isGrantUser();
        $isCreateUser = $this->dbi->isCreateUser();

        if (! $this->dbi->isSuperUser() && ! $isGrantUser && ! $isCreateUser) {
            $this->render('server/sub_page_header', [
                'type' => 'privileges',
                'is_image' => false,
            ]);
            $this->response->addHTML(
                Message::error(__('No Privileges'))
                    ->getDisplay()
            );

            return;
        }

        if (! $isGrantUser && ! $isCreateUser) {
            $this->response->addHTML(Message::notice(
                __('You do not have the privileges to administrate the users!')
            )->getDisplay());
        }

        /**
         * Checks if the user is using "Change Login Information / Copy User" dialog
         * only to update the password
         */
        if (
            isset($_POST['change_copy']) && $GLOBALS['username'] == $_POST['old_username']
            && $GLOBALS['hostname'] == $_POST['old_hostname']
        ) {
            $this->response->addHTML(
                Message::error(
                    __(
                        "Username and hostname didn't change. "
                        . 'If you only want to change the password, '
                        . "'Change password' tab should be used."
                    )
                )->getDisplay()
            );
            $this->response->setRequestStatus(false);

            return;
        }

        /**
         * Changes / copies a user, part I
         */
        [$GLOBALS['queries'], $GLOBALS['password']] = $serverPrivileges->getDataForChangeOrCopyUser();

        /**
         * Adds a user
         *   (Changes / copies a user, part II)
         */
        [
            $GLOBALS['ret_message'],
            $GLOBALS['ret_queries'],
            $GLOBALS['queries_for_display'],
            $GLOBALS['sql_query'],
            $GLOBALS['_add_user_error'],
        ] = $serverPrivileges->addUser(
            $GLOBALS['dbname'] ?? null,
            $GLOBALS['username'] ?? '',
            $GLOBALS['hostname'] ?? '',
            $GLOBALS['password'] ?? null,
            $relationParameters->configurableMenusFeature !== null
        );
        //update the old variables
        if (isset($GLOBALS['ret_queries'])) {
            $GLOBALS['queries'] = $GLOBALS['ret_queries'];
            unset($GLOBALS['ret_queries']);
        }

        if (isset($GLOBALS['ret_message'])) {
            $GLOBALS['message'] = $GLOBALS['ret_message'];
            unset($GLOBALS['ret_message']);
        }

        /**
         * Changes / copies a user, part III
         */
        if (isset($_POST['change_copy']) && $GLOBALS['username'] !== null && $GLOBALS['hostname'] !== null) {
            $GLOBALS['queries'] = $serverPrivileges->getDbSpecificPrivsQueriesForChangeOrCopyUser(
                $GLOBALS['queries'],
                $GLOBALS['username'],
                $GLOBALS['hostname']
            );
        }

        $GLOBALS['itemType'] = '';
        if (! empty($GLOBALS['routinename']) && is_string($GLOBALS['dbname'])) {
            $GLOBALS['itemType'] = $serverPrivileges->getRoutineType($GLOBALS['dbname'], $GLOBALS['routinename']);
        }

        /**
         * Updates privileges
         */
        if (! empty($_POST['update_privs'])) {
            if (is_array($GLOBALS['dbname'])) {
                foreach ($GLOBALS['dbname'] as $key => $db_name) {
                    [$GLOBALS['sql_query'][$key], $GLOBALS['message']] = $serverPrivileges->updatePrivileges(
                        ($GLOBALS['username'] ?? ''),
                        ($GLOBALS['hostname'] ?? ''),
                        ($GLOBALS['tablename'] ?? ($GLOBALS['routinename'] ?? '')),
                        ($db_name ?? ''),
                        $GLOBALS['itemType']
                    );
                }

                $GLOBALS['sql_query'] = implode("\n", $GLOBALS['sql_query']);
            } else {
                [$GLOBALS['sql_query'], $GLOBALS['message']] = $serverPrivileges->updatePrivileges(
                    ($GLOBALS['username'] ?? ''),
                    ($GLOBALS['hostname'] ?? ''),
                    ($GLOBALS['tablename'] ?? ($GLOBALS['routinename'] ?? '')),
                    ($GLOBALS['dbname'] ?? ''),
                    $GLOBALS['itemType']
                );
            }
        }

        /**
         * Assign users to user groups
         */
        if (
            ! empty($_POST['changeUserGroup']) && $relationParameters->configurableMenusFeature !== null
            && $this->dbi->isSuperUser() && $this->dbi->isCreateUser()
        ) {
            $serverPrivileges->setUserGroup($GLOBALS['username'] ?? '', $_POST['userGroup']);
            $GLOBALS['message'] = Message::success();
        }

        /**
         * Revokes Privileges
         */
        if (isset($_POST['revokeall'])) {
            [$GLOBALS['message'], $GLOBALS['sql_query']] = $serverPrivileges->getMessageAndSqlQueryForPrivilegesRevoke(
                (is_string($GLOBALS['dbname']) ? $GLOBALS['dbname'] : ''),
                ($GLOBALS['tablename'] ?? ($GLOBALS['routinename'] ?? '')),
                $GLOBALS['username'] ?? '',
                $GLOBALS['hostname'] ?? '',
                $GLOBALS['itemType']
            );
        }

        /**
         * Updates the password
         */
        if (isset($_POST['change_pw'])) {
            $GLOBALS['message'] = $serverPrivileges->updatePassword(
                $GLOBALS['errorUrl'],
                $GLOBALS['username'] ?? '',
                $GLOBALS['hostname'] ?? ''
            );
        }

        /**
         * Deletes users
         *   (Changes / copies a user, part IV)
         */
        if (isset($_POST['delete']) || (isset($_POST['change_copy']) && $_POST['mode'] < 4)) {
            $GLOBALS['queries'] = $serverPrivileges->getDataForDeleteUsers($GLOBALS['queries']);
            if (empty($_POST['change_copy'])) {
                [$GLOBALS['sql_query'], $GLOBALS['message']] = $serverPrivileges->deleteUser($GLOBALS['queries']);
            }
        }

        /**
         * Changes / copies a user, part V
         */
        if (isset($_POST['change_copy'])) {
            $GLOBALS['queries'] = $serverPrivileges->getDataForQueries(
                $GLOBALS['queries'],
                $GLOBALS['queries_for_display']
            );
            $GLOBALS['message'] = Message::success();
            $GLOBALS['sql_query'] = implode("\n", $GLOBALS['queries']);
        }

        /**
         * Reloads the privilege tables into memory
         */
        $message_ret = $serverPrivileges->updateMessageForReload();
        if ($message_ret !== null) {
            $GLOBALS['message'] = $message_ret;
            unset($message_ret);
        }

        /**
         * If we are in an Ajax request for Create User/Edit User/Revoke User/
         * Flush Privileges, show $message and return.
         */
        if (
            $this->response->isAjax()
            && empty($_REQUEST['ajax_page_request'])
            && ! isset($_GET['export'])
            && (! isset($_POST['submit_mult']) || $_POST['submit_mult'] !== 'export')
            && ((! isset($_GET['initial']) || $_GET['initial'] === '')
                || (isset($_POST['delete']) && $_POST['delete'] === __('Go')))
            && ! isset($_GET['showall'])
        ) {
            $extra_data = $serverPrivileges->getExtraDataForAjaxBehavior(
                ($GLOBALS['password'] ?? ''),
                ($GLOBALS['sql_query'] ?? ''),
                ($GLOBALS['hostname'] ?? ''),
                ($GLOBALS['username'] ?? '')
            );

            if (! empty($GLOBALS['message']) && $GLOBALS['message'] instanceof Message) {
                $this->response->setRequestStatus($GLOBALS['message']->isSuccess());
                $this->response->addJSON('message', $GLOBALS['message']);
                $this->response->addJSON($extra_data);

                return;
            }
        }

        /**
         * Displays the links
         */
        if (! empty($GLOBALS['message'])) {
            $this->response->addHTML(Generator::getMessage($GLOBALS['message']));
            unset($GLOBALS['message']);
        }

        // export user definition
        if (isset($_GET['export']) || (isset($_POST['submit_mult']) && $_POST['submit_mult'] === 'export')) {
            [$GLOBALS['title'], $GLOBALS['export']] = $serverPrivileges->getListForExportUserDefinition(
                $GLOBALS['username'] ?? '',
                $GLOBALS['hostname'] ?? ''
            );

            unset($GLOBALS['username'], $GLOBALS['hostname'], $GLOBALS['grants'], $GLOBALS['one_grant']);

            if ($this->response->isAjax()) {
                $this->response->addJSON('message', $GLOBALS['export']);
                $this->response->addJSON('title', $GLOBALS['title']);

                return;
            }

            $this->response->addHTML('<h2>' . $GLOBALS['title'] . '</h2>' . $GLOBALS['export']);
        }

        // Show back the form if an error occurred
        if (isset($_GET['adduser']) || $GLOBALS['_add_user_error'] === true) {
            // Add user
            $this->response->addHTML($serverPrivileges->getHtmlForAddUser(
                Util::escapeMysqlWildcards(is_string($GLOBALS['dbname']) ? $GLOBALS['dbname'] : '')
            ));
        } else {
            if (isset($GLOBALS['dbname']) && ! is_array($GLOBALS['dbname'])) {
                $GLOBALS['url_dbname'] = urlencode(
                    str_replace(
                        [
                            '\_',
                            '\%',
                        ],
                        [
                            '_',
                            '%',
                        ],
                        $GLOBALS['dbname']
                    )
                );
            }

            if (! isset($GLOBALS['username'])) {
                // No username is given --> display the overview
                $this->response->addHTML(
                    $serverPrivileges->getHtmlForUserOverview($GLOBALS['text_dir'])
                );
            } elseif (! empty($GLOBALS['routinename'])) {
                $this->response->addHTML(
                    $serverPrivileges->getHtmlForRoutineSpecificPrivileges(
                        $GLOBALS['username'],
                        $GLOBALS['hostname'] ?? '',
                        is_string($GLOBALS['dbname']) ? $GLOBALS['dbname'] : '',
                        $GLOBALS['routinename'],
                        Util::escapeMysqlWildcards($GLOBALS['url_dbname'] ?? '')
                    )
                );
            } else {
                // A user was selected -> display the user's properties
                // In an Ajax request, prevent cached values from showing
                if ($this->response->isAjax()) {
                    header('Cache-Control: no-cache');
                }

                $this->response->addHTML(
                    $serverPrivileges->getHtmlForUserProperties(
                        $GLOBALS['dbname_is_wildcard'],
                        Util::escapeMysqlWildcards($GLOBALS['url_dbname'] ?? ''),
                        $GLOBALS['username'],
                        $GLOBALS['hostname'] ?? '',
                        $GLOBALS['dbname'] ?? '',
                        $GLOBALS['tablename'] ?? '',
                        $request->getRoute()
                    )
                );
            }
        }

        if ($relationParameters->configurableMenusFeature === null) {
            return;
        }

        $this->response->addHTML('</div>');
    }
}
