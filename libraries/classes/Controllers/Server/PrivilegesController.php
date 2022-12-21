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
        $GLOBALS['tables'] = $GLOBALS['tables'] ?? null;
        $GLOBALS['num_tables'] = $GLOBALS['num_tables'] ?? null;
        $GLOBALS['total_num_tables'] = $GLOBALS['total_num_tables'] ?? null;
        $GLOBALS['tooltip_truename'] = $GLOBALS['tooltip_truename'] ?? null;
        $GLOBALS['tooltip_aliasname'] = $GLOBALS['tooltip_aliasname'] ?? null;
        $GLOBALS['pos'] = $GLOBALS['pos'] ?? null;

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
            $request->hasBodyParam('change_copy')
            && $GLOBALS['username'] == $request->getParsedBodyParam('old_username')
            && $GLOBALS['hostname'] == $request->getParsedBodyParam('old_hostname')
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
        $password = $serverPrivileges->getDataForChangeOrCopyUser();

        /**
         * Adds a user
         *   (Changes / copies a user, part II)
         */
        [
            $retMessage,
            $queries,
            $queriesForDisplay,
            $GLOBALS['sql_query'],
            $addUserError,
        ] = $serverPrivileges->addUser(
            $GLOBALS['dbname'] ?? null,
            $GLOBALS['username'] ?? '',
            $GLOBALS['hostname'] ?? '',
            $password,
            $relationParameters->configurableMenusFeature !== null
        );
        //update the old variables
        if (isset($retMessage)) {
            $GLOBALS['message'] = $retMessage;
            unset($retMessage);
        }

        /**
         * Changes / copies a user, part III
         */
        if ($request->hasBodyParam('change_copy') && $GLOBALS['username'] !== null && $GLOBALS['hostname'] !== null) {
            $queries = $serverPrivileges->getDbSpecificPrivsQueriesForChangeOrCopyUser(
                $queries,
                $GLOBALS['username'],
                $GLOBALS['hostname']
            );
        }

        $itemType = '';
        if (! empty($GLOBALS['routinename']) && is_string($GLOBALS['dbname'])) {
            $itemType = $serverPrivileges->getRoutineType($GLOBALS['dbname'], $GLOBALS['routinename']);
        }

        /**
         * Updates privileges
         */
        if ($request->hasBodyParam('update_privs')) {
            if (is_array($GLOBALS['dbname'])) {
                foreach ($GLOBALS['dbname'] as $key => $db_name) {
                    [$GLOBALS['sql_query'][$key], $GLOBALS['message']] = $serverPrivileges->updatePrivileges(
                        ($GLOBALS['username'] ?? ''),
                        ($GLOBALS['hostname'] ?? ''),
                        ($GLOBALS['tablename'] ?? ($GLOBALS['routinename'] ?? '')),
                        ($db_name ?? ''),
                        $itemType
                    );
                }

                $GLOBALS['sql_query'] = implode("\n", $GLOBALS['sql_query']);
            } else {
                [$GLOBALS['sql_query'], $GLOBALS['message']] = $serverPrivileges->updatePrivileges(
                    ($GLOBALS['username'] ?? ''),
                    ($GLOBALS['hostname'] ?? ''),
                    ($GLOBALS['tablename'] ?? ($GLOBALS['routinename'] ?? '')),
                    ($GLOBALS['dbname'] ?? ''),
                    $itemType
                );
            }
        }

        /**
         * Assign users to user groups
         */
        if (
            $request->hasBodyParam('changeUserGroup') && $relationParameters->configurableMenusFeature !== null
            && $this->dbi->isSuperUser() && $this->dbi->isCreateUser()
        ) {
            $serverPrivileges->setUserGroup($GLOBALS['username'] ?? '', $request->getParsedBodyParam('userGroup'));
            $GLOBALS['message'] = Message::success();
        }

        /**
         * Revokes Privileges
         */
        if ($request->hasBodyParam('revokeall')) {
            [$GLOBALS['message'], $GLOBALS['sql_query']] = $serverPrivileges->getMessageAndSqlQueryForPrivilegesRevoke(
                (is_string($GLOBALS['dbname']) ? $GLOBALS['dbname'] : ''),
                ($GLOBALS['tablename'] ?? ($GLOBALS['routinename'] ?? '')),
                $GLOBALS['username'] ?? '',
                $GLOBALS['hostname'] ?? '',
                $itemType
            );
        }

        /**
         * Updates the password
         */
        if ($request->hasBodyParam('change_pw')) {
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
        if (
            $request->hasBodyParam('delete')
            || ($request->hasBodyParam('change_copy') && $request->getParsedBodyParam('mode') < 4)
        ) {
            $queries = $serverPrivileges->getDataForDeleteUsers($queries);
            if (! $request->hasBodyParam('change_copy')) {
                [$GLOBALS['sql_query'], $GLOBALS['message']] = $serverPrivileges->deleteUser($queries);
            }
        }

        /**
         * Changes / copies a user, part V
         */
        if ($request->hasBodyParam('change_copy')) {
            $queries = $serverPrivileges->getDataForQueries(
                $queries,
                $queriesForDisplay
            );
            $GLOBALS['message'] = Message::success();
            $GLOBALS['sql_query'] = implode("\n", $queries);
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
            && ! $request->hasQueryParam('export')
            && $request->getParsedBodyParam('submit_mult') !== 'export'
            && ((! $request->hasQueryParam('initial') || $request->getQueryParam('initial') === '')
                || $request->getParsedBodyParam('delete') === __('Go'))
            && ! $request->hasQueryParam('showall')
        ) {
            $extra_data = $serverPrivileges->getExtraDataForAjaxBehavior(
                ($password ?? ''),
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
        if ($request->hasQueryParam('export') || $request->getParsedBodyParam('submit_mult') === 'export') {
            [$title, $export] = $serverPrivileges->getListForExportUserDefinition(
                $GLOBALS['username'] ?? '',
                $GLOBALS['hostname'] ?? ''
            );

            unset($GLOBALS['username'], $GLOBALS['hostname']);

            if ($this->response->isAjax()) {
                $this->response->addJSON('message', $export);
                $this->response->addJSON('title', $title);

                return;
            }

            $this->response->addHTML('<h2>' . $title . '</h2>' . $export);
        }

        // Show back the form if an error occurred
        if ($request->hasQueryParam('adduser') || $addUserError === true) {
            // Add user
            $this->response->addHTML($serverPrivileges->getHtmlForAddUser(
                $serverPrivileges->escapeGrantWildcards(is_string($GLOBALS['dbname']) ? $GLOBALS['dbname'] : '')
            ));
        } else {
            if (isset($GLOBALS['dbname']) && ! is_array($GLOBALS['dbname'])) {
                $urlDbname = urlencode(
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
                        $serverPrivileges->escapeGrantWildcards($urlDbname ?? '')
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
                        $serverPrivileges->escapeGrantWildcards($urlDbname ?? ''),
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
