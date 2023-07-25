<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Application;
use PhpMyAdmin\Config;
use PhpMyAdmin\ErrorHandler;
use PhpMyAdmin\Exceptions\ConfigException;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Template;
use PHPUnit\Framework\Attributes\BackupStaticProperties;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Http\Message\ResponseFactoryInterface;
use ReflectionProperty;
use Symfony\Component\DependencyInjection\ContainerBuilder;

#[CoversClass(Application::class)]
final class ApplicationTest extends AbstractTestCase
{
    public function testInit(): void
    {
        $application = new Application(
            $this->createStub(ErrorHandler::class),
            $this->createStub(Config::class),
            $this->createStub(Template::class),
            new ResponseFactory($this->createStub(ResponseFactoryInterface::class)),
        );
        $container = $this->createMock(ContainerBuilder::class);
        $container->expects($this->once())->method('get')
            ->with($this->identicalTo(Application::class))->willReturn($application);
        $GLOBALS['containerBuilder'] = $container;
        $this->assertSame($application, Application::init());
    }

    #[BackupStaticProperties(true)]
    public function testRunWithConfigError(): void
    {
        $GLOBALS['errorHandler'] = null;
        $errorHandler = $this->createStub(ErrorHandler::class);

        $GLOBALS['config'] = null;
        $config = $this->createMock(Config::class);
        $config->expects($this->once())->method('loadAndCheck')
            ->willThrowException(new ConfigException('Failed to load phpMyAdmin configuration.'));

        $request = $this->createMock(ServerRequest::class);
        $request->expects($this->once())->method('withAttribute')->willReturnSelf();
        (new ReflectionProperty(Application::class, 'request'))->setValue(null, $request);

        $template = new Template($config);
        $expected = $template->render('error/generic', [
            'lang' => 'en',
            'dir' => 'ltr',
            'error_message' => 'Failed to load phpMyAdmin configuration.',
        ]);

        $application = new Application($errorHandler, $config, $template, ResponseFactory::create());
        $application->run();

        $output = $this->getActualOutputForAssertion();
        $this->assertSame($expected, $output);
        $this->assertSame($config, $GLOBALS['config']);
        $this->assertSame($errorHandler, $GLOBALS['errorHandler']);

        (new ReflectionProperty(Application::class, 'request'))->setValue(null, null);
    }

    public function testCheckTokenRequestParam(): void
    {
        $application = new Application(
            $this->createStub(ErrorHandler::class),
            $this->createStub(Config::class),
            $this->createStub(Template::class),
            new ResponseFactory($this->createStub(ResponseFactoryInterface::class)),
        );

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $application->checkTokenRequestParam();
        $this->assertTrue($GLOBALS['token_mismatch']);
        $this->assertFalse($GLOBALS['token_provided']);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['test'] = 'test';
        $application->checkTokenRequestParam();
        $this->assertTrue($GLOBALS['token_mismatch']);
        $this->assertFalse($GLOBALS['token_provided']);
        $this->assertArrayNotHasKey('test', $_POST);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['token'] = 'token';
        $_POST['test'] = 'test';
        $_SESSION[' PMA_token '] = 'mismatch';
        $application->checkTokenRequestParam();
        $this->assertTrue($GLOBALS['token_mismatch']);
        $this->assertTrue($GLOBALS['token_provided']);
        $this->assertArrayNotHasKey('test', $_POST);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['token'] = 'token';
        $_POST['test'] = 'test';
        $_SESSION[' PMA_token '] = 'token';
        $application->checkTokenRequestParam();
        $this->assertFalse($GLOBALS['token_mismatch']);
        $this->assertTrue($GLOBALS['token_provided']);
        $this->assertArrayHasKey('test', $_POST);
        $this->assertEquals('test', $_POST['test']);
    }
}
