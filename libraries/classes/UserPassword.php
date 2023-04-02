<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Plugins\AuthenticationPluginFactory;
use PhpMyAdmin\Query\Compatibility;
use PhpMyAdmin\Server\Privileges;

use function __;
use function strlen;

/**
 * Functions for user password
 */
class UserPassword
{
    public function __construct(
        private Privileges $serverPrivileges,
        private AuthenticationPluginFactory $authPluginFactory,
        private DatabaseInterface $dbi,
    ) {
    }

    /**
     * Generate the message
     *
     * @return mixed[]   error value and message
     */
    public function setChangePasswordMsg(string $pmaPw, string $pmaPw2, bool $skipPassword): array
    {
        $error = false;
        $message = Message::success(__('The profile has been updated.'));

        if ($skipPassword === false) {
            if (strlen($pmaPw) === 0 || strlen($pmaPw2) === 0) {
                $message = Message::error(__('The password is empty!'));
                $error = true;
            } elseif ($pmaPw !== $pmaPw2) {
                $message = Message::error(
                    __('The passwords aren\'t the same!'),
                );
                $error = true;
            } elseif (strlen($pmaPw) > 256) {
                $message = Message::error(__('Password is too long!'));
                $error = true;
            }
        }

        return ['error' => $error, 'msg' => $message];
    }

    /**
     * Change the password
     *
     * @param string $password New password
     */
    public function changePassword(string $password, string|null $authenticationPlugin): string
    {
        $hashingFunction = $this->changePassHashingFunction($authenticationPlugin);

        [$username, $hostname] = $this->dbi->getCurrentUserAndHost();

        $serverVersion = $this->dbi->getVersion();

        if ($authenticationPlugin !== null && $authenticationPlugin !== '' && $authenticationPlugin !== '0') {
            $origAuthPlugin = $authenticationPlugin;
        } else {
            $origAuthPlugin = $this->serverPrivileges->getCurrentAuthenticationPlugin($username, $hostname);
        }

        $sqlQuery = 'SET password = '
            . ($password == '' ? '\'\'' : $hashingFunction . '(\'***\')');

        $isPerconaOrMySql = Compatibility::isMySqlOrPerconaDb();
        if ($isPerconaOrMySql && $serverVersion >= 50706) {
            $sqlQuery = 'ALTER USER ' . $this->dbi->quoteString($username)
                . '@' . $this->dbi->quoteString($hostname)
                . ' IDENTIFIED WITH ' . $origAuthPlugin . ' BY '
                . ($password == '' ? '\'\'' : '\'***\'');
        } elseif (
            ($isPerconaOrMySql && $serverVersion >= 50507)
            || (Compatibility::isMariaDb() && $serverVersion >= 50200)
        ) {
            // For MySQL and Percona versions 5.5.7+ and MariaDB versions 5.2+,
            // explicitly set value of `old_passwords` so that
            // it does not give an error while using
            // the PASSWORD() function
            if ($origAuthPlugin === 'sha256_password') {
                $value = 2;
            } else {
                $value = 0;
            }

            $this->dbi->tryQuery('SET `old_passwords` = ' . $value . ';');
        }

        $this->changePassUrlParamsAndSubmitQuery(
            $username,
            $hostname,
            $password,
            $sqlQuery,
            $hashingFunction,
            $origAuthPlugin,
        );

        $authPlugin = $this->authPluginFactory->create();
        $authPlugin->handlePasswordChange($password);

        return $sqlQuery;
    }

    private function changePassHashingFunction(string|null $authenticationPlugin): string
    {
        if ($authenticationPlugin === 'mysql_old_password') {
            return 'OLD_PASSWORD';
        }

        return 'PASSWORD';
    }

    /**
     * Changes password for a user
     *
     * @param string $username        Username
     * @param string $hostname        Hostname
     * @param string $password        Password
     * @param string $sqlQuery        SQL query
     * @param string $hashingFunction Hashing function
     * @param string $origAuthPlugin  Original Authentication Plugin
     */
    private function changePassUrlParamsAndSubmitQuery(
        string $username,
        string $hostname,
        string $password,
        string $sqlQuery,
        string $hashingFunction,
        string $origAuthPlugin,
    ): void {
        $errUrl = Url::getFromRoute('/user-password');

        $serverVersion = $this->dbi->getVersion();

        if (Compatibility::isMySqlOrPerconaDb() && $serverVersion >= 50706) {
            $localQuery = 'ALTER USER ' . $this->dbi->quoteString($username)
                . '@' . $this->dbi->quoteString($hostname)
                . ' IDENTIFIED with ' . $origAuthPlugin . ' BY '
                . $this->dbi->quoteString($password);
        } elseif (
            Compatibility::isMariaDb()
            && $serverVersion >= 50200
            && $serverVersion < 100100
            && $origAuthPlugin !== ''
        ) {
            if ($origAuthPlugin === 'mysql_native_password') {
                // Set the hashing method used by PASSWORD()
                // to be 'mysql_native_password' type
                $this->dbi->tryQuery('SET old_passwords = 0;');
            } elseif ($origAuthPlugin === 'sha256_password') {
                // Set the hashing method used by PASSWORD()
                // to be 'sha256_password' type
                $this->dbi->tryQuery('SET `old_passwords` = 2;');
            }

            $hashedPassword = $this->serverPrivileges->getHashedPassword($_POST['pma_pw']);

            $localQuery = 'UPDATE `mysql`.`user` SET'
                . " `authentication_string` = '" . $hashedPassword
                . "', `Password` = '', "
                . ' `plugin` = ' . $this->dbi->quoteString($origAuthPlugin)
                . ' WHERE `User` = ' . $this->dbi->quoteString($username)
                . ' AND Host = ' . $this->dbi->quoteString($hostname) . ';';
        } else {
            $localQuery = 'SET password = ' . ($password == ''
                ? '\'\''
                : $hashingFunction . '(' . $this->dbi->quoteString($password) . ')');
        }

        if (! @$this->dbi->tryQuery($localQuery)) {
            Generator::mysqlDie(
                $this->dbi->getError(),
                $sqlQuery,
                false,
                $errUrl,
            );
        }

        // Flush privileges after successful password change
        $this->dbi->tryQuery('FLUSH PRIVILEGES;');
    }

    /** @psalm-param non-empty-string $route */
    public function getFormForChangePassword(string|null $username, string|null $hostname, string $route): string
    {
        return $this->serverPrivileges->getFormForChangePassword($username ?? '', $hostname ?? '', false, $route);
    }
}
