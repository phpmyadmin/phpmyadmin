<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Template;
use Twig\Error\LoaderError;

class TemplateTest extends AbstractTestCase
{
    /** @var Template */
    protected $template;

    /**
     * Sets up the fixture.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->template = new Template();
    }

    /**
     * Test for set function
     *
     * @param string $data Template name
     *
     * @dataProvider providerTestSet
     */
    public function testSet(string $data): void
    {
        $result = $this->template->render($data, [
            'variable1' => 'value1',
            'variable2' => 'value2',
        ]);
        $this->assertStringContainsString('value1', $result);
        $this->assertStringContainsString('value2', $result);
    }

    /**
     * Data provider for testSet
     *
     * @return array
     */
    public function providerTestSet(): array
    {
        return [
            ['test/add_data'],
        ];
    }

    /**
     * Test for render
     *
     * @param string $templateFile Template name
     * @param string $key          Template variable array key
     * @param string $value        Template variable array value
     *
     * @dataProvider providerTestDynamicRender
     */
    public function testDynamicRender(string $templateFile, string $key, string $value): void
    {
        $this->assertEquals(
            $value,
            $this->template->render($templateFile, [$key => $value])
        );
    }

    /**
     * Data provider for testDynamicRender
     *
     * @return array
     */
    public function providerTestDynamicRender(): array
    {
        return [
            [
                'test/echo',
                'variable',
                'value',
            ],
        ];
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
     *
     * @dataProvider providerTestRender
     */
    public function testRender(string $templateFile, string $expectedResult): void
    {
        $this->assertEquals(
            $expectedResult,
            $this->template->render($templateFile)
        );
    }

    /**
     * Data provider for testSet
     *
     * @return array
     */
    public function providerTestRender(): array
    {
        return [
            [
                'test/static',
                'static content',
            ],
        ];
    }

    /**
     * Test for render
     *
     * @param string $templateFile   Template name
     * @param array  $renderParams   Render params
     * @param string $expectedResult Expected result
     *
     * @dataProvider providerTestRenderGettext
     */
    public function testRenderGettext(string $templateFile, array $renderParams, string $expectedResult): void
    {
        $this->assertEquals(
            $expectedResult,
            $this->template->render($templateFile, $renderParams)
        );
    }

    /**
     * Data provider for testRenderGettext
     *
     * @return array
     */
    public function providerTestRenderGettext(): array
    {
        return [
            [
                'test/gettext/gettext',
                [],
                'Text',
            ],
            [
                'test/gettext/pgettext',
                [],
                'Text',
            ],
            [
                'test/gettext/notes',
                [],
                'Text',
            ],
            [
                'test/gettext/plural',
                ['table_count' => 1],
                'One table',
            ],
            [
                'test/gettext/plural',
                ['table_count' => 2],
                '2 tables',
            ],
            [
                'test/gettext/plural_notes',
                ['table_count' => 1],
                'One table',
            ],
            [
                'test/gettext/plural_notes',
                ['table_count' => 2],
                '2 tables',
            ],
        ];
    }
}
