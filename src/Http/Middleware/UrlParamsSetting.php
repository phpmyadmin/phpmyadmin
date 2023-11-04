<?php

declare(strict_types=1);

namespace PhpMyAdmin\Http\Middleware;

use PhpMyAdmin\Config;
use PhpMyAdmin\Core;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class UrlParamsSetting implements MiddlewareInterface
{
    public function __construct(private readonly Config $config)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $GLOBALS['urlParams'] = [];

        $this->setGotoAndBackGlobals();

        return $handler->handle($request);
    }

    private function setGotoAndBackGlobals(): void
    {
        // Holds page that should be displayed.
        $GLOBALS['goto'] = '';

        if (isset($_REQUEST['goto']) && Core::checkPageValidity($_REQUEST['goto'])) {
            $GLOBALS['goto'] = $_REQUEST['goto'];
            $GLOBALS['urlParams']['goto'] = $GLOBALS['goto'];
        } else {
            if ($this->config->issetCookie('goto')) {
                $this->config->removeCookie('goto');
            }

            unset($_REQUEST['goto'], $_GET['goto'], $_POST['goto']);
        }

        if (isset($_REQUEST['back']) && Core::checkPageValidity($_REQUEST['back'])) {
            // Returning page.
            $GLOBALS['back'] = $_REQUEST['back'];

            return;
        }

        if ($this->config->issetCookie('back')) {
            $this->config->removeCookie('back');
        }

        unset($_REQUEST['back'], $_GET['back'], $_POST['back']);
    }
}
