<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds class PMA_Message
 *
 * @package PhpMyAdmin
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
 * @package PhpMyAdmin
 */
class PMA_Message
{
    const SUCCESS = 1; // 0001
    const NOTICE  = 2; // 0010
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
     * @param string  $string
     * @param integer $number
     * @param array   $params
     * @param integer $sanitize
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
     * @param string $string a localized string e.g. __('Your SQL query has been executed successfully')
     * @return  PMA_Message
     */
    static public function success($string = '')
    {
        if (empty($string)) {
            $string = __('Your SQL query has been executed successfully');
        }

        return new PMA_Message($string, PMA_Message::SUCCESS);
    }

    /**
     * get PMA_Message of type error
     *
     * shorthand for getting a simple error message
     *
     * @static
     * @param string $string a localized string e.g. __('Error')
     * @return  PMA_Message
     */
    static public function error($string = '')
    {
        if (empty($string)) {
            $string = __('Error');
        }

        return new PMA_Message($string, PMA_Message::ERROR);
    }

    /**
     * get PMA_Message of type notice
     *
     * shorthand for getting a simple notice message
     *
     * @static
     * @param string  $string a localized string e.g. __('The additional features for working with linked tables have been deactivated. To find out why click %shere%s.')
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
     * @param string    $message
     * @param integer   $type
     * @return  PMA_Message
     */
    static public function raw($message, $type = PMA_Message::NOTICE)
    {
        $r = new PMA_Message('', $type);
        $r->setMessage($message);
        return $r;
    }

    /**
     * get PMA_Message for number of affected rows
     *
     * shorthand for getting a customized message
     *
     * @static
     * @param integer   $rows Number of rows
     * @return  PMA_Message
     */
    static public function affected_rows($rows)
    {
        $message = PMA_Message::success(_ngettext('%1$d row affected.', '%1$d rows affected.', $rows));
        $message->addParam($rows);
        return $message;
    }

    /**
     * get PMA_Message for number of deleted rows
     *
     * shorthand for getting a customized message
     *
     * @static
     * @param integer   $rows Number of rows
     * @return  PMA_Message
     */
    static public function deleted_rows($rows)
    {
        $message = PMA_Message::success(_ngettext('%1$d row deleted.', '%1$d rows deleted.', $rows));
        $message->addParam($rows);
        return $message;
    }

    /**
     * get PMA_Message for number of inserted rows
     *
     * shorthand for getting a customized message
     *
     * @static
     * @param integer   $rows Number of rows
     * @return  PMA_Message
     */
    static public function inserted_rows($rows)
    {
        $message = PMA_Message::success(_ngettext('%1$d row inserted.', '%1$d rows inserted.', $rows));
        $message->addParam($rows);
        return $message;
    }

    /**
     * get PMA_Message of type error with custom content
     *
     * shorthand for getting a customized error message
     *
     * @static
     * @param string  $message
     * @return  PMA_Message
     */
    static public function rawError($message)
    {
        return PMA_Message::raw($message, PMA_Message::ERROR);
    }

    /**
     * get PMA_Message of type notice with custom content
     *
     * shorthand for getting a customized notice message
     *
     * @static
     * @param string  $message
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
     * @param string  $message
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
     * @param boolean $set
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
     * @param boolean $set
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
     * returns whether this message is an error message or not
     * and optionally makes this message an error message
     *
     * @param boolean $set
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
     * @param string  $message
     * @param boolean $sanitize whether to sanitize $message or not
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
     * @param string  $_string
     * @param boolean $sanitize whether to sanitize $string or not
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
     * @param integer $number
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
     * @param mixed   $param
     * @param boolean $raw
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
     * @param string  $string    to be added
     * @param string  $separator to use between this and previous string/message
     */
    public function addString($string, $separator = ' ')
    {
        $this->_added_messages[] = $separator;
        $this->_added_messages[] = PMA_Message::notice($string);
    }

    /**
     * add a bunch of messages at once
     *
     * @param array   $messages  to be added
     * @param string  $separator to use between this and previous string/message
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
     * @param mixed   $message   to be added
     * @param string  $separator to use between this and previous string/message
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
     * @param array   $params
     * @param boolean $sanitize
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
     * @return array
     */
    public function getParams()
    {
        return $this->_params;
    }

    /**
     * return all added messages
     *
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
     * @param mixed  $message the message(s)
     * @return  mixed  the sanitized message(s)
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
     * @param string  $message the message
     * @return  string  the decoded message
     * @access  public
     */
    static public function decodeBB($message)
    {
        return PMA_sanitize($message, false, true);
    }

    /**
     * wrapper for sprintf()
     *
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
     * @return  string PMA_Message::$_string
     */
    public function getString()
    {
        return $this->_string;
    }

    /**
     * returns PMA_Message::$_number
     *
     * @return  integer PMA_Message::$_number
     */
    public function getNumber()
    {
        return $this->_number;
    }

    /**
     * returns level of message
     *
     * @return  string  level of message
     */
    public function getLevel()
    {
        return PMA_Message::$level[$this->getNumber()];
    }

    /**
     * Displays the message in HTML
     *
     */
    public function display()
    {
        echo $this->getDisplay();
        $this->isDisplayed(true);
    }

    /**
     * returns HTML code for displaying this message
     *
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
     * @param boolean $is_displayed
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
