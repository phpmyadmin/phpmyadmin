<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

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
        $GLOBALS['msg'] ??= null;

        $this->addScriptFiles(['server/privileges.js', 'vendor/zxcvbn-ts.js']);

        /**
         * Displays an error message and exits if the user isn't allowed to use this
         * script
         */
        if (! $GLOBALS['cfg']['ShowChgPassword']) {
            $GLOBALS['cfg']['ShowChgPassword'] = $this->dbi->selectDb('mysql');
        }

        if ($GLOBALS['cfg']['Server']['auth_type'] === 'config' || ! $GLOBALS['cfg']['ShowChgPassword']) {
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
            if ($noPass == '1') {
                $password = '';
            } else {
                $password = $pmaPw;
            }

            $GLOBALS['change_password_message'] = $this->userPassword->setChangePasswordMsg(
                $pmaPw,
                $pmaPw2,
                (bool) $noPass,
            );
            $GLOBALS['msg'] = $GLOBALS['change_password_message']['msg'];

            if (! $GLOBALS['change_password_message']['error']) {
                $sqlQuery = $this->userPassword->changePassword(
                    $password,
                    $request->getParsedBodyParam('authentication_plugin'),
                );

                if ($this->response->isAjax()) {
                    $sqlQuery = Generator::getMessage($GLOBALS['change_password_message']['msg'], $sqlQuery, 'success');
                    $this->response->addJSON('message', $sqlQuery);

                    return;
                }

                $this->response->addHTML('<h1>' . __('Change password') . '</h1>' . "\n\n");
                $this->response->addHTML(Generator::getMessage($GLOBALS['msg'], $sqlQuery, 'success'));
                $this->render('user_password');

                return;
            }

            if ($this->response->isAjax()) {
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
        if (isset($GLOBALS['msg'])) {
            $this->response->addHTML($GLOBALS['msg']->getDisplay());
        }

        $this->response->addHTML($this->userPassword->getFormForChangePassword(
            $GLOBALS['username'],
            $GLOBALS['hostname'],
            $request->getRoute(),
        ));
    }
}
