<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Http\Factory;

use GuzzleHttp\Psr7\HttpFactory as GuzzleHttpFactory;
use Laminas\Diactoros\ServerRequestFactory as LaminasServerRequestFactory;
use Nyholm\Psr7\Factory\Psr17Factory as NyholmPsr17Factory;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Tests\AbstractTestCase;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Factory\ServerRequestFactory as SlimServerRequestFactory;

use function class_exists;

/** @covers \PhpMyAdmin\Http\Factory\ServerRequestFactory */
class ServerRequestFactoryTest extends AbstractTestCase
{
    private const IMPLEMENTATION_CLASSES = [
        'slim/psr7' => [SlimServerRequestFactory::class, 'Slim PSR-7'],
        'guzzlehttp/psr7' => [GuzzleHttpFactory::class, 'Guzzle PSR-7'],
        'nyholm/psr7' => [NyholmPsr17Factory::class, 'Nyholm PSR-7'],
        'laminas/laminas-diactoros' => [LaminasServerRequestFactory::class, 'Laminas diactoros PSR-7'],
    ];

    /** @return mixed[][] */
    public static function dataProviderPsr7Implementations(): array
    {
        return self::IMPLEMENTATION_CLASSES;
    }

    /** @phpstan-param class-string $className */
    private function testOrSkip(string $className, string $humanName): void
    {
        if (! class_exists($className)) {
            $this->markTestSkipped($humanName . ' is missing');
        }

        foreach (self::IMPLEMENTATION_CLASSES as $libName => $details) {
            /** @phpstan-var class-string */
            $classImpl = $details[0];
            if ($classImpl === $className) {
                continue;
            }

            if (! class_exists($classImpl)) {
                continue;
            }

            $this->markTestSkipped($libName . ' exists and will conflict with the test results');
        }
    }

    /**
     * @phpstan-param class-string $className
     *
     * @dataProvider dataProviderPsr7Implementations
     */
    public function testPsr7ImplementationGet(string $className, string $humanName): void
    {
        $this->testOrSkip($className, $humanName);

        $_GET['foo'] = 'bar';
        $_GET['blob'] = 'baz';
        $_SERVER['QUERY_STRING'] = 'foo=bar&blob=baz';
        $_SERVER['REQUEST_URI'] = '/test-page.php';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_HOST'] = 'phpmyadmin.local';

        $request = ServerRequestFactory::createFromGlobals();
        $this->assertSame(
            'GET',
            $request->getMethod(),
        );
        $this->assertSame(
            'http://phpmyadmin.local/test-page.php?foo=bar&blob=baz',
            $request->getUri()->__toString(),
        );
        $this->assertFalse(
            $request->isPost(),
        );
        $this->assertSame(
            'default',
            $request->getParam('not-exists', 'default'),
        );
        $this->assertSame(
            'bar',
            $request->getParam('foo'),
        );
        $this->assertSame(
            'baz',
            $request->getParam('blob'),
        );
        $this->assertSame(['foo' => 'bar', 'blob' => 'baz'], $request->getQueryParams());
    }

    public function testCreateServerRequestFromGlobals(): void
    {
        $_GET['foo'] = 'bar';
        $_GET['blob'] = 'baz';
        $_POST['input1'] = 'value1';
        $_POST['input2'] = 'value2';
        $_POST['input3'] = '';
        $_SERVER['QUERY_STRING'] = 'foo=bar&blob=baz';
        $_SERVER['REQUEST_URI'] = '/test-page.php';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_HOST'] = 'phpmyadmin.local';

        $creator = $this->getMockBuilder(ServerRequestFactory::class)
            ->onlyMethods(['getallheaders'])
            ->getMock();

        $creator
            ->method('getallheaders')
            ->willReturn(['Content-Type' => 'application/x-www-form-urlencoded']);

        /** @var ServerRequestInterface $serverRequest */
        $serverRequest = $this->callFunction(
            $creator,
            ServerRequestFactory::class,
            'createServerRequestFromGlobals',
            [$creator],
        );

        $request = new ServerRequest($serverRequest);

        $this->assertSame(
            ['application/x-www-form-urlencoded'],
            $request->getHeader('Content-Type'),
        );
        $this->assertSame(
            'POST',
            $request->getMethod(),
        );
        $this->assertSame(
            'http://phpmyadmin.local/test-page.php?foo=bar&blob=baz',
            $request->getUri()->__toString(),
        );
        $this->assertTrue(
            $request->isPost(),
        );
        $this->assertSame(
            'default',
            $request->getParam('not-exists', 'default'),
        );
        $this->assertSame(
            'bar',
            $request->getParam('foo'),
        );
        $this->assertSame(
            'baz',
            $request->getParam('blob'),
        );
        $this->assertSame(['foo' => 'bar', 'blob' => 'baz'], $request->getQueryParams());

        $this->assertSame(['input1' => 'value1', 'input2' => 'value2', 'input3' => ''], $request->getParsedBody());

        $this->assertNull($request->getParsedBodyParam('foo'));
        $this->assertSame('value1', $request->getParsedBodyParam('input1'));
        $this->assertSame('value2', $request->getParsedBodyParam('input2'));
        $this->assertSame('', $request->getParsedBodyParam('input3', 'default'));
    }

    /**
     * @phpstan-param class-string $className
     *
     * @dataProvider dataProviderPsr7Implementations
     */
    public function testPsr7ImplementationCreateServerRequestFactory(string $className, string $humanName): void
    {
        $this->testOrSkip($className, $humanName);

        $serverRequestFactory = new $className();
        $this->assertInstanceOf(ServerRequestFactoryInterface::class, $serverRequestFactory);

        $factory = new ServerRequestFactory($serverRequestFactory);
        $this->assertInstanceOf(ServerRequestFactory::class, $factory);
    }
}
