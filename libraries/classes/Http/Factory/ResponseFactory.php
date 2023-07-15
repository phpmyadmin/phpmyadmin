<?php

declare(strict_types=1);

namespace PhpMyAdmin\Http\Factory;

use GuzzleHttp\Psr7\HttpFactory;
use Laminas\Diactoros\ResponseFactory as LaminasResponseFactory;
use Nyholm\Psr7\Factory\Psr17Factory;
use PhpMyAdmin\Http\Response;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\Psr7\Factory\ResponseFactory as SlimResponseFactory;

use function class_exists;

class ResponseFactory
{
    private ResponseFactoryInterface $responseFactory;

    public function __construct(ResponseFactoryInterface|null $responseFactory = null)
    {
        $this->responseFactory = $responseFactory ?? $this->createResponseFactory();
    }

    /**
     * Create a new response.
     *
     * @param int    $code         HTTP status code; defaults to 200
     * @param string $reasonPhrase Reason phrase to associate with status code
     *     in generated response; if none is provided implementations MAY use
     *     the defaults as suggested in the HTTP specification.
     */
    public function createResponse(int $code = 200, string $reasonPhrase = ''): Response
    {
        return new Response($this->responseFactory->createResponse($code, $reasonPhrase));
    }

    private function createResponseFactory(): ResponseFactoryInterface
    {
        if (class_exists(Psr17Factory::class)) {
            /** @var ResponseFactoryInterface $factory */
            $factory = new Psr17Factory();
        } elseif (class_exists(HttpFactory::class)) {
            /** @var ResponseFactoryInterface $factory */
            $factory = new HttpFactory();
        } elseif (class_exists(LaminasResponseFactory::class)) {
            /** @var ResponseFactoryInterface $factory */
            $factory = new LaminasResponseFactory();
        } else {
            $factory = new SlimResponseFactory();
        }

        return $factory;
    }
}
