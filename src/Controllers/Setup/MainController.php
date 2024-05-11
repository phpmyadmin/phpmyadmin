<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Setup;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\LanguageManager;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

use function __;
use function file_exists;
use function in_array;

use const CONFIG_FILE;

final class MainController implements InvocableController
{
    public function __construct(
        private readonly ResponseFactory $responseFactory,
        private readonly ResponseRenderer $responseRenderer,
        private readonly Template $template,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $config = Config::getInstance();
        if (@file_exists(CONFIG_FILE) && ! $config->config->debug->demo) {
            $response = $this->responseFactory->createResponse(StatusCodeInterface::STATUS_NOT_FOUND);

            return $response->write($this->template->render('error/generic', [
                'lang' => $GLOBALS['lang'] ?? 'en',
                'dir' => LanguageManager::$textDir,
                'error_message' => __('Configuration already exists, setup is disabled!'),
            ]));
        }

        /** @var mixed $pageParam */
        $pageParam = $request->getQueryParam('page');
        $page = in_array($pageParam, ['form', 'config', 'servers'], true) ? $pageParam : 'index';

        if ($page === 'form') {
            return (new FormController($this->responseFactory, $this->responseRenderer, $this->template))($request);
        }

        if ($page === 'config') {
            return (new ConfigController($this->responseFactory, $this->responseRenderer, $this->template))($request);
        }

        if ($page === 'servers' && $request->getQueryParam('mode') === 'remove' && $request->isPost()) {
            return (new ServerDestroyController($this->responseFactory, $this->responseRenderer))($request);
        }

        if ($page === 'servers') {
            return (new ServersController($this->responseFactory, $this->responseRenderer, $this->template))($request);
        }

        return (new HomeController($this->responseFactory, $this->responseRenderer, $this->template))($request);
    }
}
