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
    /** @var UserPassword */
    private $userPassword;

    /** @var DatabaseInterface */
    private $dbi;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        UserPassword $userPassword,
        DatabaseInterface $dbi
    ) {
        parent::__construct($response, $template);
        $this->userPassword = $userPassword;
        $this->dbi = $dbi;
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['hostname'] = $GLOBALS['hostname'] ?? null;
        $GLOBALS['username'] = $GLOBALS['username'] ?? null;
        $GLOBALS['password'] = $GLOBALS['password'] ?? null;
        $GLOBALS['change_password_message'] = $GLOBALS['change_password_message'] ?? null;
        $GLOBALS['msg'] = $GLOBALS['msg'] ?? null;

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
                __('You don\'t have sufficient privileges to be here right now!')
            )->getDisplay());

            return;
        }

        /**
         * If the "change password" form has been submitted, checks for valid values
         * and submit the query or logout
         */
        if (isset($_POST['nopass'])) {
            if ($_POST['nopass'] == '1') {
                $GLOBALS['password'] = '';
            } else {
                $GLOBALS['password'] = $_POST['pma_pw'];
            }

            $GLOBALS['change_password_message'] = $this->userPassword->setChangePasswordMsg();
            $GLOBALS['msg'] = $GLOBALS['change_password_message']['msg'];

            if (! $GLOBALS['change_password_message']['error']) {
                $sql_query = $this->userPassword->changePassword($GLOBALS['password']);

                if ($this->response->isAjax()) {
                    $sql_query = Generator::getMessage(
                        $GLOBALS['change_password_message']['msg'],
                        $sql_query,
                        'success'
                    );
                    $this->response->addJSON('message', $sql_query);

                    return;
                }

                $this->response->addHTML('<h1>' . __('Change password') . '</h1>' . "\n\n");
                $this->response->addHTML(Generator::getMessage($GLOBALS['msg'], $sql_query, 'success'));
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
            $request->getRoute()
        ));
    }
}
