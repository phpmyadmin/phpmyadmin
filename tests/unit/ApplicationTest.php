<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Application;
use PhpMyAdmin\Config;
use PhpMyAdmin\Container\ContainerBuilder;
use PhpMyAdmin\Exceptions\ConfigException;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PHPUnit\Framework\Attributes\BackupStaticProperties;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Application::class)]
final class ApplicationTest extends AbstractTestCase
{
    public function testInit(): void
    {
        ContainerBuilder::$container = null;
        $application = ContainerBuilder::getContainer()->get(Application::class);
        self::assertSame($application, Application::init());
    }

    #[BackupStaticProperties(true)]
    public function testRunWithConfigError(): void
    {
        $config = $this->createMock(Config::class);
        $config->expects(self::once())->method('loadFromFile')
            ->willThrowException(new ConfigException('Failed to load phpMyAdmin configuration.'));
        $config->config = new Config\Settings([]);

        Config::$instance = $config;
        ContainerBuilder::$container = null;

        $template = new Template($config);
        $expected = $template->render('error/generic', [
            'lang' => 'en',
            'error_message' => 'Failed to load phpMyAdmin configuration.',
        ]);

        $application = new Application(ResponseFactory::create(), self::createStub(ResponseRenderer::class));
        $application->run();

        $output = $this->getActualOutputForAssertion();
        self::assertSame($expected, $output);

        Config::$instance = null;
        ContainerBuilder::$container = null;
    }
}
