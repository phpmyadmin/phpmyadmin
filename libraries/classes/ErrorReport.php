<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Error reporting functions used to generate and submit error reports
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin;

use PhpMyAdmin\Relation;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

/**
 * PhpMyAdmin\ErrorReport class
 *
 * @package PhpMyAdmin
 */
class ErrorReport
{
    /**
     * the url where to submit reports to
     */
    const SUBMISSION_URL = "https://reports.phpmyadmin.net/incidents/create";

    /**
     * returns the pretty printed error report data collected from the
     * current configuration or from the request parameters sent by the
     * error reporting js code.
     *
     * @return String the report
     */
    public static function getPrettyReportData()
    {
        $report = self::getReportData();

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
    public static function getReportData($exception_type = 'js')
    {
        $relParams = Relation::getRelationsParam();
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
            $exception["stack"] = self::translateStacktrace($exception["stack"]);
            List($uri, $script_name) = self::sanitizeUrl($exception["url"]);
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
                /* @var $errorObj PhpMyAdmin\Error */
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
    public static function sanitizeUrl($url)
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
    public static function send(array $report)
    {
        $response = Util::httpRequest(
            self::SUBMISSION_URL,
            "POST",
            false,
            json_encode($report),
            "Content-Type: application/json"
        );
        return $response;
    }

    /**
     * translates the cumulative line numbers in the stack trace as well as sanitize
     * urls and trim long lines in the context
     *
     * @param array $stack the stack trace
     *
     * @return array $stack the modified stack trace
     */
    public static function translateStacktrace(array $stack)
    {
        foreach ($stack as &$level) {
            foreach ($level["context"] as &$line) {
                if (mb_strlen($line) > 80) {
                    $line = mb_substr($line, 0, 75) . "//...";
                }
            }
            unset($level["context"]);
            List($uri, $script_name) = self::sanitizeUrl($level["url"]);
            $level["uri"] = $uri;
            $level["scriptname"] = $script_name;
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
    public static function getForm()
    {
        $datas = array(
            'report_data' => self::getPrettyReportData(),
            'hidden_inputs' => Url::getHiddenInputs(),
            'hidden_fields' => null,
        );

        $reportData = self::getReportData();
        if (!empty($reportData)) {
            $datas['hidden_fields'] = Url::getHiddenFields($reportData);
        }

        return Template::get('error/report_form')
            ->render($datas);
    }
}
