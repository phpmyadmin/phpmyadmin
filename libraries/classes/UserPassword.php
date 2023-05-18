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
     * @return array{error: bool, msg: Message} error value and message
     */
    public function setChangePasswordMsg(string $pmaPw, string $pmaPw2, bool $skipPassword): array
    {
        $error = false;
        $message = Message::success(__('The profile has been updated.'));

        if ($skipPassword === false) {
            if ($pmaPw === '' || $pmaPw2 === '') {
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

        $origAuthPlugin = $this->serverPrivileges->getCurrentAuthenticationPlugin($username, $hostname);
        $authPluginChanged = false;

        if (isset($_POST['authentication_plugin']) && ! empty($_POST['authentication_plugin'])) {
            if ($origAuthPlugin !== $_POST['authentication_plugin']) {
                $authPluginChanged = true;
            }

            $origAuthPlugin = $_POST['authentication_plugin'];
        }

        $sqlQuery = 'SET password = '
            . ($password == '' ? '\'\'' : $hashingFunction . '(\'***\')');

        $isPerconaOrMySql = Compatibility::isMySqlOrPerconaDb();
        if ($isPerconaOrMySql && $serverVersion >= 50706) {
            $sqlQuery = $this->getChangePasswordQueryAlterUserMySQL(
                $serverVersion,
                $username,
                $hostname,
                $origAuthPlugin,
                $password === '' ? '' : '***', // Mask it, preview mode
                $authPluginChanged,
            );
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
            $authPluginChanged,
        );

        $authPlugin = $this->authPluginFactory->create();
        $authPlugin->handlePasswordChange($password);

        return $sqlQuery;
    }

    private function getChangePasswordQueryAlterUserMySQL(
        int $serverVersion,
        string $username,
        string $hostname,
        string $authPlugin,
        string $password,
        bool $authPluginChanged,
    ): string {
        // Starting with MySQL 5.7.37 the security check changed
        // See: https://github.com/mysql/mysql-server/commit/b31a8a5d7805834ca2d25629c0e584d2c53b1a5b
        // See: https://github.com/phpmyadmin/phpmyadmin/issues/17654
        // That means that you should not try to change or state a plugin using IDENTIFIED WITH
        // Or it will say: Access denied; you need (at least one of) the CREATE USER privilege(s) for this operation
        // So let's avoid stating a plugin if it's not needed/changed

        if ($serverVersion >= 50706 && $serverVersion < 50737) {
            return 'ALTER USER ' . $GLOBALS['dbi']->quoteString($username)
                . '@' . $GLOBALS['dbi']->quoteString($hostname)
                . ' IDENTIFIED WITH ' . $authPlugin . ' BY '
                . ($password === '' ? "''" : '' . $GLOBALS['dbi']->quoteString($password) . '');
        }

        $sqlQuery = 'ALTER USER ' . $GLOBALS['dbi']->quoteString($username)
            . '@' . $GLOBALS['dbi']->quoteString($hostname) . ' IDENTIFIED';

        if ($authPluginChanged) {
            $sqlQuery .= ' WITH ' . $authPlugin;
        }

        return $sqlQuery . ' BY ' . (
            $password === '' ? "''" : $GLOBALS['dbi']->quoteString($password)
        );
    }

    /**
     * Generate the hashing function
     */
    private function changePassHashingFunction(string|null $authenticationPlugin): string
    {
        if ($authenticationPlugin === 'mysql_old_password') {
            return 'OLD_PASSWORD';
        }

        return 'PASSWORD';
    }

    /**
     * Changes password for a user
     */
    private function changePassUrlParamsAndSubmitQuery(
        string $username,
        string $hostname,
        string $password,
        string $sqlQuery,
        string $hashingFunction,
        string $origAuthPlugin,
        bool $authPluginChanged,
    ): void {
        $errUrl = Url::getFromRoute('/user-password');

        $serverVersion = $this->dbi->getVersion();
        $isPerconaOrMySql = Compatibility::isMySqlOrPerconaDb();

        if ($isPerconaOrMySql && $serverVersion >= 50706) {
            $localQuery = $this->getChangePasswordQueryAlterUserMySQL(
                $serverVersion,
                $username,
                $hostname,
                $origAuthPlugin,
                $password,
                $authPluginChanged,
            );
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
