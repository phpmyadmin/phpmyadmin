<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * A simple rules engine, that parses and executes the rules in advisory_rules.txt. Adjusted to phpMyAdmin
 *
 *
 * @package PhpMyAdmin
 */

class Advisor
{
    var $variables;
    var $parseResult;
    var $runResult;

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
        // Step 3: Feed the variables to the rules and let them fire. Sets $runResult
        $this->runRules();

        return array(
            'parse' => array('errors' => $this->parseResult['errors']),
            'run'   => $this->runResult
        );
    }

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
                    $this->runResult['errors'][] = 'Failed evaluating precondition for rule \''
                        . $rule['name'] . '\'. PHP threw following error: '
                        . $e->getMessage();
                    continue;
                }
            }

            if (! $precond) {
                $this->addRule('unchecked', $rule);
            } else {
                try {
                    $value = $this->ruleExprEvaluate($rule['formula']);
                } catch(Exception $e) {
                    $this->runResult['errors'][] = 'Failed calculating value for rule \''
                        . $rule['name'] . '\'. PHP threw following error: '
                        . $e->getMessage();
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
                    $this->runResult['errors'][] = 'Failed running test for rule \''
                        . $rule['name'] . '\'. PHP threw following error: '
                        . $e->getMessage();
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
     * @param string $str
     * @param mixed  $param
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
     * @param string $rule
     *
     * @return array
     */
    function splitJustification($rule)
    {
        $jst = preg_split('/\s*\|\s*/', $rule['justification'], 2);
        if (count($jst) > 1) {
            return array($jst[0], $jst[1]);
        }
        return array($rule['justification']);
    }

    // Adds a rule to the result list
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
                    $this->runResult['errors'][] = sprintf(
                        __('Failed formatting string for rule \'%s\'. PHP threw following error: %s'),
                        $rule['name'],
                        $e->getMessage()
                    );
                    return;
                }

                $rule['justification'] = $str;
            } else {
                $rule['justification'] = $this->translate($rule['justification']);
            }
            $rule['name'] = $this->translate($rule['name']);
            $rule['issue'] = $this->translate($rule['issue']);

            // Replaces {server_variable} with 'server_variable'
            // linking to server_variables.php
            $rule['recommendation'] = preg_replace(
                '/\{([a-z_0-9]+)\}/Ui',
                '<a href="server_variables.php?' . PMA_generate_common_url() . '#filter=\1">\1</a>',
                $this->translate($rule['recommendation'])
            );

            // Replaces external Links with PMA_linkURL() generated links
            $rule['recommendation'] = preg_replace(
                '#href=("|\')(https?://[^\1]+)\1#ie',
                '\'href="\' . PMA_linkURL("\2") . \'"\'',
                $rule['recommendation']
            );
            break;
        }

        $this->runResult[$type][] = $rule;
    }

    private function ruleExprEvaluate_var1($matches)
    {
        // '/fired\s*\(\s*(\'|")(.*)\1\s*\)/Uie'
        return '1'; //isset($this->runResult[\'fired\']
    }

    private function ruleExprEvaluate_var2($matches)
    {
        // '/\b(\w+)\b/e'
        return isset($this->variables[$matches[1]])
            ? (is_numeric($this->variables[$matches[1]])
                ? $this->variables[$matches[1]]
                : '"'.$this->variables[$matches[1]].'"')
            : $matches[1];
    }

    // Runs a code expression, replacing variable names with their respective values
    // ignoreUntil: if > 0, it doesn't replace any variables until that string
    // position, but still evaluates the whole expr
    function ruleExprEvaluate($expr, $ignoreUntil = 0)
    {
        if ($ignoreUntil > 0) {
            $exprIgnore = substr($expr, 0, $ignoreUntil);
            $expr = substr($expr, $ignoreUntil);
        }
        $expr = preg_replace_callback(
            '/fired\s*\(\s*(\'|")(.*)\1\s*\)/Ui',
            array($this, 'ruleExprEvaluate_var1'),
            $expr
        );
        $expr = preg_replace_callback(
            '/\b(\w+)\b/',
            array($this, 'ruleExprEvaluate_var2'),
            $expr
        );
        if ($ignoreUntil > 0) {
            $expr = $exprIgnore . $expr;
        }
        $value = 0;
        $err = 0;

        ob_start();
        eval('$value = '.$expr.';');
        $err = ob_get_contents();
        ob_end_clean();
        if ($err) {
            throw new Exception(
                strip_tags($err) . '<br />Executed code: $value = ' . $expr . ';'
            );
        }
        return $value;
    }

    // Reads the rule file into an array, throwing errors messages on syntax errors
    function parseRulesFile()
    {
        $file = file('libraries/advisory_rules.txt');
        $errors = array();
        $rules = array();
        $ruleSyntax = array('name', 'formula', 'test', 'issue', 'recommendation', 'justification');
        $numRules = count($ruleSyntax);
        $numLines = count($file);
        $j = -1;
        $ruleLine = -1;

        for ($i = 0; $i<$numLines; $i++) {
            $line = $file[$i];
            if ($line[0] == '#' || $line[0] == "\n") {
                continue;
            }

            // Reading new rule
            if (substr($line, 0, 4) == 'rule') {
                if ($ruleLine > 0) {
                    $errors[] = 'Invalid rule declaration on line ' . ($i+1)
                        . ', expected line ' . $ruleSyntax[$ruleLine++]
                        . ' of previous rule' ;
                    continue;
                }
                if (preg_match("/rule\s'(.*)'( \[(.*)\])?$/", $line, $match)) {
                    $ruleLine = 1;
                    $j++;
                    $rules[$j] = array( 'name' => $match[1]);
                    if (isset($match[3])) {
                        $rules[$j]['precondition'] = $match[3];
                    }
                } else {
                    $errors[] = 'Invalid rule declaration on line '.($i+1);
                }
                continue;
            } else {
                if ($ruleLine == -1) {
                    $errors[] = 'Unexpected characters on line '.($i+1);
                }
            }

            // Reading rule lines
            if ($ruleLine > 0) {
                if (!isset($line[0])) {
                    continue; // Empty lines are ok
                }
                // Non tabbed lines are not
                if ($line[0] != "\t") {
                    $errors[] = 'Unexpected character on line '.($i+1).'
                        . Expected tab, but found \''.$line[0].'\'';
                    continue;
                }
                $rules[$j][$ruleSyntax[$ruleLine++]] = chop(substr($line, 1));
            }

            // Rule complete
            if ($ruleLine == $numRules) {
                $ruleLine = -1;
            }
        }

        return array('rules' => $rules, 'errors' => $errors);
    }
}

function PMA_bytime($num, $precision)
{
    $per = '';
    if ($num >= 1) { // per second
        $per = __('per second');
    } elseif ($num*60 >= 1) { // per minute
        $num = $num*60;
        $per = __('per minute');
    } elseif ($num*60*60 >=1 ) { // per hour
        $num = $num*60*60;
        $per = __('per hour');
    } else {
        $num = $num*60*60*24;
        $per = __('per day');
    }

    $num = round($num, $precision);

    if ($num == 0) {
        $num = '<' . pow(10, -$precision);
    }

    return "$num $per";
}

?>
