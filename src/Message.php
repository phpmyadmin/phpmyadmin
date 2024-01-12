<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use Stringable;

use function __;
use function _ngettext;
use function htmlspecialchars;
use function is_float;
use function is_int;
use function md5;
use function sprintf;

use const ENT_COMPAT;

/**
 * a single message
 *
 * simple usage examples:
 * <code>
 * // display simple error message 'Error'
 * echo Message::error()->getDisplay();
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
 */
class Message implements Stringable
{
    public const SUCCESS = 1; // 0001
    public const NOTICE = 2; // 0010
    public const ERROR = 8; // 1000

    /**
     * The locale string identifier
     */
    protected string $string = '';

    /**
     * The formatted message
     */
    protected string $message = '';

    /**
     * Whether the message was already displayed
     */
    protected bool $isDisplayed = false;

    /**
     * Whether to use BB code when displaying.
     */
    protected bool $useBBCode = true;

    /**
     * Unique id
     */
    protected string|null $hash = null;

    /**
     * holds parameters
     *
     * @var    mixed[]
     */
    protected array $params = [];

    /**
     * holds additional messages
     *
     * @var    (string|Message)[]
     */
    protected array $addedMessages = [];

    /**
     * @param string  $string The message to be displayed
     * @param int     $type   A numeric representation of the type of message
     * @param mixed[] $params An array of parameters to use in the message
     *                        constant definitions above
     * @psalm-param self::SUCCESS|self::NOTICE|self::ERROR $type
     */
    public function __construct(
        string $string = '',
        private int $type = self::NOTICE,
        array $params = [],
    ) {
        $this->setString($string);
        $this->setParams($params);
    }

    /**
     * magic method: return string representation for this object
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
     */
    public static function success(string $string = ''): self
    {
        if ($string === '') {
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
     */
    public static function error(string $string = ''): self
    {
        if ($string === '') {
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
     * @param string $message A localized string
     * @param int    $type    A numeric representation of the type of message
     * @psalm-param self::SUCCESS|self::NOTICE|self::ERROR $type
     */
    public static function raw(string $message, int $type = self::NOTICE): self
    {
        $r = new Message('', $type);
        $r->setMessage($message);
        $r->setBBCode(false);

        return $r;
    }

    /**
     * get Message for type of affected rows
     *
     * shorthand for getting a customized message
     *
     * @param int $rows Number of rows
     */
    public static function getMessageForAffectedRows(int $rows): self
    {
        $message = self::success(
            _ngettext('%1$d row affected.', '%1$d rows affected.', $rows),
        );
        $message->addParam($rows);

        return $message;
    }

    /**
     * get Message for type of deleted rows
     *
     * shorthand for getting a customized message
     *
     * @param int $rows Number of rows
     */
    public static function getMessageForDeletedRows(int $rows): self
    {
        $message = self::success(
            _ngettext('%1$d row deleted.', '%1$d rows deleted.', $rows),
        );
        $message->addParam($rows);

        return $message;
    }

    /**
     * get Message for type of inserted rows
     *
     * shorthand for getting a customized message
     *
     * @param int $rows Number of rows
     */
    public static function getMessageForInsertedRows(int $rows): self
    {
        $message = self::success(
            _ngettext('%1$d row inserted.', '%1$d rows inserted.', $rows),
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
     */
    public static function rawNotice(string $message): self
    {
        return self::raw($message);
    }

    /**
     * get Message of type success with custom content
     *
     * shorthand for getting a customized success message
     *
     * @param string $message A localized string
     */
    public static function rawSuccess(string $message): self
    {
        return self::raw($message, self::SUCCESS);
    }

    public function isSuccess(): bool
    {
        return $this->type === self::SUCCESS;
    }

    public function isNotice(): bool
    {
        return $this->type === self::NOTICE;
    }

    public function isError(): bool
    {
        return $this->type === self::ERROR;
    }

    /**
     * Set whether we should use BB Code when rendering.
     *
     * @param bool $useBBCode Use BB Code?
     */
    public function setBBCode(bool $useBBCode): void
    {
        $this->useBBCode = $useBBCode;
    }

    /**
     * set raw message (overrides string)
     *
     * @param string $message A localized string
     */
    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    /**
     * set string (does not take effect if raw message is set)
     *
     * @param string $string string to set
     */
    public function setString(string $string): void
    {
        $this->string = $string;
    }

    /**
     * set message type type
     *
     * @param int $type message type type to set
     * @psalm-param self::SUCCESS|self::NOTICE|self::ERROR $type
     */
    public function setType(int $type): void
    {
        $this->type = $type;
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
     */
    public function addParam(mixed $param): void
    {
        if ($param instanceof self || is_float($param) || is_int($param)) {
            $this->params[] = $param;
        } else {
            $this->params[] = htmlspecialchars((string) $param, ENT_COMPAT);
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
     */
    private function addMessageToList(self $message, string $separator): void
    {
        if ($separator !== '') {
            $this->addedMessages[] = $separator;
        }

        $this->addedMessages[] = $message;
    }

    /**
     * add another raw message to be concatenated on displaying
     *
     * @param self   $message   to be added
     * @param string $separator to use between this and previous string/message
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
     */
    public function addHtml(string $message, string $separator = ' '): void
    {
        $this->addMessageToList(self::rawNotice($message), $separator);
    }

    /**
     * set all params at once, usually used in conjunction with string
     *
     * @param mixed[] $params parameters to set
     */
    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    /**
     * return all parameters
     *
     * @return mixed[]
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * return all added messages
     *
     * @return (string|Message)[]
     */
    public function getAddedMessages(): array
    {
        return $this->addedMessages;
    }

    /**
     * returns unique Message::$hash, if not exists it will be created
     *
     * @return string Message::$hash
     */
    public function getHash(): string
    {
        if ($this->hash === null) {
            $this->hash = md5($this->type . $this->string . $this->message);
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

        if ($message === '') {
            $message = $this->getString();
        }

        /** @infection-ignore-all */
        if ($this->isDisplayed()) {
            $message = $this->getMessageWithIcon($message);
        }

        if ($this->params !== []) {
            $message = sprintf($message, ...$this->params);
        }

        if ($this->useBBCode) {
            $message = Sanitize::convertBBCode($message, true);
        }

        foreach ($this->getAddedMessages() as $addMessage) {
            $message .= $addMessage;
        }

        return $message;
    }

    /**
     * Returns only message string without image & other HTML.
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
     * returns level of message
     *
     * @return string level of message
     */
    public function getLevel(): string
    {
        return match ($this->type) {
            self::SUCCESS => 'success',
            self::NOTICE => 'notice',
            self::ERROR => 'error'
        };
    }

    public function getContext(): string
    {
        return match ($this->getLevel()) {
            'error' => 'danger',
            'success' => 'success',
            default => 'primary',
        };
    }

    /**
     * returns HTML code for displaying this message
     *
     * @return string whole message box
     */
    public function getDisplay(): string
    {
        $this->isDisplayed(true);

        $context = $this->getContext();

        $template = new Template();

        return $template->render('message', ['context' => $context, 'message' => $this->getMessage()]);
    }

    /**
     * sets and returns whether the message was displayed or not
     *
     * @param bool $isDisplayed whether to set displayed flag
     *
     * @infection-ignore-all
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
        $image = match ($this->getLevel()) {
            'error' => 's_error',
            'success' => 's_success',
            default =>'s_notice',
        };

        return self::notice(Html\Generator::getImage($image)) . ' ' . $message;
    }
}
