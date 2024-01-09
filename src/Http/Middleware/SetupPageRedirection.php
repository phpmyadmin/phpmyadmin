<?php

declare(strict_types=1);

namespace PhpMyAdmin\Http\Middleware;

use PhpMyAdmin\Config;
use PhpMyAdmin\Config\ConfigFile;
use PhpMyAdmin\Container\ContainerBuilder;
use PhpMyAdmin\Exceptions\ExitException;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Routing;
use PhpMyAdmin\Theme\ThemeManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function assert;
use function ob_start;
use function restore_error_handler;

final class SetupPageRedirection implements MiddlewareInterface
{
    public function __construct(private readonly Config $config, private readonly ResponseFactory $responseFactory)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getAttribute('isSetupPage') !== true) {
            return $handler->handle($request);
        }

        $container = ContainerBuilder::getContainer();
        /** @var ThemeManager $themeManager */
        $themeManager = $container->get(ThemeManager::class);
        $this->config->loadUserPreferences($themeManager, true);
        $this->setupPageBootstrap();
        assert($request instanceof ServerRequest);

        try {
            return Routing::callSetupController($request, $this->responseFactory);
        } catch (ExitException) {
            return ResponseRenderer::getInstance()->response();
        }
    }

    private function setupPageBootstrap(): void
    {
        // use default error handler
        restore_error_handler();

        // Save current language in a cookie, since it was not set in Common::run().
        $this->config->setCookie('pma_lang', $GLOBALS['lang']);
        $this->config->set('is_setup', true);

        $GLOBALS['ConfigFile'] = new ConfigFile();
        $GLOBALS['ConfigFile']->setPersistKeys([
            'DefaultLang',
            'ServerDefault',
            'UploadDir',
            'SaveDir',
            'Servers/1/verbose',
            'Servers/1/host',
            'Servers/1/port',
            'Servers/1/socket',
            'Servers/1/auth_type',
            'Servers/1/user',
            'Servers/1/password',
        ]);

        // allows for redirection even after sending some data
        ob_start();
    }
}
