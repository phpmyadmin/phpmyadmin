<?php

/**
 * CLI interface
 *
 * @package    SqlParser
 * @subpackage Utils
 */
namespace SqlParser\Utils;

use SqlParser\Parser;
use SqlParser\Lexer;

/**
 * CLI interface
 *
 * @category   Exceptions
 * @package    SqlParser
 * @subpackage Utils
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 */
class CLI
{
    public function mergeLongOpts(&$params, &$longopts)
    {
        foreach ($longopts as $value) {
            $value = rtrim($value, ':');
            if (isset($params[$value])) {
                $params[$value[0]] = $params[$value];
            }
        }
    }

    public function usageHighlight()
    {
        echo "Usage: highlight-query --query SQL [--format html|cli|text]\n";
    }

    public function getopt($opt, $long)
    {
        return getopt($opt, $long);
    }

    public function parseHighlight()
    {
        $longopts = array('help', 'query:', 'format:');
        $params = $this->getopt(
            'hq:f:', $longopts
        );
        if ($params === false) {
            return false;
        }
        $this->mergeLongOpts($params, $longopts);
        if (! isset($params['f'])) {
            $params['f'] = 'cli';
        }
        if (! in_array($params['f'], array('html', 'cli', 'text'))) {
            echo "ERROR: Invalid value for format!\n";
            return false;
        }
        return $params;
    }

    public function runHighlight()
    {
        $params = $this->parseHighlight();
        if ($params === false) {
            return 1;
        }
        if (isset($params['h'])) {
            $this->usageHighlight();
            return 0;
        }
        if (isset($params['q'])) {
            echo Formatter::format(
                $params['q'], array('type' => $params['f'])
            );
            echo "\n";
            return 0;
        }
        echo "ERROR: Missing parameters!\n";
        $this->usageHighlight();
        return 1;
    }

    public function usageLint()
    {
        echo "Usage: lint-query --query SQL\n";
    }

    public function parseLint()
    {
        $longopts = array('help', 'query:');
        $params = $this->getopt(
            'hq:', $longopts
        );
        $this->mergeLongOpts($params, $longopts);
        return $params;
    }

    public function runLint()
    {
        $params = $this->parseLint();
        if ($params === false) {
            return 1;
        }
        if (isset($params['h'])) {
            $this->usageLint();
            return 0;
        }
        if (isset($params['q'])) {
            $lexer = new Lexer($params['q'], false);
            $parser = new Parser($lexer->list);
            $errors = Error::get(array($lexer, $parser));
            if (count($errors) == 0) {
                return 0;
            }
            $output = Error::format($errors);
            echo implode("\n", $output);
            echo "\n";
            return 10;
        }
        echo "ERROR: Missing parameters!\n";
        $this->usageLint();
        return 1;
    }
}
