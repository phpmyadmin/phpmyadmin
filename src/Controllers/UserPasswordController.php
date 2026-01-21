<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Config;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\MessageType;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
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
        $hasAccessPrivilege = true;
        if (! $this->config->settings['ShowChgPassword']) {
            $hasAccessPrivilege = $this->dbi->selectDb('mysql');
        }

        if ($this->config->selectedServer['auth_type'] === 'config' || ! $hasAccessPrivilege) {
            $this->response->addHTML(Message::error(
                __('You don\'t have sufficient privileges to be here right now!'),
            )->getDisplay());

            return $this->response->response();
        }

        $noPass = $request->getParsedBodyParamAsStringOrNull('nopass');

        /**
         * If the "change password" form has been submitted, checks for valid values
         * and submit the query or logout
         */
        if ($noPass !== null) {
            $pmaPw = $request->getParsedBodyParamAsString('pma_pw');
            $pmaPw2 = $request->getParsedBodyParamAsString('pma_pw2');

            $password = $noPass === '1' ? '' : $pmaPw;
            $changePasswordMessage = $this->userPassword->setChangePasswordMsg($pmaPw, $pmaPw2, $noPass === '1');
            $message = $changePasswordMessage['msg'];

            if (! $changePasswordMessage['error']) {
                $sqlQuery = $this->userPassword->changePassword(
                    $password,
                    $request->getParsedBodyParamAsStringOrNull('authentication_plugin'),
                );

                if ($request->isAjax()) {
                    $sqlQuery = Generator::getMessage($changePasswordMessage['msg'], $sqlQuery, MessageType::Success);
                    $this->response->addJSON('message', $sqlQuery);

                    return $this->response->response();
                }

                $this->response->addHTML('<h1>' . __('Change password') . '</h1>' . "\n\n");
                $this->response->addHTML(Generator::getMessage($message, $sqlQuery, MessageType::Success));
                $this->response->render('user_password', []);

                return $this->response->response();
            }

            if ($request->isAjax()) {
                $this->response->addJSON('message', $changePasswordMessage['msg']);
                $this->response->setRequestStatus(false);

                return $this->response->response();
            }
        }

        /**
         * If the "change password" form hasn't been submitted or the values submitted
         * aren't valid -> displays the form
         */

        // Displays an error message if required
        if (isset($message)) {
            $this->response->addHTML($message->getDisplay());
        }

        $this->response->addHTML($this->userPassword->getFormForChangePassword('', '', $request->getRoute()));

        return $this->response->response();
    }
}
