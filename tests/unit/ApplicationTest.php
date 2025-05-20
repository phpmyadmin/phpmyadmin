<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Application;
use PhpMyAdmin\Config;
use PhpMyAdmin\Console\History;
use PhpMyAdmin\Container\ContainerBuilder;
use PhpMyAdmin\Error\ErrorHandler;
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
        $application = ContainerBuilder::getContainer()->get(Application::class);
        self::assertInstanceOf(Application::class, $application);
        self::assertSame($application, Application::init());
    }

    #[BackupStaticProperties(true)]
    public function testRunWithConfigError(): void
    {
        $errorHandler = self::createStub(ErrorHandler::class);

        $config = self::createMock(Config::class);
        $config->expects(self::once())->method('loadFromFile')
            ->willThrowException(new ConfigException('Failed to load phpMyAdmin configuration.'));
        $config->config = new Config\Settings([]);

        $template = new Template($config);
        $expected = $template->render('error/generic', [
            'lang' => 'en',
            'error_message' => 'Failed to load phpMyAdmin configuration.',
        ]);

        $history = self::createMock(History::class);
        $application = new Application($errorHandler, $config, $template, ResponseFactory::create(), $history);
        $application->run();

        $output = $this->getActualOutputForAssertion();
        self::assertSame($expected, $output);
    }
}
