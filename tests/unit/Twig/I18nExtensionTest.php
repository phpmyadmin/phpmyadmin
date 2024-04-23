<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Twig;

use PhpMyAdmin\Config;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Twig\I18nExtension;
use PhpMyAdmin\Twig\Node\Expression\TransExpression;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionProperty;
use Twig\Loader\FilesystemLoader;

use const TEST_PATH;

#[CoversClass(I18nExtension::class)]
#[CoversClass(TransExpression::class)]
final class I18nExtensionTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $twigEnvironment = Template::getTwigEnvironment(null, true);
        $twigEnvironment->setLoader(new FilesystemLoader(TEST_PATH . 'tests/unit/_data/templates'));
        (new ReflectionProperty(Template::class, 'twig'))->setValue(null, $twigEnvironment);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        (new ReflectionProperty(Template::class, 'twig'))->setValue(null, null);
    }

    public function testMessage(): void
    {
        $expected = <<<'HTML'
Message
Message
Message
Message

HTML;

        self::assertSame($expected, (new Template(new Config()))->render('i18n_extension/message', []));
    }

    public function testContext(): void
    {
        $expected = <<<'HTML'
Message
Message
Message
Message

HTML;

        self::assertSame($expected, (new Template(new Config()))->render('i18n_extension/context', []));
    }

    public function testPlural(): void
    {
        $expected = <<<'HTML'
One apple
One apple
One apple
One apple
One apple
One apple

HTML;

        self::assertSame(
            $expected,
            (new Template(new Config()))->render('i18n_extension/plural', ['number_of_apples' => 1]),
        );
    }

    public function testPlural2(): void
    {
        $expected = <<<'HTML'
2 apples
2 apples
2 apples
2 apples
2 apples
2 apples

HTML;

        self::assertSame(
            $expected,
            (new Template(new Config()))->render('i18n_extension/plural', ['number_of_apples' => 2]),
        );
    }
}
