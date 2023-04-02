<?php

declare(strict_types=1);

namespace PhpMyAdmin\Advisory;

use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Server\SysInfo\SysInfo;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Throwable;

use function __;
use function array_merge;
use function htmlspecialchars;
use function implode;
use function preg_match;
use function preg_replace_callback;
use function round;
use function sprintf;
use function str_contains;
use function substr;
use function vsprintf;

/**
 * A simple rules engine, that executes the rules in the {@see Rules} class.
 *
 * @psalm-import-type RuleType from Rules
 */
class Advisor
{
    /** @var mixed[] */
    private array $variables = [];

    /** @var mixed[] */
    private array $globals = [];

    /**
     * @var array<int, array<string, string>>
     * @psalm-var list<RuleType>
     */
    private array $rules = [];

    /** @var array{fired:mixed[], notfired:mixed[], unchecked:mixed[], errors:mixed[]} */
    private array $runResult = ['fired' => [], 'notfired' => [], 'unchecked' => [], 'errors' => []];

    public function __construct(private DatabaseInterface $dbi, private ExpressionLanguage $expression)
    {
        /**
         * Register functions for ExpressionLanguage, we intentionally
         * do not implement support for compile as we do not use it.
         */
        $this->expression->register(
            'round',
            static function (): void {
            },
            static fn (array $arguments, float $num) => round($num)
        );
        $this->expression->register(
            'substr',
            static function (): void {
            },
            static fn (array $arguments, string $string, int $start, int $length) => substr($string, $start, $length)
        );
        $this->expression->register(
            'preg_match',
            static function (): void {
            },
            static fn (array $arguments, string $pattern, string $subject) => preg_match($pattern, $subject)
        );
        $this->expression->register(
            'ADVISOR_bytime',
            static function (): void {
            },
            static fn (array $arguments, float $num, int $precision) => self::byTime($num, $precision)
        );
        $this->expression->register(
            'ADVISOR_timespanFormat',
            static function (): void {
            },
            static fn (array $arguments, string $seconds) => Util::timespanFormat((int) $seconds)
        );
        $this->expression->register(
            'ADVISOR_formatByteDown',
            static function (): void {
            },
            static fn (
                array $arguments,
                int $value,
                int $limes = 6,
                int $comma = 0,
            ) => implode(' ', (array) Util::formatByteDown($value, $limes, $comma))
        );
        $this->expression->register(
            'fired',
            static function (): void {
            },
            function (array $arguments, int|string $value) {
                // Did matching rule fire?
                foreach ($this->runResult['fired'] as $rule) {
                    if ($rule['id'] == $value) {
                        return '1';
                    }
                }

                return '0';
            },
        );
        /* Some global variables for advisor */
        $this->globals = [
            'PMA_MYSQL_INT_VERSION' => $this->dbi->getVersion(),
            'IS_MARIADB' => $this->dbi->isMariaDB(),
        ];
    }

    private function setVariables(): void
    {
        $globalStatus = $this->dbi->fetchResult('SHOW GLOBAL STATUS', 0, 1);
        $globalVariables = $this->dbi->fetchResult('SHOW GLOBAL VARIABLES', 0, 1);

        $sysInfo = SysInfo::get();
        $memory = $sysInfo->memory();
        $systemMemory = ['system_memory' => $memory['MemTotal'] ?? 0];

        $this->variables = array_merge($globalStatus, $globalVariables, $systemMemory);
    }

    /**
     * @param string|int $variable Variable to set
     * @param mixed      $value    Value to set
     */
    public function setVariable(string|int $variable, mixed $value): void
    {
        $this->variables[$variable] = $value;
    }

    private function setRules(): void
    {
        $isMariaDB = str_contains($this->variables['version'], 'MariaDB');
        $genericRules = Rules::getGeneric();

        if (! $isMariaDB && $this->globals['PMA_MYSQL_INT_VERSION'] >= 80003) {
            $this->rules = $genericRules;

            return;
        }

        $extraRules = Rules::getBeforeMySql80003();
        $this->rules = array_merge($genericRules, $extraRules);
    }

    /** @return array{fired: mixed[], notfired: mixed[], unchecked: mixed[], errors: mixed[]} */
    public function getRunResult(): array
    {
        return $this->runResult;
    }

    /** @psalm-return array{fired:mixed[], notfired:mixed[], unchecked:mixed[], errors:mixed[]} */
    public function run(): array
    {
        $this->setVariables();
        $this->setRules();
        $this->runRules();

        return $this->runResult;
    }

    /**
     * Stores current error in run results.
     *
     * @param string    $description description of an error.
     * @param Throwable $exception   exception raised
     */
    private function storeError(string $description, Throwable $exception): void
    {
        $this->runResult['errors'][] = $description . ' ' . sprintf(
            __('Error when evaluating: %s'),
            $exception->getMessage(),
        );
    }

    /**
     * Executes advisor rules
     */
    private function runRules(): void
    {
        $this->runResult = ['fired' => [], 'notfired' => [], 'unchecked' => [], 'errors' => []];

        foreach ($this->rules as $rule) {
            $this->variables['value'] = 0;
            $precondition = true;

            if (isset($rule['precondition'])) {
                try {
                    $precondition = $this->evaluateRuleExpression($rule['precondition']);
                } catch (Throwable $e) {
                    $this->storeError(
                        sprintf(
                            __('Failed evaluating precondition for rule \'%s\'.'),
                            $rule['name'],
                        ),
                        $e,
                    );
                    continue;
                }
            }

            if (! $precondition) {
                $this->addRule('unchecked', $rule);

                continue;
            }

            try {
                $value = $this->evaluateRuleExpression($rule['formula']);
            } catch (Throwable $e) {
                $this->storeError(
                    sprintf(
                        __('Failed calculating value for rule \'%s\'.'),
                        $rule['name'],
                    ),
                    $e,
                );
                continue;
            }

            $this->variables['value'] = $value;

            try {
                if ($this->evaluateRuleExpression($rule['test'])) {
                    $this->addRule('fired', $rule);
                } else {
                    $this->addRule('notfired', $rule);
                }
            } catch (Throwable $e) {
                $this->storeError(
                    sprintf(
                        __('Failed running test for rule \'%s\'.'),
                        $rule['name'],
                    ),
                    $e,
                );
            }
        }
    }

    /**
     * Adds a rule to the result list
     *
     * @param string  $type type of rule
     * @param mixed[] $rule rule itself
     * @psalm-param 'notfired'|'fired'|'unchecked'|'errors' $type
     */
    public function addRule(string $type, array $rule): void
    {
        if ($type !== 'notfired' && $type !== 'fired') {
            $this->runResult[$type][] = $rule;

            return;
        }

        if (isset($rule['justification_formula'])) {
            try {
                $params = $this->evaluateRuleExpression('[' . $rule['justification_formula'] . ']');
            } catch (Throwable $e) {
                $this->storeError(
                    sprintf(__('Failed formatting string for rule \'%s\'.'), $rule['name']),
                    $e,
                );

                return;
            }

            $rule['justification'] = vsprintf($rule['justification'], $params);
        }

        // Replaces {server_variable} with 'server_variable'
        // linking to /server/variables
        $rule['recommendation'] = preg_replace_callback(
            '/\{([a-z_0-9]+)\}/Ui',
            fn (array $matches) => $this->replaceVariable($matches),
            $rule['recommendation'],
        );
        $rule['issue'] = preg_replace_callback(
            '/\{([a-z_0-9]+)\}/Ui',
            fn (array $matches) => $this->replaceVariable($matches),
            $rule['issue'],
        );

        // Replaces external Links with Core::linkURL() generated links
        $rule['recommendation'] = preg_replace_callback(
            '#href=("|\')(https?://[^"\']+)\1#i',
            fn (array $matches) => $this->replaceLinkURL($matches),
            $rule['recommendation'],
        );

        $this->runResult[$type][] = $rule;
    }

    /**
     * Callback for wrapping links with Core::linkURL
     *
     * @param mixed[] $matches List of matched elements form preg_replace_callback
     *
     * @return string Replacement value
     */
    private function replaceLinkURL(array $matches): string
    {
        return 'href="' . Core::linkURL($matches[2]) . '" target="_blank" rel="noopener noreferrer"';
    }

    /**
     * Callback for wrapping variable edit links
     *
     * @param mixed[] $matches List of matched elements form preg_replace_callback
     *
     * @return string Replacement value
     */
    private function replaceVariable(array $matches): string
    {
        return '<a href="' . Url::getFromRoute('/server/variables', ['filter' => $matches[1]])
                . '">' . htmlspecialchars($matches[1]) . '</a>';
    }

    /**
     * Runs a code expression, replacing variable names with their respective values
     *
     * @return mixed result of evaluated expression
     */
    private function evaluateRuleExpression(string $expression): mixed
    {
        return $this->expression->evaluate($expression, array_merge($this->variables, $this->globals));
    }

    /**
     * Formats interval like 10 per hour
     *
     * @param float $num       number to format
     * @param int   $precision required precision
     *
     * @return string formatted string
     */
    public static function byTime(float $num, int $precision): string
    {
        if ($num >= 1) { // per second
            $per = __('per second');
        } elseif ($num * 60 >= 1) { // per minute
            $num *= 60;
            $per = __('per minute');
        } elseif ($num * 60 * 60 >= 1) { // per hour
            $num *= 60 * 60;
            $per = __('per hour');
        } else {
            $num *= 24 * 60 * 60;
            $per = __('per day');
        }

        $num = round($num, $precision);

        if ($num == 0) {
            $num = '<' . 10 ** (-$precision);
        }

        return $num . ' ' . $per;
    }
}
