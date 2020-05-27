<?php
/**
 * A simple rules engine, that parses and executes the rules in advisory_rules.txt.
 * Adjusted to phpMyAdmin.
 */

declare(strict_types=1);

namespace PhpMyAdmin;

use Exception;
use PhpMyAdmin\Server\SysInfo\SysInfo;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Throwable;
use const FILE_IGNORE_NEW_LINES;
use function array_merge;
use function array_merge_recursive;
use function count;
use function file;
use function htmlspecialchars;
use function implode;
use function mb_substr;
use function pow;
use function preg_match;
use function preg_replace;
use function preg_replace_callback;
use function preg_split;
use function round;
use function rtrim;
use function sprintf;
use function strpos;
use function substr;
use function vsprintf;

/**
 * Advisor class
 */
class Advisor
{
    public const GENERIC_RULES_FILE = 'libraries/advisory_rules_generic.txt';
    public const BEFORE_MYSQL80003_RULES_FILE = 'libraries/advisory_rules_mysql_before80003.txt';

    /** @var DatabaseInterface */
    protected $dbi;

    /** @var array */
    protected $variables;

    /** @var array */
    protected $globals;

    /** @var array */
    protected $parseResult;

    /** @var array */
    protected $runResult;

    /** @var ExpressionLanguage */
    protected $expression;

    /**
     * @param DatabaseInterface  $dbi        DatabaseInterface object
     * @param ExpressionLanguage $expression ExpressionLanguage object
     */
    public function __construct(DatabaseInterface $dbi, ExpressionLanguage $expression)
    {
        $this->dbi = $dbi;
        $this->expression = $expression;
        /*
         * Register functions for ExpressionLanguage, we intentionally
         * do not implement support for compile as we do not use it.
         */
        $this->expression->register(
            'round',
            static function () {
            },
            /**
             * @param array $arguments
             * @param float $num
             */
            static function ($arguments, $num) {
                return round($num);
            }
        );
        $this->expression->register(
            'substr',
            static function () {
            },
            /**
             * @param array $arguments
             * @param string $string
             * @param int $start
             * @param int $length
             */
            static function ($arguments, $string, $start, $length) {
                return substr($string, $start, $length);
            }
        );
        $this->expression->register(
            'preg_match',
            static function () {
            },
            /**
             * @param array $arguments
             * @param string $pattern
             * @param string $subject
             */
            static function ($arguments, $pattern, $subject) {
                return preg_match($pattern, $subject);
            }
        );
        $this->expression->register(
            'ADVISOR_bytime',
            static function () {
            },
            /**
             * @param array $arguments
             * @param float $num
             * @param int $precision
             */
            static function ($arguments, $num, $precision) {
                return self::byTime($num, $precision);
            }
        );
        $this->expression->register(
            'ADVISOR_timespanFormat',
            static function () {
            },
            /**
             * @param array $arguments
             * @param string $seconds
             */
            static function ($arguments, $seconds) {
                return self::timespanFormat((int) $seconds);
            }
        );
        $this->expression->register(
            'ADVISOR_formatByteDown',
            static function () {
            },
            /**
             * @param array $arguments
             * @param int $value
             * @param int $limes
             * @param int $comma
             */
            static function ($arguments, $value, $limes = 6, $comma = 0) {
                return self::formatByteDown($value, $limes, $comma);
            }
        );
        $this->expression->register(
            'fired',
            static function () {
            },
            /**
             * @param array $arguments
             * @param int $value
             */
            function ($arguments, $value) {
                if (! isset($this->runResult['fired'])) {
                    return 0;
                }

                // Did matching rule fire?
                foreach ($this->runResult['fired'] as $rule) {
                    if ($rule['id'] == $value) {
                        return '1';
                    }
                }

                return '0';
            }
        );
        /* Some global variables for advisor */
        $this->globals = [
            'PMA_MYSQL_INT_VERSION' => $this->dbi->getVersion(),
        ];
    }

    /**
     * Get variables
     *
     * @return array
     */
    public function getVariables(): array
    {
        return $this->variables;
    }

    /**
     * Set variables
     *
     * @param array $variables Variables
     *
     * @return Advisor
     */
    public function setVariables(array $variables): self
    {
        $this->variables = $variables;

        return $this;
    }

    /**
     * Set a variable and its value
     *
     * @param string|int $variable Variable to set
     * @param mixed      $value    Value to set
     *
     * @return Advisor
     */
    public function setVariable($variable, $value): self
    {
        $this->variables[$variable] = $value;

        return $this;
    }

    /**
     * Get parseResult
     *
     * @return array
     */
    public function getParseResult(): array
    {
        return $this->parseResult;
    }

    /**
     * Set parseResult
     *
     * @param array $parseResult Parse result
     *
     * @return Advisor
     */
    public function setParseResult(array $parseResult): self
    {
        $this->parseResult = $parseResult;

        return $this;
    }

    /**
     * Get runResult
     *
     * @return array
     */
    public function getRunResult(): array
    {
        return $this->runResult;
    }

    /**
     * Set runResult
     *
     * @param array $runResult Run result
     *
     * @return Advisor
     */
    public function setRunResult(array $runResult): self
    {
        $this->runResult = $runResult;

        return $this;
    }

    /**
     * Parses and executes advisor rules
     *
     * @return array with run and parse results
     */
    public function run(): array
    {
        // HowTo: A simple Advisory system in 3 easy steps.

        // Step 1: Get some variables to evaluate on
        $this->setVariables(
            array_merge(
                $this->dbi->fetchResult('SHOW GLOBAL STATUS', 0, 1),
                $this->dbi->fetchResult('SHOW GLOBAL VARIABLES', 0, 1)
            )
        );

        // Add total memory to variables as well
        $sysinfo = SysInfo::get();
        $memory  = $sysinfo->memory();
        $this->variables['system_memory'] = $memory['MemTotal'] ?? 0;

        $ruleFiles = $this->defineRulesFiles();

        // Step 2: Read and parse the list of rules
        $parsedResults = [];
        foreach ($ruleFiles as $ruleFile) {
            $parsedResults[] = static::parseRulesFile($ruleFile);
        }
        $this->setParseResult(array_merge_recursive(...$parsedResults));

        // Step 3: Feed the variables to the rules and let them fire. Sets
        // $runResult
        $this->runRules();

        return [
            'parse' => ['errors' => $this->parseResult['errors']],
            'run'   => $this->runResult,
        ];
    }

    /**
     * Stores current error in run results.
     *
     * @param string    $description description of an error.
     * @param Throwable $exception   exception raised
     */
    public function storeError(string $description, Throwable $exception): void
    {
        $this->runResult['errors'][] = $description
            . ' '
            . sprintf(
                __('Error when evaluating: %s'),
                $exception->getMessage()
            );
    }

    /**
     * Executes advisor rules
     */
    public function runRules(): bool
    {
        $this->setRunResult(
            [
                'fired'     => [],
                'notfired'  => [],
                'unchecked' => [],
                'errors'    => [],
            ]
        );

        foreach ($this->parseResult['rules'] as $rule) {
            $this->variables['value'] = 0;
            $precond = true;

            if (isset($rule['precondition'])) {
                try {
                     $precond = $this->ruleExprEvaluate($rule['precondition']);
                } catch (Throwable $e) {
                    $this->storeError(
                        sprintf(
                            __('Failed evaluating precondition for rule \'%s\'.'),
                            $rule['name']
                        ),
                        $e
                    );
                    continue;
                }
            }

            if (! $precond) {
                $this->addRule('unchecked', $rule);
            } else {
                try {
                    $value = $this->ruleExprEvaluate($rule['formula']);
                } catch (Throwable $e) {
                    $this->storeError(
                        sprintf(
                            __('Failed calculating value for rule \'%s\'.'),
                            $rule['name']
                        ),
                        $e
                    );
                    continue;
                }

                $this->variables['value'] = $value;

                try {
                    if ($this->ruleExprEvaluate($rule['test'])) {
                        $this->addRule('fired', $rule);
                    } else {
                        $this->addRule('notfired', $rule);
                    }
                } catch (Throwable $e) {
                    $this->storeError(
                        sprintf(
                            __('Failed running test for rule \'%s\'.'),
                            $rule['name']
                        ),
                        $e
                    );
                }
            }
        }

        return true;
    }

    /**
     * Escapes percent string to be used in format string.
     *
     * @param string $str string to escape
     */
    public static function escapePercent(string $str): string
    {
        return preg_replace('/%( |,|\.|$|\(|\)|<|>)/', '%%\1', $str);
    }

    /**
     * Wrapper function for translating.
     *
     * @param string $str   the string
     * @param string $param the parameters
     *
     * @throws Exception
     */
    public function translate(string $str, ?string $param = null): string
    {
        $string = _gettext(self::escapePercent($str));
        if ($param !== null) {
            $params = $this->ruleExprEvaluate('[' . $param . ']');
        } else {
            $params = [];
        }

        return vsprintf($string, $params);
    }

    /**
     * Splits justification to text and formula.
     *
     * @param array $rule the rule
     *
     * @return string[]
     */
    public static function splitJustification(array $rule): array
    {
        $jst = preg_split('/\s*\|\s*/', $rule['justification'], 2);
        if ($jst !== false && count($jst) > 1) {
            return [
                $jst[0],
                $jst[1],
            ];
        }

        return [$rule['justification']];
    }

    /**
     * Adds a rule to the result list
     *
     * @param string $type type of rule
     * @param array  $rule rule itself
     *
     * @throws Exception
     */
    public function addRule(string $type, array $rule): void
    {
        switch ($type) {
            case 'notfired':
            case 'fired':
                $jst = self::splitJustification($rule);
                if (count($jst) > 1) {
                    try {
                        /* Translate */
                        $str = $this->translate($jst[0], $jst[1]);
                    } catch (Throwable $e) {
                        $this->storeError(
                            sprintf(
                                __('Failed formatting string for rule \'%s\'.'),
                                $rule['name']
                            ),
                            $e
                        );

                        return;
                    }

                    $rule['justification'] = $str;
                } else {
                    $rule['justification'] = $this->translate($rule['justification']);
                }
                $rule['id'] = $rule['name'];
                $rule['name'] = $this->translate($rule['name']);
                $rule['issue'] = $this->translate($rule['issue']);

                // Replaces {server_variable} with 'server_variable'
                // linking to /server/variables
                $rule['recommendation'] = preg_replace_callback(
                    '/\{([a-z_0-9]+)\}/Ui',
                    [
                        $this,
                        'replaceVariable',
                    ],
                    $this->translate($rule['recommendation'])
                );

                // Replaces external Links with Core::linkURL() generated links
                $rule['recommendation'] = preg_replace_callback(
                    '#href=("|\')(https?://[^\1]+)\1#i',
                    [
                        $this,
                        'replaceLinkURL',
                    ],
                    $rule['recommendation']
                );
                break;
        }

        $this->runResult[$type][] = $rule;
    }

    /**
     * Defines the rules files to use
     *
     * @return array
     */
    protected function defineRulesFiles(): array
    {
        $isMariaDB = strpos($this->getVariables()['version'], 'MariaDB') !== false;
        $ruleFiles = [self::GENERIC_RULES_FILE];
        // If MariaDB (= not MySQL) OR MYSQL < 8.0.3, add another rules file.
        if ($isMariaDB || $this->globals['PMA_MYSQL_INT_VERSION'] < 80003) {
            $ruleFiles[] = self::BEFORE_MYSQL80003_RULES_FILE;
        }

        return $ruleFiles;
    }

    /**
     * Callback for wrapping links with Core::linkURL
     *
     * @param array $matches List of matched elements form preg_replace_callback
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
     * @param array $matches List of matched elements form preg_replace_callback
     *
     * @return string Replacement value
     */
    private function replaceVariable(array $matches): string
    {
        return '<a href="' . Url::getFromRoute('/server/variables', ['filter' => $matches[1]])
                . '">' . htmlspecialchars($matches[1]) . '</a>';
    }

    /**
     * Runs a code expression, replacing variable names with their respective
     * values
     *
     * @param string $expr expression to evaluate
     *
     * @return mixed result of evaluated expression
     *
     * @throws Exception
     */
    public function ruleExprEvaluate(string $expr)
    {
        // Actually evaluate the code
        // This can throw exception
        return $this->expression->evaluate(
            $expr,
            array_merge($this->variables, $this->globals)
        );
    }

    /**
     * Reads the rule file into an array, throwing errors messages on syntax
     * errors.
     *
     * @param string $filename Name of file to parse
     *
     * @return array with parsed data
     */
    public static function parseRulesFile(string $filename): array
    {
        $file = file($filename, FILE_IGNORE_NEW_LINES);

        $errors = [];
        $rules = [];
        $lines = [];

        if ($file === false) {
            $errors[] = sprintf(
                __('Error in reading file: The file \'%s\' does not exist or is not readable!'),
                $filename
            );

            return [
                'rules' => $rules,
                'lines' => $lines,
                'errors' => $errors,
            ];
        }

        $ruleSyntax = [
            'name',
            'formula',
            'test',
            'issue',
            'recommendation',
            'justification',
        ];
        $numRules = count($ruleSyntax);
        $numLines = count($file);
        $ruleNo = -1;
        $ruleLine = -1;

        for ($i = 0; $i < $numLines; $i++) {
            $line = $file[$i];
            if ($line == '' || $line[0] == '#') {
                continue;
            }

            // Reading new rule
            if (substr($line, 0, 4) == 'rule') {
                if ($ruleLine > 0) {
                    $errors[] = sprintf(
                        __(
                            'Invalid rule declaration on line %1$s, expected line '
                            . '%2$s of previous rule.'
                        ),
                        $i + 1,
                        $ruleSyntax[$ruleLine++]
                    );
                    continue;
                }
                if (preg_match("/rule\s'(.*)'( \[(.*)\])?$/", $line, $match)) {
                    $ruleLine = 1;
                    $ruleNo++;
                    $rules[$ruleNo] = ['name' => $match[1]];
                    $lines[$ruleNo] = ['name' => $i + 1];
                    if (isset($match[3])) {
                        $rules[$ruleNo]['precondition'] = $match[3];
                        $lines[$ruleNo]['precondition'] = $i + 1;
                    }
                } else {
                    $errors[] = sprintf(
                        __('Invalid rule declaration on line %s.'),
                        $i + 1
                    );
                }
                continue;
            } elseif ($ruleLine == -1) {
                $errors[] = sprintf(
                    __('Unexpected characters on line %s.'),
                    $i + 1
                );
            }

            // Reading rule lines
            if ($ruleLine > 0) {
                if (! isset($line[0])) {
                    continue; // Empty lines are ok
                }
                // Non tabbed lines are not
                if ($line[0] != "\t") {
                    $errors[] = sprintf(
                        __(
                            'Unexpected character on line %1$s. Expected tab, but '
                            . 'found "%2$s".'
                        ),
                        $i + 1,
                        $line[0]
                    );
                    continue;
                }
                $rules[$ruleNo][$ruleSyntax[$ruleLine]] = rtrim(
                    mb_substr($line, 1)
                );
                $lines[$ruleNo][$ruleSyntax[$ruleLine]] = $i + 1;
                ++$ruleLine;
            }

            // Rule complete
            if ($ruleLine != $numRules) {
                continue;
            }

            $ruleLine = -1;
        }

        return [
            'rules' => $rules,
            'lines' => $lines,
            'errors' => $errors,
        ];
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
            $num = '<' . pow(10, -$precision);
        }

        return $num . ' ' . $per;
    }

    /**
     * Wrapper for PhpMyAdmin\Util::timespanFormat
     *
     * This function is used when evaluating advisory_rules.txt
     *
     * @param int $seconds the timespan
     *
     * @return string  the formatted value
     */
    public static function timespanFormat(int $seconds): string
    {
        return Util::timespanFormat($seconds);
    }

    /**
     * Wrapper around PhpMyAdmin\Util::formatByteDown
     *
     * This function is used when evaluating advisory_rules.txt
     *
     * @param double|int $value the value to format
     * @param int        $limes the sensitiveness
     * @param int        $comma the number of decimals to retain
     *
     * @return string the formatted value with unit
     */
    public static function formatByteDown($value, int $limes = 6, int $comma = 0): string
    {
        return implode(' ', Util::formatByteDown($value, $limes, $comma));
    }
}
