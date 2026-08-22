<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use Stringable;

use function __;
use function _ngettext;
use function htmlspecialchars;
use function is_float;
use function is_int;
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
     * @param mixed[] $params An array of parameters to use in the message constant definitions above
     */
    public function __construct(
        string $string = '',
        private MessageType $type = MessageType::Notice,
        array $params = [],
    ) {
        $this->string = $string;
        $this->params = $params;
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
     * @param string  $string A localized string
     *                        e.g. __('Your SQL query has been
     *                        executed successfully')
     * @param mixed[] $params Parameters to substitute into the string
     */
    public static function success(string $string = '', array $params = []): self
    {
        if ($string === '') {
            $string = __('Your SQL query has been executed successfully.');
        }

        return self::create($string, MessageType::Success, $params);
    }

    /**
     * get Message of type error
     *
     * shorthand for getting a simple error message
     *
     * @param string  $string A localized string e.g. __('Error')
     * @param mixed[] $params Parameters to substitute into the string
     */
    public static function error(string $string = '', array $params = []): self
    {
        if ($string === '') {
            $string = __('Error');
        }

        return self::create($string, MessageType::Error, $params);
    }

    /**
     * get Message of type notice
     *
     * shorthand for getting a simple notice message
     *
     * @param string  $string A localized string
     *                        e.g. __('The additional features for working with
     *                        linked tables have been deactivated. To find out
     *                        why click %shere%s.')
     * @param mixed[] $params Parameters to substitute into the string
     */
    public static function notice(string $string, array $params = []): self
    {
        return self::create($string, MessageType::Notice, $params);
    }

    /**
     * Builds a message, escaping each parameter the same way addParam() does.
     *
     * @param mixed[] $params Parameters to substitute into the string
     */
    private static function create(string $string, MessageType $type, array $params): self
    {
        $message = new Message($string, $type);
        foreach ($params as $param) {
            $message->addParam($param);
        }

        return $message;
    }

    /**
     * get Message with customized content
     *
     * shorthand for getting a customized message
     *
     * @param string $message A localized string
     */
    public static function raw(string $message, MessageType $type = MessageType::Notice): self
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
        return self::raw($message, MessageType::Error);
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
        return self::raw($message, MessageType::Success);
    }

    public function isSuccess(): bool
    {
        return $this->type === MessageType::Success;
    }

    public function isNotice(): bool
    {
        return $this->type === MessageType::Notice;
    }

    public function isError(): bool
    {
        return $this->type === MessageType::Error;
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

    public function setType(MessageType $type): void
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
     * return all parameters
     *
     * @return mixed[]
     */
    public function getParams(): array
    {
        return $this->params;
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
            // phpcs:disable SlevomatCodingStandard.PHP.OptimizedFunctionsWithoutUnpacking.UnpackingUsed
            $message = sprintf($message, ...$this->params);
        }

        if ($this->useBBCode) {
            $message = Sanitize::convertBBCode($message, true);
        }

        foreach ($this->addedMessages as $addMessage) {
            $message .= $addMessage;
        }

        return $message;
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

    protected function getLevel(): MessageType
    {
        return $this->type;
    }

    public function getContext(): string
    {
        return match ($this->getLevel()) {
            MessageType::Error => 'danger',
            MessageType::Success => 'success',
            MessageType::Notice => 'primary',
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

        $template = new Template(Config::getInstance());

        return $template->render('message', ['context' => $this->getContext(), 'message' => $this->getMessage()]);
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
            MessageType::Error => 's_error',
            MessageType::Success => 's_success',
            MessageType::Notice =>'s_notice',
        };

        return self::notice(Html\Generator::getImage($image)) . ' ' . $message;
    }
}
