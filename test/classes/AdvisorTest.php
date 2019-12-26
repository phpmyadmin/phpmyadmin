<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for Advisor class
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

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
    protected function setUp(): void
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
    public function testEscape($text, $expected): void
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
        return [
            [
                '80%',
                '80%%',
            ],
            [
                '%s%',
                '%s%%',
            ],
            [
                '80% foo',
                '80%% foo',
            ],
            [
                '%s% foo',
                '%s%% foo',
            ],
        ];
    }

    /**
     * test for parseRulesFile
     *
     * @return void
     */
    public function testParse()
    {
        $advisor = new Advisor($GLOBALS['dbi'], new ExpressionLanguage());
        $parseResult = $advisor->parseRulesFile(Advisor::GENERIC_RULES_FILE);
        $this->assertEquals($parseResult['errors'], []);
    }

    /**
     * test for Advisor::byTime
     *
     * @param float  $time     time
     * @param string $expected expected result
     *
     * @return void
     *
     * @dataProvider advisorTimes
     */
    public function testAdvisorBytime($time, $expected): void
    {
        $result = Advisor::byTime($time, 2);
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function advisorTimes()
    {
        return [
            [
                10,
                "10 per second",
            ],
            [
                0.02,
                "1.2 per minute",
            ],
            [
                0.003,
                "10.8 per hour",
            ],
            [
                0.00003,
                "2.59 per day",
            ],
            [
                0.0000000003,
                "<0.01 per day",
            ],
        ];
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
    public function testAddRule($rule, $expected, $error): void
    {
        $advisor = new Advisor($GLOBALS['dbi'], new ExpressionLanguage());
        $parseResult = $advisor->parseRulesFile(Advisor::GENERIC_RULES_FILE);
        $this->assertEquals($parseResult['errors'], []);
        $advisor->setVariable('value', 0);
        $advisor->addRule('fired', $rule);
        $runResult = $advisor->getRunResult();
        if (isset($runResult['errors']) || $error !== null) {
            $this->assertEquals([$error], $runResult['errors']);
        }
        if (isset($runResult['fired']) || $expected != []) {
            $this->assertEquals([$expected], $runResult['fired']);
        }
    }

    /**
     * rules Provider
     *
     * @return array
     */
    public function rulesProvider()
    {
        return [
            [
                [
                    'justification' => 'foo',
                    'name' => 'Basic',
                    'issue' => 'issue',
                    'recommendation' => 'Recommend',
                ],
                [
                    'justification' => 'foo',
                    'id' => 'Basic',
                    'name' => 'Basic',
                    'issue' => 'issue',
                    'recommendation' => 'Recommend',
                ],
                null,
            ],
            [
                [
                    'justification' => 'foo',
                    'name' => 'Variable',
                    'issue' => 'issue',
                    'recommendation' => 'Recommend {status_var}',
                ],
                [
                    'justification' => 'foo',
                    'id' => 'Variable',
                    'name' => 'Variable',
                    'issue' => 'issue',
                    'recommendation' => 'Recommend <a href="server_variables.php?' .
                    'filter=status_var&amp;lang=en">status_var</a>',
                ],
                null,
            ],
            [
                [
                    'justification' => '%s foo | value',
                    'name' => 'Format',
                    'issue' => 'issue',
                    'recommendation' => 'Recommend',
                ],
                [
                    'justification' => '0 foo',
                    'id' => 'Format',
                    'name' => 'Format',
                    'issue' => 'issue',
                    'recommendation' => 'Recommend',
                ],
                null,
            ],
            [
                [
                    'justification' => '%s% foo | value',
                    'name' => 'Percent',
                    'issue' => 'issue',
                    'recommendation' => 'Recommend',
                ],
                [
                    'justification' => '0% foo',
                    'id' => 'Percent',
                    'name' => 'Percent',
                    'issue' => 'issue',
                    'recommendation' => 'Recommend',
                ],
                null,
            ],
            [
                [
                    'justification' => '%s% %d foo | value, value',
                    'name' => 'Double',
                    'issue' => 'issue',
                    'recommendation' => 'Recommend',
                ],
                [
                    'justification' => '0% 0 foo',
                    'id' => 'Double',
                    'name' => 'Double',
                    'issue' => 'issue',
                    'recommendation' => 'Recommend',
                ],
                null,
            ],
            [
                [
                    'justification' => '"\'foo',
                    'name' => 'Quotes',
                    'issue' => 'issue',
                    'recommendation' => 'Recommend"\'',
                ],
                [
                    'justification' => '"\'foo',
                    'id' => 'Quotes',
                    'name' => 'Quotes',
                    'issue' => 'issue',
                    'recommendation' => 'Recommend"\'',
                ],
                null,
            ],
            [
                [
                    'justification' => 'foo | fsafdsa',
                    'name' => 'Failure',
                    'issue' => 'issue',
                    'recommendation' => 'Recommend',
                ],
                [],
                'Failed formatting string for rule \'Failure\'. ' .
                'Error when evaluating: Variable "fsafdsa" is not ' .
                'valid around position 2 for expression `[fsafdsa]`.',
            ],
            [
                [
                    'justification' => 'Version string (%s) | value',
                    'name' => 'Distribution',
                    'issue' => 'official MySQL binaries.',
                    'recommendation' => 'See <a href="https://example.com/">web</a>',
                ],
                [
                    'justification' => 'Version string (0)',
                    'name' => 'Distribution',
                    'issue' => 'official MySQL binaries.',
                    'recommendation' => 'See <a href="./url.php?url=https%3A%2F%2F' .
                        'example.com%2F" target="_blank" rel="noopener noreferrer">web</a>',
                    'id' => 'Distribution',
                ],
                null,
            ],
            [
                [
                    'justification' => 'Timestamp (%s) | ADVISOR_timespanFormat(1377027)',
                    'name' => 'Distribution',
                    'issue' => 'official MySQL binaries.',
                    'recommendation' => 'See <a href="https://example.com/">web</a>',
                ],
                [
                    'justification' => 'Timestamp (15 days, 22 hours, 30 minutes and 27 seconds)',
                    'name' => 'Distribution',
                    'issue' => 'official MySQL binaries.',
                    'recommendation' => 'See <a href="./url.php?url=https%3A%2F%2F' .
                        'example.com%2F" target="_blank" rel="noopener noreferrer">web</a>',
                    'id' => 'Distribution',
                ],
                null,
            ],
            [
                [
                    'justification' => 'Memory: %s | ADVISOR_formatByteDown(1000000, 2, 2)',
                    'name' => 'Distribution',
                    'issue' => 'official MySQL binaries.',
                    'recommendation' => 'See <a href="https://example.com/">web</a>',
                ],
                [
                    'justification' => 'Memory: 0.95 MiB',
                    'name' => 'Distribution',
                    'issue' => 'official MySQL binaries.',
                    'recommendation' => 'See <a href="./url.php?url=https%3A%2F%2F' .
                        'example.com%2F" target="_blank" rel="noopener noreferrer">web</a>',
                    'id' => 'Distribution',
                ],
                null,
            ],
            [
                [
                    'justification' => 'Time: %s | ADVISOR_bytime(0.02, 2)',
                    'name' => 'Distribution',
                    'issue' => 'official MySQL binaries.',
                    'recommendation' => 'See <a href="https://example.com/">web</a>',
                ],
                [
                    'justification' => 'Time: 1.2 per minute',
                    'name' => 'Distribution',
                    'issue' => 'official MySQL binaries.',
                    'recommendation' => 'See <a href="./url.php?url=https%3A%2F%2F' .
                        'example.com%2F" target="_blank" rel="noopener noreferrer">web</a>',
                    'id' => 'Distribution',
                ],
                null,
            ],
            [
                [
                    'justification' => 'Current version: %s | value',
                    'name' => 'Minor Version',
                    'precondition' => '! fired(\'Release Series\')',
                    'issue' => 'Version less than 5.1.30',
                    'recommendation' => 'You should upgrade',
                    'formula' => 'version',
                    'test' => "substr(value,0,2) <= '5.' && substr(value,2,1) <= 1 && substr(value,4,2) < 30",
                ],
                [
                    'justification' => 'Current version: 0',
                    'name' => 'Minor Version',
                    'issue' => 'Version less than 5.1.30',
                    'recommendation' => 'You should upgrade',
                    'id' => 'Minor Version',
                    'precondition' => '! fired(\'Release Series\')',
                    'formula' => 'version',
                    'test' => "substr(value,0,2) <= '5.' && substr(value,2,1) <= 1 && substr(value,4,2) < 30",
                ],
                null,
            ],
        ];
    }
}
