<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationCleanup;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Server\Plugins;
use PhpMyAdmin\Server\Privileges;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\UserPrivilegesFactory;

use function __;
use function htmlspecialchars;
use function implode;
use function is_array;
use function is_string;
use function str_replace;
use function urlencode;

/**
 * Server privileges and users manipulations.
 */
final class PrivilegesController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly Template $template,
        private readonly Relation $relation,
        private readonly DatabaseInterface $dbi,
        private readonly UserPrivilegesFactory $userPrivilegesFactory,
        private readonly Config $config,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $GLOBALS['errorUrl'] ??= null;
        $GLOBALS['message'] ??= null;
        $GLOBALS['username'] ??= null;
        $GLOBALS['hostname'] ??= null;
        $GLOBALS['dbname'] ??= null;

        $userPrivileges = $this->userPrivilegesFactory->getPrivileges();

        $relationParameters = $this->relation->getRelationParameters();

        $this->response->addScriptFiles(['server/privileges.js', 'vendor/zxcvbn-ts.js']);

        $relationCleanup = new RelationCleanup($this->dbi, $this->relation);
        $serverPrivileges = new Privileges(
            $this->template,
            $this->dbi,
            $this->relation,
            $relationCleanup,
            new Plugins($this->dbi),
            $this->config,
        );

        $this->response->addHTML('<div class="container-fluid">');

        if ($relationParameters->configurableMenusFeature !== null && ! $request->isAjax()) {
            $this->response->render('server/privileges/subnav', [
                'active' => 'privileges',
                'is_super_user' => $this->dbi->isSuperUser(),
            ]);
        }

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
            $tablename,
            $routinename,
            $dbnameIsWildcard,
        ] = $serverPrivileges->getDataForDBInfo();

        /**
         * Checks if the user is allowed to do what they try to...
         */
        $isGrantUser = $this->dbi->isGrantUser();
        $isCreateUser = $this->dbi->isCreateUser();

        if (! $this->dbi->isSuperUser() && ! $isGrantUser && ! $isCreateUser) {
            $this->response->render('server/sub_page_header', ['type' => 'privileges', 'is_image' => false]);
            $this->response->addHTML(
                Message::error(__('No Privileges'))
                    ->getDisplay(),
            );

            return $this->response->response();
        }

        if (! $isGrantUser && ! $isCreateUser) {
            $this->response->addHTML(Message::notice(
                __('You do not have the privileges to administrate the users!'),
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
                        . "'Change password' tab should be used.",
                    ),
                )->getDisplay(),
            );
            $this->response->setRequestStatus(false);

            return $this->response->response();
        }

        /**
         * Changes / copies a user, part I
         */
        $password = $serverPrivileges->getDataForChangeOrCopyUser(
            $request->getParsedBodyParam('old_username', ''),
            $request->getParsedBodyParam('old_hostname', ''),
        );

        /**
         * Adds a user
         *   (Changes / copies a user, part II)
         */
        [$retMessage, $queries, $queriesForDisplay, $GLOBALS['sql_query'], $addUserError] = $serverPrivileges->addUser(
            $GLOBALS['dbname'] ?? null,
            $GLOBALS['username'] ?? '',
            $GLOBALS['hostname'] ?? '',
            $password,
            $relationParameters->configurableMenusFeature !== null,
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
                $GLOBALS['hostname'],
                $request->getParsedBodyParam('old_username'),
                $request->getParsedBodyParam('old_hostname'),
            );
        }

        $itemType = '';
        if (! empty($routinename) && is_string($GLOBALS['dbname'])) {
            $itemType = $serverPrivileges->getRoutineType($GLOBALS['dbname'], $routinename);
        }

        /**
         * Updates privileges
         */
        if ($request->hasBodyParam('update_privs')) {
            if (is_array($GLOBALS['dbname'])) {
                foreach ($GLOBALS['dbname'] as $key => $dbName) {
                    [$GLOBALS['sql_query'][$key], $GLOBALS['message']] = $serverPrivileges->updatePrivileges(
                        $GLOBALS['username'] ?? '',
                        $GLOBALS['hostname'] ?? '',
                        $tablename ?? $routinename ?? '',
                        $dbName ?? '',
                        $itemType,
                    );
                }

                $GLOBALS['sql_query'] = implode("\n", $GLOBALS['sql_query']);
            } else {
                [$GLOBALS['sql_query'], $GLOBALS['message']] = $serverPrivileges->updatePrivileges(
                    $GLOBALS['username'] ?? '',
                    $GLOBALS['hostname'] ?? '',
                    $tablename ?? $routinename ?? '',
                    $GLOBALS['dbname'] ?? '',
                    $itemType,
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
            $serverPrivileges->setUserGroup($GLOBALS['username'] ?? '', $request->getParsedBodyParam('userGroup', ''));
            $GLOBALS['message'] = Message::success();
        }

        /**
         * Revokes Privileges
         */
        if ($request->hasBodyParam('revokeall')) {
            [$GLOBALS['message'], $GLOBALS['sql_query']] = $serverPrivileges->getMessageAndSqlQueryForPrivilegesRevoke(
                is_string($GLOBALS['dbname']) ? $GLOBALS['dbname'] : '',
                $tablename ?? $routinename ?? '',
                $GLOBALS['username'] ?? '',
                $GLOBALS['hostname'] ?? '',
                $itemType,
            );
        }

        /**
         * Updates the password
         */
        if ($request->hasBodyParam('change_pw')) {
            $GLOBALS['message'] = $serverPrivileges->updatePassword(
                $GLOBALS['errorUrl'],
                $GLOBALS['username'] ?? '',
                $GLOBALS['hostname'] ?? '',
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
            $queries = $serverPrivileges->getDataForQueries($queries, $queriesForDisplay);
            $GLOBALS['message'] = Message::success();
            $GLOBALS['sql_query'] = implode("\n", $queries);
        }

        /**
         * Reloads the privilege tables into memory
         */
        $messageRet = $serverPrivileges->updateMessageForReload();
        if ($messageRet !== null) {
            $GLOBALS['message'] = $messageRet;
            unset($messageRet);
        }

        /**
         * If we are in an Ajax request for Create User/Edit User/Revoke User/
         * Flush Privileges, show $message and return.
         */
        if (
            $request->isAjax()
            && empty($_REQUEST['ajax_page_request'])
            && ! $request->hasQueryParam('export')
            && $request->getParsedBodyParam('submit_mult') !== 'export'
            && ((! $request->hasQueryParam('initial') || $request->getQueryParam('initial') === '')
                || $request->getParsedBodyParam('delete') === __('Go'))
            && ! $request->hasQueryParam('showall')
        ) {
            $extraData = $serverPrivileges->getExtraDataForAjaxBehavior(
                $password ?? '',
                $GLOBALS['sql_query'] ?? '',
                $GLOBALS['hostname'] ?? '',
                $GLOBALS['username'] ?? '',
            );

            if (! empty($GLOBALS['message']) && $GLOBALS['message'] instanceof Message) {
                $this->response->setRequestStatus($GLOBALS['message']->isSuccess());
                $this->response->addJSON('message', $GLOBALS['message']);
                $this->response->addJSON($extraData);

                return $this->response->response();
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
            /** @var string[]|null $selectedUsers */
            $selectedUsers = $request->getParsedBodyParam('selected_usr');

            $title = $this->getExportPageTitle($GLOBALS['username'] ?? '', $GLOBALS['hostname'] ?? '', $selectedUsers);

            $export = $serverPrivileges->getExportUserDefinitionTextarea(
                $GLOBALS['username'] ?? '',
                $GLOBALS['hostname'] ?? '',
                $selectedUsers,
            );

            unset($GLOBALS['username'], $GLOBALS['hostname']);

            if ($request->isAjax()) {
                $this->response->addJSON('message', $export);
                $this->response->addJSON('title', $title);

                return $this->response->response();
            }

            $this->response->addHTML('<h2>' . $title . '</h2>' . $export);
        }

        // Show back the form if an error occurred
        if ($request->hasQueryParam('adduser') || $addUserError === true) {
            // Add user
            $this->response->addHTML($serverPrivileges->getHtmlForAddUser(
                $serverPrivileges->escapeGrantWildcards(is_string($GLOBALS['dbname']) ? $GLOBALS['dbname'] : ''),
            ));
        } else {
            if (isset($GLOBALS['dbname']) && ! is_array($GLOBALS['dbname'])) {
                $urlDbname = urlencode(
                    str_replace(
                        ['\_', '\%'],
                        ['_', '%'],
                        $GLOBALS['dbname'],
                    ),
                );
            }

            if (! isset($GLOBALS['username'])) {
                // No username is given --> display the overview
                $this->response->addHTML($serverPrivileges->getHtmlForUserOverview(
                    $userPrivileges,
                    $request->getQueryParam('initial'),
                ));
            } elseif (! empty($routinename)) {
                $this->response->addHTML(
                    $serverPrivileges->getHtmlForRoutineSpecificPrivileges(
                        $GLOBALS['username'],
                        $GLOBALS['hostname'] ?? '',
                        is_string($GLOBALS['dbname']) ? $GLOBALS['dbname'] : '',
                        $routinename,
                        $serverPrivileges->escapeGrantWildcards($urlDbname ?? ''),
                    ),
                );
            } else {
                // A user was selected -> display the user's properties
                // In an Ajax request, prevent cached values from showing
                if ($request->isAjax()) {
                    $this->response->addHeader('Cache-Control', 'no-cache');
                }

                $this->response->addHTML(
                    $serverPrivileges->getHtmlForUserProperties(
                        $dbnameIsWildcard,
                        $serverPrivileges->escapeGrantWildcards($urlDbname ?? ''),
                        $GLOBALS['username'],
                        $GLOBALS['hostname'] ?? '',
                        $GLOBALS['dbname'] ?? '',
                        $tablename ?? '',
                        $request->getRoute(),
                    ),
                );
            }
        }

        if ($relationParameters->configurableMenusFeature === null) {
            return $this->response->response();
        }

        $this->response->addHTML('</div>');

        return $this->response->response();
    }

    private function getExportPageTitle(string $username, string $hostname, array|null $selectedUsers): string
    {
        if ($selectedUsers !== null) {
            return __('Privileges');
        }

        return __('User') . ' `' . htmlspecialchars($username)
            . '`@`' . htmlspecialchars($hostname) . '`';
    }
}
