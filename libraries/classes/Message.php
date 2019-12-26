<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds class Message
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\Sanitize;
use PhpMyAdmin\Util;

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
 * // create another message, a hint, with a localized string which expects
 * $hint = Message::notice('Read the %smanual%s');
 * // replace placeholders with the following params
 * $hint->addParam('[doc@cfg_Example]');
 * $hint->addParam('[/doc]');
 * // add this hint as a tooltip
 * $hint = showHint($hint);
 *
 * // add the retrieved tooltip reference to the original message
 * $message->addMessage($hint);
 * </code>
 *
 * @package PhpMyAdmin
 */
class Message
{
    public const SUCCESS = 1; // 0001
    public const NOTICE  = 2; // 0010
    public const ERROR   = 8; // 1000

    public const SANITIZE_NONE   = 0;  // 0000 0000
    public const SANITIZE_STRING = 16; // 0001 0000
    public const SANITIZE_PARAMS = 32; // 0010 0000
    public const SANITIZE_BOOTH  = 48; // 0011 0000

    /**
     * message levels
     *
     * @var array
     */
    public static $level =  [
        Message::SUCCESS => 'success',
        Message::NOTICE  => 'notice',
        Message::ERROR   => 'error',
    ];

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
     * Whether to use BB code when displaying.
     *
     * @access  protected
     * @var     boolean
     */
    protected $useBBCode = true;

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
    protected $params = [];

    /**
     * holds additional messages
     *
     * @access  protected
     * @var     array
     */
    protected $addedMessages = [];

    /**
     * Constructor
     *
     * @param string  $string   The message to be displayed
     * @param integer $number   A numeric representation of the type of message
     * @param array   $params   An array of parameters to use in the message
     * @param integer $sanitize A flag to indicate what to sanitize, see
     *                          constant definitions above
     */
    public function __construct(
        string $string = '',
        int $number = Message::NOTICE,
        array $params = [],
        int $sanitize = Message::SANITIZE_NONE
    ) {
        $this->setString($string, $sanitize & self::SANITIZE_STRING);
        $this->setNumber($number);
        $this->setParams($params, $sanitize & self::SANITIZE_PARAMS);
    }

    /**
     * magic method: return string representation for this object
     *
     * @return string
     */
    public function __toString(): string
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
    public static function success(string $string = ''): self
    {
        if (empty($string)) {
            $string = __('Your SQL query has been executed successfully.');
        }

        return new Message($string, self::SUCCESS);
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
    public static function error(string $string = ''): self
    {
        if (empty($string)) {
            $string = __('Error');
        }

        return new Message($string, self::ERROR);
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
    public static function notice(string $string): self
    {
        return new Message($string, self::NOTICE);
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
    public static function raw(string $message, int $type = Message::NOTICE): self
    {
        $r = new Message('', $type);
        $r->setMessage($message);
        $r->setBBCode(false);
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
    public static function getMessageForAffectedRows(int $rows): self
    {
        $message = self::success(
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
    public static function getMessageForDeletedRows(int $rows): self
    {
        $message = self::success(
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
    public static function getMessageForInsertedRows(int $rows): self
    {
        $message = self::success(
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
    public static function rawError(string $message): self
    {
        return self::raw($message, self::ERROR);
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
    public static function rawNotice(string $message): self
    {
        return self::raw($message, self::NOTICE);
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
    public static function rawSuccess(string $message): self
    {
        return self::raw($message, self::SUCCESS);
    }

    /**
     * returns whether this message is a success message or not
     * and optionally makes this message a success message
     *
     * @param boolean $set Whether to make this message of SUCCESS type
     *
     * @return boolean whether this is a success message or not
     */
    public function isSuccess(bool $set = false): bool
    {
        if ($set) {
            $this->setNumber(self::SUCCESS);
        }

        return $this->getNumber() === self::SUCCESS;
    }

    /**
     * returns whether this message is a notice message or not
     * and optionally makes this message a notice message
     *
     * @param boolean $set Whether to make this message of NOTICE type
     *
     * @return boolean whether this is a notice message or not
     */
    public function isNotice(bool $set = false): bool
    {
        if ($set) {
            $this->setNumber(self::NOTICE);
        }

        return $this->getNumber() === self::NOTICE;
    }

    /**
     * returns whether this message is an error message or not
     * and optionally makes this message an error message
     *
     * @param boolean $set Whether to make this message of ERROR type
     *
     * @return boolean Whether this is an error message or not
     */
    public function isError(bool $set = false): bool
    {
        if ($set) {
            $this->setNumber(self::ERROR);
        }

        return $this->getNumber() === self::ERROR;
    }

    /**
     * Set whether we should use BB Code when rendering.
     *
     * @param boolean $useBBCode Use BB Code?
     *
     * @return void
     */
    public function setBBCode(bool $useBBCode): void
    {
        $this->useBBCode = $useBBCode;
    }

    /**
     * set raw message (overrides string)
     *
     * @param string  $message  A localized string
     * @param boolean $sanitize Whether to sanitize $message or not
     *
     * @return void
     */
    public function setMessage(string $message, bool $sanitize = false): void
    {
        if ($sanitize) {
            $message = self::sanitize($message);
        }
        $this->message = $message;
    }

    /**
     * set string (does not take effect if raw message is set)
     *
     * @param string      $string   string to set
     * @param boolean|int $sanitize whether to sanitize $string or not
     *
     * @return void
     */
    public function setString(string $string, $sanitize = true): void
    {
        if ($sanitize) {
            $string = self::sanitize($string);
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
    public function setNumber(int $number): void
    {
        $this->number = $number;
    }

    /**
     * add string or Message parameter
     *
     * usage
     * <code>
     * $message->addParam('[em]some string[/em]');
     * </code>
     *
     * @param mixed $param parameter to add
     *
     * @return void
     */
    public function addParam($param): void
    {
        if ($param instanceof self || is_float($param) || is_int($param)) {
            $this->params[] = $param;
        } else {
            $this->params[] = htmlspecialchars((string) $param);
        }
    }

    /**
     * add parameter as raw HTML, usually in conjunction with strings
     *
     * usage
     * <code>
     * $message->addParamHtml('<img src="img">');
     * </code>
     *
     * @param string $param parameter to add
     *
     * @return void
     */
    public function addParamHtml(string $param): void
    {
        $this->params[] = self::notice($param);
    }

    /**
     * add a bunch of messages at once
     *
     * @param Message[] $messages  to be added
     * @param string    $separator to use between this and previous string/message
     *
     * @return void
     */
    public function addMessages(array $messages, string $separator = ' '): void
    {
        foreach ($messages as $message) {
            $this->addMessage($message, $separator);
        }
    }

    /**
     * add a bunch of messages at once
     *
     * @param string[] $messages  to be added
     * @param string   $separator to use between this and previous string/message
     *
     * @return void
     */
    public function addMessagesString(array $messages, string $separator = ' '): void
    {
        foreach ($messages as $message) {
            $this->addText($message, $separator);
        }
    }

    /**
     * Real implementation of adding message
     *
     * @param Message $message   to be added
     * @param string  $separator to use between this and previous string/message
     *
     * @return void
     */
    private function addMessageToList(self $message, string $separator): void
    {
        if (! empty($separator)) {
            $this->addedMessages[] = $separator;
        }
        $this->addedMessages[] = $message;
    }

    /**
     * add another raw message to be concatenated on displaying
     *
     * @param self   $message   to be added
     * @param string $separator to use between this and previous string/message
     *
     * @return void
     */
    public function addMessage(self $message, string $separator = ' '): void
    {
        $this->addMessageToList($message, $separator);
    }

    /**
     * add another raw message to be concatenated on displaying
     *
     * @param string $message   to be added
     * @param string $separator to use between this and previous string/message
     *
     * @return void
     */
    public function addText(string $message, string $separator = ' '): void
    {
        $this->addMessageToList(self::notice(htmlspecialchars($message)), $separator);
    }

    /**
     * add another html message to be concatenated on displaying
     *
     * @param string $message   to be added
     * @param string $separator to use between this and previous string/message
     *
     * @return void
     */
    public function addHtml(string $message, string $separator = ' '): void
    {
        $this->addMessageToList(self::rawNotice($message), $separator);
    }

    /**
     * set all params at once, usually used in conjunction with string
     *
     * @param array|string $params   parameters to set
     * @param bool|int     $sanitize whether to sanitize params
     *
     * @return void
     */
    public function setParams($params, $sanitize = false): void
    {
        if ($sanitize) {
            $params = self::sanitize($params);
        }
        $this->params = $params;
    }

    /**
     * return all parameters
     *
     * @return array|string
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
    public function getAddedMessages(): array
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
    public static function sanitize($message)
    {
        if (is_array($message)) {
            foreach ($message as $key => $val) {
                $message[$key] = self::sanitize($val);
            }

            return $message;
        }

        return htmlspecialchars((string) $message);
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
    public static function decodeBB(string $message): string
    {
        return Sanitize::sanitizeMessage($message, false, true);
    }

    /**
     * wrapper for sprintf()
     *
     * @param mixed[] ...$params Params
     * @return string formatted
     */
    public static function format(...$params): string
    {
        if (isset($params[1]) && is_array($params[1])) {
            array_unshift($params[1], $params[0]);
            $params = $params[1];
        }

        return sprintf(...$params);
    }

    /**
     * returns unique Message::$hash, if not exists it will be created
     *
     * @return string Message::$hash
     */
    public function getHash(): string
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
    public function getMessage(): string
    {
        $message = $this->message;

        if (strlen($message) === 0) {
            $string = $this->getString();
            if (strlen($string) === 0) {
                $message = '';
            } else {
                $message = $string;
            }
        }

        if ($this->isDisplayed()) {
            $message = $this->getMessageWithIcon($message);
        }
        if (count($this->getParams()) > 0) {
            $message = self::format($message, $this->getParams());
        }

        if ($this->useBBCode) {
            $message = self::decodeBB($message);
        }

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
    public function getOnlyMessage(): string
    {
        return $this->message;
    }


    /**
     * returns Message::$string
     *
     * @return string Message::$string
     */
    public function getString(): string
    {
        return $this->string;
    }

    /**
     * returns Message::$number
     *
     * @return integer Message::$number
     */
    public function getNumber(): int
    {
        return $this->number;
    }

    /**
     * returns level of message
     *
     * @return string level of message
     */
    public function getLevel(): string
    {
        return self::$level[$this->getNumber()];
    }

    /**
     * Displays the message in HTML
     *
     * @return void
     */
    public function display(): void
    {
        echo $this->getDisplay();
    }

    /**
     * returns HTML code for displaying this message
     *
     * @return string whole message box
     */
    public function getDisplay(): string
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
    public function isDisplayed(bool $isDisplayed = false): bool
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
    public function getMessageWithIcon(string $message): string
    {
        if ('error' === $this->getLevel()) {
            $image = 's_error';
        } elseif ('success' === $this->getLevel()) {
            $image = 's_success';
        } else {
            $image = 's_notice';
        }
        $message = self::notice(Util::getImage($image)) . " " . $message;
        return $message;
    }
}
