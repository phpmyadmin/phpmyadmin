<?php

declare(strict_types=1);

namespace PhpMyAdmin\Error;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Http\RequestMethod;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Utils\HttpRequest;
use PhpMyAdmin\Version;

use function count;
use function http_build_query;
use function is_array;
use function json_encode;
use function mb_strlen;
use function mb_substr;
use function parse_str;
use function parse_url;
use function preg_match;
use function str_replace;

use const E_USER_WARNING;
use const PHP_VERSION;

/**
 * Error reporting functions used to generate and submit error reports
 */
class ErrorReport
{
    /**
     * The URL where to submit reports to
     */
    private string $submissionUrl = 'https://reports.phpmyadmin.net/incidents/create';

    public function __construct(
        private HttpRequest $httpRequest,
        private Relation $relation,
        public Template $template,
        private Config $config,
    ) {
    }

    /**
     * Set the URL where to submit reports to
     *
     * @param string $submissionUrl Submission URL
     */
    public function setSubmissionUrl(string $submissionUrl): void
    {
        $this->submissionUrl = $submissionUrl;
    }

    /**
     * Returns the error report data collected from the current configuration or
     * from the request parameters sent by the error reporting js code.
     *
     * @param string $exceptionType whether exception is 'js' or 'php'
     *
     * @return mixed[] error report if success, Empty Array otherwise
     */
    public function getData(string $exceptionType = 'js'): array
    {
        $relationParameters = $this->relation->getRelationParameters();
        // common params for both, php & js exceptions
        $report = [
            'pma_version' => Version::VERSION,
            'browser_name' => $this->config->get('PMA_USR_BROWSER_AGENT'),
            'browser_version' => $this->config->get('PMA_USR_BROWSER_VER'),
            'user_os' => $this->config->get('PMA_USR_OS'),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? null,
            'user_agent_string' => $_SERVER['HTTP_USER_AGENT'],
            'locale' => $this->config->getCookie('pma_lang'),
            'configuration_storage' => $relationParameters->db === null ? 'disabled' : 'enabled',
            'php_version' => PHP_VERSION,
        ];

        if ($exceptionType === 'js') {
            if (empty($_POST['exception'])) {
                return [];
            }

            $exception = $_POST['exception'];

            if (isset($exception['stack'])) {
                $exception['stack'] = $this->translateStacktrace($exception['stack']);
            }

            if (isset($exception['url'])) {
                [$uri, $scriptName] = $this->sanitizeUrl($exception['url']);
                $exception['uri'] = $uri;
                $report['script_name'] = $scriptName;
                unset($exception['url']);
            } elseif (isset($_POST['url'])) {
                [$uri, $scriptName] = $this->sanitizeUrl($_POST['url']);
                $exception['uri'] = $uri;
                $report['script_name'] = $scriptName;
                unset($_POST['url']);
            } else {
                $report['script_name'] = null;
            }

            $report['exception_type'] = 'js';
            $report['exception'] = $exception;

            if (! empty($_POST['description'])) {
                $report['steps'] = $_POST['description'];
            }
        } elseif ($exceptionType === 'php') {
            $errors = [];
            // create php error report
            $i = 0;
            if (! isset($_SESSION['prev_errors']) || $_SESSION['prev_errors'] == '') {
                return [];
            }

            /** @var Error $errorObj */
            foreach ($_SESSION['prev_errors'] as $errorObj) {
                if (
                    ! $errorObj->getLine() || ! $errorObj->getType() || $errorObj->getErrorNumber() === E_USER_WARNING
                ) {
                    continue;
                }

                $errors[$i++] = [
                    'lineNum' => $errorObj->getLine(),
                    'file' => $errorObj->getFile(),
                    'type' => $errorObj->getType(),
                    'msg' => $errorObj->getOnlyMessage(),
                    'stackTrace' => $errorObj->getBacktrace(5),
                    'stackhash' => $errorObj->getHash(),
                ];
            }

            // if there were no 'actual' errors to be submitted.
            if ($i === 0) {
                return []; // then return empty array
            }

            $report['exception_type'] = 'php';
            $report['errors'] = $errors;
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
     * @return array{string, string} the uri and script name
     */
    private function sanitizeUrl(string $url): array
    {
        $components = parse_url($url);

        if (! is_array($components)) {
            $components = [];
        }

        if (isset($components['fragment']) && preg_match('<PMAURL-\d+:>', $components['fragment'], $matches) === 1) {
            $uri = str_replace($matches[0], '', $components['fragment']);
            $url = 'https://example.com/' . $uri;
            $components = parse_url($url);

            if (! is_array($components)) {
                $components = [];
            }
        }

        // get script name
        preg_match('<([a-zA-Z\-_\d\.]*\.php|js\/[a-zA-Z\-_\d\/\.]*\.js)$>', $components['path'] ?? '', $matches);
        $scriptName = count($matches) < 2 ? 'index.php' : $matches[1];

        // remove deployment specific details to make uri more generic
        if (isset($components['query'])) {
            parse_str($components['query'], $queryArray);
            unset($queryArray['db'], $queryArray['table'], $queryArray['token'], $queryArray['server']);
            unset($queryArray['eq']);
            $query = http_build_query($queryArray);
        } else {
            $query = '';
        }

        $uri = $scriptName . '?' . $query;

        return [$uri, $scriptName];
    }

    /**
     * Sends report data to the error reporting server
     *
     * @param mixed[] $report the report info to be sent
     *
     * @return string|bool|null the reply of the server
     */
    public function send(array $report): string|bool|null
    {
        return $this->httpRequest->create(
            $this->submissionUrl,
            RequestMethod::Post,
            false,
            json_encode($report),
            'Content-Type: application/json',
        );
    }

    /**
     * Translates the cumulative line numbers in the stack trace as well as sanitize
     * urls and trim long lines in the context
     *
     * @param mixed[] $stack the stack trace
     *
     * @return mixed[] the modified stack trace
     */
    private function translateStacktrace(array $stack): array
    {
        foreach ($stack as &$level) {
            if (is_array($level['context'])) {
                foreach ($level['context'] as &$line) {
                    if (mb_strlen($line) <= 80) {
                        continue;
                    }

                    $line = mb_substr($line, 0, 75) . '//...';
                }
            }

            [$uri, $scriptName] = $this->sanitizeUrl($level['url']);
            $level['uri'] = $uri;
            $level['scriptname'] = $scriptName;
            unset($level['url']);
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
    public function getForm(): string
    {
        $reportData = $this->getData();

        $datas = [
            'report_data' => $reportData,
            'hidden_inputs' => Url::getHiddenInputs(),
            'hidden_fields' => null,
            'allowed_to_send_error_reports' => $this->config->get('SendErrorReports') !== 'never',
        ];

        if ($reportData !== []) {
            $datas['hidden_fields'] = Url::getHiddenFields($reportData, '', true);
        }

        return $this->template->render('error/report_form', $datas);
    }

    public function getEmptyModal(): string
    {
        return $this->template->render('error/report_modal', [
            'allowed_to_send_error_reports' => $this->config->get('SendErrorReports') !== 'never',
        ]);
    }
}
