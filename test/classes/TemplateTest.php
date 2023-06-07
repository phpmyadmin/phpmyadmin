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
        $this->loadContainerBuilder();

        $GLOBALS['cfg']['environment'] = 'production';
        $twig = Template::getTwigEnvironment(null);
        $this->assertFalse($twig->isDebug());
        $this->assertFalse(TransNode::$enableAddDebugInfo);
        $GLOBALS['cfg']['environment'] = 'development';
        $twig = Template::getTwigEnvironment(null);
        $this->assertTrue($twig->isDebug());
        $this->assertTrue(TransNode::$enableAddDebugInfo);
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
        $this->assertStringContainsString('value1', $result);
        $this->assertStringContainsString('value2', $result);
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
        $this->assertEquals(
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
        $this->assertEquals(
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
        return [['test/static', 'static content']];
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
        $this->assertEquals(
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
        $config = $this->createMock(Config::class);
        $config->expects($this->once())->method('getTempDir')->with($this->equalTo('twig'))->willReturn(null);

        $template = new Template($config);
        $this->assertSame('static content', $template->render('test/static'));

        $template2 = new Template($config);
        $this->assertSame('static content', $template2->render('test/static'));
    }
}
