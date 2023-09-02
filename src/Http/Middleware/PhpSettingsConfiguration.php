<?php

declare(strict_types=1);

namespace PhpMyAdmin\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function date_default_timezone_get;
use function date_default_timezone_set;
use function ini_set;
use function mb_internal_encoding;

/**
 * Applies changes to PHP configuration.
 */
final class PhpSettingsConfiguration implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->configurePhpSettings();

        return $handler->handle($request);
    }

    private function configurePhpSettings(): void
    {
        /**
         * Set utf-8 encoding for PHP
         */
        ini_set('default_charset', 'utf-8');
        mb_internal_encoding('utf-8');

        /**
         * Set precision to sane value, with higher values
         * things behave slightly unexpectedly, for example
         * round(1.2, 2) returns 1.199999999999999956.
         */
        ini_set('precision', '14');

        /**
         * check timezone setting
         * this could produce an E_WARNING - but only once,
         * if not done here it will produce E_WARNING on every date/time function
         */
        date_default_timezone_set(@date_default_timezone_get());
    }
}
