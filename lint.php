<?php

// Loads the SQL lexer and parser, which are used to parse this error to detect
// any errors.
require_once 'libraries/sql-parser/autoload.php';

/**
 * Gets the starting position of each line.
 *
 * @param string $str String to be analyzed.
 *
 * @return array
 */
function getLines($str)
{
    $lines = array(0);
    for ($i = 0, $len = strlen($str); $i < $len; ++$i) {
        if ($str[$i] === "\n") {
            $lines[] = $i;
        }
    }
    return $lines;
}

/**
 * Computes the number of the line and column given an absolute position.
 *
 * @param array $lines The starting position of each line.
 * @param int   $pos   The absolute position
 *
 * @return void
 */
function findLineNumberAndColumn($lines, $pos)
{
    $line = 0;
    foreach ($lines as $lineNo => $lineStart) {
        if ($lineStart >= $pos) {
            break;
        }
        $line = $lineNo;
    }
    return array($line, $pos - $lines[$line]);
}

/**
 * Runs the linting process.
 *
 * @param string $query The query to be checked.
 *
 * @return void
 */
function linter($query)
{
    // Disabling lint for huge queries to save some resources.
    if (strlen($query) > 10000) {
        echo json_encode(
            array(
                array(
                    'message' => 'The linting is disabled for this query because it exceededs the maxmimum length',
                    'fromLine' => 0,
                    'fromColumn' => 0,
                    'toLine' => 0,
                    'toColumn' => 0,
                    'severity' => 'warning',
                )
            )
        );
        return;
    }

    /**
     * Lexer used for tokenizing the query.
     *
     * @var SqlParser\Lexer
     */
    $lexer = new SqlParser\Lexer($query);

    /**
     * Parsed used for analysing the query.
     *
     * @var SqlParser\Parser
     */
    $parser = new SqlParser\Parser($lexer->list);

    /**
     * Array containing all errors.
     *
     * @var array
     */
    $errors = SqlParser\Utils\Error::get(array($lexer, $parser));

    /**
     * The response containing of all errors.
     *
     * @var array
     */
    $response = array();

    /**
     * The starting position for each line.
     *
     * CodeMirror requires relative position to line, but the parser stores
     * only the absolute position of the character in string.
     *
     * @var array
     */
    $lines = getLines($query);

    // Building the response.
    foreach ($errors as $idx => $error) {

        // Starting position of the string that caused the error.
        list($fromLine, $fromColumn) = findLineNumberAndColumn(
            $lines, $error[3]
        );

        // Ending position of the string that caused the error.
        list($toLine, $toColumn) = findLineNumberAndColumn(
            $lines, $error[3] + strlen($error[2])
        );

        // Building the response.
        $response[] = array(
            'message' => $error[0] . ' (near <code>' . $error[2] . '</code>)',
            'fromLine' => $fromLine,
            'fromColumn' => $fromColumn,
            'toLine' => $toLine,
            'toColumn' => $toColumn,
            'severity' => 'error',
        );
    }

    // Sending back the answer.
    echo json_encode($response);
}

linter($_REQUEST['sql_query']);
