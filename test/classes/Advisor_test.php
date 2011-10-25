<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for Advisor class
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/Advisor.class.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/core.lib.php';

class Advisor_test extends PHPUnit_Framework_TestCase
{
    public function setup()
    {
        $_SESSION[' PMA_token '] = 'token';
        $GLOBALS['lang'] = 'en';
    }

    /**
     * @dataProvider escapeStrings
     */
    public function testEscape($text, $expected)
    {
        $this->assertEquals(Advisor::escapePercent($text), $expected);
    }

    public function escapeStrings() {
        return array(
            array('80%', '80%%'),
            array('%s%', '%s%%'),
            array('80% foo', '80%% foo'),
            array('%s% foo', '%s%% foo'),
            );
    }

    public function testParse()
    {
        $advisor = new Advisor();
        $parseResult = $advisor->parseRulesFile();
        $this->assertEquals($parseResult['errors'], array());
    }

    /**
     * @depends testParse
     * @dataProvider rulesProvider
     */
    public function testAddRule($rule, $expected, $error)
    {
        $advisor = new Advisor();
        $parseResult = $advisor->parseRulesFile();
        $this->assertEquals($parseResult['errors'], array());
        $advisor->variables['value'] = 0;
        $advisor->addRule('fired', $rule);
        if (isset($advisor->runResult['errors']) || !is_null($error)) {
            $this->assertEquals($advisor->runResult['errors'], array($error));
        }
        if (isset($advisor->runResult['fired']) || $expected != array()) {
            $this->assertEquals($advisor->runResult['fired'], array($expected));
        }
    }

    public function rulesProvider()
    {
        return array(
            array(
                array('justification' => 'foo', 'name' => 'Basic', 'issue' => 'issue', 'recommendation' => 'Recommend'),
                array('justification' => 'foo', 'name' => 'Basic', 'issue' => 'issue', 'recommendation' => 'Recommend'),
                null,
            ),
            array(
                array('justification' => 'foo', 'name' => 'Variable', 'issue' => 'issue', 'recommendation' => 'Recommend {status_var}'),
                array('justification' => 'foo', 'name' => 'Variable', 'issue' => 'issue', 'recommendation' => 'Recommend <a href="server_variables.php?lang=en&amp;token=token#filter=status_var">status_var</a>'),
                null,
            ),
            array(
                array('justification' => '%s foo | value', 'name' => 'Format', 'issue' => 'issue', 'recommendation' => 'Recommend'),
                array('justification' => '0 foo', 'name' => 'Format', 'issue' => 'issue', 'recommendation' => 'Recommend'),
                null,
            ),
            array(
                array('justification' => '%s% foo | value', 'name' => 'Percent', 'issue' => 'issue', 'recommendation' => 'Recommend'),
                array('justification' => '0% foo', 'name' => 'Percent', 'issue' => 'issue', 'recommendation' => 'Recommend'),
                null,
            ),
            array(
                array('justification' => '"\'foo', 'name' => 'Quotes', 'issue' => 'issue', 'recommendation' => 'Recommend"\''),
                array('justification' => '"\'foo', 'name' => 'Quotes', 'issue' => 'issue', 'recommendation' => 'Recommend"\''),
                null,
            ),
            array(
                array('justification' => 'foo | fsafdsa', 'name' => 'Failure', 'issue' => 'issue', 'recommendation' => 'Recommend'),
                array(),
                'Failed formatting string for rule \'Failure\'. PHP threw following error: Use of undefined constant fsafdsa - assumed \'fsafdsa\'',
            ),
        );
    }
}
?>
