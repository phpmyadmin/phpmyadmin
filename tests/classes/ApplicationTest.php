<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Application;
use PhpMyAdmin\Config;
use PhpMyAdmin\Core;
use PhpMyAdmin\ErrorHandler;
use PhpMyAdmin\Exceptions\ConfigException;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Template;
use PHPUnit\Framework\Attributes\BackupStaticProperties;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Application::class)]
final class ApplicationTest extends AbstractTestCase
{
    public function testInit(): void
    {
        $application = Core::getContainerBuilder()->get(Application::class);
        self::assertInstanceOf(Application::class, $application);
        self::assertSame($application, Application::init());
    }

    #[BackupStaticProperties(true)]
    public function testRunWithConfigError(): void
    {
        $errorHandler = $this->createStub(ErrorHandler::class);

        $config = $this->createMock(Config::class);
        $config->expects($this->once())->method('loadAndCheck')
            ->willThrowException(new ConfigException('Failed to load phpMyAdmin configuration.'));

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
    }
}
