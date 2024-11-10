<?php

declare(strict_types=1);

namespace PhpMyAdmin\Http\Middleware;

use PhpMyAdmin\Config;
use PhpMyAdmin\Core;
use PhpMyAdmin\UrlParams;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webmozart\Assert\Assert;

final class UrlParamsSetting implements MiddlewareInterface
{
    public function __construct(private readonly Config $config)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        UrlParams::$params = [];

        $this->setGotoAndBackGlobals($request);

        return $handler->handle($request);
    }

    private function setGotoAndBackGlobals(ServerRequestInterface $request): void
    {
        // Holds page that should be displayed.
        UrlParams::$goto = '';

        $goto = $request->getQueryParams()['goto'] ?? $request->getParsedBody()['goto'] ?? null;
        Assert::nullOrString($goto);

        if ($goto !== null && Core::checkPageValidity($goto)) {
            UrlParams::$goto = $goto;
            UrlParams::$params['goto'] = $goto;
        } else {
            if ($this->config->issetCookie('goto')) {
                $this->config->removeCookie('goto');
            }
        }

        $back = $request->getQueryParams()['back'] ?? $request->getParsedBody()['back'] ?? null;
        Assert::nullOrString($back);

        if ($back !== null && Core::checkPageValidity($back)) {
            // Returning page.
            UrlParams::$back = $back;

            return;
        }

        if (! $this->config->issetCookie('back')) {
            return;
        }

        $this->config->removeCookie('back');
    }
}
