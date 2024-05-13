<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Setup;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

use function in_array;

final class MainController implements InvocableController
{
    public function __construct(
        private readonly ResponseFactory $responseFactory,
        private readonly ResponseRenderer $responseRenderer,
        private readonly Template $template,
        private readonly Config $config,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $page = $this->getPageParam($request->getQueryParam('page'));
        if ($page === 'form') {
            return (new FormController(
                $this->responseFactory,
                $this->responseRenderer,
                $this->template,
                $this->config,
            ))($request);
        }

        if ($page === 'config') {
            return (new ConfigController(
                $this->responseFactory,
                $this->responseRenderer,
                $this->template,
                $this->config,
            ))($request);
        }

        if ($page === 'servers' && $request->getQueryParam('mode') === 'remove' && $request->isPost()) {
            return (new ServerDestroyController(
                $this->responseFactory,
                $this->responseRenderer,
                $this->template,
                $this->config,
            ))($request);
        }

        if ($page === 'servers') {
            return (new ServersController(
                $this->responseFactory,
                $this->responseRenderer,
                $this->template,
                $this->config,
            ))($request);
        }

        return (new HomeController(
            $this->responseFactory,
            $this->responseRenderer,
            $this->template,
            $this->config,
        ))($request);
    }

    /** @psalm-return 'form'|'config'|'servers'|'index' */
    private function getPageParam(mixed $pageParam): string
    {
        return in_array($pageParam, ['form', 'config', 'servers'], true) ? $pageParam : 'index';
    }
}
