<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for Advisor class
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Advisor;
use PhpMyAdmin\Config;
use PhpMyAdmin\Tests\PmaTestCase;
use PhpMyAdmin\Theme;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * Tests behaviour of PMA_Advisor class
 *
 * @package PhpMyAdmin-test
 */
class AdvisorTest extends PmaTestCase
{
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @return void
     */
    public function setup()
    {
        $GLOBALS['PMA_Config'] = new Config();
        $GLOBALS['server'] = 1;
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
        $advisor = new Advisor($GLOBALS['dbi'], new ExpressionLanguage());
        $parseResult = $advisor->parseRulesFile();
        $this->assertEquals($parseResult['errors'], array());
    }

    /**
     * test for Advisor::byTime
     *
     * @return void
     *
     * @dataProvider advisorTimes
     */
    public function testAdvisorBytime($time, $expected)
    {
        $result = Advisor::byTime($time, 2);
        $this->assertEquals($expected, $result);
    }

    public function advisorTimes()
    {
        return array(
            array(10, "10 per second"),
            array(0.02, "1.2 per minute"),
            array(0.003, "10.8 per hour"),
            array(0.00003, "2.59 per day"),
            array(0.0000000003, "<0.01 per day"),
        );
    }

    /**
     * test for Advisor::timespanFormat
     *
     * @return void
     */
    public function testAdvisorTimespanFormat()
    {
        $result = Advisor::timespanFormat(1200);
        $this->assertEquals("0 days, 0 hours, 20 minutes and 0 seconds", $result);

        $result = Advisor::timespanFormat(100);
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
        $advisor = new Advisor($GLOBALS['dbi'], new ExpressionLanguage());
        $parseResult = $advisor->parseRulesFile();
        $this->assertEquals($parseResult['errors'], array());
        $advisor->setVariable('value', 0);
        $advisor->addRule('fired', $rule);
        $runResult = $advisor->getRunResult();
        if (isset($runResult['errors']) || !is_null($error)) {
            $this->assertEquals(array($error), $runResult['errors']);
        }
        if (isset($runResult['fired']) || $expected != array()) {
            $this->assertEquals(array($expected), $runResult['fired']);
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
                    'filter=status_var&amp;lang=en">status_var</a>'
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
                'Failed formatting string for rule \'Failure\'. ' .
                'Error when evaluating: Variable "fsafdsa" is not ' .
                'valid around position 2 for expression `[fsafdsa]`.'
            ),
            array(
                array(
                    'justification' => 'Version string (%s) | value',
                    'name' => 'Distribution',
                    'issue' => 'official MySQL binaries.',
                    'recommendation' => 'See <a href="https://example.com/">web</a>',
                ),
                array(
                    'justification' => 'Version string (0)',
                    'name' => 'Distribution',
                    'issue' => 'official MySQL binaries.',
                    'recommendation' => 'See <a href="./url.php?url=https%3A%2F%2F' .
                        'example.com%2F" target="_blank" rel="noopener noreferrer">web</a>',
                    'id' => 'Distribution'
                ),
                null,
            ),
            array(
                array(
                    'justification' => 'Timestamp (%s) | ADVISOR_timespanFormat(1377027)',
                    'name' => 'Distribution',
                    'issue' => 'official MySQL binaries.',
                    'recommendation' => 'See <a href="https://example.com/">web</a>',
                ),
                array(
                    'justification' => 'Timestamp (15 days, 22 hours, 30 minutes and 27 seconds)',
                    'name' => 'Distribution',
                    'issue' => 'official MySQL binaries.',
                    'recommendation' => 'See <a href="./url.php?url=https%3A%2F%2F' .
                        'example.com%2F" target="_blank" rel="noopener noreferrer">web</a>',
                    'id' => 'Distribution'
                ),
                null,
            ),
            array(
                array(
                    'justification' => 'Memory: %s | ADVISOR_formatByteDown(1000000, 2, 2)',
                    'name' => 'Distribution',
                    'issue' => 'official MySQL binaries.',
                    'recommendation' => 'See <a href="https://example.com/">web</a>',
                ),
                array(
                    'justification' => 'Memory: 0.95 MiB',
                    'name' => 'Distribution',
                    'issue' => 'official MySQL binaries.',
                    'recommendation' => 'See <a href="./url.php?url=https%3A%2F%2F' .
                        'example.com%2F" target="_blank" rel="noopener noreferrer">web</a>',
                    'id' => 'Distribution'
                ),
                null,
            ),
            array(
                array(
                    'justification' => 'Time: %s | ADVISOR_bytime(0.02, 2)',
                    'name' => 'Distribution',
                    'issue' => 'official MySQL binaries.',
                    'recommendation' => 'See <a href="https://example.com/">web</a>',
                ),
                array(
                    'justification' => 'Time: 1.2 per minute',
                    'name' => 'Distribution',
                    'issue' => 'official MySQL binaries.',
                    'recommendation' => 'See <a href="./url.php?url=https%3A%2F%2F' .
                        'example.com%2F" target="_blank" rel="noopener noreferrer">web</a>',
                    'id' => 'Distribution'
                ),
                null,
            ),
            array(
                array(
                    'justification' => 'Current version: %s | value',
                    'name' => 'Minor Version',
                    'precondition' => '! fired(\'Release Series\')',
                    'issue' => 'Version less than 5.1.30',
                    'recommendation' => 'You should upgrade',
                    'formula' => 'version',
                    'test' => "substr(value,0,2) <= '5.' && substr(value,2,1) <= 1 && substr(value,4,2) < 30",
                ),
                array(
                    'justification' => 'Current version: 0',
                    'name' => 'Minor Version',
                    'issue' => 'Version less than 5.1.30',
                    'recommendation' => 'You should upgrade',
                    'id' => 'Minor Version',
                    'precondition' => '! fired(\'Release Series\')',
                    'formula' => 'version',
                    'test' => "substr(value,0,2) <= '5.' && substr(value,2,1) <= 1 && substr(value,4,2) < 30",
                ),
                null,
            ),
        );
    }
}
