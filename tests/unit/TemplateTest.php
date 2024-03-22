<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Config;
use PhpMyAdmin\Template;
use PhpMyAdmin\Twig\Extensions\Node\TransNode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use ReflectionProperty;
use Twig\Cache\CacheInterface;
use Twig\Environment;
use Twig\Error\LoaderError;

#[CoversClass(Template::class)]
class TemplateTest extends AbstractTestCase
{
    protected Template $template;

    /**
     * Sets up the fixture.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->template = new Template();
    }

    /**
     * Test that Twig Environment can be built
     * and that all Twig extensions are loaded
     */
    public function testGetTwigEnvironment(): void
    {
        $twig = Template::getTwigEnvironment(null, false);
        self::assertFalse($twig->isDebug());
        self::assertFalse(TransNode::$enableAddDebugInfo);

        $twig = Template::getTwigEnvironment(null, true);
        self::assertTrue($twig->isDebug());
        self::assertTrue(TransNode::$enableAddDebugInfo);
    }

    /**
     * Test for set function
     *
     * @param string $data Template name
     */
    #[DataProvider('providerTestSet')]
    public function testSet(string $data): void
    {
        $result = $this->template->render($data, ['variable1' => 'value1', 'variable2' => 'value2']);
        self::assertStringContainsString('value1', $result);
        self::assertStringContainsString('value2', $result);
    }

    /**
     * Data provider for testSet
     *
     * @return mixed[]
     */
    public static function providerTestSet(): array
    {
        return [['test/add_data']];
    }

    /**
     * Test for render
     *
     * @param string $templateFile Template name
     * @param string $key          Template variable array key
     * @param string $value        Template variable array value
     */
    #[DataProvider('providerTestDynamicRender')]
    public function testDynamicRender(string $templateFile, string $key, string $value): void
    {
        self::assertSame(
            $value,
            $this->template->render($templateFile, [$key => $value]),
        );
    }

    /**
     * Data provider for testDynamicRender
     *
     * @return mixed[]
     */
    public static function providerTestDynamicRender(): array
    {
        return [['test/echo', 'variable', 'value']];
    }

    /**
     * Test for render
     */
    public function testRenderTemplateNotFound(): void
    {
        $this->expectException(LoaderError::class);
        $this->template->render('template not found');
    }

    /**
     * Test for render
     *
     * @param string $templateFile   Template name
     * @param string $expectedResult Expected result
     */
    #[DataProvider('providerTestRender')]
    public function testRender(string $templateFile, string $expectedResult): void
    {
        self::assertSame(
            $expectedResult,
            $this->template->render($templateFile),
        );
    }

    /**
     * Data provider for testSet
     *
     * @return mixed[]
     */
    public static function providerTestRender(): array
    {
        return [['test/static', "static content\n"]];
    }

    /**
     * Test for render
     *
     * @param string  $templateFile   Template name
     * @param mixed[] $renderParams   Render params
     * @param string  $expectedResult Expected result
     */
    #[DataProvider('providerTestRenderGettext')]
    public function testRenderGettext(string $templateFile, array $renderParams, string $expectedResult): void
    {
        self::assertSame(
            $expectedResult,
            $this->template->render($templateFile, $renderParams),
        );
    }

    /**
     * Data provider for testRenderGettext
     *
     * @return mixed[]
     */
    public static function providerTestRenderGettext(): array
    {
        return [
            ['test/gettext/gettext', [], 'Text'],
            ['test/gettext/pgettext', [], 'Text'],
            ['test/gettext/notes', [], 'Text'],
            ['test/gettext/plural', ['table_count' => 1], 'One table'],
            ['test/gettext/plural', ['table_count' => 2], '2 tables'],
            ['test/gettext/plural_notes', ['table_count' => 1], 'One table'],
            ['test/gettext/plural_notes', ['table_count' => 2], '2 tables'],
        ];
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testLoadingTwigEnvOnlyOnce(): void
    {
        $twigEnvCacheProperty = new ReflectionProperty(Template::class, 'twig');
        $twigEnvCacheProperty->setValue(null, null);
        $template = new Template();
        self::assertSame("static content\n", $template->render('test/static'));
        $twigEnv = $twigEnvCacheProperty->getValue();
        self::assertInstanceOf(Environment::class, $twigEnv);
        $template2 = new Template();
        self::assertSame("static content\n", $template2->render('test/static'));
        self::assertSame($twigEnv, $twigEnvCacheProperty->getValue());
    }

    public function testDisableCache(): void
    {
        (new ReflectionProperty(Template::class, 'twig'))->setValue(null, null);
        $template = new Template(self::createStub(Config::class));
        $template->disableCache();
        $twig = (new ReflectionProperty(Template::class, 'twig'))->getValue();
        self::assertInstanceOf(Environment::class, $twig);
        self::assertFalse($twig->getCache());
        $twig->setCache(self::createStub(CacheInterface::class));
        self::assertNotFalse($twig->getCache());
        $template->disableCache();
        self::assertFalse($twig->getCache());
        (new ReflectionProperty(Template::class, 'twig'))->setValue(null, null);
    }
}
