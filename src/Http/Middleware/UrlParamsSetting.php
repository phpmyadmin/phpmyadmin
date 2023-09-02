<?php

declare(strict_types=1);

namespace PhpMyAdmin\Http\Middleware;

use PhpMyAdmin\Config;
use PhpMyAdmin\Core;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class UrlParamsSetting implements MiddlewareInterface
{
    public function __construct(private readonly Config $config)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $container = Core::getContainerBuilder();

        $GLOBALS['urlParams'] = [];
        $container->setParameter('url_params', $GLOBALS['urlParams']);

        $this->setGotoAndBackGlobals($container);

        return $handler->handle($request);
    }

    private function setGotoAndBackGlobals(ContainerInterface $container): void
    {
        // Holds page that should be displayed.
        $GLOBALS['goto'] = '';
        $container->setParameter('goto', $GLOBALS['goto']);

        if (isset($_REQUEST['goto']) && Core::checkPageValidity($_REQUEST['goto'])) {
            $GLOBALS['goto'] = $_REQUEST['goto'];
            $GLOBALS['urlParams']['goto'] = $GLOBALS['goto'];
            $container->setParameter('goto', $GLOBALS['goto']);
            $container->setParameter('url_params', $GLOBALS['urlParams']);
        } else {
            if ($this->config->issetCookie('goto')) {
                $this->config->removeCookie('goto');
            }

            unset($_REQUEST['goto'], $_GET['goto'], $_POST['goto']);
        }

        if (isset($_REQUEST['back']) && Core::checkPageValidity($_REQUEST['back'])) {
            // Returning page.
            $GLOBALS['back'] = $_REQUEST['back'];
            $container->setParameter('back', $GLOBALS['back']);

            return;
        }

        if ($this->config->issetCookie('back')) {
            $this->config->removeCookie('back');
        }

        unset($_REQUEST['back'], $_GET['back'], $_POST['back']);
    }
}
