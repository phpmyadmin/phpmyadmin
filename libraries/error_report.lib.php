<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Error reporting functions used to generate and submit error reports
 *
 * @package PhpMyAdmin
 */

if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * The generated file that contains the line numbers for the js files
 * If you change any of the js files you can run the scripts/line-counts.sh
 */
if (is_readable('js/line_counts.php')) {
    include_once 'js/line_counts.php';
}

/**
 * the url where to submit reports to
 */
define('SUBMISSION_URL', "https://reports.phpmyadmin.net/incidents/create");

/**
 * returns the pretty printed error report data collected from the
 * current configuration or from the request parameters sent by the
 * error reporting js code.
 *
 * @return String the report
 */
function PMA_getPrettyReportData()
{
    $report = PMA_getReportData();

    return json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}

/**
 * returns the error report data collected from the current configuration or
 * from the request parameters sent by the error reporting js code.
 *
 * @param string $exception_type whether exception is 'js' or 'php'
 *
 * @return array error report if success, Empty Array otherwise
 */
function PMA_getReportData($exception_type = 'js')
{
    $relParams = PMA_getRelationsParam();
    // common params for both, php & js exceptions
    $report = array(
            "pma_version" => PMA_VERSION,
            "browser_name" => PMA_USR_BROWSER_AGENT,
            "browser_version" => PMA_USR_BROWSER_VER,
            "user_os" => PMA_USR_OS,
            "server_software" => $_SERVER['SERVER_SOFTWARE'],
            "user_agent_string" => $_SERVER['HTTP_USER_AGENT'],
            "locale" => $_COOKIE['pma_lang'],
            "configuration_storage" =>
                is_null($relParams['db']) ? "disabled" :
                "enabled",
            "php_version" => phpversion()
            );

    if ($exception_type == 'js') {
        if (empty($_REQUEST['exception'])) {
            return array();
        }
        $exception = $_REQUEST['exception'];
        $exception["stack"] = PMA_translateStacktrace($exception["stack"]);
        List($uri, $script_name) = PMA_sanitizeUrl($exception["url"]);
        $exception["uri"] = $uri;
        unset($exception["url"]);

        $report ["exception_type"] = 'js';
        $report ["exception"] = $exception;
        $report ["script_name"] = $script_name;
        $report ["microhistory"] = $_REQUEST['microhistory'];

        if (! empty($_REQUEST['description'])) {
            $report['steps'] = $_REQUEST['description'];
        }
    } elseif ($exception_type == 'php') {
        $errors = array();
        // create php error report
        $i = 0;
        if (!isset($_SESSION['prev_errors'])
            || $_SESSION['prev_errors'] == ''
        ) {
            return array();
        }
        foreach ($_SESSION['prev_errors'] as $errorObj) {
            /* @var $errorObj PMA\libraries\Error */
            if ($errorObj->getLine()
                && $errorObj->getType()
                && $errorObj->getNumber() != E_USER_WARNING
            ) {
                $errors[$i++] = array(
                    "lineNum" => $errorObj->getLine(),
                    "file" => $errorObj->getFile(),
                    "type" => $errorObj->getType(),
                    "msg" => $errorObj->getOnlyMessage(),
                    "stackTrace" => $errorObj->getBacktrace(5),
                    "stackhash" => $errorObj->getHash()
                    );

            }
        }

        // if there were no 'actual' errors to be submitted.
        if ($i==0) {
            return array();   // then return empty array
        }
        $report ["exception_type"] = 'php';
        $report["errors"] = $errors;
    } else {
        return array();
    }

    return $report;
}

/**
 * Sanitize a url to remove the identifiable host name and extract the
 * current script name from the url fragment
 *
 * It returns two things in an array. The first is the uri without the
 * hostname and identifying query params. The second is the name of the
 * php script in the url
 *
 * @param String $url the url to sanitize
 *
 * @return array the uri and script name
 */
function PMA_sanitizeUrl($url)
{
    $components = parse_url($url);
    if (isset($components["fragment"])
        && preg_match("<PMAURL-\d+:>", $components["fragment"], $matches)
    ) {
        $uri = str_replace($matches[0], "", $components["fragment"]);
        $url = "https://example.com/" . $uri;
        $components = parse_url($url);
    }

    // get script name
    preg_match("<([a-zA-Z\-_\d]*\.php)$>", $components["path"], $matches);
    if (count($matches) < 2) {
        $script_name = 'index.php';
    } else {
        $script_name = $matches[1];
    }

    // remove deployment specific details to make uri more generic
    if (isset($components["query"])) {
        parse_str($components["query"], $query_array);
        unset($query_array["db"]);
        unset($query_array["table"]);
        unset($query_array["token"]);
        unset($query_array["server"]);
        $query = http_build_query($query_array);
    } else {
        $query = '';
    }

    $uri = $script_name . "?" . $query;
    return array($uri, $script_name);
}

/**
 * Sends report data to the error reporting server
 *
 * @param array $report the report info to be sent
 *
 * @return String the reply of the server
 */
function PMA_sendErrorReport($report)
{
    $data_string = json_encode($report);
    if (function_exists('curl_init')) {
        $curl_handle = curl_init(SUBMISSION_URL);
        if ($curl_handle === false) {
            return null;
        }
        $curl_handle = PMA\libraries\Util::configureCurl($curl_handle);
        curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt(
            $curl_handle, CURLOPT_HTTPHEADER,
            array('Expect:', 'Content-Type: application/json')
        );
        curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($curl_handle);
        curl_close($curl_handle);

        return $response;
    } else if (ini_get('allow_url_fopen')) {
        $context = array("http" =>
            array(
                'method'  => 'POST',
                'content' => $data_string,
                'header' => "Content-Type: application/json\r\n",
            )
        );
        $context = PMA\libraries\Util::handleContext($context);
        $response = @file_get_contents(
            SUBMISSION_URL,
            false,
            stream_context_create($context)
        );
        return $response;
    }

    return null;
}

/**
 * Returns number of lines in given javascript file.
 *
 * @param string $filename javascript filename
 *
 * @return Number of lines
 *
 * @todo Should gracefully handle non existing files
 */
function PMA_countLines($filename)
{
    global $LINE_COUNT;
    if (defined('LINE_COUNTS')) {
        return $LINE_COUNT[$filename];
    }

    // ensure that the file is inside the phpMyAdmin folder
    $depath = 1;
    foreach (explode('/', $filename) as $part) {
        if ($part == '..') {
            $depath--;
        } elseif ($part != '.' || $part === '') {
            $depath++;
        }
        if ($depath < 0) {
            return 0;
        }
    }

    $linecount = 0;
    $handle = fopen('./js/' . $filename, 'r');
    while (!feof($handle)) {
        $line = fgets($handle);
        if ($line === false) {
            break;
        }
        $linecount++;
    }
    fclose($handle);
    return $linecount;
}

/**
 * returns the translated line number and the file name from the cumulative line
 * number and an array of files
 *
 * uses the $LINE_COUNT global array of file names and line numbers
 *
 * @param array   $filenames         list of files in order of concatenation
 * @param Integer $cumulative_number the cumulative line number in the
 *                                   concatenated files
 *
 * @return array the filename and line number
 * Returns two variables in an array:
 * - A String $filename the filename where the requested cumulative number
 *   exists
 * - Integer $linenumber the translated line number in the returned file
 */
function PMA_getLineNumber($filenames, $cumulative_number)
{
    $cumulative_sum = 0;
    foreach ($filenames as $filename) {
        $filecount = PMA_countLines($filename);
        if ($cumulative_number <= $cumulative_sum + $filecount + 2) {
            $linenumber = $cumulative_number - $cumulative_sum;
            break;
        }
        $cumulative_sum += $filecount + 2;
    }
    if (! isset($filename)) {
        $filename = '';
    }
    return array($filename, $linenumber);
}

/**
 * translates the cumulative line numbers in the stack trace as well as sanitize
 * urls and trim long lines in the context
 *
 * @param array $stack the stack trace
 *
 * @return array $stack the modified stack trace
 */
function PMA_translateStacktrace($stack)
{
    foreach ($stack as &$level) {
        foreach ($level["context"] as &$line) {
            if (mb_strlen($line) > 80) {
                $line = mb_substr($line, 0, 75) . "//...";
            }
        }
        if (preg_match("<js/get_scripts.js.php\?(.*)>", $level["url"], $matches)) {
            parse_str($matches[1], $vars);
            List($file_name, $line_number) = PMA_getLineNumber(
                $vars["scripts"], $level["line"]
            );
            $level["filename"] = $file_name;
            $level["line"] = $line_number;
        } else {
            unset($level["context"]);
            List($uri, $script_name) = PMA_sanitizeUrl($level["url"]);
            $level["uri"] = $uri;
            $level["scriptname"] = $script_name;
        }
        unset($level["url"]);
    }
    unset($level);
    return $stack;
}

/**
 * generates the error report form to collect user description and preview the
 * report before being sent
 *
 * @return String the form
 */
function PMA_getErrorReportForm()
{
    $datas = array(
        'report_data' => PMA_getPrettyReportData(),
        'hidden_inputs' => PMA_URL_getHiddenInputs(),
        'hidden_fields' => null,
    );

    $reportData = PMA_getReportData();
    if (!empty($reportData)) {
        $datas['hidden_fields'] = PMA_getHiddenFields($reportData);
    }

    return PMA\libraries\Template::get('error/report_form')
        ->render($datas);
}
