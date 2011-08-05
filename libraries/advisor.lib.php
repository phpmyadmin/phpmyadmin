<?php

class Advisor {
    var $variables;
    var $parseResult;
    var $runResult;
    
    function run() {        
        // HowTo: A simple Advisory system in 3 easy steps.
        
        // Step 1: Get some variables to evaluate on
        $this->variables = array_merge(PMA_DBI_fetch_result('SHOW GLOBAL STATUS', 0, 1), PMA_DBI_fetch_result('SHOW GLOBAL VARIABLES', 0, 1));
        // Step 2: Read and parse the list of rules
        $this->parseResult = $this->parseRulesFile();
        // Step 3: Feed the variables to the rules and let them fire. Sets $runResult
        $this->runRules();
        
      /*  echo '<br/><hr>';
        echo 'Total rules: '.count($this->parseResult['rules']).' <br><br>';
        echo '<b>Possible performance issues</b><br/>';
        foreach($this->runResult['fired'] as $rule) {
            echo $rule['issue'].'<br />';
        }
        echo '<br/><b>Rules not checked due to unmet preconditions</b><br/>';
        foreach($this->runResult['unchecked'] as $rule) {
            echo $rule['name'].'<br />';
        }
        echo '<br/><b>Rules that didn\'t fire</b><br/>';
        foreach($this->runResult['notfired'] as $rule) {
            echo $rule['name'].'<br />';
        }
        
        if($this->runResult['errors'])
            echo 'There were errors while testing the rules.';
          */
        return $this->runResult;
    }

    function runRules() {
        $this->runResult = array( 'fired' => array(), 'notfired' => array(), 'unchecked'=> array(), 'errors' => array() );
        
        foreach($this->parseResult['rules'] as $rule) {
            $this->variables['value'] = 0;
            $precond = true;
            
            if(isset($rule['precondition'])) {
                try {
                     $precond = $this->ruleExprEvaluate($rule['precondition']);
                } catch (Exception $e) {
                    $this->runResult['errors'][] = 'Failed evaluating precondition for rule \''.$rule['name'].'\'. PHP threw following error: '.$e->getMessage();
                    continue;
                }
            }
            
            if(! $precond)
                $this->addRule('unchecked', $rule);
            else {
                try {
                    $value = $this->ruleExprEvaluate($rule['formula']);
                } catch(Exception $e) {
                    $this->runResult['errors'][] = 'Failed calculating value for rule \''.$rule['name'].'\'. PHP threw following error: '.$e->getMessage();
                    continue;
                }
                
                $this->variables['value'] = $value;
                
                try {
                    if($this->ruleExprEvaluate($rule['test']))
                        $this->addRule('fired', $rule);
                    else $this->addRule('notfired', $rule);
                }  catch(Exception $e) {
                    $this->runResult['errors'][] = 'Failed running test for rule \''.$rule['name'].'\'. PHP threw following error: '.$e->getMessage();
                }
            }
        }
        
        return true;
    }
    
    function addRule($type, $rule) {
        switch($type) {
            case 'notfired':
            case 'fired':
                    $jst = preg_split('/\s*\|\s*/',$rule['justification'],2);
                    if(count($jst) > 1) {
                        $jst[0] = preg_replace('/%( |,|\.|$)/','%%\1',$jst[0]);
                        try {
                            $str = $this->ruleExprEvaluate('sprintf("'.$jst[0].'",'.$jst[1].')',strlen('sprintf("'.$jst[0].'"'));
                        } catch (Exception $e) {
                            $this->runResult['errors'][] = 'Failed formattingstring for rule \''.$rule['name'].'\'. PHP threw following error: '.$e->getMessage();
                            return;
                        }
                        
                        $rule['justification'] = $str;
                    }
                    break;
        }
        
        $this->runResult[$type][] = $rule;
    }

    // Runs a code expression, replacing variable names with their respective values
    // ignoreUntil: if > 0, it doesn't replace any variables until that string position, but still evaluates the whole expr
    function ruleExprEvaluate($expr, $ignoreUntil) {
        if($ignoreUntil > 0) {
            $exprIgnore = substr($expr,0,$ignoreUntil);
            $expr = substr($expr,$ignoreUntil);
        }
        $expr = preg_replace('/fired\s*\(\s*(\'|")(.*)\1\s*\)/Uie','1',$expr); //isset($this->runResult[\'fired\']
        $expr = preg_replace('/\b(\w+)\b/e','isset($this->variables[\'\1\']) ? (!is_numeric($this->variables[\'\1\']) ? \'"\'.$this->variables[\'\1\'].\'"\' : $this->variables[\'\1\']) : \'\1\'', $expr); 
        if($ignoreUntil > 0){
            $expr = $exprIgnore . $expr;
        }
        $value = 0;
        $err = 0;
        ob_start();
        eval('$value = '.$expr.';');
        $err = ob_get_contents();
        ob_end_clean();
        if($err) throw new Exception(strip_tags($err) . '<br />Executed code: $value = '.$expr.';');
        return $value;
    }

    function parseRulesFile() {
        $file = file('libraries/advisory_rules.txt');
        $errors = array();
        $rules = array();
        $ruleSyntax = array('name','formula','test','issue','recommendation','justification');
        $numRules = count($ruleSyntax);
        $numLines = count($file);
        $j = -1;
        $ruleLine = -1;
        
        for ($i = 0; $i<$numLines; $i++) {
            $line = $file[$i];
            if($line[0] == '#' || $line[0] == "\n") continue;

            // Reading new rule
            if(substr($line, 0, 4) == 'rule') {
                if($ruleLine > 0) { $errors[] = 'Invalid rule declaration on line '.($i+1). ', expected line '.$ruleSyntax[$ruleLine++].' of previous rule' ; continue; }
                $ruleLine = 1;
                if(preg_match("/rule\s'(.*)'( \[(.*)\])?$/",$line,$match)) {
                    $j++;
                    $rules[$j] = array( 'name' => $match[1]);
                    if(isset($match[3])) $rules[$j]['precondition'] = $match[3];
                } else {
                    $errors[] = 'Invalid rule declaration on line '.($i+1);
                }
                continue;
            } else {
                if($ruleLine == -1) $errors[] = 'Unexpected characters on line '.($i+1);
            }
            
            // Reading rule lines
            if($ruleLine > 0) {
                if(!isset($line[0])) continue; // Empty lines are ok
                // Non tabbed lines are not
                if($line[0] != "\t") { $errors[] = 'Unexpected character on line '.($i+1).'. Expected tab, but found \''.$line[0].'\''; continue; }
                $rules[$j][$ruleSyntax[$ruleLine++]] = chop(substr($line,1));
            }

            // Rule complete
            if($ruleLine == $numRules) {
                $ruleLine = -1;
            }
        }
        
        return array('rules' => $rules, 'errors' => $errors);
    }
}

function PMA_bytime($num, $precision) {
    $per = '';
    if ($num >= 1) { # per second
        $per = "per second";
    } 
    elseif ($num*60 >= 1) { # per minute
        $num = $num*60;
        $per = "per minute";
    } 
    elseif ($num*60*60 >=1 ) { # per hour
        $num = $num*60*60;
        $per = "per hour";
    } 
    else {
        $num = $num*60*60*24;
        $per = "per day";
    }
    
    $num = round($num, $precision);
    
    if($num == 0) $num = '<'.pow(10,-$precision);
    
    return "$num $per";
}

?>