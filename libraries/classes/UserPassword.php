<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PhpMyAdmin\UserPassword class
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin;

use PhpMyAdmin\Core;
use PhpMyAdmin\Message;
use PhpMyAdmin\Response;
use PhpMyAdmin\Server\Privileges;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

/**
 * Functions for user_password.php
 *
 * @package PhpMyAdmin
 */
class UserPassword
{
    /**
     * Send the message as an ajax request
     *
     * @param array  $change_password_message Message to display
     * @param string $sql_query               SQL query executed
     *
     * @return void
     */
    public function getChangePassMessage(array $change_password_message, $sql_query = '')
    {
        $response = Response::getInstance();
        if ($response->isAjax()) {
            /**
             * If in an Ajax request, we don't need to show the rest of the page
             */
            if ($change_password_message['error']) {
                $response->addJSON('message', $change_password_message['msg']);
                $response->setRequestStatus(false);
            } else {
                $sql_query = Util::getMessage(
                    $change_password_message['msg'],
                    $sql_query,
                    'success'
                );
                $response->addJSON('message', $sql_query);
            }
            exit;
        }
    }

    /**
     * Generate the message
     *
     * @return array   error value and message
     */
    public function setChangePasswordMsg()
    {
        $error = false;
        $message = Message::success(__('The profile has been updated.'));

        if (($_POST['nopass'] != '1')) {
            if (strlen($_POST['pma_pw']) === 0 || strlen($_POST['pma_pw2']) === 0) {
                $message = Message::error(__('The password is empty!'));
                $error = true;
            } elseif ($_POST['pma_pw'] !== $_POST['pma_pw2']) {
                $message = Message::error(
                    __('The passwords aren\'t the same!')
                );
                $error = true;
            } elseif (strlen($_POST['pma_pw']) > 256) {
                $message = Message::error(__('Password is too long!'));
                $error = true;
            }
        }
        return array('error' => $error, 'msg' => $message);
    }

    /**
     * Change the password
     *
     * @param string $password                New password
     * @param string $message                 Message
     * @param array  $change_password_message Message to show
     *
     * @return void
     */
    public function changePassword($password, $message, array $change_password_message)
    {
        global $auth_plugin;

        $hashing_function = $this->changePassHashingFunction();

        list($username, $hostname) = $GLOBALS['dbi']->getCurrentUserAndHost();

        $serverType = Util::getServerType();
        $serverVersion = $GLOBALS['dbi']->getVersion();

        if (isset($_POST['authentication_plugin'])
            && ! empty($_POST['authentication_plugin'])
        ) {
            $orig_auth_plugin = $_POST['authentication_plugin'];
        } else {
            $orig_auth_plugin = Privileges::getCurrentAuthenticationPlugin(
                'change', $username, $hostname
            );
        }

        $sql_query = 'SET password = '
            . (($password == '') ? '\'\'' : $hashing_function . '(\'***\')');

        if ($serverType == 'MySQL'
            && $serverVersion >= 50706
        ) {
            $sql_query = 'ALTER USER \'' . $GLOBALS['dbi']->escapeString($username)
                . '\'@\'' . $GLOBALS['dbi']->escapeString($hostname)
                . '\' IDENTIFIED WITH ' . $orig_auth_plugin . ' BY '
                . (($password == '') ? '\'\'' : '\'***\'');
        } elseif (($serverType == 'MySQL'
            && $serverVersion >= 50507)
            || ($serverType == 'MariaDB'
            && $serverVersion >= 50200)
        ) {
            // For MySQL versions 5.5.7+ and MariaDB versions 5.2+,
            // explicitly set value of `old_passwords` so that
            // it does not give an error while using
            // the PASSWORD() function
            if ($orig_auth_plugin == 'sha256_password') {
                $value = 2;
            } else {
                $value = 0;
            }
            $GLOBALS['dbi']->tryQuery('SET `old_passwords` = ' . $value . ';');
        }

        $this->changePassUrlParamsAndSubmitQuery(
            $username, $hostname, $password,
            $sql_query, $hashing_function, $orig_auth_plugin
        );

        $auth_plugin->handlePasswordChange($password);
        $this->getChangePassMessage($change_password_message, $sql_query);
        $this->changePassDisplayPage($message, $sql_query);
    }

    /**
     * Generate the hashing function
     *
     * @return string  $hashing_function
     */
    private function changePassHashingFunction()
    {
        if (Core::isValid(
            $_POST['authentication_plugin'], 'identical', 'mysql_old_password'
        )) {
            $hashing_function = 'OLD_PASSWORD';
        } else {
            $hashing_function = 'PASSWORD';
        }
        return $hashing_function;
    }

    /**
     * Changes password for a user
     *
     * @param string $username         Username
     * @param string $hostname         Hostname
     * @param string $password         Password
     * @param string $sql_query        SQL query
     * @param string $hashing_function Hashing function
     * @param string $orig_auth_plugin Original Authentication Plugin
     *
     * @return void
     */
    private function changePassUrlParamsAndSubmitQuery(
        $username, $hostname, $password, $sql_query, $hashing_function, $orig_auth_plugin
    ) {
        $err_url = 'user_password.php' . Url::getCommon();

        $serverType = Util::getServerType();
        $serverVersion = $GLOBALS['dbi']->getVersion();

        if ($serverType == 'MySQL' && $serverVersion >= 50706) {
            $local_query = 'ALTER USER \'' . $GLOBALS['dbi']->escapeString($username)
                . '\'@\'' . $GLOBALS['dbi']->escapeString($hostname) . '\''
                . ' IDENTIFIED with ' . $orig_auth_plugin . ' BY '
                . (($password == '')
                ? '\'\''
                : '\'' . $GLOBALS['dbi']->escapeString($password) . '\'');
        } elseif ($serverType == 'MariaDB'
            && $serverVersion >= 50200
            && $serverVersion < 100100
            && $orig_auth_plugin !== ''
        ) {
            if ($orig_auth_plugin == 'mysql_native_password') {
                // Set the hashing method used by PASSWORD()
                // to be 'mysql_native_password' type
                $GLOBALS['dbi']->tryQuery('SET old_passwords = 0;');
            } elseif ($orig_auth_plugin == 'sha256_password') {
                // Set the hashing method used by PASSWORD()
                // to be 'sha256_password' type
                $GLOBALS['dbi']->tryQuery('SET `old_passwords` = 2;');
            }

            $hashedPassword = Privileges::getHashedPassword($_POST['pma_pw']);

            $local_query = "UPDATE `mysql`.`user` SET"
                . " `authentication_string` = '" . $hashedPassword
                . "', `Password` = '', "
                . " `plugin` = '" . $orig_auth_plugin . "'"
                . " WHERE `User` = '" . $GLOBALS['dbi']->escapeString($username)
                . "' AND Host = '" . $GLOBALS['dbi']->escapeString($hostname) . "';";
        } else {
            $local_query = 'SET password = ' . (($password == '')
                ? '\'\''
                : $hashing_function . '(\''
                    . $GLOBALS['dbi']->escapeString($password) . '\')');
        }
        if (! @$GLOBALS['dbi']->tryQuery($local_query)) {
            Util::mysqlDie(
                $GLOBALS['dbi']->getError(),
                $sql_query,
                false,
                $err_url
            );
        }

        // Flush privileges after successful password change
        $GLOBALS['dbi']->tryQuery("FLUSH PRIVILEGES;");
    }

    /**
     * Display the page
     *
     * @param string $message   Message
     * @param string $sql_query SQL query
     *
     * @return void
     */
    private function changePassDisplayPage($message, $sql_query)
    {
        echo '<h1>' , __('Change password') , '</h1>' , "\n\n";
        echo Util::getMessage(
            $message, $sql_query, 'success'
        );
        echo '<a href="index.php' , Url::getCommon()
            , ' target="_parent">' , "\n"
            , '<strong>' , __('Back') , '</strong></a>';
        exit;
    }
}
