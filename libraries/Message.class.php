<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds class PMA_Message
 *
 * @author Sebastian Mendel <info@sebastianmendel.de>
 * @version $Id$
 * @package phpMyAdmin
 */

/**
 * a single message
 *
 * simple usage examples:
 * <code>
 * // display simple error message 'Error'
 * PMA_Message::error()->display();
 *
 * // get simple success message 'Success'
 * $message = PMA_Message::success();
 *
 * // get special notice 'Some locale notice'
 * $message = PMA_Message::notice('strSomeLocaleNotice');
 *
 * // display raw warning message 'This is a warning!'
 * PMA_Message::rawWarning('This is a warning!')->display();
 * </code>
 *
 * more advanced usage example:
 * <code>
 * // create a localized success message
 * $message = PMA_Message::success('strSomeLocaleMessage');
 *
 * // create another message, a hint, with a localized string which expects
 * // two parameters: $strSomeFootnote = 'Read the %smanual%s'
 * $hint = PMA_Message::notice('strSomeFootnote');
 * // replace %d with the following params
 * $hint->addParam('[a@./Documentation.html#cfg_Example@_blank]');
 * $hint->addParam('[/a]');
 * // add this hint as a footnote
 * $hint = PMA_showHint($hint);
 *
 * // add the retrieved footnote reference to the original message
 * $message->addMessage($hint);
 *
 * // create another message ...
 * $more = PMA_Message::notice('strSomeMoreLocale');
 * $more->addString('strSomeEvenMoreLocale', '<br />');
 * $more->addParam('parameter for strSomeMoreLocale');
 * $more->addParam('more parameter for strSomeMoreLocale');
 *
 * // and add it also to the original message
 * $message->addMessage($more);
 * // finally add another raw message
 * $message->addMessage('some final words', ' - ');
 *
 * // display() will now print all messages in the same order as they are added
 * $message->display();
 * // strSomeLocaleMessage <sup>1</sup> strSomeMoreLocale<br />
 * // strSomeEvenMoreLocale - some final words
 * </code>
 * @package phpMyAdmin
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
     * @access  protected
     * @var     integer
     */
    protected $_number = PMA_Message::NOTICE;

    /**
     * The locale string identifier
     *
     * @access  protected
     * @var     string
     */
    protected $_string = '';

    /**
     * The formatted message
     *
     * @access  protected
     * @var     string
     */
    protected $_message = '';

    /**
     * Whether the message was already displayed
     *
     * @access  protected
     * @var     boolean
     */
    protected $_is_displayed = false;

    /**
     * Unique id
     *
     * @access  protected
     * @var string
     */
    protected $_hash = null;

    /**
     * holds parameters
     *
     * @access  protected
     * @var     array
     */
    protected $_params = array();

    /**
     * holds additional messages
     *
     * @access  protected
     * @var     array
     */
    protected $_added_messages = array();

    /**
     * Constructor
     *
     * @uses    PMA_Message::setNumber()
     * @uses    PMA_Message::setString()
     * @uses    PMA_Message::setParams()
     * @uses    PMA_Message::NOTICE
     * @uses    PMA_Message::SANITIZE_NONE
     * @uses    PMA_Message::SANITIZE_STRING
     * @uses    PMA_Message::SANITIZE_PARAMS
     * @param   string  $string
     * @param   integer $number
     * @param   array   $$params
     * @param   boolean $sanitize
     */
    public function __construct($string = '', $number = PMA_Message::NOTICE,
        $params = array(), $sanitize = PMA_Message::SANITIZE_NONE)
    {
        $this->setString($string, $sanitize & PMA_Message::SANITIZE_STRING);
        $this->setNumber($number);
        $this->setParams($params, $sanitize & PMA_Message::SANITIZE_PARAMS);
    }

    /**
     * magic method: return string representation for this object
     *
     * @uses    PMA_Message::getMessage()
     * @return string
     */
    public function __toString()
    {
        return $this->getMessage();
    }

    /**
     * get PMA_Message of type success
     *
     * shorthand for getting a simple success message
     *
     * @static
     * @uses    PMA_Message as returned object
     * @uses    PMA_Message::SUCCESS
     * @param   string $string a localized string e.g. 'strSuccess'
     * @return  PMA_Message
     */
    static public function success($string = '')
    {
        if (empty($string)) {
            $string = 'strSuccess';
        }

        return new PMA_Message($string, PMA_Message::SUCCESS);
    }

    /**
     * get PMA_Message of type error
     *
     * shorthand for getting a simple error message
     *
     * @static
     * @uses    PMA_Message as returned object
     * @uses    PMA_Message::ERROR
     * @param   string $string a localized string e.g. 'strError'
     * @return  PMA_Message
     */
    static public function error($string = '')
    {
        if (empty($string)) {
            $string = 'strError';
        }

        return new PMA_Message($string, PMA_Message::ERROR);
    }

    /**
     * get PMA_Message of type warning
     *
     * shorthand for getting a simple warning message
     *
     * @static
     * @uses    PMA_Message as returned object
     * @uses    PMA_Message::WARNING
     * @param   string $string a localized string e.g. 'strSetupWarning'
     * @return  PMA_Message
     */
    static public function warning($string)
    {
        return new PMA_Message($string, PMA_Message::WARNING);
    }

    /**
     * get PMA_Message of type notice
     *
     * shorthand for getting a simple notice message
     *
     * @static
     * @uses    PMA_Message as returned object
     * @uses    PMA_Message::NOTICE
     * @param   string  $string a localized string e.g. 'strRelationNotWorking'
     * @return  PMA_Message
     */
    static public function notice($string)
    {
        return new PMA_Message($string, PMA_Message::NOTICE);
    }

    /**
     * get PMA_Message with customized content
     *
     * shorthand for getting a customized message
     *
     * @static
     * @uses    PMA_Message as returned object
     * @uses    PMA_Message::setMessage()
     * @param   string    $message
     * @param   integer   $type
     * @return  PMA_Message
     */
    static public function raw($message, $type = PMA_Message::NOTICE)
    {
        $r = new PMA_Message('', $type);
        $r->setMessage($message);
        return $r;
    }

    /**
     * get PMA_Message of type error with custom content
     *
     * shorthand for getting a customized error message
     *
     * @static
     * @uses    PMA_Message::raw()
     * @uses    PMA_Message::ERROR
     * @param   string  $message
     * @return  PMA_Message
     */
    static public function rawError($message)
    {
        return PMA_Message::raw($message, PMA_Message::ERROR);
    }

    /**
     * get PMA_Message of type warning with custom content
     *
     * shorthand for getting a customized warning message
     *
     * @static
     * @uses    PMA_Message::raw()
     * @uses    PMA_Message::WARNING
     * @param   string  $message
     * @return  PMA_Message
     */
    static public function rawWarning($message)
    {
        return PMA_Message::raw($message, PMA_Message::WARNING);
    }

    /**
     * get PMA_Message of type notice with custom content
     *
     * shorthand for getting a customized notice message
     *
     * @static
     * @uses    PMA_Message::raw()
     * @uses    PMA_Message::NOTICE
     * @param   string  $message
     * @return  PMA_Message
     */
    static public function rawNotice($message)
    {
        return PMA_Message::raw($message, PMA_Message::NOTICE);
    }

    /**
     * get PMA_Message of type success with custom content
     *
     * shorthand for getting a customized success message
     *
     * @static
     * @uses    PMA_Message::raw()
     * @uses    PMA_Message::SUCCESS
     * @param   string  $message
     * @return  PMA_Message
     */
    static public function rawSuccess($message)
    {
        return PMA_Message::raw($message, PMA_Message::SUCCESS);
    }

    /**
     * returns whether this message is a success message or not
     * and optionaly makes this message a success message
     *
     * @uses    PMA_Message::SUCCESS
     * @uses    PMA_Message::setNumber()
     * @uses    PMA_Message::getNumber()
     * @param   boolean $set
     * @return  boolean whether this is a success message or not
     */
    public function isSuccess($set = false)
    {
        if ($set) {
            $this->setNumber(PMA_Message::SUCCESS);
        }

        return $this->getNumber() === PMA_Message::SUCCESS;
    }

    /**
     * returns whether this message is a notice message or not
     * and optionally makes this message a notice message
     *
     * @uses    PMA_Message::NOTICE
     * @uses    PMA_Message::setNumber()
     * @uses    PMA_Message::getNumber()
     * @param   boolean $set
     * @return  boolean whether this is a notice message or not
     */
    public function isNotice($set = false)
    {
        if ($set) {
            $this->setNumber(PMA_Message::NOTICE);
        }

        return $this->getNumber() === PMA_Message::NOTICE;
    }

    /**
     * returns whether this message is a warning message or not
     * and optionally makes this message a warning message
     *
     * @uses    PMA_Message::WARNING
     * @uses    PMA_Message::setNumber()
     * @uses    PMA_Message::getNumber()
     * @param   boolean $set
     * @return  boolean whether this is a warning message or not
     */
    public function isWarning($set = false)
    {
        if ($set) {
            $this->setNumber(PMA_Message::WARNING);
        }

        return $this->getNumber() === PMA_Message::WARNING;
    }

    /**
     * returns whether this message is an error message or not
     * and optionally makes this message an error message
     *
     * @uses    PMA_Message::ERROR
     * @uses    PMA_Message::setNumber()
     * @uses    PMA_Message::getNumber()
     * @param   boolean $set
     * @return  boolean whether this is an error message or not
     */
    public function isError($set = false)
    {
        if ($set) {
            $this->setNumber(PMA_Message::ERROR);
        }

        return $this->getNumber() === PMA_Message::ERROR;
    }

    /**
     * set raw message (overrides string)
     *
     * @uses    PMA_Message::$_message to set it
     * @uses    PMA_Message::sanitize()
     * @param   string  $message
     * @param   boolean $sanitize whether to sanitize $message or not
     */
    public function setMessage($message, $sanitize = false)
    {
        if ($sanitize) {
            $message = PMA_Message::sanitize($message);
        }
        $this->_message = $message;
    }

    /**
     * set string (does not take effect if raw message is set)
     *
     * @uses    PMA_Message::$_string to set it
     * @uses    PMA_Message::sanitize()
     * @param   string  $_string
     * @param   boolean $sanitize whether to sanitize $string or not
     */
    public function setString($_string, $sanitize = true)
    {
        if ($sanitize) {
            $_string = PMA_Message::sanitize($_string);
        }
        $this->_string = $_string;
    }

    /**
     * set message type number
     *
     * @uses    PMA_Message::$_number to set it
     * @param   integer $number
     */
    public function setNumber($number)
    {
        $this->_number = $number;
    }

    /**
     * add parameter, usually in conjunction with strings
     *
     * usage
     * <code>
     * $message->addParam('strLocale', false);
     * $message->addParam('[em]some string[/em]');
     * $message->addParam('<img src="img" />', false);
     * </code>
     *
     * @uses    htmlspecialchars()
     * @uses    PMA_Message::$_params to fill
     * @uses    PMA_Message::notice()
     * @param   mixed   $param
     * @param   boolean $raw
     */
    public function addParam($param, $raw = true)
    {
        if ($param instanceof PMA_Message) {
            $this->_params[] = $param;
        } elseif ($raw) {
            $this->_params[] = htmlspecialchars($param);
        } else {
            $this->_params[] = PMA_Message::notice($param);
        }
    }

    /**
     * add another string to be concatenated on displaying
     *
     * @uses    PMA_Message::$_added_messages to fill
     * @uses    PMA_Message::notice()
     * @param   string  $string    to be added
     * @param   string  $separator to use between this and previous string/message
     */
    public function addString($string, $separator = ' ')
    {
        $this->_added_messages[] = $separator;
        $this->_added_messages[] = PMA_Message::notice($string);
    }

    /**
     * add a bunch of messages at once
     *
     * @uses    PMA_Message::addMessage()
     * @param   array   $messages  to be added
     * @param   string  $separator to use between this and previous string/message
     */
    public function addMessages($messages, $separator = ' ')
    {
        foreach ($messages as $message) {
            $this->addMessage($message, $separator);
        }
    }

    /**
     * add another raw message to be concatenated on displaying
     *
     * @uses    PMA_Message::$_added_messages to fill
     * @uses    PMA_Message::rawNotice()
     * @param   mixed   $message   to be added
     * @param   string  $separator to use between this and previous string/message
     */
    public function addMessage($message, $separator = ' ')
    {
        if (strlen($separator)) {
            $this->_added_messages[] = $separator;
        }

        if ($message instanceof PMA_Message) {
            $this->_added_messages[] = $message;
        } else {
            $this->_added_messages[] = PMA_Message::rawNotice($message);
        }
    }

    /**
     * set all params at once, usually used in conjunction with string
     *
     * @uses    PMA_Message::sanitize()
     * @uses    PMA_Message::$_params to set
     * @param   array   $params
     * @param   boolean $sanitize
     */
    public function setParams($params, $sanitize = false)
    {
        if ($sanitize) {
            $params = PMA_Message::sanitize($params);
        }
        $this->_params = $params;
    }

    /**
     * return all parameters
     *
     * @uses    PMA_Message::$_params as return value
     * @return array
     */
    public function getParams()
    {
        return $this->_params;
    }

    /**
     * return all added messages
     *
     * @uses    PMA_Message::$_added_messages as return value
     * @return array
     */
    public function getAddedMessages()
    {
        return $this->_added_messages;
    }

    /**
     * Sanitizes $message
     *
     * @static
     * @uses    is_array()
     * @uses    htmlspecialchars()
     * @uses    PMA_Message::sanitize() recursiv
     * @param   mixed   the message(s)
     * @return  mixed   the sanitized message(s)
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
     * @static
     * @uses    PREG_SET_ORDER
     * @uses    in_array()
     * @uses    preg_match_all()
     * @uses    preg_match()
     * @uses    preg_replace()
     * @uses    substr()
     * @uses    strtr()
     * @param   string  $message the message
     * @return  string  the decoded message
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

    /**
     * wrapper for sprintf()
     *
     * @uses    sprintf()
     * @uses    func_get_args()
     * @uses    is_array()
     * @uses    array_unshift()
     * @uses    call_user_func_array()
     * @return  string formatted
     */
    static public function format()
    {
        $params = func_get_args();
        if (isset($params[1]) && is_array($params[1])) {
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
     * returns compiled message
     *
     * @uses    PMA_Message::$_message as return value
     * @uses    PMA_Message::getString()
     * @uses    PMA_Message::getParams()
     * @uses    PMA_Message::format()
     * @uses    PMA_Message::decodeBB()
     * @uses    PMA_Message::getAddedMessages()
     * @uses    strlen()
     * @return  string complete message
     */
    public function getMessage()
    {
        $message = $this->_message;

        if (0 === strlen($message)) {
            $string = $this->getString();
            if (isset($GLOBALS[$string])) {
                $message = $GLOBALS[$string];
            } elseif (0 === strlen($string)) {
                $message = '';
            } else {
                $message = $string;
            }
        }

        if (count($this->getParams()) > 0) {
            $message = PMA_Message::format($message, $this->getParams());
        }

        $message = PMA_Message::decodeBB($message);

        foreach ($this->getAddedMessages() as $add_message) {
            $message .= $add_message;
        }

        return $message;
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
        echo $this->getDisplay();
        $this->isDisplayed(true);
    }

    /**
     * returns HTML code for displaying this message
     *
     * @return string whole message box
     */
    public function getDisplay()
    {
        return '<div class="' . $this->getLevel() . '">'
            . $this->getMessage() . '</div>';
    }

    /**
     * sets and returns whether the message was displayed or not
     *
     * @uses    PMA_Message::$_is_displayed to set it and/or return it
     * @param   boolean $is_displayed
     * @return  boolean PMA_Message::$_is_displayed
     */
    public function isDisplayed($is_displayed = false)
    {
        if ($is_displayed) {
            $this->_is_displayed = true;
        }

        return $this->_is_displayed;
    }
}
?>
