<?php

declare(strict_types=1);

namespace PhpMyAdmin\Http\Factory;

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
     * {@inheritdoc}
     */
    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        return $this->factory->createServerRequest($method, $uri, $serverParams);
    }

    public static function createFromGlobals(): ServerRequestInterface
    {
        /** @psalm-suppress InternalMethod */
        return RequestFactory::createFromGlobals();
    }
}
