<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Config;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Exceptions\UserPasswordUpdateFailure;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Url;
use PhpMyAdmin\UserPassword;

use function __;

/**
 * Displays and handles the form where the user can change their password.
 */
#[Route('/user-password', ['GET', 'POST'])]
final readonly class UserPasswordController implements InvocableController
{
    public function __construct(
        private ResponseRenderer $response,
        private UserPassword $userPassword,
        private DatabaseInterface $dbi,
        private Config $config,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $this->response->addScriptFiles(['server/privileges.js', 'vendor/zxcvbn-ts.js']);

        /**
         * Displays an error message and exits if the user isn't allowed to use this
         * script
         */
        $hasAccessPrivilege = $this->config->config->ShowChgPassword || $this->dbi->selectDb('mysql');

        if ($this->config->selectedServer['auth_type'] === 'config' || ! $hasAccessPrivilege) {
            $this->response->addHTML(Message::error(
                __('You don\'t have sufficient privileges to be here right now!'),
            ));

            return $this->response->response();
        }

        $noPass = $request->getParsedBodyParamAsStringOrNull('nopass');

        $changePasswordMessage = null;
        /**
         * If the "change password" form has been submitted, checks for valid values
         * and submit the query or logout
         */
        if ($noPass !== null) {
            $pmaPw = $request->getParsedBodyParamAsString('pma_pw');
            $pmaPw2 = $request->getParsedBodyParamAsString('pma_pw2');

            $password = $noPass === '1' ? '' : $pmaPw;
            $changePasswordMessage = $this->userPassword->setChangePasswordMsg($pmaPw, $pmaPw2, $noPass === '1');

            if (! $changePasswordMessage->isError()) {
                try {
                    $sqlQuery = $this->userPassword->changePassword(
                        $password,
                        $request->getParsedBodyParamAsStringOrNull('authentication_plugin'),
                    );
                } catch (UserPasswordUpdateFailure $exception) {
                    if ($request->isAjax()) {
                        $this->response->setRequestStatus(false);
                        $this->response->addJSON('message', $exception->getMessage());

                        return $this->response->response();
                    }

                    $backUrlHtml = Generator::getBackUrlHtml(Url::getFromRoute('/user-password'));
                    $this->response->addHTML($exception->getMessage() . $backUrlHtml);

                    return $this->response->response();
                }

                if ($request->isAjax()) {
                    $sqlQuery = Generator::getMessage($changePasswordMessage, $sqlQuery);
                    $this->response->addJSON('message', $sqlQuery);

                    return $this->response->response();
                }

                $this->response->addHTML('<h1>' . __('Change password') . '</h1>' . "\n\n");
                $this->response->addHTML(Generator::getMessage($changePasswordMessage, $sqlQuery));
                $this->response->render('user_password', []);

                return $this->response->response();
            }

            if ($request->isAjax()) {
                $this->response->addJSON('message', $changePasswordMessage);
                $this->response->setRequestStatus(false);

                return $this->response->response();
            }
        }

        /**
         * If the "change password" form hasn't been submitted or the values submitted
         * aren't valid -> displays the form
         */

        // Displays an error message if required
        if ($changePasswordMessage instanceof Message) {
            $this->response->addHTML($changePasswordMessage);
        }

        $this->response->addHTML($this->userPassword->getFormForChangePassword('', '', $request->getRoute()));

        return $this->response->response();
    }
}
