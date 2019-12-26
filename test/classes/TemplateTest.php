<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PhpMyAdmin\Template class
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\PmaTestCase;
use Twig\Error\LoaderError;

/**
 * Test for PhpMyAdmin\Template class
 *
 * @package PhpMyAdmin-test
 */
class TemplateTest extends PmaTestCase
{
    /**
     * @var Template
     */
    protected $template;

    /**
     * Sets up the fixture.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->template = new Template();
    }

    /**
     * Test for set function
     *
     * @param string $data Template name
     *
     * @return void
     *
     * @dataProvider providerTestSet
     */
    public function testSet($data): void
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
    public function providerTestSet()
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
     * @return void
     *
     * @dataProvider providerTestDynamicRender
     */
    public function testDynamicRender($templateFile, $key, $value): void
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
    public function providerTestDynamicRender()
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
     *
     * @return void
     */
    public function testRenderTemplateNotFound()
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
     * @return void
     *
     * @dataProvider providerTestRender
     */
    public function testRender($templateFile, $expectedResult): void
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
    public function providerTestRender()
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
     * @return void
     *
     * @dataProvider providerTestRenderGettext
     */
    public function testRenderGettext($templateFile, $renderParams, $expectedResult): void
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
    public function providerTestRenderGettext()
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
