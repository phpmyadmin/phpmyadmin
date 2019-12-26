<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Manages the rendering of pages in PMA
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\Core;
use PhpMyAdmin\Footer;
use PhpMyAdmin\Header;
use PhpMyAdmin\Message;
use PhpMyAdmin\OutputBuffering;

/**
 * Singleton class used to manage the rendering of pages in PMA
 *
 * @package PhpMyAdmin
 */
class Response
{
    /**
     * Response instance
     *
     * @access private
     * @static
     * @var Response
     */
    private static $_instance;
    /**
     * Header instance
     *
     * @access private
     * @var Header
     */
    private $_header;
    /**
     * HTML data to be used in the response
     *
     * @access private
     * @var string
     */
    private $_HTML;
    /**
     * An array of JSON key-value pairs
     * to be sent back for ajax requests
     *
     * @access private
     * @var array
     */
    private $_JSON;
    /**
     * PhpMyAdmin\Footer instance
     *
     * @access private
     * @var Footer
     */
    private $_footer;
    /**
     * Whether we are servicing an ajax request.
     *
     * @access private
     * @var bool
     */
    private $_isAjax;
    /**
     * Whether response object is disabled
     *
     * @access private
     * @var bool
     */
    private $_isDisabled;
    /**
     * Whether there were any errors during the processing of the request
     * Only used for ajax responses
     *
     * @access private
     * @var bool
     */
    private $_isSuccess;
    /**
     * Workaround for PHP bug
     *
     * @access private
     * @var string|bool
     */
    private $_CWD;

    /**
     * @var array<int, string>
     * @see http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     */
    protected static $httpStatusMessages = [
        // Informational
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        103 => 'Early Hints',
        // Success
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',
        // Redirection
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        // Client Error
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        421 => 'Misdirected Request',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Too Early',
        426 => 'Upgrade Required',
        427 => 'Unassigned',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        430 => 'Unassigned',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',
        // Server Error
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        509 => 'Unassigned',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
    ];

    /**
     * Creates a new class instance
     */
    private function __construct()
    {
        if (! defined('TESTSUITE')) {
            $buffer = OutputBuffering::getInstance();
            $buffer->start();
            register_shutdown_function([$this, 'response']);
        }
        $this->_header = new Header();
        $this->_HTML   = '';
        $this->_JSON   = [];
        $this->_footer = new Footer();

        $this->_isSuccess  = true;
        $this->_isDisabled = false;
        $this->setAjax(! empty($_REQUEST['ajax_request']));
        $this->_CWD = getcwd();
    }

    /**
     * Set the ajax flag to indicate whether
     * we are servicing an ajax request
     *
     * @param bool $isAjax Whether we are servicing an ajax request
     *
     * @return void
     */
    public function setAjax(bool $isAjax): void
    {
        $this->_isAjax = $isAjax;
        $this->_header->setAjax($this->_isAjax);
        $this->_footer->setAjax($this->_isAjax);
    }

    /**
     * Returns the singleton Response object
     *
     * @return Response object
     */
    public static function getInstance()
    {
        if (empty(self::$_instance)) {
            self::$_instance = new Response();
        }
        return self::$_instance;
    }

    /**
     * Set the status of an ajax response,
     * whether it is a success or an error
     *
     * @param bool $state Whether the request was successfully processed
     *
     * @return void
     */
    public function setRequestStatus(bool $state): void
    {
        $this->_isSuccess = ($state === true);
    }

    /**
     * Returns true or false depending on whether
     * we are servicing an ajax request
     *
     * @return bool
     */
    public function isAjax(): bool
    {
        return $this->_isAjax;
    }

    /**
     * Returns the path to the current working directory
     * Necessary to work around a PHP bug where the CWD is
     * reset after the initial script exits
     *
     * @return string
     */
    public function getCWD()
    {
        return $this->_CWD;
    }

    /**
     * Disables the rendering of the header
     * and the footer in responses
     *
     * @return void
     */
    public function disable()
    {
        $this->_header->disable();
        $this->_footer->disable();
        $this->_isDisabled = true;
    }

    /**
     * Returns a PhpMyAdmin\Header object
     *
     * @return Header
     */
    public function getHeader()
    {
        return $this->_header;
    }

    /**
     * Returns a PhpMyAdmin\Footer object
     *
     * @return Footer
     */
    public function getFooter()
    {
        return $this->_footer;
    }

    /**
     * Add HTML code to the response
     *
     * @param string $content A string to be appended to
     *                        the current output buffer
     *
     * @return void
     */
    public function addHTML($content)
    {
        if (is_array($content)) {
            foreach ($content as $msg) {
                $this->addHTML($msg);
            }
        } elseif ($content instanceof Message) {
            $this->_HTML .= $content->getDisplay();
        } else {
            $this->_HTML .= $content;
        }
    }

    /**
     * Add JSON code to the response
     *
     * @param mixed $json  Either a key (string) or an
     *                     array or key-value pairs
     * @param mixed $value Null, if passing an array in $json otherwise
     *                     it's a string value to the key
     *
     * @return void
     */
    public function addJSON($json, $value = null)
    {
        if (is_array($json)) {
            foreach ($json as $key => $value) {
                $this->addJSON($key, $value);
            }
        } else {
            if ($value instanceof Message) {
                $this->_JSON[$json] = $value->getDisplay();
            } else {
                $this->_JSON[$json] = $value;
            }
        }
    }

    /**
     * Renders the HTML response text
     *
     * @return string
     */
    private function _getDisplay()
    {
        // The header may contain nothing at all,
        // if its content was already rendered
        // and, in this case, the header will be
        // in the content part of the request
        $retval  = $this->_header->getDisplay();
        $retval .= $this->_HTML;
        $retval .= $this->_footer->getDisplay();
        return $retval;
    }

    /**
     * Sends an HTML response to the browser
     *
     * @return void
     */
    private function _htmlResponse()
    {
        echo $this->_getDisplay();
    }

    /**
     * Sends a JSON response to the browser
     *
     * @return void
     */
    private function _ajaxResponse()
    {
        /* Avoid wrapping in case we're disabled */
        if ($this->_isDisabled) {
            echo $this->_getDisplay();
            return;
        }

        if (! isset($this->_JSON['message'])) {
            $this->_JSON['message'] = $this->_getDisplay();
        } elseif ($this->_JSON['message'] instanceof Message) {
            $this->_JSON['message'] = $this->_JSON['message']->getDisplay();
        }

        if ($this->_isSuccess) {
            $this->_JSON['success'] = true;
        } else {
            $this->_JSON['success'] = false;
            $this->_JSON['error']   = $this->_JSON['message'];
            unset($this->_JSON['message']);
        }

        if ($this->_isSuccess) {
            $this->addJSON('title', '<title>' . $this->getHeader()->getPageTitle() . '</title>');

            if (isset($GLOBALS['dbi'])) {
                $menuHash = $this->getHeader()->getMenu()->getHash();
                $this->addJSON('menuHash', $menuHash);
                $hashes = [];
                if (isset($_REQUEST['menuHashes'])) {
                    $hashes = explode('-', $_REQUEST['menuHashes']);
                }
                if (! in_array($menuHash, $hashes)) {
                    $this->addJSON(
                        'menu',
                        $this->getHeader()
                            ->getMenu()
                            ->getDisplay()
                    );
                }
            }

            $this->addJSON('scripts', $this->getHeader()->getScripts()->getFiles());
            $this->addJSON('selflink', $this->getFooter()->getSelfUrl());
            $this->addJSON('displayMessage', $this->getHeader()->getMessage());

            $debug = $this->_footer->getDebugMessage();
            if (empty($_REQUEST['no_debug'])
                && strlen($debug) > 0
            ) {
                $this->addJSON('debug', $debug);
            }

            $errors = $this->_footer->getErrorMessages();
            if (strlen($errors) > 0) {
                $this->addJSON('errors', $errors);
            }
            $promptPhpErrors = $GLOBALS['error_handler']->hasErrorsForPrompt();
            $this->addJSON('promptPhpErrors', $promptPhpErrors);

            if (empty($GLOBALS['error_message'])) {
                // set current db, table and sql query in the querywindow
                // (this is for the bottom console)
                $query = '';
                $maxChars = $GLOBALS['cfg']['MaxCharactersInDisplayedSQL'];
                if (isset($GLOBALS['sql_query'])
                    && mb_strlen($GLOBALS['sql_query']) < $maxChars
                ) {
                    $query = $GLOBALS['sql_query'];
                }
                $this->addJSON(
                    'reloadQuerywindow',
                    [
                        'db' => Core::ifSetOr($GLOBALS['db'], ''),
                        'table' => Core::ifSetOr($GLOBALS['table'], ''),
                        'sql_query' => $query,
                    ]
                );
                if (! empty($GLOBALS['focus_querywindow'])) {
                    $this->addJSON('_focusQuerywindow', $query);
                }
                if (! empty($GLOBALS['reload'])) {
                    $this->addJSON('reloadNavigation', 1);
                }
                $this->addJSON('params', $this->getHeader()->getJsParams());
            }
        }

        // Set the Content-Type header to JSON so that jQuery parses the
        // response correctly.
        Core::headerJSON();

        $result = json_encode($this->_JSON);
        if ($result === false) {
            switch (json_last_error()) {
                case JSON_ERROR_NONE:
                    $error = 'No errors';
                    break;
                case JSON_ERROR_DEPTH:
                    $error = 'Maximum stack depth exceeded';
                    break;
                case JSON_ERROR_STATE_MISMATCH:
                    $error = 'Underflow or the modes mismatch';
                    break;
                case JSON_ERROR_CTRL_CHAR:
                    $error = 'Unexpected control character found';
                    break;
                case JSON_ERROR_SYNTAX:
                    $error = 'Syntax error, malformed JSON';
                    break;
                case JSON_ERROR_UTF8:
                    $error = 'Malformed UTF-8 characters, possibly incorrectly encoded';
                    break;
                case JSON_ERROR_RECURSION:
                    $error = 'One or more recursive references in the value to be encoded';
                    break;
                case JSON_ERROR_INF_OR_NAN:
                    $error = 'One or more NAN or INF values in the value to be encoded';
                    break;
                case JSON_ERROR_UNSUPPORTED_TYPE:
                    $error = 'A value of a type that cannot be encoded was given';
                    break;
                default:
                    $error = 'Unknown error';
                    break;
            }
            echo json_encode([
                'success' => false,
                'error' => 'JSON encoding failed: ' . $error,
            ]);
        } else {
            echo $result;
        }
    }

    /**
     * Sends an HTML response to the browser
     *
     * @return void
     */
    public function response()
    {
        chdir($this->getCWD());
        $buffer = OutputBuffering::getInstance();
        if (empty($this->_HTML)) {
            $this->_HTML = $buffer->getContents();
        }
        if ($this->isAjax()) {
            $this->_ajaxResponse();
        } else {
            $this->_htmlResponse();
        }
        $buffer->flush();
        exit;
    }

    /**
     * Wrapper around PHP's header() function.
     *
     * @param string $text header string
     *
     * @return void
     */
    public function header($text)
    {
        header($text);
    }

    /**
     * Wrapper around PHP's headers_sent() function.
     *
     * @return bool
     */
    public function headersSent()
    {
        return headers_sent();
    }

    /**
     * Wrapper around PHP's http_response_code() function.
     *
     * @param int $response_code will set the response code.
     *
     * @return void
     */
    public function httpResponseCode($response_code)
    {
        http_response_code($response_code);
    }

    /**
     * Sets http response code.
     *
     * @param int $responseCode will set the response code.
     *
     * @return void
     */
    public function setHttpResponseCode(int $responseCode): void
    {
        $this->httpResponseCode($responseCode);
        $header = 'status: ' . $responseCode . ' ';
        if (isset(static::$httpStatusMessages[$responseCode])) {
            $header .= static::$httpStatusMessages[$responseCode];
        } else {
            $header .= 'Web server is down';
        }
        if (PHP_SAPI !== 'cgi-fcgi') {
            $this->header($header);
        }
    }

    /**
     * Generate header for 303
     *
     * @param string $location will set location to redirect.
     *
     * @return void
     */
    public function generateHeader303($location)
    {
        $this->setHttpResponseCode(303);
        $this->header('Location: ' . $location);
        if (! defined('TESTSUITE')) {
            exit;
        }
    }

    /**
     * Configures response for the login page
     *
     * @return bool Whether caller should exit
     */
    public function loginPage()
    {
        /* Handle AJAX redirection */
        if ($this->isAjax()) {
            $this->setRequestStatus(false);
            // redirect_flag redirects to the login page
            $this->addJSON('redirect_flag', '1');
            return true;
        }

        $this->getFooter()->setMinimal();
        $header = $this->getHeader();
        $header->setBodyId('loginform');
        $header->setTitle('phpMyAdmin');
        $header->disableMenuAndConsole();
        $header->disableWarnings();
        return false;
    }
}
