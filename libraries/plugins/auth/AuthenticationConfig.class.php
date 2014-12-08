<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Config Authentication plugin for phpMyAdmin
 *
 * @package    PhpMyAdmin-Authentication
 * @subpackage Config
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* Get the authentication interface */
require_once 'libraries/plugins/AuthenticationPlugin.class.php';

/**
 * Handles the config authentication method
 *
 * @package PhpMyAdmin-Authentication
 */
class AuthenticationConfig extends AuthenticationPlugin
{
    /**
     * Displays authentication form
     *
     * @return boolean always true
     */
    public function auth()
    {
        $response = PMA_Response::getInstance();
        if ($response->isAjax()) {
            $response->isSuccess(false);
            // reload_flag removes the token parameter from the URL and reloads
            $response->addJSON('reload_flag', '1');
            if (defined('TESTSUITE')) {
                return true;
            } else {
                exit;
            }
        }
        return true;
    }

    /**
     * Gets advanced authentication settings
     *
     * @return boolean always true
     */
    public function authCheck()
    {
        if ($GLOBALS['token_provided'] && $GLOBALS['token_mismatch']) {
            return false;
        }
        return true;
    }

    /**
     * Set the user and password after last checkings if required
     *
     * @return boolean always true
     */
    public function authSetUser()
    {
        // try to workaround PHP 5 session garbage collection which
        // looks at the session file's last modified time
        if (isset($_REQUEST['access_time'])) {
            $_SESSION['last_access_time'] = time()- $_REQUEST['access_time'];
        } else {
            $_SESSION['last_access_time'] = time();
        }

        return true;
    }

    /**
     * User is not allowed to login to MySQL -> authentication failed
     *
     * @global string  the MySQL error message PHP returns
     * @global string  the connection type (persistent or not)
     * @global string  the MySQL server port to use
     * @global string  the MySQL socket port to use
     * @global array   the current server settings
     * @global string  the font face to use in case of failure
     * @global string  the default font size to use in case of failure
     * @global string  the big font size to use in case of failure
     * @global boolean tell the "PMA_mysqlDie()" function headers have been
     *                 sent
     *
     * @return boolean   always true (no return indeed)
     */
    public function authFails()
    {
        $conn_error = $GLOBALS['dbi']->getError();
        if (! $conn_error) {
            $conn_error = __('Cannot connect: invalid settings.');
        }

        /* HTML header */
        $response = PMA_Response::getInstance();
        $response->getFooter()->setMinimal();
        $header = $response->getHeader();
        $header->setBodyId('loginform');
        $header->setTitle(__('Access denied!'));
        $header->disableMenuAndConsole();
        echo '<br /><br />
    <center>
        <h1>';
        echo sprintf(__('Welcome to %s'), ' phpMyAdmin ');
        echo '</h1>
    </center>
    <br />
    <table cellpadding="0" cellspacing="3" style="margin: 0 auto" width="80%">
        <tr>
            <td>';
        if (isset($GLOBALS['allowDeny_forbidden'])
            && $GLOBALS['allowDeny_forbidden']
        ) {
            trigger_error(__('Access denied!'), E_USER_NOTICE);
        } else {
            // Check whether user has configured something
            if ($GLOBALS['PMA_Config']->source_mtime == 0) {
                echo '<p>' . sprintf(
                    __(
                        'You probably did not create a configuration file.'
                        . ' You might want to use the %1$ssetup script%2$s to'
                        . ' create one.'
                    ),
                    '<a href="setup/">',
                    '</a>'
                ) . '</p>' . "\n";
            } elseif (! isset($GLOBALS['errno'])
                || (isset($GLOBALS['errno']) && $GLOBALS['errno'] != 2002)
                && $GLOBALS['errno'] != 2003
            ) {
                // if we display the "Server not responding" error, do not confuse
                // users by telling them they have a settings problem
                // (note: it's true that they could have a badly typed host name,
                // but anyway the current message tells that the server
                //  rejected the connection, which is not really what happened)
                // 2002 is the error given by mysqli
                // 2003 is the error given by mysql
                trigger_error(
                    __(
                        'phpMyAdmin tried to connect to the MySQL server, and the'
                        . ' server rejected the connection. You should check the'
                        . ' host, username and password in your configuration and'
                        . ' make sure that they correspond to the information given'
                        . ' by the administrator of the MySQL server.'
                    ), E_USER_WARNING
                );
            }
            echo PMA_Util::mysqlDie(
                $conn_error, '', true, '', false
            );
        }
        $GLOBALS['error_handler']->dispUserErrors();
        echo '</td>
        </tr>
        <tr>
            <td>' . "\n";
        echo '<a href="'
            . $GLOBALS['cfg']['DefaultTabServer']
            . PMA_URL_getCommon(array()) . '" class="button disableAjax">'
            . __('Retry to connect')
            . '</a>' . "\n";
        echo '</td>
        </tr>' . "\n";
        if (count($GLOBALS['cfg']['Servers']) > 1) {
            // offer a chance to login to other servers if the current one failed
            include_once './libraries/select_server.lib.php';
            echo '<tr>' . "\n";
            echo ' <td>' . "\n";
            echo PMA_selectServer(true, true);
            echo ' </td>' . "\n";
            echo '</tr>' . "\n";
        }
        echo '</table>' . "\n";
        if (!defined('TESTSUITE')) {
            exit;
        }
        return true;
    }
}
