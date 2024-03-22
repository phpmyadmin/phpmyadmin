<?php

declare(strict_types=1);

namespace PhpMyAdmin\Error;

use ErrorException;
use Fig\Http\Message\StatusCodeInterface;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use PhpMyAdmin\Config;
use PhpMyAdmin\Current;
use PhpMyAdmin\Exceptions\ExitException;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Url;
use Throwable;

use function __;
use function array_splice;
use function count;
use function defined;
use function error_reporting;
use function function_exists;
use function htmlspecialchars;
use function sprintf;

use const E_COMPILE_ERROR;
use const E_COMPILE_WARNING;
use const E_CORE_ERROR;
use const E_CORE_WARNING;
use const E_DEPRECATED;
use const E_ERROR;
use const E_NOTICE;
use const E_PARSE;
use const E_RECOVERABLE_ERROR;
use const E_STRICT;
use const E_USER_DEPRECATED;
use const E_USER_ERROR;
use const E_USER_NOTICE;
use const E_USER_WARNING;
use const E_WARNING;

/**
 * handling errors
 */
class ErrorHandler
{
    public static self|null $instance = null;

    /**
     * holds errors to be displayed or reported later ...
     *
     * @var Error[]
     */
    protected array $errors = [];

    /**
     * Hide location of errors
     */
    protected bool $hideLocation = false;

    /**
     * Initial error reporting state
     */
    protected int $errorReporting = 0;

    public function __construct()
    {
        if (! function_exists('error_reporting')) {
            return;
        }

        $this->errorReporting = error_reporting();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Destructor
     *
     * stores errors in session
     */
    public function __destruct()
    {
        if (! isset($_SESSION['errors'])) {
            $_SESSION['errors'] = [];
        }

        // remember only not displayed errors
        foreach ($this->errors as $key => $error) {
            /**
             * We don't want to store all errors here as it would
             * explode user session.
             */
            if (count($_SESSION['errors']) >= 10) {
                $error = new Error(
                    0,
                    __('Too many error messages, some are not displayed.'),
                    __FILE__,
                    __LINE__,
                );
                $_SESSION['errors'][$error->getHash()] = $error;
                break;
            }

            if ($error->isDisplayed()) {
                continue;
            }

            $_SESSION['errors'][$key] = $error;
        }
    }

    /**
     * Toggles location hiding
     *
     * @param bool $hide Whether to hide
     */
    public function setHideLocation(bool $hide): void
    {
        $this->hideLocation = $hide;
    }

    /**
     * returns array with all errors
     *
     * @param bool $check Whether to check for session errors
     *
     * @return Error[]
     */
    public function getErrors(bool $check = true): array
    {
        if ($check) {
            $this->checkSavedErrors();
        }

        return $this->errors;
    }

    /**
     * returns the errors occurred in the current run only.
     * Does not include the errors saved in the SESSION
     *
     * @return Error[]
     */
    public function getCurrentErrors(): array
    {
        return $this->errors;
    }

    /**
     * Pops recent errors from the storage
     *
     * @param int $count Old error count (amount of errors to splice)
     *
     * @return Error[] The non spliced elements (total-$count)
     */
    public function sliceErrors(int $count): array
    {
        // store the errors before any operation, example number of items: 10
        $errors = $this->getErrors(false);

        // before array_splice $this->errors has 10 elements
        // cut out $count items out, let's say $count = 9
        // $errors will now contain 10 - 9 = 1 elements
        // $this->errors will contain the 9 elements left
        $this->errors = array_splice($errors, 0, $count);

        return $errors;
    }

    /**
     * Error handler - called when errors are triggered/occurred
     *
     * This calls the addError() function, escaping the error string
     * Ignores the errors wherever Error Control Operator (@) is used.
     *
     * @param int    $errno   error number
     * @param string $errstr  error string
     * @param string $errfile error file
     * @param int    $errline error line
     *
     * @return false
     *
     * @throws ErrorException
     */
    public function handleError(
        int $errno,
        string $errstr,
        string $errfile,
        int $errline,
    ): bool {
        if (function_exists('error_reporting')) {
            /**
             * Check if Error Control Operator (@) was used, but still show
             * user errors even in this case.
             * See: https://github.com/phpmyadmin/phpmyadmin/issues/16729
             */
            $isSilenced = (error_reporting() & $errno) === 0;

            $config = Config::getInstance();
            if (
                isset($config->settings['environment'])
                && $config->settings['environment'] === 'development'
                && ! $isSilenced
            ) {
                throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
            }

            if (
                $isSilenced &&
                $this->errorReporting != 0 &&
                ($errno & (E_USER_WARNING | E_USER_ERROR | E_USER_NOTICE | E_USER_DEPRECATED)) === 0
            ) {
                return false;
            }
        } elseif (($errno & (E_USER_WARNING | E_USER_ERROR | E_USER_NOTICE | E_USER_DEPRECATED)) === 0) {
            return false;
        }

        $this->addError($errstr, $errno, $errfile, $errline);

        return false;
    }

    /**
     * Hides exception if it's not in the development environment.
     */
    public function handleException(Throwable $exception): void
    {
        $this->hideLocation = Config::getInstance()->get('environment') !== 'development';
        $this->addError(
            $exception::class . ': ' . $exception->getMessage(),
            (int) $exception->getCode(),
            $exception->getFile(),
            $exception->getLine(),
        );
    }

    /**
     * Add an error; can also be called directly (with or without escaping)
     *
     * The following error types cannot be handled with a user defined function:
     * E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR,
     * E_COMPILE_WARNING,
     * and most of E_STRICT raised in the file where set_error_handler() is called.
     *
     * Do not use the context parameter as we want to avoid storing the
     * complete $GLOBALS inside $_SESSION['errors']
     *
     * @param string $errstr  error string
     * @param int    $errno   error number
     * @param string $errfile error file
     * @param int    $errline error line
     * @param bool   $escape  whether to escape the error string
     */
    public function addError(
        string $errstr,
        int $errno,
        string $errfile,
        int $errline,
        bool $escape = true,
    ): void {
        if ($escape) {
            $errstr = htmlspecialchars($errstr);
        }

        // create error object
        $error = new Error($errno, $errstr, $errfile, $errline);
        $error->setHideLocation($this->hideLocation);

        // Deprecation errors will be shown in development environment, as they will have a different number.
        if ($error->getErrorNumber() !== E_DEPRECATED) {
            // do not repeat errors
            $this->errors[$error->getHash()] = $error;
        }

        switch ($error->getErrorNumber()) {
            case E_STRICT:
            case E_DEPRECATED:
            case E_NOTICE:
            case E_WARNING:
            case E_CORE_WARNING:
            case E_COMPILE_WARNING:
            case E_RECOVERABLE_ERROR:
                /* Avoid rendering BB code in PHP errors */
                $error->setBBCode(false);
                break;
            case E_USER_NOTICE:
            case E_USER_WARNING:
            case E_USER_ERROR:
            case E_USER_DEPRECATED:
                // just collect the error
                // display is called from outside
                break;
            case E_ERROR:
            case E_PARSE:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            default:
                // FATAL error, display it and exit
                $this->dispFatalError($error);
        }
    }

    /**
     * display fatal error and exit
     *
     * @param Error $error the error
     */
    protected function dispFatalError(Error $error): never
    {
        $response = ResponseFactory::create()->createResponse(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR);
        $response->getBody()->write(sprintf(
            "<!DOCTYPE html>\n<html lang=\"en\">\n<head><title>%s</title></head>\n<body>\n%s\n</body>\n</html>",
            $error->getTitle(),
            $error->getDisplay(),
        ));

        (new SapiEmitter())->emit($response);

        if (defined('TESTSUITE')) {
            throw new ExitException();
        }

        exit;
    }

    /**
     * Displays user errors not displayed
     */
    public function dispUserErrors(): void
    {
        echo $this->getDispUserErrors();
    }

    /**
     * Renders user errors not displayed
     */
    public function getDispUserErrors(): string
    {
        $retval = '';
        foreach ($this->getErrors() as $error) {
            if (! $error->isUserError() || $error->isDisplayed()) {
                continue;
            }

            $retval .= $error->getDisplay();
        }

        return $retval;
    }

    /**
     * renders errors not displayed
     */
    public function getDispErrors(): string
    {
        $retval = '';
        // display errors if SendErrorReports is set to 'ask'.
        $config = Config::getInstance();
        if ($config->settings['SendErrorReports'] !== 'never') {
            foreach ($this->getErrors() as $error) {
                if ($error->isDisplayed()) {
                    continue;
                }

                $retval .= $error->getDisplay();
            }
        } else {
            $retval .= $this->getDispUserErrors();
        }

        // if preference is not 'never' and
        // there are 'actual' errors to be reported
        if ($config->settings['SendErrorReports'] !== 'never' && $this->countErrors() !== $this->countUserErrors()) {
            // add report button.
            $retval .= '<form method="post" action="' . Url::getFromRoute('/error-report')
                    . '" id="pma_report_errors_form"';
            if ($config->settings['SendErrorReports'] === 'always') {
                // in case of 'always', generate 'invisible' form.
                $retval .= ' class="hide"';
            }

            $retval .= '>';
            $retval .= Url::getHiddenFields([
                'exception_type' => 'php',
                'send_error_report' => '1',
                'server' => Current::$server,
            ]);
            $retval .= '<input type="submit" value="'
                    . __('Report')
                    . '" id="pma_report_errors" class="btn btn-primary float-end">'
                    . '<input type="checkbox" name="always_send"'
                    . ' id="errorReportAlwaysSendCheckbox" value="true">'
                    . '<label for="errorReportAlwaysSendCheckbox">'
                    . __('Automatically send report next time')
                    . '</label>';

            if ($config->settings['SendErrorReports'] === 'ask') {
                // add ignore buttons
                $retval .= '<input type="submit" value="'
                        . __('Ignore')
                        . '" id="pma_ignore_errors_bottom" class="btn btn-secondary float-end">';
            }

            $retval .= '<input type="submit" value="'
                    . __('Ignore All')
                    . '" id="pma_ignore_all_errors_bottom" class="btn btn-secondary float-end">';
            $retval .= '</form>';
        }

        return $retval;
    }

    /**
     * look in session for saved errors
     */
    protected function checkSavedErrors(): void
    {
        if (! isset($_SESSION['errors'])) {
            return;
        }

        // restore saved errors
        foreach ($_SESSION['errors'] as $hash => $error) {
            if (! $error instanceof Error || isset($this->errors[$hash])) {
                continue;
            }

            $this->errors[$hash] = $error;
        }

        // delete stored errors
        $_SESSION['errors'] = [];
        unset($_SESSION['errors']);
    }

    /**
     * return count of errors
     *
     * @param bool $check Whether to check for session errors
     *
     * @return int number of errors occurred
     */
    public function countErrors(bool $check = true): int
    {
        return count($this->getErrors($check));
    }

    /**
     * return count of user errors
     *
     * @return int number of user errors occurred
     */
    public function countUserErrors(): int
    {
        $count = 0;
        if ($this->countErrors() !== 0) {
            foreach ($this->getErrors() as $error) {
                if (! $error->isUserError()) {
                    continue;
                }

                $count++;
            }
        }

        return $count;
    }

    /**
     * whether use errors occurred or not
     */
    public function hasUserErrors(): bool
    {
        return (bool) $this->countUserErrors();
    }

    /**
     * whether errors occurred or not
     */
    public function hasErrors(): bool
    {
        return (bool) $this->countErrors();
    }

    /**
     * number of errors to be displayed
     *
     * @return int number of errors to be displayed
     */
    public function countDisplayErrors(): int
    {
        if (Config::getInstance()->settings['SendErrorReports'] !== 'never') {
            return $this->countErrors();
        }

        return $this->countUserErrors();
    }

    /**
     * whether there are errors to display or not
     */
    public function hasDisplayErrors(): bool
    {
        return (bool) $this->countDisplayErrors();
    }

    /**
     * Deletes previously stored errors in SESSION.
     * Saves current errors in session as previous errors.
     * Required to save current errors in case  'ask'
     */
    public function savePreviousErrors(): void
    {
        unset($_SESSION['prev_errors']);
        $_SESSION['prev_errors'] = $this->getCurrentErrors();
    }

    /**
     * Function to check if there are any errors to be prompted.
     * Needed because user warnings raised are
     *      also collected by global error handler.
     * This distinguishes between the actual errors
     *      and user errors raised to warn user.
     */
    public function hasErrorsForPrompt(): bool
    {
        return Config::getInstance()->settings['SendErrorReports'] !== 'never'
            && $this->countErrors() !== $this->countUserErrors();
    }

    /**
     * Function to report all the collected php errors.
     * Must be called at the end of each script
     *      by the {@see getInstance()} only.
     */
    public function reportErrors(): void
    {
        // if there're no actual errors,
        if (! $this->hasErrors() || $this->countErrors() === $this->countUserErrors()) {
            // then simply return.
            return;
        }

        // Delete all the prev_errors in session & store new prev_errors in session
        $this->savePreviousErrors();
        $response = ResponseRenderer::getInstance();
        $jsCode = '';
        $config = Config::getInstance();
        if ($config->settings['SendErrorReports'] === 'always') {
            if ($response->isAjax()) {
                // set flag for automatic report submission.
                $response->addJSON('sendErrorAlways', '1');
            } else {
                // send the error reports asynchronously & without asking user
                $jsCode .= '$("#pma_report_errors_form").submit();'
                        . 'window.ajaxShowMessage(
                            window.Messages.phpErrorsBeingSubmitted, false
                        );';
                // js code to appropriate focusing,
                $jsCode .= '$("html, body").animate({
                                scrollTop:$(document).height()
                            }, "slow");';
            }
        } elseif ($config->settings['SendErrorReports'] === 'ask') {
            //ask user whether to submit errors or not.
            if (! $response->isAjax()) {
                // js code to show appropriate msgs, event binding & focusing.
                $jsCode = 'window.ajaxShowMessage(window.Messages.phpErrorsFound);'
                        . '$("#pma_ignore_errors_popup").on("click", function() {
                            window.ignorePhpErrors()
                        });'
                        . '$("#pma_ignore_all_errors_popup").on("click",
                            function() {
                                window.ignorePhpErrors(false)
                            });'
                        . '$("#pma_ignore_errors_bottom").on("click", function(e) {
                            e.preventDefault();
                            window.ignorePhpErrors()
                        });'
                        . '$("#pma_ignore_all_errors_bottom").on("click",
                            function(e) {
                                e.preventDefault();
                                window.ignorePhpErrors(false)
                            });'
                        . '$("html, body").animate({
                            scrollTop:$(document).height()
                        }, "slow");';
            }
        }

        // The errors are already sent from the response.
        // Just focus on errors division upon load event.
        $response->getFooterScripts()->addCode($jsCode);
    }
}
