<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PhpMyAdmin\Template class
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\PmaTestCase;

/**
 * Test for PhpMyAdmin\Template class
 *
 * @package PhpMyAdmin-test
 */
class TemplateTest extends PmaTestCase
{
    /**
     * Test for set function
     *
     * @param string $data Template name
     *
     * @return void
     *
     * @dataProvider providerTestSet
     */
    public function testSet($data)
    {
        $template = Template::get($data);
        $result = $template->render(
            array(
                'variable1' => 'value1',
                'variable2' => 'value2',
            )
        );
        $this->assertContains('value1', $result);
        $this->assertContains('value2', $result);
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
    public function testDynamicRender($templateFile, $key, $value)
    {
        $this->assertEquals(
            $value,
            Template::get($templateFile)->render([$key => $value])
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
            ['test/echo', 'variable', 'value'],
        ];
    }

    /**
     * Test for render
     *
     * @return void
     */
    public function testRenderTemplateNotFound()
    {
        $this->setExpectedException('Twig\Error\LoaderError');
        Template::get('template not found')->render();
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
    public function testRender($templateFile, $expectedResult)
    {
        $this->assertEquals(
            $expectedResult,
            Template::get($templateFile)->render()
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
            ['test/static', 'static content'],
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
    public function testRenderGettext($templateFile, $renderParams, $expectedResult)
    {
        $this->assertEquals(
            $expectedResult,
            Template::get($templateFile)->render($renderParams)
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
            ['test/gettext/gettext', [], 'Text'],
            ['test/gettext/pgettext', [], 'Text'],
            ['test/gettext/notes', [], 'Text'],
            ['test/gettext/plural', ['table_count' => 1], 'One table'],
            ['test/gettext/plural', ['table_count' => 2], '2 tables'],
            ['test/gettext/plural_notes', ['table_count' => 1], 'One table'],
            ['test/gettext/plural_notes', ['table_count' => 2], '2 tables'],
        ];
    }
}
