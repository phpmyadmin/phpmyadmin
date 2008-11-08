<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds class PMA_Error_Handler
 *
 * @version $Id$
 */

/**
 *
 */
require_once './libraries/Error.class.php';

/**
 * handling errors
 *
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
     * @uses    set_error_handler()
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
     * @uses    $_SESSION['errors']
     * @uses    array_merge()
     * @uses    PMA_Error_Handler::$_errors
     * @uses    PMA_Error::isDisplayed()
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
                    if (! $error->isDisplayed()) {
                        $_SESSION['errors'][$key] = $error;
                    }
                }
            }
        }
    }

    /**
     * returns array with all errors
     *
     * @uses    PMA_Error_Handler::$_errors as return value
     * @uses    PMA_Error_Handler::_checkSavedErrors()
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
     * The following error types cannot be handled with a user defined function:
     * E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR,
     * E_COMPILE_WARNING,
     * and most of E_STRICT raised in the file where set_error_handler() is called.
     *
     * @uses    E_USER_NOTICE
     * @uses    E_USER_WARNING
     * @uses    E_STRICT
     * @uses    E_NOTICE
     * @uses    E_WARNING
     * @uses    E_CORE_WARNING
     * @uses    E_COMPILE_WARNING
     * @uses    E_USER_ERROR
     * @uses    E_ERROR
     * @uses    E_PARSE
     * @uses    E_CORE_ERROR
     * @uses    E_COMPILE_ERROR
     * @uses    E_RECOVERABLE_ERROR
     * @uses    PMA_Error
     * @uses    PMA_Error_Handler::$_errors
     * @uses    PMA_Error_Handler::_dispFatalError()
     * @uses    PMA_Error::getHash()
     * @uses    PMA_Error::getNumber()
     * @param   integer $errno
     * @param   string  $errstr
     * @param   string  $errfile
     * @param   integer $errline
     * @param   array   $errcontext
     */
    public function handleError($errno, $errstr, $errfile, $errline, $errcontext)
    {
        // create error object
        $error = new PMA_Error($errno, $errstr, $errfile, $errline, $errcontext);

        // do not repeat errors
        $this->_errors[$error->getHash()] = $error;

        switch ($error->getNumber()) {
            case E_USER_NOTICE:
            case E_USER_WARNING:
            case E_STRICT:
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
     * @uses    PMA_Error::getMessage()
     * @uses    error_log()
     * @param   PMA_Error $error
     */
    protected function _logError($error)
    {
        return error_log($error->getMessage());
    }

    /**
     * trigger a custom error
     *
     * @uses    trigger_error()
     * @param   string  $errorInfo
     * @param   integer $errorNumber
     * @param   string  $file
     * @param   integer $line
     */
    public function triggerError($errorInfo, $errorNumber = null, $file = null, $line = null)
    {
        // we could also extract file and line from backtrace and call handleError() directly
        trigger_error($errorInfo, $errorNumber);
    }

    /**
     * display fatal error and exit
     *
     * @uses    headers_sent()
     * @uses    PMA_Error::display()
     * @uses    PMA_Error_Handler::_dispPageStart()
     * @uses    PMA_Error_Handler::_dispPageEnd()
     * @param   PMA_Error $error
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
     * @uses    headers_sent()
     * @uses    PMA_Error_Handler::dispAllErrors()
     * @uses    PMA_Error_Handler::_dispPageStart()
     * @uses    PMA_Error_Handler::_dispPageEnd()
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
     * @uses    PMA_Error_Handler::getErrors()
     * @uses    PMA_Error::isDisplayed()
     * @uses    PMA_Error::isUserError()
     * @uses    PMA_Error::display()
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
     * @uses    PMA_Error::getTitle()
     * @param   PMA_error $error
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
     * @uses    PMA_Error_Handler::getErrors()
     * @uses    PMA_Error::display()
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
     * @uses    $cfg['Error_Handler']['display']
     * @uses    PMA_Error_Handler::getErrors()
     * @uses    PMA_Error_Handler::dispUserErrors()
     * @uses    PMA_Error::isDisplayed()
     * @uses    PMA_Error::display()
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
     * @uses    $_SESSION['errors']
     * @uses    PMA_Error_Handler::$_errors
     * @uses    array_merge()
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
     * @uses    PMA_Error_Handler::getErrors()
     * @uses    count()
     * @return  integer number of errors occoured
     */
    public function countErrors()
    {
        return count($this->getErrors());
    }

    /**
     * return count of user errors
     *
     * @uses    PMA_Error_Handler::countErrors()
     * @uses    PMA_Error_Handler::getErrors()
     * @uses    PMA_Error::isUserError()
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
     * @uses    PMA_Error_Handler::countUserErrors()
     * @return  boolean
     */
    public function hasUserErrors()
    {
        return (bool) $this->countUserErrors();
    }

    /**
     * whether errors occured or not
     *
     * @uses    PMA_Error_Handler::countErrors()
     * @return  boolean
     */
    public function hasErrors()
    {
        return (bool) $this->countErrors();
    }

    /**
     * number of errors to be displayed
     *
     * @uses    $cfg['Error_Handler']['display']
     * @uses    PMA_Error_Handler::countErrors()
     * @uses    PMA_Error_Handler::countUserErrors()
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
     * @uses    PMA_Error_Handler::countDisplayErrors()
     * @return boolean
     */
    public function hasDisplayErrors()
    {
        return (bool) $this->countDisplayErrors();
    }
}
?>
