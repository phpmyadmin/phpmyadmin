<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Advisor;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class AdvisorTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        parent::setGlobalConfig();
        $GLOBALS['server'] = 1;
    }

    /**
     * test for Advisor::byTime
     *
     * @param float  $time     time
     * @param string $expected expected result
     *
     * @dataProvider advisorTimes
     */
    public function testAdvisorBytime(float $time, string $expected): void
    {
        $result = Advisor::byTime($time, 2);
        $this->assertEquals($expected, $result);
    }

    public function advisorTimes(): array
    {
        return [
            [
                10,
                '10 per second',
            ],
            [
                0.02,
                '1.2 per minute',
            ],
            [
                0.003,
                '10.8 per hour',
            ],
            [
                0.00003,
                '2.59 per day',
            ],
            [
                0.0000000003,
                '<0.01 per day',
            ],
        ];
    }

    /**
     * Test for adding rule
     *
     * @param array       $rule     Rule to test
     * @param array       $expected Expected rendered rule in fired/errors list
     * @param string|null $error    Expected error string (null if none error expected)
     *
     * @dataProvider rulesProvider
     */
    public function testAddRule(array $rule, array $expected, ?string $error): void
    {
        parent::loadDefaultConfig();
        parent::setLanguage();
        $advisor = new Advisor($GLOBALS['dbi'], new ExpressionLanguage());
        $parseResult = include ROOT_PATH . 'libraries/advisory_rules_generic.php';
        $this->assertIsArray($parseResult);
        $this->assertArrayHasKey(0, $parseResult);
        $this->assertIsArray($parseResult[0]);
        $advisor->setVariable('value', 0);
        $advisor->addRule('fired', $rule);
        $runResult = $advisor->getRunResult();
        if (isset($runResult['errors']) || $error !== null) {
            $this->assertEquals([$error], $runResult['errors']);
        }
        if (! isset($runResult['fired']) && $expected == []) {
            return;
        }

        $this->assertEquals([$expected], $runResult['fired']);
    }

    public function rulesProvider(): array
    {
        return [
            [
                [
                    'id' => 'Basic',
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
                    'id' => 'Variable',
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
                    'recommendation' => 'Recommend <a href="index.php?route=/server/variables&' .
                    'filter=status_var&lang=en">status_var</a>',
                ],
                null,
            ],
            [
                [
                    'id' => 'Format',
                    'justification' => '%s foo',
                    'justification_formula' => 'value',
                    'name' => 'Format',
                    'issue' => 'issue',
                    'recommendation' => 'Recommend',
                ],
                [
                    'justification' => '0 foo',
                    'justification_formula' => 'value',
                    'id' => 'Format',
                    'name' => 'Format',
                    'issue' => 'issue',
                    'recommendation' => 'Recommend',
                ],
                null,
            ],
            [
                [
                    'id' => 'Percent',
                    'justification' => '%s%% foo',
                    'justification_formula' => 'value',
                    'name' => 'Percent',
                    'issue' => 'issue',
                    'recommendation' => 'Recommend',
                ],
                [
                    'justification' => '0% foo',
                    'justification_formula' => 'value',
                    'id' => 'Percent',
                    'name' => 'Percent',
                    'issue' => 'issue',
                    'recommendation' => 'Recommend',
                ],
                null,
            ],
            [
                [
                    'id' => 'Double',
                    'justification' => '%s%% %d foo',
                    'justification_formula' => 'value, value',
                    'name' => 'Double',
                    'issue' => 'issue',
                    'recommendation' => 'Recommend',
                ],
                [
                    'justification' => '0% 0 foo',
                    'justification_formula' => 'value, value',
                    'id' => 'Double',
                    'name' => 'Double',
                    'issue' => 'issue',
                    'recommendation' => 'Recommend',
                ],
                null,
            ],
            [
                [
                    'id' => 'Quotes',
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
                    'justification' => 'foo',
                    'justification_formula' => 'fsafdsa',
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
                    'id' => 'Distribution',
                    'justification' => 'Version string (%s)',
                    'justification_formula' => 'value',
                    'name' => 'Distribution',
                    'issue' => 'official MySQL binaries.',
                    'recommendation' => 'See <a href="https://example.com/">web</a>',
                ],
                [
                    'justification' => 'Version string (0)',
                    'justification_formula' => 'value',
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
                    'id' => 'Distribution',
                    'justification' => 'Timestamp (%s)',
                    'justification_formula' => 'ADVISOR_timespanFormat(1377027)',
                    'name' => 'Distribution',
                    'issue' => 'official MySQL binaries.',
                    'recommendation' => 'See <a href="https://example.com/">web</a>',
                ],
                [
                    'justification' => 'Timestamp (15 days, 22 hours, 30 minutes and 27 seconds)',
                    'justification_formula' => 'ADVISOR_timespanFormat(1377027)',
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
                    'id' => 'Distribution',
                    'justification' => 'Memory: %s',
                    'justification_formula' => 'ADVISOR_formatByteDown(1000000, 2, 2)',
                    'name' => 'Distribution',
                    'issue' => 'official MySQL binaries.',
                    'recommendation' => 'See <a href="https://example.com/">web</a> and'
                        . ' <a href="https://example.com/">web2</a>',
                ],
                [
                    'justification' => 'Memory: 0.95 MiB',
                    'justification_formula' => 'ADVISOR_formatByteDown(1000000, 2, 2)',
                    'name' => 'Distribution',
                    'issue' => 'official MySQL binaries.',
                    'recommendation' => 'See <a href="./url.php?url=https%3A%2F%2F'
                        . 'example.com%2F" target="_blank" rel="noopener noreferrer">web</a>'
                        . ' and <a href="./url.php?url=https%3A%2F%2Fexample.com%2F" target="_blank"'
                        . ' rel="noopener noreferrer">web2</a>',
                    'id' => 'Distribution',
                ],
                null,
            ],
            [
                [
                    'id' => 'Distribution',
                    'justification' => 'Time: %s',
                    'justification_formula' => 'ADVISOR_bytime(0.02, 2)',
                    'name' => 'Distribution',
                    'issue' => '{long_query_time} is set to 10 seconds or more',
                    'recommendation' => 'See <a href=\'https://example.com/\'>web</a> and'
                        . ' <a href=\'https://example.com/\'>web2</a>',
                ],
                [
                    'justification' => 'Time: 1.2 per minute',
                    'justification_formula' => 'ADVISOR_bytime(0.02, 2)',
                    'name' => 'Distribution',
                    'issue' => '<a href="index.php?route=/server/variables&filter=long_query_time&lang=en">'
                        . 'long_query_time</a> is set to 10 seconds or more',
                    'recommendation' => 'See <a href="./url.php?url=https%3A%2F%2F'
                        . 'example.com%2F" target="_blank" rel="noopener noreferrer">web</a>'
                        . ' and <a href="./url.php?url=https%3A%2F%2Fexample.com%2F" target="_blank"'
                        . ' rel="noopener noreferrer">web2</a>',
                    'id' => 'Distribution',
                ],
                null,
            ],
            [
                [
                    'id' => 'Minor Version',
                    'justification' => 'Current version: %s',
                    'justification_formula' => 'value',
                    'name' => 'Minor Version',
                    'precondition' => '! fired(\'Release Series\')',
                    'issue' => 'Version less than 5.1.30',
                    'recommendation' => 'You should upgrade',
                    'formula' => 'version',
                    'test' => "substr(value,0,2) <= '5.' && substr(value,2,1) <= 1 && substr(value,4,2) < 30",
                ],
                [
                    'justification' => 'Current version: 0',
                    'justification_formula' => 'value',
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
