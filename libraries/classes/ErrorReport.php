<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PhpMyAdmin\ErrorReport class
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin;

use PhpMyAdmin\Relation;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Utils\HttpRequest;

/**
 * Error reporting functions used to generate and submit error reports
 *
 * @package PhpMyAdmin
 */
class ErrorReport
{
    /**
     * The URL where to submit reports to
     *
     * @var string
     */
    private $submissionUrl;

    /**
     * @var HttpRequest
     */
    private $httpRequest;

    /**
     * @var Relation $relation
     */
    private $relation;

    /**
     * Constructor
     *
     * @param HttpRequest $httpRequest HttpRequest instance
     */
    public function __construct(HttpRequest $httpRequest)
    {
        $this->httpRequest = $httpRequest;
        $this->submissionUrl = 'https://reports.phpmyadmin.net/incidents/create';
        $this->relation = new Relation();
    }

    /**
     * Returns the pretty printed error report data collected from the
     * current configuration or from the request parameters sent by the
     * error reporting js code.
     *
     * @return string the report
     */
    private function getPrettyData()
    {
        $report = $this->getData();

        return json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Returns the error report data collected from the current configuration or
     * from the request parameters sent by the error reporting js code.
     *
     * @param string $exceptionType whether exception is 'js' or 'php'
     *
     * @return array error report if success, Empty Array otherwise
     */
    public function getData($exceptionType = 'js')
    {
        $relParams = $this->relation->getRelationsParam();
        // common params for both, php & js exceptions
        $report = [
            "pma_version" => PMA_VERSION,
            "browser_name" => PMA_USR_BROWSER_AGENT,
            "browser_version" => PMA_USR_BROWSER_VER,
            "user_os" => PMA_USR_OS,
            "server_software" => $_SERVER['SERVER_SOFTWARE'],
            "user_agent_string" => $_SERVER['HTTP_USER_AGENT'],
            "locale" => $_COOKIE['pma_lang'],
            "configuration_storage" =>
                is_null($relParams['db']) ? "disabled" : "enabled",
            "php_version" => phpversion()
        ];

        if ($exceptionType == 'js') {
            if (empty($_REQUEST['exception'])) {
                return [];
            }
            $exception = $_REQUEST['exception'];
            $exception["stack"] = $this->translateStacktrace($exception["stack"]);
            list($uri, $scriptName) = $this->sanitizeUrl($exception["url"]);
            $exception["uri"] = $uri;
            unset($exception["url"]);

            $report["exception_type"] = 'js';
            $report["exception"] = $exception;
            $report["script_name"] = $scriptName;
            $report["microhistory"] = $_REQUEST['microhistory'];

            if (! empty($_REQUEST['description'])) {
                $report['steps'] = $_REQUEST['description'];
            }
        } elseif ($exceptionType == 'php') {
            $errors = [];
            // create php error report
            $i = 0;
            if (!isset($_SESSION['prev_errors'])
                || $_SESSION['prev_errors'] == ''
            ) {
                return [];
            }
            foreach ($_SESSION['prev_errors'] as $errorObj) {
                /* @var $errorObj PhpMyAdmin\Error */
                if ($errorObj->getLine()
                    && $errorObj->getType()
                    && $errorObj->getNumber() != E_USER_WARNING
                ) {
                    $errors[$i++] = [
                        "lineNum" => $errorObj->getLine(),
                        "file" => $errorObj->getFile(),
                        "type" => $errorObj->getType(),
                        "msg" => $errorObj->getOnlyMessage(),
                        "stackTrace" => $errorObj->getBacktrace(5),
                        "stackhash" => $errorObj->getHash()
                    ];
                }
            }

            // if there were no 'actual' errors to be submitted.
            if ($i==0) {
                return [];   // then return empty array
            }
            $report["exception_type"] = 'php';
            $report["errors"] = $errors;
        } else {
            return [];
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
     * @param string $url the url to sanitize
     *
     * @return array the uri and script name
     */
    private function sanitizeUrl($url)
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
            $scriptName = 'index.php';
        } else {
            $scriptName = $matches[1];
        }

        // remove deployment specific details to make uri more generic
        if (isset($components["query"])) {
            parse_str($components["query"], $queryArray);
            unset($queryArray["db"]);
            unset($queryArray["table"]);
            unset($queryArray["token"]);
            unset($queryArray["server"]);
            $query = http_build_query($queryArray);
        } else {
            $query = '';
        }

        $uri = $scriptName . "?" . $query;
        return [$uri, $scriptName];
    }

    /**
     * Sends report data to the error reporting server
     *
     * @param array $report the report info to be sent
     *
     * @return string the reply of the server
     */
    public function send(array $report)
    {
        $response = $this->httpRequest->create(
            $this->submissionUrl,
            "POST",
            false,
            json_encode($report),
            "Content-Type: application/json"
        );
        return $response;
    }

    /**
     * Translates the cumulative line numbers in the stack trace as well as sanitize
     * urls and trim long lines in the context
     *
     * @param array $stack the stack trace
     *
     * @return array $stack the modified stack trace
     */
    private function translateStacktrace(array $stack)
    {
        foreach ($stack as &$level) {
            foreach ($level["context"] as &$line) {
                if (mb_strlen($line) > 80) {
                    $line = mb_substr($line, 0, 75) . "//...";
                }
            }
            unset($level["context"]);
            list($uri, $scriptName) = $this->sanitizeUrl($level["url"]);
            $level["uri"] = $uri;
            $level["scriptname"] = $scriptName;
            unset($level["url"]);
        }
        unset($level);
        return $stack;
    }

    /**
     * Generates the error report form to collect user description and preview the
     * report before being sent
     *
     * @return string the form
     */
    public function getForm()
    {
        $datas = [
            'report_data' => $this->getPrettyData(),
            'hidden_inputs' => Url::getHiddenInputs(),
            'hidden_fields' => null,
        ];

        $reportData = $this->getData();
        if (!empty($reportData)) {
            $datas['hidden_fields'] = Url::getHiddenFields($reportData);
        }

        return Template::get('error/report_form')->render($datas);
    }
}
