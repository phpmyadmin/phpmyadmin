<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationCleanup;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
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
#[Route('/server/privileges', ['GET', 'POST'])]
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

        $errorUrl = Url::getFromRoute('/');

        if ($this->dbi->isSuperUser()) {
            $this->dbi->selectDb('mysql');
        }

        $serverPrivileges->username = $serverPrivileges->getUsernameParam($request);
        $serverPrivileges->hostname = $serverPrivileges->getHostnameParam($request);
        $databaseName = $serverPrivileges->getDbname($request);
        $tablename = $serverPrivileges->getTablename($request);
        $routinename = $serverPrivileges->getRoutinename($request);
        $dbnameIsWildcard = $serverPrivileges->isDatabaseNameWildcard($databaseName);

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
            && $serverPrivileges->username === $request->getParsedBodyParam('old_username')
            && $serverPrivileges->hostname === $request->getParsedBodyParam('old_hostname')
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
            $request->getParsedBodyParamAsString('old_username', ''),
            $request->getParsedBodyParamAsString('old_hostname', ''),
        );

        /**
         * Adds a user
         *   (Changes / copies a user, part II)
         */
        $queries = [];
        $queriesForDisplay = null;
        Current::$sqlQuery = '';
        $addUserError = false;
        if ($request->hasBodyParam('adduser_submit') || $request->hasBodyParam('change_copy')) {
            $hostname = $serverPrivileges->getHostname(
                $request->getParsedBodyParamAsString('pred_hostname', ''),
                $serverPrivileges->hostname ?? '',
            );
            [
                $retMessage,
                $queries,
                $queriesForDisplay,
                Current::$sqlQuery,
                $addUserError,
            ] = $serverPrivileges->addUser(
                is_string($databaseName) ? $databaseName : '',
                $serverPrivileges->username ?? '',
                $hostname,
                $password,
                $relationParameters->configurableMenusFeature !== null,
            );
            //update the old variables
            if (isset($retMessage)) {
                Current::$message = $retMessage;
                unset($retMessage);
            }
        }

        /**
         * Changes / copies a user, part III
         */
        if (
            $request->hasBodyParam('change_copy')
            && $serverPrivileges->username !== null
            && $serverPrivileges->hostname !== null
        ) {
            $queries = $serverPrivileges->getDbSpecificPrivsQueriesForChangeOrCopyUser(
                $queries,
                $serverPrivileges->username,
                $serverPrivileges->hostname,
                $request->getParsedBodyParamAsString('old_username'),
                $request->getParsedBodyParamAsString('old_hostname'),
            );
        }

        $itemType = '';
        if ($routinename !== '' && is_string($databaseName)) {
            $itemType = $serverPrivileges->getRoutineType($databaseName, $routinename);
        }

        /**
         * Updates privileges
         */
        if ($request->hasBodyParam('update_privs')) {
            if (is_array($databaseName)) {
                $statements = [];
                foreach ($databaseName as $key => $dbName) {
                    [$statements[$key], Current::$message] = $serverPrivileges->updatePrivileges(
                        $serverPrivileges->username ?? '',
                        $serverPrivileges->hostname ?? '',
                        $tablename ?? $routinename,
                        $dbName,
                        $itemType,
                    );
                }

                Current::$sqlQuery = implode("\n", $statements);
            } else {
                [Current::$sqlQuery, Current::$message] = $serverPrivileges->updatePrivileges(
                    $serverPrivileges->username ?? '',
                    $serverPrivileges->hostname ?? '',
                    $tablename ?? $routinename,
                    $databaseName ?? '',
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
            $serverPrivileges->setUserGroup(
                $serverPrivileges->username ?? '',
                $request->getParsedBodyParamAsString('userGroup', ''),
            );
            Current::$message = Message::success();
        }

        /**
         * Revokes Privileges
         */
        if ($request->hasBodyParam('revokeall')) {
            [Current::$message, Current::$sqlQuery] = $serverPrivileges->getMessageAndSqlQueryForPrivilegesRevoke(
                is_string($databaseName) ? $databaseName : '',
                $tablename ?? $routinename,
                $serverPrivileges->username ?? '',
                $serverPrivileges->hostname ?? '',
                $itemType,
            );
        }

        /**
         * Updates the password
         */
        if ($request->hasBodyParam('change_pw')) {
            Current::$message = $serverPrivileges->updatePassword(
                $errorUrl,
                $serverPrivileges->username ?? '',
                $serverPrivileges->hostname ?? '',
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
                [Current::$sqlQuery, Current::$message] = $serverPrivileges->deleteUser($queries);
            }
        }

        /**
         * Changes / copies a user, part V
         */
        if ($request->hasBodyParam('change_copy')) {
            $queries = $serverPrivileges->getDataForQueries($queries, $queriesForDisplay);
            Current::$message = Message::success();
            Current::$sqlQuery = implode("\n", $queries);
        }

        /**
         * Reloads the privilege tables into memory
         */
        $messageRet = $serverPrivileges->updateMessageForReload();
        if ($messageRet !== null) {
            Current::$message = $messageRet;
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
                Current::$sqlQuery,
                $serverPrivileges->hostname ?? '',
                $serverPrivileges->username ?? '',
                ! is_array($databaseName) ? $databaseName : null,
            );

            if (Current::$message instanceof Message) {
                $this->response->setRequestStatus(Current::$message->isSuccess());
                $this->response->addJSON('message', Current::$message);
                $this->response->addJSON($extraData);

                return $this->response->response();
            }
        }

        /**
         * Displays the links
         */
        if (Current::$message !== null) {
            $this->response->addHTML(Generator::getMessage(Current::$message));
            Current::$message = null;
        }

        // export user definition
        if ($request->hasQueryParam('export') || $request->getParsedBodyParam('submit_mult') === 'export') {
            /** @var string[]|null $selectedUsers */
            $selectedUsers = $request->getParsedBodyParam('selected_usr');

            $title = $this->getExportPageTitle(
                $serverPrivileges->username ?? '',
                $serverPrivileges->hostname ?? '',
                $selectedUsers,
            );

            $export = $serverPrivileges->getExportUserDefinitionTextarea(
                $serverPrivileges->username ?? '',
                $serverPrivileges->hostname ?? '',
                $selectedUsers,
            );

            $serverPrivileges->username = null;
            $serverPrivileges->hostname = null;

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
                $serverPrivileges->escapeGrantWildcards(is_string($databaseName) ? $databaseName : ''),
            ));
        } else {
            if (is_string($databaseName)) {
                $urlDbname = urlencode(
                    str_replace(
                        ['\_', '\%'],
                        ['_', '%'],
                        $databaseName,
                    ),
                );
            }

            if ($serverPrivileges->username === null) {
                // No username is given --> display the overview
                $this->response->addHTML($serverPrivileges->getHtmlForUserOverview(
                    $userPrivileges,
                    $request->getQueryParam('initial'),
                ));
            } elseif ($routinename !== '') {
                $this->response->addHTML(
                    $serverPrivileges->getHtmlForRoutineSpecificPrivileges(
                        $serverPrivileges->username,
                        $serverPrivileges->hostname ?? '',
                        is_string($databaseName) ? $databaseName : '',
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
                        $serverPrivileges->username,
                        $serverPrivileges->hostname ?? '',
                        $databaseName ?? '',
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
