<?php /* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Output buffering wrapper
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Output buffering wrapper class
 *
 * @package PhpMyAdmin
 */
class PMA_OutputBuffering
{
    private static $_instance;
    private $_mode;
    private $_contents;
    private $_current;

    private $_gzHandlerActivated = false;

    /**
     * Initializes class
     */
    private function __construct()
    {
        $this->_mode = $this->_getMode();
        $this->_contents = array();
        $this->_current = 0;
    }

    /**
     * This function could be used eventually to support more modes.
     *
     * @return integer  the output buffer mode
     */
    private function _getMode()
    {
        $mode = 0;
        if ($GLOBALS['cfg']['OBGzip'] && function_exists('ob_start')) {
            if (ini_get('output_handler') == 'ob_gzhandler') {
                // If a user sets the output_handler in php.ini to ob_gzhandler, then
                // any right frame file in phpMyAdmin will not be handled properly by
                // the browser. My fix was to check the ini file within the
                // PMA_outBufferModeGet() function.
                $mode = 0;
            } elseif (function_exists('ob_get_level') && ob_get_level() > 0) {
                // happens when php.ini's output_buffering is not Off
                ob_end_clean();
                $mode = 1;
            } else {
                $mode = 1;
            }
        }
        // Zero (0) is no mode or in other words output buffering is OFF.
        // Follow 2^0, 2^1, 2^2, 2^3 type values for the modes.
        // Useful if we ever decide to combine modes.  Then a bitmask field of
        // the sum of all modes will be the natural choice.
        return $mode;
    }

    /**
     * Returns the singleton PMA_OutputBuffering object
     *
     * @return PMA_OutputBuffering object
     */
    public static function getInstance()
    {
        if (empty(self::$_instance)) {
            self::$_instance = new PMA_OutputBuffering();
        }
        return self::$_instance;
    }

    /**
     * This function will need to run at the top of all pages if output
     * output buffering is turned on.  It also needs to be passed $mode from
     * the PMA_outBufferModeGet() function or it will be useless.
     *
     * @return void
     */
    public function start()
    {
        //If output buffering has already started, get content and clean it.
        if (0 !== $this->_current) {
            $this->_contents[$this->_current]['before'] .= $this->getContents();
            $this->_contents[$this->_current++]['before'] .= ob_get_contents();
            ob_clean();
            return;
        }

        //Else, start output buffering.
        if ($this->_mode && function_exists('ob_gzhandler')&& !$this->_gzHandlerActivated) {
            $this->_gzHandlerActivated = true;
            ob_start('ob_gzhandler');
        }
        ob_start();
        if (! defined('TESTSUITE')) {
            header('X-ob_mode: ' . $this->_mode);
        }
        register_shutdown_function('PMA_OutputBuffering::stop');
        $this->_contents[++$this->_current] = array(
            'before' => null,
            'after' => null
        );
    }

    /**
     * This function will need to run at the bottom of all pages if output
     * buffering is turned on.  It also needs to be passed $mode from the
     * PMA_outBufferModeGet() function or it will be useless.
     *
     * @return bool Success
     */
    public static function stop()
    {
        $buffer = PMA_OutputBuffering::getInstance();

        if (0 === $buffer->_current) {
            return false;
        }

        $buffer->_contents[$buffer->_current]['after'] = ob_get_contents();
        ob_clean();

        //If last output buffering, close it.
        if (0 === --$buffer->_current) {
            ob_end_clean();
        }

        return true;
    }

    /**
     * Gets buffer content
     *
     * @return string buffer content
     */
    public function getContents($index = null)
    {
        if (null === $index) {
            $index = $this->_current + 1;
        }

        if (!array_key_exists($index, $this->_contents)) {
            return null;
        }

        $aCurrentContent = $this->_contents[$index];
        $content = array_key_exists('before', $aCurrentContent) ? $aCurrentContent['before'] : null;
        $content .= $this->getContents($index + 1);
        $content .= array_key_exists('after', $aCurrentContent) ? $aCurrentContent['after'] : null;
        unset($this->_contents[$index]);

        return $content;
    }

    /**
     * Flushes output buffer
     *
     * @return void
     */
    public function flush()
    {
        if (ob_get_status() && $this->_mode) {
            ob_flush();
        } else {
            flush();
        }
    }
}

?>
