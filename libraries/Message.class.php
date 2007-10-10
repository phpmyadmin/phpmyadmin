<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds class PMA_Message
 *
 * @version $Id: Error.class.php 10738 2007-10-08 16:02:58Z cybot_tm $
 */

/**
 * a single message
 *
 * Constructor:
 * @param   string  $string
 * @param   integer $number
 * @param   boolean $sanitize
 * @param   scalare $parameters,...
 */
class PMA_Message
{
    const SUCCESS = 1; // 0001
    const NOTICE  = 2; // 0010
    const WARNING = 4; // 0100
    const ERROR   = 8; // 1000

    const SANITIZE_NONE   = 0;  // 0000 0000
    const SANITIZE_STRING = 16; // 0001 0000
    const SANITIZE_PARAMS = 32; // 0010 0000
    const SANITIZE_BOOTH  = 48; // 0011 0000

    /**
     * message levels
     *
     * @var array
     */
    static public $level = array (
        PMA_Message::SUCCESS => 'success',
        PMA_Message::NOTICE  => 'notice',
        PMA_Message::WARNING => 'warning',
        PMA_Message::ERROR   => 'error',
    );

    /**
     * The message number
     *
     * @var integer
     */
    protected $_number = PMA_Message::NOTICE;

    /**
     * The locale string identifier
     *
     * @var string
     */
    protected $_string = '';

    /**
     * The formated message
     *
     * @var string
     */
    protected $_message = '';

    /**
     * Whether the message was already displayed
     *
     * @var boolean
     */
    protected $_is_displayed = false;

    /**
     * Unique id
     *
     * @var string
     */
    protected $_hash = null;

    protected $_params = array();

    /**
     * Constructor
     *
     * @uses    debug_backtrace()
     * @uses    PMA_Message::setNumber()
     * @uses    PMA_Message::setMessage()
     * @param   string  $string
     * @param   integer $number
     * @param   boolean $sanitize
     * @param   scalare $parameters,...
     */
    public function __construct($string = '', $number = PMA_Message::NOTICE,
        $params = array(), $sanitize = PMA_Message::SANITIZE_NONE)
    {
        $this->setString($string, $sanitize & PMA_Message::SANITIZE_STRING);
        $this->setNumber($number);
        $this->setParams($params, $sanitize & PMA_Message::SANITIZE_PARAMS);
    }

    public function __toString()
    {
        return $this->getMessage();
    }

    public function append($message)
    {
        $this->setMessage($this->getMessage() . $message);
    }

    public function isSuccess($set = false)
    {
        if ($set) {
            $this->setNumber(PMA_Message::SUCCESS);
        }

        return $this->getNumber() & PMA_Message::SUCCESS;
    }

    public function isNotice($set = false)
    {
        if ($set) {
            $this->setNumber(PMA_Message::NOTICE);
        }

        return $this->getNumber() & PMA_Message::NOTICE;
    }

    public function isWarning($set = false)
    {
        if ($set) {
            $this->setNumber(PMA_Message::WARNING);
        }

        return $this->getNumber() & PMA_Message::WARNING;
    }

    public function isError($set = false)
    {
        if ($set) {
            $this->setNumber(PMA_Message::ERROR);
        }

        return $this->getNumber() & PMA_Message::ERROR;
    }

    /**
     * sets PMA_Message::$_message
     *
     * @uses    PMA_Message::$_message to set it
     * @param   string  $message
     * @param   boolean $sanitize
     */
    public function setMessage($message, $sanitize = false)
    {
        if ($sanitize) {
            $message = PMA_Message::sanitize($message);
        }
        $this->_message = $message;
    }

    /**
     * sets PMA_Message::$_string
     *
     * @uses    PMA_Message::$_string to set it
     * @param   string  $_string
     */
    public function setString($_string, $sanitize = true)
    {
        if ($sanitize) {
            $_string = PMA_Message::sanitize($_string);
        }
        $this->_string = $_string;
    }

    /**
     * sets PMA_Message::$_number
     *
     * @uses    PMA_Message::$_number to set it
     * @param   integer $number
     */
    public function setNumber($number)
    {
        $this->_number = $number;
    }

    public function addParam($param)
    {
        $this->_params[] = $param;
    }

    public function setParams($params, $sanitize = false)
    {
        if ($sanitize) {
            $params = PMA_Message::sanitize($params);
        }
        $this->_params = $params;
    }

    public function getParams()
    {
        return $this->_params;
    }

    /**
     * Sanitizes $message, taking into account our special codes
     * for formatting
     *
     * @uses    htmlspecialchars()
     * @param   string   the message
     * @return  string   the sanitized message
     * @access  public
     */
    static public function sanitize($message)
    {
        if (is_array($message)) {
            foreach ($message as $key => $val) {
                $message[$key] = PMA_Message::sanitize($val);
            }

            return $message;
        }

        return htmlspecialchars($message);
    }

    /**
     * decode $message, taking into account our special codes
     * for formatting
     *
     * @uses    PREG_SET_ORDER
     * @uses    in_array()
     * @uses    preg_match_all()
     * @uses    preg_match()
     * @uses    preg_replace()
     * @uses    substr()
     * @uses    strtr()
     * @param   string   the message
     * @return  string   the decoded message
     * @access  public
     */
    static public function decodeBB($message)
    {
        $replace_pairs = array(
            '[i]'       => '<em>',      // deprecated by em
            '[/i]'      => '</em>',     // deprecated by em
            '[em]'      => '<em>',
            '[/em]'     => '</em>',
            '[b]'       => '<strong>',  // deprecated by strong
            '[/b]'      => '</strong>', // deprecated by strong
            '[strong]'  => '<strong>',
            '[/strong]' => '</strong>',
            '[tt]'      => '<code>',    // deprecated by CODE or KBD
            '[/tt]'     => '</code>',   // deprecated by CODE or KBD
            '[code]'    => '<code>',
            '[/code]'   => '</code>',
            '[kbd]'     => '<kbd>',
            '[/kbd]'    => '</kbd>',
            '[br]'      => '<br />',
            '[/a]'      => '</a>',
            '[sup]'     => '<sup>',
            '[/sup]'    => '</sup>',
        );

        $message = strtr($message, $replace_pairs);

        $pattern = '/\[a@([^"@]*)@([^]"]*)\]/';

        if (preg_match_all($pattern, $message, $founds, PREG_SET_ORDER)) {
            $valid_links = array(
                'http',  // default http:// links (and https://)
                './Do',  // ./Documentation
            );

            foreach ($founds as $found) {
                // only http... and ./Do... allowed
                if (! in_array(substr($found[1], 0, 4), $valid_links)) {
                    return $message;
                }
                // a-z and _ allowed in target
                if (! empty($found[2]) && preg_match('/[^a-z_]+/i', $found[2])) {
                    return $message;
                }
            }

            $message = preg_replace($pattern, '<a href="\1" target="\2">', $message);
        }

        return $message;
    }

    static public function format()
    {
        $params = func_get_args();
        if (is_array($params[1])) {
            array_unshift($params[1], $params[0]);
            $params = $params[1];
        }

        return call_user_func_array('sprintf', $params);
    }

    /**
     * returns unique PMA_Message::$_hash, if not exists it will be created
     *
     * @uses    PMA_Message::$_hash as return value and to set it if required
     * @uses    PMA_Message::getNumber()
     * @uses    PMA_Message::$_string
     * @uses    PMA_Message::$_message
     * @uses    md5()
     * @param   string $file
     * @return  string PMA_Message::$_hash
     */
    public function getHash()
    {
        if (null === $this->_hash) {
            $this->_hash = md5(
                $this->getNumber() .
                $this->_string .
                $this->_message
            );
        }

        return $this->_hash;
    }

    /**
     * returns PMA_Message::$_message
     *
     * @uses    PMA_Message::$_message as return value
     * @return  string PMA_Message::$_message
     */
    public function getMessage()
    {
        $message = $this->_message;

        if (0 === strlen($message)) {
            $message = $GLOBALS[$this->getString()];
            echo '<pre>';
            debug_print_backtrace();
            echo '</pre>';
        }

        if (count($this->getParams()) > 0) {
            $message = PMA_Message::format($message, $this->getParams());
        }

        return PMA_Message::decodeBB($message);
    }

    /**
     * returns PMA_Message::$_string
     *
     * @uses    PMA_Message::$_string as return value
     * @return  string PMA_Message::$_string
     */
    public function getString()
    {
        return $this->_string;
    }

    /**
     * returns PMA_Message::$_number
     *
     * @uses    PMA_Message::$_number as return value
     * @return  integer PMA_Message::$_number
     */
    public function getNumber()
    {
        return $this->_number;
    }

    /**
     * returns level of message
     *
     * @uses    PMA_Message::$level
     * @uses    PMA_Message::getNumber()
     * @return  string  level of message
     */
    public function getLevel()
    {
        return PMA_Message::$level[$this->getNumber()];
    }

    /**
     * Displays the message in HTML
     *
     * @uses    PMA_Message::getLevel()
     * @uses    PMA_Message::getMessage()
     * @uses    PMA_Message::isDisplayed()
     */
    public function display()
    {
        echo '<div class="' . $this->getLevel() . '">';
        echo $this->getMessage();
        echo '</div>';
        $this->isDisplayed(true);
    }

    /**
     * displays a message
     *
     * @param   string  $string
     * @param   integer $number
     * @param   boolean $sanitize
     * @param   scalare $parameter, ...
     */
    static function sDisplay()
    {
        $args = func_get_args();
        $defaults = array(
            '',
            PMA_Message::NOTICE,
            true,
        );

        if (! isset($args[0])) {
            trigger_error(__METHOD__ . ' called without arguments.', E_WARNING);
            return false;
        } else {
            foreach ($args as $key => $val) {
                $defaults[$key] = $val;
            }
        }


        $message = new PMA_Message();
    }

    /**
     * sets and returns PMA_Message::$_is_displayed
     *
     * @uses    PMA_Message::$_is_displayed
     * @param   boolean $is_displayed
     * @return  boolean PMA_Message::$_is_displayed
     */
    public function isDisplayed($is_displayed = false)
    {
        if ($is_displayed){
            $this->_is_displayed = $is_displayed;
        }

        return $this->_is_displayed;
    }
}
?>
