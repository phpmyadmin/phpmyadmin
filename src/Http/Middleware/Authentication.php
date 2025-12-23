<?php

declare(strict_types=1);

namespace PhpMyAdmin\Http\Middleware;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Config;
use PhpMyAdmin\Config\Settings\Server;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\ConnectionType;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Exceptions\AuthenticationFailure;
use PhpMyAdmin\Exceptions\AuthenticationPluginException;
use PhpMyAdmin\Exceptions\ExitException;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Logging;
use PhpMyAdmin\Plugins\AuthenticationPluginFactory;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tracking\Tracker;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

use function assert;
use function define;

final readonly class Authentication implements MiddlewareInterface
{
    public function __construct(
        private Config $config,
        private Template $template,
        private ResponseFactory $responseFactory,
        private AuthenticationPluginFactory $authPluginFactory,
        private DatabaseInterface $dbi,
        private Relation $relation,
        private ResponseRenderer $responseRenderer,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (! $this->config->hasSelectedServer()) {
            return $handler->handle($request);
        }

        try {
            $authPlugin = $this->authPluginFactory->create();
        } catch (AuthenticationPluginException $exception) {
            $response = $this->responseFactory->createResponse(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR);

            return $response->write($this->template->render('error/generic', [
                'lang' => Current::$lang,
                'error_message' => $exception->getMessage(),
            ]));
        }

        try {
            try {
                $response = $authPlugin->authenticate();
                if ($response !== null) {
                    return $response;
                }
            } catch (AuthenticationFailure $exception) {
                return $authPlugin->showFailure($exception);
            } catch (Throwable $exception) {
                $response = $this->responseFactory->createResponse(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR);

                return $response->write($this->template->render('error/generic', [
                    'lang' => Current::$lang,
                    'error_message' => $exception->getMessage(),
                ]));
            }

            $currentServer = new Server($this->config->selectedServer);

            /* Enable LOAD DATA LOCAL INFILE for LDI plugin */
            if ($request->getAttribute('route') === '/import' && ($_POST['format'] ?? '') === 'ldi') {
                // Switch this before the DB connection is done
                // phpcs:disable PSR1.Files.SideEffects
                define('PMA_ENABLE_LDI', 1);
                // phpcs:enable
            }

            try {
                $this->connectToDatabaseServer($this->dbi, $currentServer);
            } catch (AuthenticationFailure $exception) {
                return $authPlugin->showFailure($exception);
            }

            // Relation should only be initialized after the connection is successful
            $this->relation->initRelationParamsCache();

            // Tracker can only be activated after the relation has been initialized
            Tracker::enable();

            $response = $authPlugin->rememberCredentials();
            if ($response !== null) {
                return $response;
            }

            assert($request instanceof ServerRequest);
            $response = $authPlugin->checkTwoFactor($request);
            if ($response !== null) {
                return $response;
            }
        } catch (ExitException) {
            return $this->responseRenderer->response();
        }

        /* Log success */
        Logging::logUser($this->config, $currentServer->user);

        return $handler->handle($request);
    }

    /** @throws AuthenticationFailure */
    private function connectToDatabaseServer(DatabaseInterface $dbi, Server $currentServer): void
    {
        /**
         * Try to connect MySQL with the control user profile (will be used to get the privileges list for the current
         * user but the true user link must be open after this one, so it would be default one for all the scripts).
         */
        $controlConnection = null;
        if ($currentServer->controlUser !== '') {
            $controlConnection = $dbi->connect($currentServer, ConnectionType::ControlUser);
        }

        // Connects to the server (validates user's login)
        $userConnection = $dbi->connect($currentServer, ConnectionType::User);
        if ($userConnection === null) {
            throw AuthenticationFailure::deniedByDatabaseServer();
        }

        if ($controlConnection !== null) {
            return;
        }

        /**
         * Open separate connection for control queries, this is needed to avoid problems with table locking used in
         * main connection and phpMyAdmin issuing queries to configuration storage, which is not locked by that time.
         */
        $dbi->connect($currentServer, ConnectionType::User, ConnectionType::ControlUser);
    }
}
