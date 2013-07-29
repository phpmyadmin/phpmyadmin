<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds class PMA_Error_Handler
 *
 * @package PhpMyAdmin
 */

if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 *
 */
require_once './libraries/Error.class.php';

/**
 * handling errors
 *
 * @package PhpMyAdmin
 */
class PMA_Error_Handler
{
    /**
     * holds errors to be displayed or reported later ...
     *
     * @var array of PMA_Error
     */
    protected $errors = array();

    /**
     * Constructor - set PHP error handler
     *
     */
    public function __construct()
    {
        /**
         * Do not set ourselves as error handler in case of testsuite.
         *
         * This behavior is not tested there and breaks other tests as they
         * rely on PHPUnit doing it's own error handling which we break here.
         */
        if (!defined('TESTSUITE')) {
            set_error_handler(array($this, 'handleError'));
        }
    }

    /**
     * Destructor
     *
     * stores errors in session
     *
     */
    public function __destruct()
    {
        if (isset($_SESSION)) {
            if (! isset($_SESSION['errors'])) {
                $_SESSION['errors'] = array();
            }

            if (isset($GLOBALS['cfg']['Error_Handler'])
                && $GLOBALS['cfg']['Error_Handler']['gather']
            ) {
                // remember all errors
                $_SESSION['errors'] = array_merge(
                    $_SESSION['errors'],
                    $this->errors
                );
            } else {
                // remember only not displayed errors
                foreach ($this->errors as $key => $error) {
                    /**
                     * We don't want to store all errors here as it would
                     * explode user session. In case  you want them all set
                     * $GLOBALS['cfg']['Error_Handler']['gather'] to true
                     */
                    if (count($_SESSION['errors']) >= 20) {
                        $error = new PMA_Error(
                            0,
                            __('Too many error messages, some are not displayed.'),
                            __FILE__,
                            __LINE__
                        );
                        $_SESSION['errors'][$error->getHash()] = $error;
                        break;
                    } else if (($error instanceof PMA_Error)
                        && ! $error->isDisplayed()
                    ) {
                        $_SESSION['errors'][$key] = $error;
                    }
                }
            }
        }
    }

    /**
     * returns array with all errors
     *
     * @return array PMA_Error_Handler::$_errors
     */
    protected function getErrors()
    {
        $this->checkSavedErrors();
        return $this->errors;
    }

    /**
     * Error handler - called when errors are triggered/occured
     *
     * This calls the addError() function, escaping the error string
     *
     * @param integer $errno   error number
     * @param string  $errstr  error string
     * @param string  $errfile error file
     * @param integer $errline error line
     *
     * @return void
     */
    public function handleError($errno, $errstr, $errfile, $errline)
    {
        $this->addError($errstr, $errno, $errfile, $errline, true);
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
     * @param string  $errstr  error string
     * @param integer $errno   error number
     * @param string  $errfile error file
     * @param integer $errline error line
     * @param boolean $escape  whether to escape the error string
     *
     * @return void
     */
    public function addError($errstr, $errno, $errfile, $errline, $escape = true)
    {
        if ($escape) {
            $errstr = htmlspecialchars($errstr);
        }
        // create error object
        $error = new PMA_Error(
            $errno,
            $errstr,
            $errfile,
            $errline
        );

        // do not repeat errors
        $this->errors[$error->getHash()] = $error;

        switch ($error->getNumber()) {
        case E_USER_NOTICE:
        case E_USER_WARNING:
        case E_STRICT:
        case E_DEPRECATED:
        case E_NOTICE:
        case E_WARNING:
        case E_CORE_WARNING:
        case E_COMPILE_WARNING:
        case E_USER_ERROR:
        case E_RECOVERABLE_ERROR:
            // just collect the error
            // display is called from outside
            break;
        case E_ERROR:
        case E_PARSE:
        case E_CORE_ERROR:
        case E_COMPILE_ERROR:
        default:
            // FATAL error, dislay it and exit
            $this->dispFatalError($error);
            exit;
            break;
        }
    }


    /**
     * log error to configured log facility
     *
     * @param PMA_Error $error the error
     *
     * @return bool
     *
     * @todo finish!
     */
    protected function logError($error)
    {
        return error_log($error->getMessage());
    }

    /**
     * trigger a custom error
     *
     * @param string  $errorInfo   error message
     * @param integer $errorNumber error number
     * @param string  $file        file name
     * @param integer $line        line number
     *
     * @return void
     */
    public function triggerError($errorInfo, $errorNumber = null,
        $file = null, $line = null
    ) {
        // we could also extract file and line from backtrace
        // and call handleError() directly
        trigger_error($errorInfo, $errorNumber);
    }

    /**
     * display fatal error and exit
     *
     * @param PMA_Error $error the error
     *
     * @return void
     */
    protected function dispFatalError($error)
    {
        if (! headers_sent()) {
            $this->dispPageStart($error);
        }
        $error->display();
        $this->dispPageEnd();
        exit;
    }

    /**
     * display the whole error page with all errors
     *
     * @return void
     */
    public function dispErrorPage()
    {
        if (! headers_sent()) {
            $this->dispPageStart();
        }
        $this->dispAllErrors();
        $this->dispPageEnd();
    }

    /**
     * Displays user errors not displayed
     *
     * @return void
     */
    public function dispUserErrors()
    {
        echo $this->getDispUserErrors();
    }

    /**
     * Renders user errors not displayed
     *
     * @return string
     */
    public function getDispUserErrors()
    {
        $retval = '';
        foreach ($this->getErrors() as $error) {
            if ($error->isUserError() && ! $error->isDisplayed()) {
                $retval .= $error->getDisplay();
            }
        }
        return $retval;
    }

    /**
     * display HTML header
     *
     * @param PMA_error $error the error
     *
     * @return void
     */
    protected function dispPageStart($error = null)
    {
        PMA_Response::getInstance()->disable();
        echo '<html><head><title>';
        if ($error) {
            echo $error->getTitle();
        } else {
            echo 'phpMyAdmin error reporting page';
        }
        echo '</title></head>';
    }

    /**
     * display HTML footer
     *
     * @return void
     */
    protected function dispPageEnd()
    {
        echo '</body></html>';
    }

    /**
     * display all errors regardless already displayed or user errors
     *
     * @return void
     */
    public function dispAllErrors()
    {
        foreach ($this->getErrors() as $error) {
            $error->display();
        }
    }

    /**
     * renders errors not displayed
     *
     * @return void
     */
    public function getDispErrors()
    {
        $retval = '';
        if ($GLOBALS['cfg']['Error_Handler']['display']) {
            foreach ($this->getErrors() as $error) {
                if ($error instanceof PMA_Error) {
                    if (! $error->isDisplayed()) {
                        $retval .= $error->getDisplay();
                    }
                } else {
                    ob_start();
                    var_dump($error);
                    $retval .= ob_get_contents();
                    ob_end_clean();
                }
            }
        } else {
            $retval .= $this->getDispUserErrors();
        }
        return $retval;
    }

    /**
     * displays errors not displayed
     *
     * @return void
     */
    public function dispErrors()
    {
        echo $this->getDispErrors();
    }

    /**
     * look in session for saved errors
     *
     * @return void
     */
    protected function checkSavedErrors()
    {
        if (isset($_SESSION['errors'])) {

            // restore saved errors
            foreach ($_SESSION['errors'] as $hash => $error) {
                if ($error instanceof PMA_Error && ! isset($this->errors[$hash])) {
                    $this->errors[$hash] = $error;
                }
            }
            //$this->errors = array_merge($_SESSION['errors'], $this->errors);

            // delete stored errors
            $_SESSION['errors'] = array();
            unset($_SESSION['errors']);
        }
    }

    /**
     * return count of errors
     *
     * @return integer number of errors occoured
     */
    public function countErrors()
    {
        return count($this->getErrors());
    }

    /**
     * return count of user errors
     *
     * @return integer number of user errors occoured
     */
    public function countUserErrors()
    {
        $count = 0;
        if ($this->countErrors()) {
            foreach ($this->getErrors() as $error) {
                if ($error->isUserError()) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * whether use errors occured or not
     *
     * @return boolean
     */
    public function hasUserErrors()
    {
        return (bool) $this->countUserErrors();
    }

    /**
     * whether errors occured or not
     *
     * @return boolean
     */
    public function hasErrors()
    {
        return (bool) $this->countErrors();
    }

    /**
     * number of errors to be displayed
     *
     * @return integer number of errors to be displayed
     */
    public function countDisplayErrors()
    {
        if ($GLOBALS['cfg']['Error_Handler']['display']) {
            return $this->countErrors();
        } else {
            return $this->countUserErrors();
        }
    }

    /**
     * whether there are errors to display or not
     *
     * @return boolean
     */
    public function hasDisplayErrors()
    {
        return (bool) $this->countDisplayErrors();
    }
}
?>
