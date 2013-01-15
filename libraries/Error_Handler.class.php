<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds class PMA_Error_Handler
 *
 * @package PhpMyAdmin
 */

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
    protected $_errors = array();

    /**
     * Constructor - set PHP error handler
     *
     */
    public function __construct()
    {
        set_error_handler(array($this, 'handleError'));
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

            if ($GLOBALS['cfg']['Error_Handler']['gather']) {
                // remember all errors
                $_SESSION['errors'] = array_merge($_SESSION['errors'], $this->_errors);
            } else {
                // remember only not displayed errors
                foreach ($this->_errors as $key => $error) {
                    /**
                     * We don't want to store all errors here as it would explode user
                     * session. In case  you want them all set
                     * $GLOBALS['cfg']['Error_Handler']['gather'] to true
                     */
                    if (count($_SESSION['errors']) >= 20) {
                        $error = new PMA_Error(0, __('Too many error messages, some are not displayed.'), __FILE__, __LINE__);
                        $_SESSION['errors'][$error->getHash()] = $error;
                    }
                    if (($error instanceof PMA_Error) && ! $error->isDisplayed()) {
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
        $this->_checkSavedErrors();
        return $this->_errors;
    }

    /**
     * Error handler - called when errors are triggered/occured
     *
     * This calls the addError() function, escaping the error string
     *
     * @param integer $errno
     * @param string  $errstr
     * @param string  $errfile
     * @param integer $errline
     */
    public function handleError($errno, $errstr, $errfile, $errline)
    {
        $this->addError($errstr, $errno, $errfile, $errline, $escape=true);
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
     * @param integer $errno
     * @param string  $errstr
     * @param string  $errfile
     * @param integer $errline
     * @param boolean $escape
     */
    public function addError($errstr, $errno, $errfile, $errline, $escape=true)
    {
        if ($escape) {
            $errstr = htmlspecialchars($errstr);
        }
        // create error object
        $error = new PMA_Error($errno, $errstr, $errfile, $errline);

        // do not repeat errors
        $this->_errors[$error->getHash()] = $error;

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
                $this->_dispFatalError($error);
                exit;
                break;
        }
    }


    /**
     * log error to configured log facility
     *
     * @todo    finish!
     * @param PMA_Error $error
     * @return bool
     */
    protected function _logError($error)
    {
        return error_log($error->getMessage());
    }

    /**
     * trigger a custom error
     *
     * @param string  $errorInfo
     * @param integer $errorNumber
     * @param string  $file
     * @param integer $line
     */
    public function triggerError($errorInfo, $errorNumber = null, $file = null, $line = null)
    {
        // we could also extract file and line from backtrace and call handleError() directly
        trigger_error($errorInfo, $errorNumber);
    }

    /**
     * display fatal error and exit
     *
     * @param PMA_Error $error
     */
    protected function _dispFatalError($error)
    {
        if (! headers_sent()) {
            $this->_dispPageStart($error);
        }
        $error->display();
        $this->_dispPageEnd();
        exit;
    }

    /**
     * display the whole error page with all errors
     *
     */
    public function dispErrorPage()
    {
        if (! headers_sent()) {
            $this->_dispPageStart();
        }
        $this->dispAllErrors();
        $this->_dispPageEnd();
    }

    /**
     * display user errors not displayed
     *
     */
    public function dispUserErrors()
    {
        foreach ($this->getErrors() as $error) {
            if ($error->isUserError() && ! $error->isDisplayed()) {
                $error->display();
            }
        }
    }

    /**
     * display HTML header
     *
     * @param PMA_error $error
     */
    protected function _dispPageStart($error = null)
    {
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
     */
    protected function _dispPageEnd()
    {
        echo '</body></html>';
    }

    /**
     * display all errors regardless already displayed or user errors
     *
     */
    public function dispAllErrors()
    {
        foreach ($this->getErrors() as $error) {
            $error->display();
        }
    }

    /**
     * display errors not displayed
     *
     */
    public function dispErrors()
    {
        if ($GLOBALS['cfg']['Error_Handler']['display']) {
            foreach ($this->getErrors() as $error) {
                if ($error instanceof PMA_Error) {
                    if (! $error->isDisplayed()) {
                        $error->display();
                    }
                } else {
                    var_dump($error);
                }
            }
        } else {
            $this->dispUserErrors();
        }
    }

    /**
     * look in session for saved errors
     *
     */
    protected function _checkSavedErrors()
    {
        if (isset($_SESSION['errors'])) {

            // restore saved errors
            foreach ($_SESSION['errors'] as $hash => $error) {
                if ($error instanceof PMA_Error && ! isset($this->_errors[$hash])) {
                    $this->_errors[$hash] = $error;
                }
            }
            //$this->_errors = array_merge($_SESSION['errors'], $this->_errors);

            // delet stored errors
            $_SESSION['errors'] = array();
            unset($_SESSION['errors']);
        }
    }

    /**
     * return count of errors
     *
     * @return  integer number of errors occoured
     */
    public function countErrors()
    {
        return count($this->getErrors());
    }

    /**
     * return count of user errors
     *
     * @return  integer number of user errors occoured
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
     * @return  boolean
     */
    public function hasUserErrors()
    {
        return (bool) $this->countUserErrors();
    }

    /**
     * whether errors occured or not
     *
     * @return  boolean
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
