<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Twig;

use PhpMyAdmin\Config;
use PhpMyAdmin\I18n\LanguageManager;
use PhpMyAdmin\I18n\TextDirection;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Twig\PmaGlobalVariable;
use PhpMyAdmin\Version;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionProperty;
use RuntimeException;
use Twig\Error\RuntimeError;
use Twig\Loader\FilesystemLoader;

#[CoversClass(PmaGlobalVariable::class)]
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

    public function testUndefinedVariable(): void
    {
        self::expectException(RuntimeException::class);
        self::expectExceptionMessage('The "pma.undefined_variable" variable is not available.');
        /**
         * @psalm-suppress UndefinedMagicMethod
         * @phpstan-ignore-next-line
         */
        (new PmaGlobalVariable())->undefined_variable();
    }

    public function testVersion(): void
    {
        self::assertSame(Version::VERSION, (new PmaGlobalVariable())->version());
    }

    public function testTextDir(): void
    {
        LanguageManager::$textDirection = TextDirection::LeftToRight;
        self::assertSame('ltr', (new PmaGlobalVariable())->text_dir());
    }

    public function testUndefinedVariableFromTwig(): void
    {
        self::expectException(RuntimeError::class);
        self::expectExceptionMessage('The "pma.undefined_variable" variable is not available.');
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
