<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * HTTP Authentication plugin for phpMyAdmin.
 * NOTE: Requires PHP loaded as a Apache module.
 *
 * @package    PhpMyAdmin-Authentication
 * @subpackage HTTP
 */
namespace PhpMyAdmin\Plugins\Auth;

use PhpMyAdmin\Plugins\AuthenticationPlugin;
use PhpMyAdmin\Message;
use PhpMyAdmin\Response;
use PhpMyAdmin\Config;
use PhpMyAdmin\Core;

require_once './libraries/hash.lib.php';

/**
 * Handles the HTTP authentication methods
 *
 * @package PhpMyAdmin-Authentication
 */
class AuthenticationHttp extends AuthenticationPlugin
{
    /**
     * Displays authentication form and redirect as necessary
     *
     * @return boolean   always true (no return indeed)
     */
    public function showLoginForm()
    {
        $response = Response::getInstance();
        if ($response->isAjax()) {
            $response->setRequestStatus(false);
            // reload_flag removes the token parameter from the URL and reloads
            $response->addJSON('reload_flag', '1');
            if (defined('TESTSUITE')) {
                return true;
            } else {
                exit;
            }
        }

        return $this->authForm();
    }

    /**
     * Displays authentication form
     *
     * @return boolean
     */
    public function authForm()
    {
        if (empty($GLOBALS['cfg']['Server']['auth_http_realm'])) {
            if (empty($GLOBALS['cfg']['Server']['verbose'])) {
                $server_message = $GLOBALS['cfg']['Server']['host'];
            } else {
                $server_message = $GLOBALS['cfg']['Server']['verbose'];
            }
            $realm_message = 'phpMyAdmin ' . $server_message;
        } else {
            $realm_message = $GLOBALS['cfg']['Server']['auth_http_realm'];
        }

        $response = Response::getInstance();

        // remove non US-ASCII to respect RFC2616
        $realm_message = preg_replace('/[^\x20-\x7e]/i', '', $realm_message);
        $response->header('WWW-Authenticate: Basic realm="' . $realm_message . '"');
        $response->setHttpResponseCode(401);

        /* HTML header */
        $footer = $response->getFooter();
        $footer->setMinimal();
        $header = $response->getHeader();
        $header->setTitle(__('Access denied!'));
        $header->disableMenuAndConsole();
        $header->setBodyId('loginform');

        $response->addHTML('<h1>');
        $response->addHTML(sprintf(__('Welcome to %s'), ' phpMyAdmin'));
        $response->addHTML('</h1>');
        $response->addHTML('<h3>');
        $response->addHTML(
            Message::error(
                __('Wrong username/password. Access denied.')
            )
        );
        $response->addHTML('</h3>');

        $response->addHTML(Config::renderFooter());

        if (!defined('TESTSUITE')) {
            exit;
        } else {
            return false;
        }
    }

    /**
     * Gets authentication credentials
     *
     * @return boolean   whether we get authentication settings or not
     */
    public function readCredentials()
    {
        // Grabs the $PHP_AUTH_USER variable
        if (isset($GLOBALS['PHP_AUTH_USER'])) {
            $this->user = $GLOBALS['PHP_AUTH_USER'];
        }
        if (empty($this->user)) {
            if (Core::getenv('PHP_AUTH_USER')) {
                $this->user = Core::getenv('PHP_AUTH_USER');
            } elseif (Core::getenv('REMOTE_USER')) {
                // CGI, might be encoded, see below
                $this->user = Core::getenv('REMOTE_USER');
            } elseif (Core::getenv('REDIRECT_REMOTE_USER')) {
                // CGI, might be encoded, see below
                $this->user = Core::getenv('REDIRECT_REMOTE_USER');
            } elseif (Core::getenv('AUTH_USER')) {
                // WebSite Professional
                $this->user = Core::getenv('AUTH_USER');
            } elseif (Core::getenv('HTTP_AUTHORIZATION')) {
                // IIS, might be encoded, see below
                $this->user = Core::getenv('HTTP_AUTHORIZATION');
            } elseif (Core::getenv('Authorization')) {
                // FastCGI, might be encoded, see below
                $this->user = Core::getenv('Authorization');
            }
        }
        // Grabs the $PHP_AUTH_PW variable
        if (isset($GLOBALS['PHP_AUTH_PW'])) {
            $this->password = $GLOBALS['PHP_AUTH_PW'];
        }
        if (empty($this->password)) {
            if (Core::getenv('PHP_AUTH_PW')) {
                $this->password = Core::getenv('PHP_AUTH_PW');
            } elseif (Core::getenv('REMOTE_PASSWORD')) {
                // Apache/CGI
                $this->password = Core::getenv('REMOTE_PASSWORD');
            } elseif (Core::getenv('AUTH_PASSWORD')) {
                // WebSite Professional
                $this->password = Core::getenv('AUTH_PASSWORD');
            }
        }
        // Sanitize empty password login
        if (is_null($this->password)) {
            $this->password = '';
        }

        // Avoid showing the password in phpinfo()'s output
        unset($GLOBALS['PHP_AUTH_PW']);
        unset($_SERVER['PHP_AUTH_PW']);

        // Decode possibly encoded information (used by IIS/CGI/FastCGI)
        // (do not use explode() because a user might have a colon in his password
        if (strcmp(substr($this->user, 0, 6), 'Basic ') == 0) {
            $usr_pass = base64_decode(substr($this->user, 6));
            if (!empty($usr_pass)) {
                $colon = strpos($usr_pass, ':');
                if ($colon) {
                    $this->user = substr($usr_pass, 0, $colon);
                    $this->password = substr($usr_pass, $colon + 1);
                }
                unset($colon);
            }
            unset($usr_pass);
        }

        // sanitize username
        $this->user = Core::sanitizeMySQLUser($this->user);

        // User logged out -> ensure the new username is not the same
        $old_usr = isset($_REQUEST['old_usr']) ? $_REQUEST['old_usr'] : '';
        if (! empty($old_usr)
            && (isset($this->user) && hash_equals($old_usr, $this->user))
        ) {
            $this->user = '';
        }

        // Returns whether we get authentication settings or not
        return !empty($this->user);
    }

    /**
     * User is not allowed to login to MySQL -> authentication failed
     *
     * @param string $failure String describing why authentication has failed
     *
     * @return void
     */
    public function showFailure($failure)
    {
        parent::showFailure($failure);
        $error = $GLOBALS['dbi']->getError();
        if ($error && $GLOBALS['errno'] != 1045) {
            Core::fatalError($error);
        } else {
            $this->authForm();
        }
    }

    /**
     * Returns URL for login form.
     *
     * @return string
     */
    public function getLoginFormURL()
    {
        return './index.php?old_usr=' . $this->user;
    }
}
