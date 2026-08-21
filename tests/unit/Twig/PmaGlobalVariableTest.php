<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Twig;

use PhpMyAdmin\Config;
use PhpMyAdmin\I18n\LanguageManager;
use PhpMyAdmin\I18n\TextDirection;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Version;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionProperty;
use Twig\Error\RuntimeError;
use Twig\Loader\FilesystemLoader;

#[CoversClass(Template::class)]
final class PmaGlobalVariableTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $twigEnvironment = Template::getTwigEnvironment(null, true);
        $twigEnvironment->setLoader(new FilesystemLoader(__DIR__ . '/../_data/templates'));
        (new ReflectionProperty(Template::class, 'twig'))->setValue(null, $twigEnvironment);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        (new ReflectionProperty(Template::class, 'twig'))->setValue(null, null);
    }

    public function testUndefinedVariableFromTwig(): void
    {
        self::expectException(RuntimeError::class);
        self::expectExceptionMessage(
            'Key "undefined_variable" for sequence/mapping with keys "version, text_dir" does not exist'
            . ' in "pma_global_variable/undefined_variable.twig" at line 1.',
        );
        (new Template(new Config()))->render('pma_global_variable/undefined_variable', []);
    }

    public function testVersionFromTwig(): void
    {
        $expected = '<span>' . Version::VERSION . '</span>' . "\n";
        self::assertSame($expected, (new Template(new Config()))->render('pma_global_variable/version', []));
    }

    public function testTextDirFromTwig(): void
    {
        LanguageManager::$textDirection = TextDirection::LeftToRight;
        $expected = '<span>ltr</span>' . "\n";
        self::assertSame($expected, (new Template(new Config()))->render('pma_global_variable/text_dir', []));
    }
}
