<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Config;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\UserPassword;

use function __;

/**
 * Displays and handles the form where the user can change their password.
 */
class UserPasswordController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private UserPassword $userPassword,
        private DatabaseInterface $dbi,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['hostname'] ??= null;
        $GLOBALS['username'] ??= null;
        $GLOBALS['change_password_message'] ??= null;

        $this->addScriptFiles(['server/privileges.js', 'vendor/zxcvbn-ts.js']);

        $config = Config::getInstance();
        /**
         * Displays an error message and exits if the user isn't allowed to use this
         * script
         */
        if (! $config->settings['ShowChgPassword']) {
            $config->settings['ShowChgPassword'] = $this->dbi->selectDb('mysql');
        }

        if ($config->selectedServer['auth_type'] === 'config' || ! $config->settings['ShowChgPassword']) {
            $this->response->addHTML(Message::error(
                __('You don\'t have sufficient privileges to be here right now!'),
            )->getDisplay());

            return;
        }

        $noPass = $request->getParsedBodyParam('nopass');
        $pmaPw = $request->getParsedBodyParam('pma_pw');
        $pmaPw2 = $request->getParsedBodyParam('pma_pw2');

        /**
         * If the "change password" form has been submitted, checks for valid values
         * and submit the query or logout
         */
        if ($noPass !== null) {
            $password = $noPass == '1' ? '' : $pmaPw;
            $GLOBALS['change_password_message'] = $this->userPassword->setChangePasswordMsg(
                $pmaPw,
                $pmaPw2,
                (bool) $noPass,
            );
            $message = $GLOBALS['change_password_message']['msg'];

            if (! $GLOBALS['change_password_message']['error']) {
                $sqlQuery = $this->userPassword->changePassword(
                    $password,
                    $request->getParsedBodyParam('authentication_plugin'),
                );

                if ($request->isAjax()) {
                    $sqlQuery = Generator::getMessage($GLOBALS['change_password_message']['msg'], $sqlQuery, 'success');
                    $this->response->addJSON('message', $sqlQuery);

                    return;
                }

                $this->response->addHTML('<h1>' . __('Change password') . '</h1>' . "\n\n");
                $this->response->addHTML(Generator::getMessage($message, $sqlQuery, 'success'));
                $this->render('user_password');

                return;
            }

            if ($request->isAjax()) {
                $this->response->addJSON('message', $GLOBALS['change_password_message']['msg']);
                $this->response->setRequestStatus(false);

                return;
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

        $this->response->addHTML($this->userPassword->getFormForChangePassword(
            $GLOBALS['username'],
            $GLOBALS['hostname'],
            $request->getRoute(),
        ));
    }
}
