<?php

declare(strict_types=1);

namespace PhpMyAdmin\Http\Factory;

use PhpMyAdmin\Http\ServerRequest;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Factory\ServerRequestFactory as RequestFactory;

class ServerRequestFactory implements ServerRequestFactoryInterface
{
    /** @var ServerRequestFactoryInterface */
    private $factory;

    public function __construct(?ServerRequestFactoryInterface $factory = null)
    {
        if ($factory === null) {
            $this->factory = new RequestFactory();

            return;
        }

        $this->factory = $factory;
    }

    /**
     * @inheritDoc
     */
    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        $serverRequest = $this->factory->createServerRequest($method, $uri, $serverParams);

        return new ServerRequest($serverRequest);
    }

    public static function createFromGlobals(): ServerRequest
    {
        /** @psalm-suppress InternalMethod */
        $serverRequest = RequestFactory::createFromGlobals();

        return new ServerRequest($serverRequest);
    }
}
