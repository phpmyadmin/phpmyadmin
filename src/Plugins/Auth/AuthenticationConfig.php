<?php
/**
 * Config Authentication plugin for phpMyAdmin
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Auth;

use PhpMyAdmin\Config;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Error\ErrorHandler;
use PhpMyAdmin\Exceptions\AuthenticationFailure;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Plugins\AuthenticationPlugin;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Server\Select;
use PhpMyAdmin\Url;

use function __;
use function count;
use function ob_get_clean;
use function ob_start;
use function sprintf;

/**
 * Handles the config authentication method
 */
class AuthenticationConfig extends AuthenticationPlugin
{
    /**
     * Displays authentication form
     */
    public function showLoginForm(): Response|null
    {
        $responseRenderer = ResponseRenderer::getInstance();
        if (! $responseRenderer->isAjax()) {
            return null;
        }

        $responseRenderer->setRequestStatus(false);
        // reload_flag removes the token parameter from the URL and reloads
        $responseRenderer->addJSON('reload_flag', '1');

        return $responseRenderer->response();
    }

    /**
     * Gets authentication credentials
     *
     * @return bool always true
     */
    public function readCredentials(): bool
    {
        $config = Config::getInstance();
        $this->user = $config->selectedServer['user'];
        $this->password = $config->selectedServer['password'];

        return true;
    }

    /**
     * User is not allowed to login to MySQL -> authentication failed
     */
    public function showFailure(AuthenticationFailure $failure): Response
    {
        $this->logFailure($failure);
        $dbi = DatabaseInterface::getInstance();
        $errorHandler = ErrorHandler::getInstance();

        /* HTML header */
        $responseRenderer = ResponseRenderer::getInstance();
        $responseRenderer->setMinimalFooter();
        $header = $responseRenderer->getHeader();
        $header->setBodyId('loginform');
        $header->setTitle(__('Access denied!'));
        $header->disableMenuAndConsole();

        ob_start();
        echo '<br><br>
    <div class="text-center">
        <h1>';
        echo sprintf(__('Welcome to %s'), ' phpMyAdmin ');
        echo '</h1>
    </div>
    <br>
    <table class="table table-borderless text-start w-75 mx-auto">
        <tr>
            <td>';
        $config = Config::getInstance();
        if ($failure->failureType === AuthenticationFailure::ALLOW_DENIED) {
            $errorHandler->addNotice($failure->getMessage());
        } else {
            // Check whether user has configured something
            if ($config->sourceMtime === 0) {
                echo '<p>' , sprintf(
                    __(
                        'You probably did not create a configuration file.'
                        . ' You might want to use the %1$ssetup script%2$s to'
                        . ' create one.',
                    ),
                    '<a href="setup/">',
                    '</a>',
                ) , '</p>' , "\n";
            } elseif ($dbi->getConnectionErrorNumber() !== 2002 && $dbi->getConnectionErrorNumber() !== 2003) {
                // if we display the "Server not responding" error, do not confuse
                // users by telling them they have a settings problem
                // (note: it's true that they could have a badly typed host name,
                // but anyway the current message tells that the server
                //  rejected the connection, which is not really what happened)
                // 2002 is the error given by mysqli
                // 2003 is the error given by mysql
                $errorHandler->addUserError(
                    __(
                        'phpMyAdmin tried to connect to the MySQL server, and the'
                        . ' server rejected the connection. You should check the'
                        . ' host, username and password in your configuration and'
                        . ' make sure that they correspond to the information given'
                        . ' by the administrator of the MySQL server.',
                    ),
                );
            }
        }

        $errorHandler->dispUserErrors();
        echo '</td>
        </tr>
        <tr>
            <td>' , "\n";
        echo '<a href="'
            , Url::getFromRoute($config->settings['DefaultTabServer'])
            , '" class="btn btn-primary mt-1 mb-1 disableAjax">'
            , __('Retry to connect')
            , '</a>' , "\n";
        echo '</td>
        </tr>' , "\n";
        if (count($config->settings['Servers']) > 1) {
            // offer a chance to login to other servers if the current one failed
            echo '<tr>' , "\n";
            echo ' <td>' , "\n";
            echo Select::render(true);
            echo ' </td>' , "\n";
            echo '</tr>' , "\n";
        }

        echo '</table>' , "\n";

        $responseRenderer->addHTML((string) ob_get_clean());

        return $responseRenderer->response();
    }
}
