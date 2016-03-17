<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds class Message
 *
 * @package PhpMyAdmin
 */
namespace PMA\libraries;

/**
 * a single message
 *
 * simple usage examples:
 * <code>
 * // display simple error message 'Error'
 * Message::error()->display();
 *
 * // get simple success message 'Success'
 * $message = Message::success();
 *
 * // get special notice
 * $message = Message::notice(__('This is a localized notice'));
 * </code>
 *
 * more advanced usage example:
 * <code>
 * // create a localized success message
 * $message = Message::success('strSomeLocaleMessage');
 *
 * // create another message, a hint, with a localized string which expects
 * // two parameters: $strSomeTooltip = 'Read the %smanual%s'
 * $hint = Message::notice('strSomeTooltip');
 * // replace placeholders with the following params
 * $hint->addParam('[doc@cfg_Example]');
 * $hint->addParam('[/doc]');
 * // add this hint as a tooltip
 * $hint = showHint($hint);
 *
 * // add the retrieved tooltip reference to the original message
 * $message->addMessage($hint);
 *
 * // create another message ...
 * $more = Message::notice('strSomeMoreLocale');
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
 *
 * @package PhpMyAdmin
 */
class Message
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
        Message::SUCCESS => 'success',
        Message::NOTICE  => 'notice',
        Message::ERROR   => 'error',
    );

    /**
     * The message number
     *
     * @access  protected
     * @var     integer
     */
    protected $number = Message::NOTICE;

    /**
     * The locale string identifier
     *
     * @access  protected
     * @var     string
     */
    protected $string = '';

    /**
     * The formatted message
     *
     * @access  protected
     * @var     string
     */
    protected $message = '';

    /**
     * Whether the message was already displayed
     *
     * @access  protected
     * @var     boolean
     */
    protected $isDisplayed = false;

    /**
     * Unique id
     *
     * @access  protected
     * @var string
     */
    protected $hash = null;

    /**
     * holds parameters
     *
     * @access  protected
     * @var     array
     */
    protected $params = array();

    /**
     * holds additional messages
     *
     * @access  protected
     * @var     array
     */
    protected $addedMessages = array();

    /**
     * Constructor
     *
     * @param string  $string   The message to be displayed
     * @param integer $number   A numeric representation of the type of message
     * @param array   $params   An array of parameters to use in the message
     * @param integer $sanitize A flag to indicate what to sanitize, see
     *                          constant definitions above
     */
    public function __construct($string = '', $number = Message::NOTICE,
        $params = array(), $sanitize = Message::SANITIZE_NONE
    ) {
        $this->setString($string, $sanitize & Message::SANITIZE_STRING);
        $this->setNumber($number);
        $this->setParams($params, $sanitize & Message::SANITIZE_PARAMS);
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
     * get Message of type success
     *
     * shorthand for getting a simple success message
     *
     * @param string $string A localized string
     *                       e.g. __('Your SQL query has been
     *                       executed successfully')
     *
     * @return Message
     * @static
     */
    static public function success($string = '')
    {
        if (empty($string)) {
            $string = __('Your SQL query has been executed successfully.');
        }

        return new Message($string, Message::SUCCESS);
    }

    /**
     * get Message of type error
     *
     * shorthand for getting a simple error message
     *
     * @param string $string A localized string e.g. __('Error')
     *
     * @return Message
     * @static
     */
    static public function error($string = '')
    {
        if (empty($string)) {
            $string = __('Error');
        }

        return new Message($string, Message::ERROR);
    }

    /**
     * get Message of type notice
     *
     * shorthand for getting a simple notice message
     *
     * @param string $string A localized string
     *                       e.g. __('The additional features for working with
     *                       linked tables have been deactivated. To find out
     *                       why click %shere%s.')
     *
     * @return Message
     * @static
     */
    static public function notice($string)
    {
        return new Message($string, Message::NOTICE);
    }

    /**
     * get Message with customized content
     *
     * shorthand for getting a customized message
     *
     * @param string  $message A localized string
     * @param integer $type    A numeric representation of the type of message
     *
     * @return Message
     * @static
     */
    static public function raw($message, $type = Message::NOTICE)
    {
        $r = new Message('', $type);
        $r->setMessage($message);
        return $r;
    }

    /**
     * get Message for number of affected rows
     *
     * shorthand for getting a customized message
     *
     * @param integer $rows Number of rows
     *
     * @return Message
     * @static
     */
    static public function getMessageForAffectedRows($rows)
    {
        $message = Message::success(
            _ngettext('%1$d row affected.', '%1$d rows affected.', $rows)
        );
        $message->addParam($rows);
        return $message;
    }

    /**
     * get Message for number of deleted rows
     *
     * shorthand for getting a customized message
     *
     * @param integer $rows Number of rows
     *
     * @return Message
     * @static
     */
    static public function getMessageForDeletedRows($rows)
    {
        $message = Message::success(
            _ngettext('%1$d row deleted.', '%1$d rows deleted.', $rows)
        );
        $message->addParam($rows);
        return $message;
    }

    /**
     * get Message for number of inserted rows
     *
     * shorthand for getting a customized message
     *
     * @param integer $rows Number of rows
     *
     * @return Message
     * @static
     */
    static public function getMessageForInsertedRows($rows)
    {
        $message = Message::success(
            _ngettext('%1$d row inserted.', '%1$d rows inserted.', $rows)
        );
        $message->addParam($rows);
        return $message;
    }

    /**
     * get Message of type error with custom content
     *
     * shorthand for getting a customized error message
     *
     * @param string $message A localized string
     *
     * @return Message
     * @static
     */
    static public function rawError($message)
    {
        return Message::raw($message, Message::ERROR);
    }

    /**
     * get Message of type notice with custom content
     *
     * shorthand for getting a customized notice message
     *
     * @param string $message A localized string
     *
     * @return Message
     * @static
     */
    static public function rawNotice($message)
    {
        return Message::raw($message, Message::NOTICE);
    }

    /**
     * get Message of type success with custom content
     *
     * shorthand for getting a customized success message
     *
     * @param string $message A localized string
     *
     * @return Message
     * @static
     */
    static public function rawSuccess($message)
    {
        return Message::raw($message, Message::SUCCESS);
    }

    /**
     * returns whether this message is a success message or not
     * and optionally makes this message a success message
     *
     * @param boolean $set Whether to make this message of SUCCESS type
     *
     * @return boolean whether this is a success message or not
     */
    public function isSuccess($set = false)
    {
        if ($set) {
            $this->setNumber(Message::SUCCESS);
        }

        return $this->getNumber() === Message::SUCCESS;
    }

    /**
     * returns whether this message is a notice message or not
     * and optionally makes this message a notice message
     *
     * @param boolean $set Whether to make this message of NOTICE type
     *
     * @return boolean whether this is a notice message or not
     */
    public function isNotice($set = false)
    {
        if ($set) {
            $this->setNumber(Message::NOTICE);
        }

        return $this->getNumber() === Message::NOTICE;
    }

    /**
     * returns whether this message is an error message or not
     * and optionally makes this message an error message
     *
     * @param boolean $set Whether to make this message of ERROR type
     *
     * @return boolean Whether this is an error message or not
     */
    public function isError($set = false)
    {
        if ($set) {
            $this->setNumber(Message::ERROR);
        }

        return $this->getNumber() === Message::ERROR;
    }

    /**
     * set raw message (overrides string)
     *
     * @param string  $message  A localized string
     * @param boolean $sanitize Whether to sanitize $message or not
     *
     * @return void
     */
    public function setMessage($message, $sanitize = false)
    {
        if ($sanitize) {
            $message = Message::sanitize($message);
        }
        $this->message = $message;
    }

    /**
     * set string (does not take effect if raw message is set)
     *
     * @param string  $string   string to set
     * @param boolean $sanitize whether to sanitize $string or not
     *
     * @return void
     */
    public function setString($string, $sanitize = true)
    {
        if ($sanitize) {
            $string = Message::sanitize($string);
        }
        $this->string = $string;
    }

    /**
     * set message type number
     *
     * @param integer $number message type number to set
     *
     * @return void
     */
    public function setNumber($number)
    {
        $this->number = $number;
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
     * @param mixed   $param parameter to add
     * @param boolean $raw   whether parameter should be passed as is
     *                       without html escaping
     *
     * @return void
     */
    public function addParam($param, $raw = true)
    {
        if ($param instanceof Message) {
            $this->params[] = $param;
        } elseif ($raw) {
            $this->params[] = htmlspecialchars($param);
        } else {
            $this->params[] = Message::notice($param);
        }
    }

    /**
     * add another string to be concatenated on displaying
     *
     * @param string $string    to be added
     * @param string $separator to use between this and previous string/message
     *
     * @return void
     */
    public function addString($string, $separator = ' ')
    {
        $this->addedMessages[] = $separator;
        $this->addedMessages[] = Message::notice($string);
    }

    /**
     * add a bunch of messages at once
     *
     * @param array  $messages  to be added
     * @param string $separator to use between this and previous string/message
     *
     * @return void
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
     * @param mixed  $message   to be added
     * @param string $separator to use between this and previous string/message
     *
     * @return void
     */
    public function addMessage($message, $separator = ' ')
    {
        if (mb_strlen($separator)) {
            $this->addedMessages[] = $separator;
        }

        if ($message instanceof Message) {
            $this->addedMessages[] = $message;
        } else {
            $this->addedMessages[] = Message::rawNotice($message);
        }
    }

    /**
     * set all params at once, usually used in conjunction with string
     *
     * @param array|string $params   parameters to set
     * @param boolean      $sanitize whether to sanitize params
     *
     * @return void
     */
    public function setParams($params, $sanitize = false)
    {
        if ($sanitize) {
            $params = Message::sanitize($params);
        }
        $this->params = $params;
    }

    /**
     * return all parameters
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * return all added messages
     *
     * @return array
     */
    public function getAddedMessages()
    {
        return $this->addedMessages;
    }

    /**
     * Sanitizes $message
     *
     * @param mixed $message the message(s)
     *
     * @return mixed  the sanitized message(s)
     * @access  public
     * @static
     */
    static public function sanitize($message)
    {
        if (is_array($message)) {
            foreach ($message as $key => $val) {
                $message[$key] = Message::sanitize($val);
            }

            return $message;
        }

        return htmlspecialchars($message);
    }

    /**
     * decode $message, taking into account our special codes
     * for formatting
     *
     * @param string $message the message
     *
     * @return string  the decoded message
     * @access  public
     * @static
     */
    static public function decodeBB($message)
    {
        return PMA_sanitize($message, false, true);
    }

    /**
     * wrapper for sprintf()
     *
     * @return string formatted
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
     * returns unique Message::$hash, if not exists it will be created
     *
     * @return string Message::$hash
     */
    public function getHash()
    {
        if (null === $this->hash) {
            $this->hash = md5(
                $this->getNumber() .
                $this->string .
                $this->message
            );
        }

        return $this->hash;
    }

    /**
     * returns compiled message
     *
     * @return string complete message
     */
    public function getMessage()
    {
        $message = $this->message;

        if (0 === mb_strlen($message)) {
            $string = $this->getString();
            if (isset($GLOBALS[$string])) {
                $message = $GLOBALS[$string];
            } elseif (0 === mb_strlen($string)) {
                $message = '';
            } else {
                $message = $string;
            }
        }

        if ($this->isDisplayed()) {
            $message = $this->getMessageWithIcon($message);
        }
        if (count($this->getParams()) > 0) {
            $message = Message::format($message, $this->getParams());
        }

        $message = Message::decodeBB($message);

        foreach ($this->getAddedMessages() as $add_message) {
            $message .= $add_message;
        }

        return $message;
    }

    /**
    * Returns only message string without image & other HTML.
    *
    * @return string
    */
    public function getOnlyMessage()
    {
        return $this->message;
    }


    /**
     * returns Message::$string
     *
     * @return string Message::$string
     */
    public function getString()
    {
        return $this->string;
    }

    /**
     * returns Message::$number
     *
     * @return integer Message::$number
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * returns level of message
     *
     * @return string  level of message
     */
    public function getLevel()
    {
        return Message::$level[$this->getNumber()];
    }

    /**
     * Displays the message in HTML
     *
     * @return void
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
        $this->isDisplayed(true);
        return '<div class="' . $this->getLevel() . '">'
            . $this->getMessage() . '</div>';
    }

    /**
     * sets and returns whether the message was displayed or not
     *
     * @param boolean $isDisplayed whether to set displayed flag
     *
     * @return boolean Message::$isDisplayed
     */
    public function isDisplayed($isDisplayed = false)
    {
        if ($isDisplayed) {
            $this->isDisplayed = true;
        }

        return $this->isDisplayed;
    }

    /**
     * Returns the message with corresponding image icon
     *
     * @param string $message the message(s)
     *
     * @return string message with icon
     */
    public function getMessageWithIcon($message)
    {
        if ('error' == $this->getLevel()) {
            $image = 's_error.png';
        } elseif ('success' == $this->getLevel()) {
            $image = 's_success.png';
        } else {
            $image = 's_notice.png';
        }
        $message = Message::notice(Util::getImage($image)) . " " . $message;
        return $message;

    }
}
