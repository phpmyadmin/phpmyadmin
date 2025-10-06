<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Advisory;

use PhpMyAdmin\Advisory\Advisor;
use PhpMyAdmin\Advisory\Rules;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/** @psalm-import-type RuleType from Rules */
#[CoversClass(Advisor::class)]
class AdvisorTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setGlobalConfig();

        DatabaseInterface::$instance = $this->createDatabaseInterface();
    }

    /**
     * test for Advisor::byTime
     *
     * @param float  $time     time
     * @param string $expected expected result
     */
    #[DataProvider('advisorTimes')]
    public function testAdvisorBytime(float $time, string $expected): void
    {
        $result = Advisor::byTime($time, 2);
        self::assertSame($expected, $result);
    }

    /** @return mixed[][] */
    public static function advisorTimes(): array
    {
        return [
            [10, '10 per second'],
            [0.02, '1.2 per minute'],
            [0.003, '10.8 per hour'],
            [0.00003, '2.59 per day'],
            [0.0000000003, '<0.01 per day'],
        ];
    }

    /**
     * Test for adding rule
     *
     * @param array<string, string> $rule     Rule to test
     * @param array<string, string> $expected Expected rendered rule in fired/errors list
     * @param string|null           $error    Expected error string (null if none error expected)
     * @psalm-param RuleType $rule
     * @psalm-param RuleType|array<empty> $expected
     */
    #[DataProvider('rulesProvider')]
    public function testAddRule(array $rule, array $expected, string|null $error): void
    {
        $this->setLanguage();

        $advisor = new Advisor(DatabaseInterface::getInstance(), new ExpressionLanguage());
        $advisor->setVariable('value', 0);
        $advisor->addRule('fired', $rule);
        $runResult = $advisor->getRunResult();
        if ($error !== null) {
            self::assertSame([$error], $runResult['errors']);
        }

        if ($runResult['fired'] === [] && $expected === []) {
            return;
        }

        self::assertEquals([$expected], $runResult['fired']);
    }

    /**
     * @return mixed[][]
     * @psalm-return array<int, array{RuleType, RuleType|array<empty>, string|null}>
     */
    public static function rulesProvider(): array
    {
        return [
            [
                [
                    'id' => 'Basic',
                    'justification' => 'foo',
                    'name' => 'Basic',
                    'issue' => 'issue',
                    'recommendation' => 'Recommend',
                    'formula' => 'formula',
                    'test' => 'test',
                ],
                [
                    'justification' => 'foo',
                    'id' => 'Basic',
                    'name' => 'Basic',
                    'issue' => 'issue',
                    'recommendation' => 'Recommend',
                    'formula' => 'formula',
                    'test' => 'test',
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
                    'formula' => 'formula',
                    'test' => 'test',
                ],
                [
                    'justification' => 'foo',
                    'id' => 'Variable',
                    'name' => 'Variable',
                    'issue' => 'issue',
                    'recommendation' => 'Recommend <a href="index.php?route=/server/variables&'
                        . 'filter=status_var&lang=en">status_var</a>',
                    'formula' => 'formula',
                    'test' => 'test',
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
                    'formula' => 'formula',
                    'test' => 'test',
                ],
                [
                    'justification' => '0 foo',
                    'justification_formula' => 'value',
                    'id' => 'Format',
                    'name' => 'Format',
                    'issue' => 'issue',
                    'recommendation' => 'Recommend',
                    'formula' => 'formula',
                    'test' => 'test',
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
                    'formula' => 'formula',
                    'test' => 'test',
                ],
                [
                    'justification' => '0% foo',
                    'justification_formula' => 'value',
                    'id' => 'Percent',
                    'name' => 'Percent',
                    'issue' => 'issue',
                    'recommendation' => 'Recommend',
                    'formula' => 'formula',
                    'test' => 'test',
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
                    'formula' => 'formula',
                    'test' => 'test',
                ],
                [
                    'justification' => '0% 0 foo',
                    'justification_formula' => 'value, value',
                    'id' => 'Double',
                    'name' => 'Double',
                    'issue' => 'issue',
                    'recommendation' => 'Recommend',
                    'formula' => 'formula',
                    'test' => 'test',
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
                    'formula' => 'formula',
                    'test' => 'test',
                ],
                [
                    'justification' => '"\'foo',
                    'id' => 'Quotes',
                    'name' => 'Quotes',
                    'issue' => 'issue',
                    'recommendation' => 'Recommend"\'',
                    'formula' => 'formula',
                    'test' => 'test',
                ],
                null,
            ],
            [
                [
                    'id' => 'Failure',
                    'justification' => 'foo',
                    'justification_formula' => 'fsafdsa',
                    'name' => 'Failure',
                    'issue' => 'issue',
                    'recommendation' => 'Recommend',
                    'formula' => 'formula',
                    'test' => 'test',
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
                    'formula' => 'formula',
                    'test' => 'test',
                ],
                [
                    'justification' => 'Version string (0)',
                    'justification_formula' => 'value',
                    'name' => 'Distribution',
                    'issue' => 'official MySQL binaries.',
                    'recommendation' => 'See <a href="index.php?route=/url&url=https%3A%2F%2F' .
                        'example.com%2F" target="_blank" rel="noopener noreferrer">web</a>',
                    'id' => 'Distribution',
                    'formula' => 'formula',
                    'test' => 'test',
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
                    'formula' => 'formula',
                    'test' => 'test',
                ],
                [
                    'justification' => 'Timestamp (15 days, 22 hours, 30 minutes and 27 seconds)',
                    'justification_formula' => 'ADVISOR_timespanFormat(1377027)',
                    'name' => 'Distribution',
                    'issue' => 'official MySQL binaries.',
                    'recommendation' => 'See <a href="index.php?route=/url&url=https%3A%2F%2F' .
                        'example.com%2F" target="_blank" rel="noopener noreferrer">web</a>',
                    'id' => 'Distribution',
                    'formula' => 'formula',
                    'test' => 'test',
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
                    'formula' => 'formula',
                    'test' => 'test',
                ],
                [
                    'justification' => 'Memory: 0.95 MiB',
                    'justification_formula' => 'ADVISOR_formatByteDown(1000000, 2, 2)',
                    'name' => 'Distribution',
                    'issue' => 'official MySQL binaries.',
                    'recommendation' => 'See <a href="index.php?route=/url&url=https%3A%2F%2F'
                        . 'example.com%2F" target="_blank" rel="noopener noreferrer">web</a>'
                        . ' and <a href="index.php?route=/url&url=https%3A%2F%2Fexample.com%2F" target="_blank"'
                        . ' rel="noopener noreferrer">web2</a>',
                    'id' => 'Distribution',
                    'formula' => 'formula',
                    'test' => 'test',
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
                    'formula' => 'formula',
                    'test' => 'test',
                ],
                [
                    'justification' => 'Time: 1.2 per minute',
                    'justification_formula' => 'ADVISOR_bytime(0.02, 2)',
                    'name' => 'Distribution',
                    'issue' => '<a href="index.php?route=/server/variables&filter=long_query_time&lang=en">'
                        . 'long_query_time</a> is set to 10 seconds or more',
                    'recommendation' => 'See <a href="index.php?route=/url&url=https%3A%2F%2F'
                        . 'example.com%2F" target="_blank" rel="noopener noreferrer">web</a>'
                        . ' and <a href="index.php?route=/url&url=https%3A%2F%2Fexample.com%2F" target="_blank"'
                        . ' rel="noopener noreferrer">web2</a>',
                    'id' => 'Distribution',
                    'formula' => 'formula',
                    'test' => 'test',
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
