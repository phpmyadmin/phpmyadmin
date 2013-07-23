<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * A simple rules engine, that parses and executes the rules in advisory_rules.txt.
 * Adjusted to phpMyAdmin.
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Advisor class
 *
 * @package PhpMyAdmin
 */
class Advisor
{
    var $variables;
    var $parseResult;
    var $runResult;

    /**
     * Parses and executes advisor rules
     *
     * @return array with run and parse results
     */
    function run()
    {
        // HowTo: A simple Advisory system in 3 easy steps.

        // Step 1: Get some variables to evaluate on
        $this->variables = array_merge(
            PMA_DBI_fetch_result('SHOW GLOBAL STATUS', 0, 1),
            PMA_DBI_fetch_result('SHOW GLOBAL VARIABLES', 0, 1)
        );
        if (PMA_DRIZZLE) {
            $this->variables = array_merge(
                $this->variables,
                PMA_DBI_fetch_result(
                    "SELECT concat('Com_', variable_name), variable_value
                    FROM data_dictionary.GLOBAL_STATEMENTS", 0, 1
                )
            );
        }
        // Add total memory to variables as well
        include_once 'libraries/sysinfo.lib.php';
        $sysinfo = PMA_getSysInfo();
        $memory  = $sysinfo->memory();
        $this->variables['system_memory'] = $memory['MemTotal'];

        // Step 2: Read and parse the list of rules
        $this->parseResult = $this->parseRulesFile();
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
     * @param string $description description of an error.
     * @param object $exception   exception raised
     *
     * @return void
     */
    function storeError($description, $exception)
    {
        $this->runResult['errors'][] = $description
            . ' '
            . sprintf(__('PHP threw following error: %s'), $exception->getMessage());
    }

    /**
     * Executes advisor rules
     *
     * @return void
     */
    function runRules()
    {
        $this->runResult = array(
            'fired' => array(),
            'notfired' => array(),
            'unchecked'=> array(),
            'errors' => array()
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
                            __('Failed evaluating precondition for rule \'%s\''),
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
                } catch(Exception $e) {
                    $this->storeError(
                        sprintf(
                            __('Failed calculating value for rule \'%s\''),
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
                }  catch(Exception $e) {
                    $this->storeError(
                        sprintf(
                            __('Failed running test for rule \'%s\''),
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
    static function escapePercent($str)
    {
        return preg_replace('/%( |,|\.|$|\(|\)|<|>)/', '%%\1', $str);
    }

    /**
     * Wrapper function for translating.
     *
     * @param string $str   the string
     * @param mixed  $param the parameters
     *
     * @return string
     */
    function translate($str, $param = null)
    {
        if (is_null($param)) {
            return sprintf(_gettext(Advisor::escapePercent($str)));
        } else {
            $printf = 'sprintf("' . _gettext(Advisor::escapePercent($str)) . '",';
            return $this->ruleExprEvaluate(
                $printf . $param . ')',
                strlen($printf)
            );
        }
    }

    /**
     * Splits justification to text and formula.
     *
     * @param string $rule the rule
     *
     * @return array
     */
    static function splitJustification($rule)
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
     * @param array  $rule rule itslef
     *
     * @return void
     */
    function addRule($type, $rule)
    {
        switch($type) {
        case 'notfired':
        case 'fired':
            $jst = Advisor::splitJustification($rule);
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
            $rule['recommendation'] = preg_replace(
                '/\{([a-z_0-9]+)\}/Ui',
                '<a href="server_variables.php?' . PMA_generate_common_url() . '&filter=\1">\1</a>',
                $this->translate($rule['recommendation'])
            );

            // Replaces external Links with PMA_linkURL() generated links
            $rule['recommendation'] = preg_replace_callback(
                '#href=("|\')(https?://[^\1]+)\1#i',
                array($this, '_replaceLinkURL'),
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
     * @return Replacement value
     */
    private function _replaceLinkURL($matches)
    {
        return 'href="' . PMA_linkURL($matches[2]) . '"';
    }

    /**
     * Callback for evaluating fired() condition.
     *
     * @param array $matches List of matched elements form preg_replace_callback
     *
     * @return Replacement value
     */
    private function _ruleExprEvaluateFired($matches)
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
     * @return Replacement value
     */
    private function _ruleExprEvaluateVariable($matches)
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
     * @param string $expr        expression to evaluate
     * @param int    $ignoreUntil if > 0, it doesn't replace any variables until
     *                            that string position, but still evaluates the
     *                            whole expr
     *
     * @return result of evaluated expression
     */
    function ruleExprEvaluate($expr, $ignoreUntil = 0)
    {
        if ($ignoreUntil > 0) {
            $exprIgnore = substr($expr, 0, $ignoreUntil);
            $expr = substr($expr, $ignoreUntil);
        }
        // Evaluate fired() conditions
        $expr = preg_replace_callback(
            '/fired\s*\(\s*(\'|")(.*)\1\s*\)/Ui',
            array($this, '_ruleExprEvaluateFired'),
            $expr
        );
        // Evaluate variables
        $expr = preg_replace_callback(
            '/\b(\w+)\b/',
            array($this, '_ruleExprEvaluateVariable'),
            $expr
        );
        if ($ignoreUntil > 0) {
            $expr = $exprIgnore . $expr;
        }
        $value = 0;
        $err = 0;

        // Actually evaluate the code
        ob_start();
        eval('$value = ' . $expr . ';');
        $err = ob_get_contents();
        ob_end_clean();

        // Error handling
        if ($err) {
            throw new Exception(
                strip_tags($err) . '<br />Executed code: $value = ' . htmlspecialchars($expr) . ';'
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
    static function parseRulesFile()
    {
        $file = file('libraries/advisory_rules.txt', FILE_IGNORE_NEW_LINES);
        $errors = array();
        $rules = array();
        $lines = array();
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
                        __('Invalid rule declaration on line %1$s, expected line %2$s of previous rule'),
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
                        __('Invalid rule declaration on line %s'),
                        $i + 1
                    );
                }
                continue;
            } else {
                if ($ruleLine == -1) {
                    $errors[] = sprintf(
                        __('Unexpected characters on line %s'),
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
                        __('Unexpected character on line %1$s. Expected tab, but found "%2$s"'),
                        $i + 1,
                        $line[0]
                    );
                    continue;
                }
                $rules[$ruleNo][$ruleSyntax[$ruleLine]] = chop(substr($line, 1));
                $lines[$ruleNo][$ruleSyntax[$ruleLine]] = $i + 1;
                $ruleLine += 1;
            }

            // Rule complete
            if ($ruleLine == $numRules) {
                $ruleLine = -1;
            }
        }

        return array('rules' => $rules, 'lines' => $lines, 'errors' => $errors);
    }
}

/**
 * Formats interval like 10 per hour
 *
 * @param integer $num       number to format
 * @param intefer $precision required precision
 *
 * @return formatted string
 */
function ADVISOR_bytime($num, $precision)
{
    $per = '';
    if ($num >= 1) { // per second
        $per = __('per second');
    } elseif ($num * 60 >= 1) { // per minute
        $num = $num * 60;
        $per = __('per minute');
    } elseif ($num * 60 * 60 >= 1 ) { // per hour
        $num = $num * 60 * 60;
        $per = __('per hour');
    } else {
        $num = $num * 60 * 60 * 24;
        $per = __('per day');
    }

    $num = round($num, $precision);

    if ($num == 0) {
        $num = '<' . PMA_Util::pow(10, -$precision);
    }

    return "$num $per";
}

/**
 * Wrapper for PMA_Util::timespanFormat
 *
 * @param int $seconds the timespan
 *
 * @return string  the formatted value
 */
function ADVISOR_timespanFormat($seconds)
{
    return PMA_Util::timespanFormat($seconds);
}

/**
 * Wrapper around PMA_Util::formatByteDown
 *
 * @param double $value the value to format
 * @param int    $limes the sensitiveness
 * @param int    $comma the number of decimals to retain
 *
 * @return array    the formatted value and its unit
 */
function ADVISOR_formatByteDown($value, $limes = 6, $comma = 0)
{
    return PMA_Util::formatByteDown($value, $limes, $comma);
}

?>
