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
require_once 'libraries/Theme.class.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/core.lib.php';
require_once 'libraries/Util.class.php';

/**
 * Tests behaviour of PMA_Advisor class
 *
 * @package PhpMyAdmin-test
 */
class Advisor_Test extends PHPUnit_Framework_TestCase
{

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @return void
     */
    public function setup()
    {
        $_SESSION['PMA_Theme'] = PMA_Theme::load('./themes/pmahomme');
        $GLOBALS['server'] = 0;
        $GLOBALS['cfg']['ServerDefault'] = '';
    }

    /**
     * Tests string escaping
     *
     * @param string $text     Text to escape
     * @param string $expected Expected output
     *
     * @return void
     *
     * @dataProvider escapeStrings
     */
    public function testEscape($text, $expected)
    {
        $this->assertEquals(Advisor::escapePercent($text), $expected);
    }

    /**
     * return of escape Strings
     *
     * @return array
     */
    public function escapeStrings()
    {
        return array(
            array('80%', '80%%'),
            array('%s%', '%s%%'),
            array('80% foo', '80%% foo'),
            array('%s% foo', '%s%% foo'),
            );
    }

    /**
     * test for parseRulesFile
     *
     * @return void
     */
    public function testParse()
    {
        $advisor = new Advisor();
        $parseResult = $advisor->parseRulesFile();
        $this->assertEquals($parseResult['errors'], array());
    }

    /**
     * test for ADVISOR_bytime
     *
     * @return void
     */
    public function testAdvisorBytime()
    {
        $result = ADVISOR_bytime(10, 2);
        $this->assertEquals("10 per second", $result);

        $result = ADVISOR_bytime(0.02, 2);
        $this->assertEquals("1.2 per minute", $result);

        $result = ADVISOR_bytime(0.003, 2);
        $this->assertEquals("10.8 per hour", $result);
    }

    /**
     * test for ADVISOR_timespanFormat
     *
     * @return void
     */
    public function testAdvisorTimespanFormat()
    {
        $result = ADVISOR_timespanFormat(1200);
        $this->assertEquals("0 days, 0 hours, 20 minutes and 0 seconds", $result);

        $result = ADVISOR_timespanFormat(100);
        $this->assertEquals("0 days, 0 hours, 1 minutes and 40 seconds", $result);
    }

    /**
     * Test for adding rule
     *
     * @param array  $rule     Rule to test
     * @param array  $expected Expected rendered rule in fired/errors list
     * @param string $error    Expected error string (null if none error expected)
     *
     * @return void
     *
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
            $this->assertEquals(array($error), $advisor->runResult['errors']);
        }
        if (isset($advisor->runResult['fired']) || $expected != array()) {
            $this->assertEquals(array($expected), $advisor->runResult['fired']);
        }
    }

    /**
     * rules Provider
     *
     * @return array
     */
    public function rulesProvider()
    {
        return array(
            array(
                array(
                    'justification' => 'foo',
                    'name' => 'Basic',
                    'issue' => 'issue',
                    'recommendation' => 'Recommend'
                ),
                array(
                    'justification' => 'foo',
                    'id' => 'Basic',
                    'name' => 'Basic',
                    'issue' => 'issue',
                    'recommendation' => 'Recommend'
                ),
                null,
            ),
            array(
                array(
                    'justification' => 'foo',
                    'name' => 'Variable',
                    'issue' => 'issue',
                    'recommendation' => 'Recommend {status_var}'
                ),
                array(
                    'justification' => 'foo',
                    'id' => 'Variable',
                    'name' => 'Variable',
                    'issue' => 'issue',
                    'recommendation' => 'Recommend <a href="server_variables.php?' .
                    'lang=en&amp;token=token&filter=status_var">status_var</a>'
                ),
                null,
            ),
            array(
                array(
                    'justification' => '%s foo | value',
                    'name' => 'Format',
                    'issue' => 'issue',
                    'recommendation' => 'Recommend'
                ),
                array(
                    'justification' => '0 foo',
                    'id' => 'Format',
                    'name' => 'Format',
                    'issue' => 'issue',
                    'recommendation' => 'Recommend'
                ),
                null,
            ),
            array(
                array(
                    'justification' => '%s% foo | value',
                    'name' => 'Percent',
                    'issue' => 'issue',
                    'recommendation' => 'Recommend'
                ),
                array(
                    'justification' => '0% foo',
                    'id' => 'Percent',
                    'name' => 'Percent',
                    'issue' => 'issue',
                    'recommendation' => 'Recommend'
                ),
                null,
            ),
            array(
                array(
                    'justification' => '%s% %d foo | value, value',
                    'name' => 'Double',
                    'issue' => 'issue',
                    'recommendation' => 'Recommend'
                ),
                array(
                    'justification' => '0% 0 foo',
                    'id' => 'Double',
                    'name' => 'Double',
                    'issue' => 'issue',
                    'recommendation' => 'Recommend'
                ),
                null,
            ),
            array(
                array(
                    'justification' => '"\'foo',
                    'name' => 'Quotes',
                    'issue' => 'issue',
                    'recommendation' => 'Recommend"\''
                ),
                array(
                    'justification' => '"\'foo',
                    'id' => 'Quotes',
                    'name' => 'Quotes',
                    'issue' => 'issue',
                    'recommendation' => 'Recommend"\''
                ),
                null,
            ),
            array(
                array(
                    'justification' => 'foo | fsafdsa',
                    'name' => 'Failure',
                    'issue' => 'issue',
                    'recommendation' => 'Recommend'
                ),
                array(),
                'Failed formatting string for rule \'Failure\'. PHP threw ' .
                'following error: Use of undefined constant fsafdsa - ' .
                'assumed \'fsafdsa\'<br />Executed code: $value = array(fsafdsa);',
            ),
            array(
                array(
                    'justification' => 'Version string (%s) | value',
                    'name' => 'Distribution',
                    'issue' => 'official MySQL binaries.',
                    'recommendation' => 'See <a href="http://phpma.org/">web</a>',
                ),
                array(
                    'justification' => 'Version string (0)',
                    'name' => 'Distribution',
                    'issue' => 'official MySQL binaries.',
                    'recommendation' => 'See <a href="./url.php?url=http%3A%2F%2F' .
                        'phpma.org%2F" target="_blank">web</a>',
                    'id' => 'Distribution'
                ),
                null,
            ),
        );
    }
}
