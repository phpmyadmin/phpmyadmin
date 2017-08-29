<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * A simple rules engine, that parses and executes the rules in advisory_rules.txt.
 * Adjusted to phpMyAdmin.
 *
 * @package PhpMyAdmin
 */
namespace PMA\libraries;

use \Exception;
use PMA\libraries\URL;

require_once 'libraries/advisor.lib.php';

/**
 * Advisor class
 *
 * @package PhpMyAdmin
 */
class Advisor
{
    protected $variables;
    protected $parseResult;
    protected $runResult;

    /**
     * Get variables
     *
     * @return mixed
     */
    public function getVariables()
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
    public function setVariables($variables)
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
     * @return $this
     */
    public function setVariable($variable, $value)
    {
        $this->variables[$variable] = $value;

        return $this;
    }

    /**
     * Get parseResult
     *
     * @return mixed
     */
    public function getParseResult()
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
    public function setParseResult($parseResult)
    {
        $this->parseResult = $parseResult;

        return $this;
    }

    /**
     * Get runResult
     *
     * @return mixed
     */
    public function getRunResult()
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
    public function setRunResult($runResult)
    {
        $this->runResult = $runResult;

        return $this;
    }

    /**
     * Parses and executes advisor rules
     *
     * @return array with run and parse results
     */
    public function run()
    {
        // HowTo: A simple Advisory system in 3 easy steps.

        // Step 1: Get some variables to evaluate on
        $this->setVariables(
            array_merge(
                $GLOBALS['dbi']->fetchResult('SHOW GLOBAL STATUS', 0, 1),
                $GLOBALS['dbi']->fetchResult('SHOW GLOBAL VARIABLES', 0, 1)
            )
        );

        // Add total memory to variables as well
        include_once 'libraries/sysinfo.lib.php';
        $sysinfo = PMA_getSysInfo();
        $memory  = $sysinfo->memory();
        $this->variables['system_memory']
            = isset($memory['MemTotal']) ? $memory['MemTotal'] : 0;

        // Step 2: Read and parse the list of rules
        $this->setParseResult(static::parseRulesFile());
        // Step 3: Feed the variables to the rules and let them fire. Sets
        // $runResult
        $this->runRules();

        return array(
            'parse' => array('errors' => $this->parseResult['errors']),
            'run'   => $this->runResult
        );
    }

    /**
     * Stores current error in run results.
     *
     * @param string    $description description of an error.
     * @param Exception $exception   exception raised
     *
     * @return void
     */
    public function storeError($description, $exception)
    {
        $this->runResult['errors'][] = $description
            . ' '
            . sprintf(
                __('PHP threw following error: %s'),
                $exception->getMessage()
            );
    }

    /**
     * Executes advisor rules
     *
     * @return boolean
     */
    public function runRules()
    {
        $this->setRunResult(
            array(
                'fired'     => array(),
                'notfired'  => array(),
                'unchecked' => array(),
                'errors'    => array(),
            )
        );

        foreach ($this->parseResult['rules'] as $rule) {
            $this->variables['value'] = 0;
            $precond = true;

            if (isset($rule['precondition'])) {
                try {
                     $precond = $this->ruleExprEvaluate($rule['precondition']);
                } catch (Exception $e) {
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
                } catch (Exception $e) {
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
                } catch (Exception $e) {
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
     *
     * @return string
     */
    public static function escapePercent($str)
    {
        return preg_replace('/%( |,|\.|$|\(|\)|<|>)/', '%%\1', $str);
    }

    /**
     * Wrapper function for translating.
     *
     * @param string $str   the string
     * @param string $param the parameters
     *
     * @return string
     */
    public function translate($str, $param = null)
    {
        $string = _gettext(self::escapePercent($str));
        if (! is_null($param)) {
            $params = $this->ruleExprEvaluate('array(' . $param . ')');
        } else {
            $params = array();
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
    public static function splitJustification($rule)
    {
        $jst = preg_split('/\s*\|\s*/', $rule['justification'], 2);
        if (count($jst) > 1) {
            return array($jst[0], $jst[1]);
        }
        return array($rule['justification']);
    }

    /**
     * Adds a rule to the result list
     *
     * @param string $type type of rule
     * @param array  $rule rule itself
     *
     * @return void
     */
    public function addRule($type, $rule)
    {
        switch ($type) {
        case 'notfired':
        case 'fired':
            $jst = self::splitJustification($rule);
            if (count($jst) > 1) {
                try {
                    /* Translate */
                    $str = $this->translate($jst[0], $jst[1]);
                } catch (Exception $e) {
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
            // linking to server_variables.php
            $rule['recommendation'] = preg_replace_callback(
                '/\{([a-z_0-9]+)\}/Ui',
                array($this, 'replaceVariable'),
                $this->translate($rule['recommendation'])
            );

            // Replaces external Links with PMA_linkURL() generated links
            $rule['recommendation'] = preg_replace_callback(
                '#href=("|\')(https?://[^\1]+)\1#i',
                array($this, 'replaceLinkURL'),
                $rule['recommendation']
            );
            break;
        }

        $this->runResult[$type][] = $rule;
    }

    /**
     * Callback for wrapping links with PMA_linkURL
     *
     * @param array $matches List of matched elements form preg_replace_callback
     *
     * @return string Replacement value
     */
    private function replaceLinkURL($matches)
    {
        return 'href="' . PMA_linkURL($matches[2]) . '" target="_blank" rel="noopener noreferrer"';
    }

    /**
     * Callback for wrapping variable edit links
     *
     * @param array $matches List of matched elements form preg_replace_callback
     *
     * @return string Replacement value
     */
    private function replaceVariable($matches)
    {
        return '<a href="server_variables.php' . URL::getCommon(array('filter' => $matches[1]))
                . '">' . htmlspecialchars($matches[1]). '</a>';
    }

    /**
     * Callback for evaluating fired() condition.
     *
     * @param array $matches List of matched elements form preg_replace_callback
     *
     * @return string Replacement value
     */
    private function ruleExprEvaluateFired($matches)
    {
        // No list of fired rules
        if (!isset($this->runResult['fired'])) {
            return '0';
        }

        // Did matching rule fire?
        foreach ($this->runResult['fired'] as $rule) {
            if ($rule['id'] == $matches[2]) {
                return '1';
            }
        }

        return '0';
    }

    /**
     * Callback for evaluating variables in expression.
     *
     * @param array $matches List of matched elements form preg_replace_callback
     *
     * @return string Replacement value
     */
    private function ruleExprEvaluateVariable($matches)
    {
        if (! isset($this->variables[$matches[1]])) {
            return $matches[1];
        }
        if (is_numeric($this->variables[$matches[1]])) {
            return $this->variables[$matches[1]];
        } else {
            return '\'' . addslashes($this->variables[$matches[1]]) . '\'';
        }
    }

    /**
     * Runs a code expression, replacing variable names with their respective
     * values
     *
     * @param string $expr expression to evaluate
     *
     * @return integer result of evaluated expression
     *
     * @throws Exception
     */
    public function ruleExprEvaluate($expr)
    {
        // Evaluate fired() conditions
        $expr = preg_replace_callback(
            '/fired\s*\(\s*(\'|")(.*)\1\s*\)/Ui',
            array($this, 'ruleExprEvaluateFired'),
            $expr
        );
        // Evaluate variables
        $expr = preg_replace_callback(
            '/\b(\w+)\b/',
            array($this, 'ruleExprEvaluateVariable'),
            $expr
        );
        $value = 0;
        $err = 0;

        // Actually evaluate the code
        ob_start();
        try {
            // TODO: replace by using symfony/expression-language
            eval('$value = ' . $expr . ';');
            $err = ob_get_contents();
        } catch (Exception $e) {
            // In normal operation, there is just output in the buffer,
            // but when running under phpunit, error in eval raises exception
            $err = $e->getMessage();
        }
        ob_end_clean();

        // Error handling
        if ($err) {
            // Remove PHP 7.2 and newer notice (it's not really interesting for user)
            throw new Exception(
                str_replace(
                    ' (this will throw an Error in a future version of PHP)',
                    '',
                    strip_tags($err)
                )
                . '<br />Executed code: $value = ' . htmlspecialchars($expr) . ';'
            );
        }
        return $value;
    }

    /**
     * Reads the rule file into an array, throwing errors messages on syntax
     * errors.
     *
     * @return array with parsed data
     */
    public static function parseRulesFile()
    {
        $filename = 'libraries/advisory_rules.txt';
        $file = file($filename, FILE_IGNORE_NEW_LINES);

        $errors = array();
        $rules = array();
        $lines = array();

        if ($file === FALSE) {
            $errors[] = sprintf(
                __('Error in reading file: The file \'%s\' does not exist or is not readable!'),
                $filename
            );
            return array('rules' => $rules, 'lines' => $lines, 'errors' => $errors);
        }

        $ruleSyntax = array(
            'name', 'formula', 'test', 'issue', 'recommendation', 'justification'
        );
        $numRules = count($ruleSyntax);
        $numLines = count($file);
        $ruleNo = -1;
        $ruleLine = -1;

        for ($i = 0; $i < $numLines; $i++) {
            $line = $file[$i];
            if ($line == "" || $line[0] == '#') {
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
                    $rules[$ruleNo] = array('name' => $match[1]);
                    $lines[$ruleNo] = array('name' => $i + 1);
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
            } else {
                if ($ruleLine == -1) {
                    $errors[] = sprintf(
                        __('Unexpected characters on line %s.'),
                        $i + 1
                    );
                }
            }

            // Reading rule lines
            if ($ruleLine > 0) {
                if (!isset($line[0])) {
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
                $rules[$ruleNo][$ruleSyntax[$ruleLine]] = chop(
                    mb_substr($line, 1)
                );
                $lines[$ruleNo][$ruleSyntax[$ruleLine]] = $i + 1;
                ++$ruleLine;
            }

            // Rule complete
            if ($ruleLine == $numRules) {
                $ruleLine = -1;
            }
        }

        return array('rules' => $rules, 'lines' => $lines, 'errors' => $errors);
    }
}
