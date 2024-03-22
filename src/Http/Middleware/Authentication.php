<?php

declare(strict_types=1);

namespace PhpMyAdmin\Http\Middleware;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Config;
use PhpMyAdmin\Config\Settings\Server;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Container\ContainerBuilder;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Dbal\ConnectionType;
use PhpMyAdmin\Exceptions\AuthenticationPluginException;
use PhpMyAdmin\Exceptions\ExitException;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\LanguageManager;
use PhpMyAdmin\Logging;
use PhpMyAdmin\Plugins\AuthenticationPlugin;
use PhpMyAdmin\Plugins\AuthenticationPluginFactory;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tracking\Tracker;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function assert;
use function define;

final class Authentication implements MiddlewareInterface
{
    public function __construct(
        private readonly Config $config,
        private readonly Template $template,
        private readonly ResponseFactory $responseFactory,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (! $this->config->hasSelectedServer()) {
            return $handler->handle($request);
        }

        /** @var AuthenticationPluginFactory $authPluginFactory */
        $authPluginFactory = ContainerBuilder::getContainer()->get(AuthenticationPluginFactory::class);
        try {
            $authPlugin = $authPluginFactory->create();
        } catch (AuthenticationPluginException $exception) {
            $response = $this->responseFactory->createResponse(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR);

            return $response->write($this->template->render('error/generic', [
                'lang' => $GLOBALS['lang'] ?? 'en',
                'dir' => LanguageManager::$textDir,
                'error_message' => $exception->getMessage(),
            ]));
        }

        try {
            $authPlugin->authenticate();
            $currentServer = new Server(Config::getInstance()->selectedServer);

            /* Enable LOAD DATA LOCAL INFILE for LDI plugin */
            if ($request->getAttribute('route') === '/import' && ($_POST['format'] ?? '') === 'ldi') {
                // Switch this before the DB connection is done
                // phpcs:disable PSR1.Files.SideEffects
                define('PMA_ENABLE_LDI', 1);
                // phpcs:enable
            }

            $this->connectToDatabaseServer(DatabaseInterface::getInstance(), $authPlugin, $currentServer);

            // Relation should only be initialized after the connection is successful
            /** @var Relation $relation */
            $relation = ContainerBuilder::getContainer()->get('relation');
            $relation->initRelationParamsCache();

            // Tracker can only be activated after the relation has been initialized
            Tracker::enable();

            $authPlugin->rememberCredentials();
            assert($request instanceof ServerRequest);
            $authPlugin->checkTwoFactor($request);
        } catch (ExitException) {
            return ResponseRenderer::getInstance()->response();
        }

        /* Log success */
        Logging::logUser($this->config, $currentServer->user);

        return $handler->handle($request);
    }

    private function connectToDatabaseServer(
        DatabaseInterface $dbi,
        AuthenticationPlugin $auth,
        Server $currentServer,
    ): void {
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
            $auth->showFailure('mysql-denied');
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
