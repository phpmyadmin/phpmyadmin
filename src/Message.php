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
 */
final class Message implements Stringable
{
    /**
     * Whether to use BB code when displaying.
     */
    private bool $useBBCode = true;

    /**
     * holds parameters
     *
     * @var array<self|string|int|float>
     */
    private array $params = [];

    /**
     * holds additional messages
     *
     * @var    (string|Message)[]
     */
    private array $addedMessages = [];

    /**
     * @param string                       $string The message to be displayed
     * @param array<self|string|int|float> $params Parameters to substitute into the string
     */
    public function __construct(
        private string $string = '',
        private MessageType $type = MessageType::Notice,
        array $params = [],
    ) {
        foreach ($params as $param) {
            $this->addParam($param);
        }
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
     * @param string                       $string A localized string
     *                                             e.g. __('Your SQL query has been
     *                                             executed successfully')
     * @param array<self|string|int|float> $params Parameters to substitute into the string
     */
    public static function success(string $string = '', array $params = []): self
    {
        if ($string === '') {
            $string = __('Your SQL query has been executed successfully.');
        }

        return new self($string, MessageType::Success, $params);
    }

    /**
     * get Message of type error
     *
     * shorthand for getting a simple error message
     *
     * @param string                       $string A localized string e.g. __('Error')
     * @param array<self|string|int|float> $params Parameters to substitute into the string
     */
    public static function error(string $string = '', array $params = []): self
    {
        if ($string === '') {
            $string = __('Error');
        }

        return new self($string, MessageType::Error, $params);
    }

    /**
     * get Message of type notice
     *
     * shorthand for getting a simple notice message
     *
     * @param string                       $string A localized string
     *                                             e.g. __('The additional features for working with
     *                                             linked tables have been deactivated. To find out
     *                                             why click %shere%s.')
     * @param array<self|string|int|float> $params Parameters to substitute into the string
     */
    public static function notice(string $string, array $params = []): self
    {
        return new self($string, MessageType::Notice, $params);
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
        $r = new self($message, $type);
        $r->useBBCode = false;

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
     */
    public function addParam(self|string|int|float $param): void
    {
        if ($param instanceof self || is_float($param) || is_int($param)) {
            $this->params[] = $param;
        } else {
            $this->params[] = htmlspecialchars($param, ENT_COMPAT);
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
     * returns compiled message
     *
     * @return string complete message
     */
    public function getMessage(): string
    {
        return $this->compile($this->string);
    }

    public function getMessageWithIcon(): string
    {
        $image = match ($this->type) {
            MessageType::Error => 's_error',
            MessageType::Success => 's_success',
            MessageType::Notice => 's_notice',
        };

        $icon = Html\Generator::getImage($image);

        return $this->compile($icon . ' ' . $this->string);
    }

    private function compile(string $message): string
    {
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

    public function getContext(): string
    {
        return match ($this->type) {
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
    public function getDisplay(Template|null $template = null): string
    {
        $template ??= new Template(Config::getInstance());

        return $template->render(
            'message',
            ['context' => $this->getContext(), 'message' => $this->getMessageWithIcon()],
        );
    }
}
