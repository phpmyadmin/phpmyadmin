<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Console\Console;
use PhpMyAdmin\Container\ContainerBuilder;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Error\ErrorHandler;
use PhpMyAdmin\Exceptions\ExitException;
use PhpMyAdmin\Html\MySQLDocumentation;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Response;

use function __;
use function is_array;
use function json_encode;
use function json_last_error_msg;
use function mb_strlen;
use function str_starts_with;
use function substr;

/**
 * Singleton class used to manage the rendering of pages in PMA
 */
class ResponseRenderer
{
    private static ResponseRenderer|null $instance = null;

    /**
     * HTML data to be used in the response
     */
    private string $HTML = '';
    /**
     * An array of JSON key-value pairs
     * to be sent back for ajax requests
     *
     * @var mixed[]
     */
    private array $JSON = [];

    /**
     * Whether we are servicing an ajax request.
     */
    protected bool $isAjax = false;
    /**
     * Whether there were any errors during the processing of the request
     * Only used for ajax responses
     */
    protected bool $isSuccess = true;

    /**
     * @see https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     *
     * @var array<int, string>
     */
    protected static array $httpStatusMessages = [
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

    protected Response $response;

    public static bool $reload = false;

    protected function __construct(
        protected Config $config,
        protected Template $template,
        protected Header $header,
        protected Footer $footer,
        protected ErrorHandler $errorHandler,
        protected DatabaseInterface $dbi,
        ResponseFactory $responseFactory,
    ) {
        $this->response = $responseFactory->createResponse(StatusCodeInterface::STATUS_OK, 'OK');
    }

    /**
     * Set the ajax flag to indicate whether
     * we are servicing an ajax request
     *
     * @param bool $isAjax Whether we are servicing an ajax request
     */
    public function setAjax(bool $isAjax): void
    {
        $this->isAjax = $isAjax;
    }

    /**
     * Returns the singleton object
     */
    public static function getInstance(): ResponseRenderer
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $console = ContainerBuilder::getContainer()->get(Console::class);

        $config = Config::getInstance();
        $template = new Template($config);
        $dbi = DatabaseInterface::getInstance();

        self::$instance = new ResponseRenderer(
            $config,
            $template,
            new Header($template, $console, $config),
            new Footer($template, $config),
            ErrorHandler::getInstance(),
            $dbi,
            ResponseFactory::create(),
        );

        return self::$instance;
    }

    /**
     * Set the status of an ajax response,
     * whether it is a success or an error
     *
     * @param bool $state Whether the request was successfully processed
     */
    public function setRequestStatus(bool $state): void
    {
        $this->isSuccess = $state;
    }

    /**
     * Returns true or false depending on whether
     * we are servicing an ajax request
     */
    public function isAjax(): bool
    {
        return $this->isAjax;
    }

    /**
     * Returns a PhpMyAdmin\Header object
     */
    public function getHeader(): Header
    {
        return $this->header;
    }

    /**
     * Append HTML code to the current output buffer
     */
    public function addHTML(string $content): void
    {
        $this->HTML .= $content;
    }

    /**
     * Add JSON code to the response
     *
     * @param string|int|mixed[] $json  Either a key (string) or an array or key-value pairs
     * @param mixed|null         $value Null, if passing an array in $json otherwise
     *                                  it's a string value to the key
     */
    public function addJSON(string|int|array $json, mixed $value = null): void
    {
        if (is_array($json)) {
            foreach ($json as $key => $value) {
                $this->addJSON($key, $value);
            }
        } elseif ($value instanceof Message) {
            $this->JSON[$json] = $value->getDisplay();
        } else {
            $this->JSON[$json] = $value;
        }
    }

    /**
     * Renders the HTML response text
     */
    private function getDisplay(): string
    {
        return $this->template->render('base', [
            'header' => $this->header->getDisplay(),
            'content' => $this->HTML,
            'footer' => $this->footer->getDisplay(),
        ]);
    }

    /**
     * Sends a JSON response to the browser
     */
    private function ajaxResponse(): string
    {
        if (! isset($this->JSON['message'])) {
            $this->JSON['message'] = $this->HTML;
        } elseif ($this->JSON['message'] instanceof Message) {
            $this->JSON['message'] = $this->JSON['message']->getDisplay();
        }

        if ($this->isSuccess) {
            $this->JSON['success'] = true;
        } else {
            $this->JSON['success'] = false;
            $this->JSON['error'] = $this->JSON['message'];
            unset($this->JSON['message']);
        }

        if ($this->isSuccess) {
            if (! isset($this->JSON['title'])) {
                $this->addJSON('title', '<title>' . $this->getHeader()->getPageTitle() . '</title>');
            }

            if ($this->dbi->isConnected()) {
                $this->addJSON('menu', $this->getHeader()->getMenu()->getDisplay());
            }

            $this->addJSON('scripts', $this->getHeader()->getScripts()->getFiles());
            $this->addJSON('selflink', $this->footer->getSelfUrl());
            $this->addJSON('displayMessage', $this->getHeader()->getMessage());

            $debug = $this->footer->getDebugMessage();
            if (empty($_REQUEST['no_debug']) && $debug !== '') {
                $this->addJSON('debug', $debug);
            }

            $errors = $this->footer->getErrorMessages();
            if ($errors !== '') {
                $this->addJSON('errors', $errors);
            }

            $promptPhpErrors = $this->errorHandler->hasErrorsForPrompt();
            $this->addJSON('promptPhpErrors', $promptPhpErrors);

            // set current db, table and sql query in the querywindow
            // (this is for the bottom console)
            $query = '';
            $maxChars = $this->config->settings['MaxCharactersInDisplayedSQL'];
            if (mb_strlen(Current::$sqlQuery) < $maxChars) {
                $query = Current::$sqlQuery;
            }

            $this->addJSON(
                'reloadQuerywindow',
                ['db' => Current::$database, 'table' => Current::$table, 'sql_query' => $query],
            );

            if (self::$reload) {
                $this->addJSON('reloadNavigation', 1);
            }

            $this->addJSON('params', $this->getHeader()->getJsParams());
        }

        $result = json_encode($this->JSON);
        if ($result === false) {
            return (string) json_encode([
                'success' => false,
                'error' => 'JSON encoding failed: ' . json_last_error_msg(),
            ]);
        }

        return $result;
    }

    public function response(): Response
    {
        if ($this->isAjax()) {
            $headers = Core::headerJSON();
            $body = $this->ajaxResponse();
        } else {
            $headers = $this->header->getHttpHeaders();
            $body = $this->getDisplay();
        }

        foreach ($headers as $name => $value) {
            $this->response = $this->response->withHeader($name, $value);
        }

        return $this->response->write($body);
    }

    public function addHeader(string $name, string $value): void
    {
        $this->response = $this->response->withHeader($name, $value);
    }

    /** @psalm-param StatusCodeInterface::STATUS_* $code */
    public function setStatusCode(int $code): void
    {
        if (isset(static::$httpStatusMessages[$code])) {
            $this->response = $this->response->withStatus($code, static::$httpStatusMessages[$code]);
        } else {
            $this->response = $this->response->withStatus($code);
        }
    }

    /**
     * Configures response for the login page
     *
     * @return bool Whether caller should exit
     */
    public function loginPage(): bool
    {
        /* Handle AJAX redirection */
        if ($this->isAjax()) {
            $this->setRequestStatus(false);
            // redirect_flag redirects to the login page
            $this->addJSON('redirect_flag', '1');

            return true;
        }

        $this->setMinimalFooter();
        $header = $this->getHeader();
        $header->setBodyId('loginform');
        $header->setTitle('phpMyAdmin');
        $header->disableMenuAndConsole();
        $header->disableWarnings();

        return false;
    }

    public function setMinimalFooter(): void
    {
        $this->footer->setMinimal();
    }

    public function getSelfUrl(): string
    {
        return $this->footer->getSelfUrl();
    }

    public function getFooterScripts(): Scripts
    {
        return $this->footer->getScripts();
    }

    public function callExit(): never
    {
        throw new ExitException();
    }

    /**
     * Avoid relative path redirect problems in case user entered URL
     * like /phpmyadmin/index.php/ which some web servers happily accept.
     */
    public function fixRelativeUrlForRedirect(string $url): string
    {
        if (! str_starts_with($url, '.')) {
            return $url;
        }

        return $this->config->getRootPath() . substr($url, 2);
    }

    /**
     * @psalm-param non-empty-string $url
     * @psalm-param StatusCodeInterface::STATUS_* $statusCode
     */
    public function redirect(string $url, int $statusCode = StatusCodeInterface::STATUS_FOUND): void
    {
        $this->addHeader('Location', $this->fixRelativeUrlForRedirect($url));
        $this->setStatusCode($statusCode);
    }

    /** @param array<string, mixed> $params */
    public function redirectToRoute(string $route, array $params = []): Response
    {
        $this->redirect('./index.php?route=' . $route . Url::getCommonRaw($params, '&'));

        return $this->response();
    }

    /** @psalm-param list<string> $files */
    public function addScriptFiles(array $files): void
    {
        $this->getHeader()->getScripts()->addFiles($files);
    }

    /** @param non-empty-string $param */
    public function missingParameterError(string $param): Response
    {
        $errorMessage =
            __('Missing parameter:') . ' '
            . $param
            . MySQLDocumentation::showDocumentation('faq', 'faqmissingparameters', true)
            . '[br]';
        $this->setStatusCode(StatusCodeInterface::STATUS_BAD_REQUEST);
        $this->setRequestStatus(false);
        $this->addHTML(Message::error($errorMessage)->getDisplay());

        return $this->response();
    }

    /** @param array<string, mixed> $templateData */
    public function render(string $templatePath, array $templateData = []): void
    {
        $this->addHTML($this->template->render($templatePath, $templateData));
    }
}
