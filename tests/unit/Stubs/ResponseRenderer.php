<?php
/**
 * Fake response stub for testing purposes
 *
 * It will concatenate HTML and JSON for given calls to addHTML and addJSON
 * respectively, what make it easy to determine whether the output is correct in test
 * suite. Feel free to modify for any future test needs.
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Stubs;

use PhpMyAdmin\Bookmarks\BookmarkRepository;
use PhpMyAdmin\Clock\Clock;
use PhpMyAdmin\Config;
use PhpMyAdmin\Config\UserPreferences;
use PhpMyAdmin\Config\UserPreferencesHandler;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Console\Console;
use PhpMyAdmin\Console\History;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Error\ErrorHandler;
use PhpMyAdmin\Footer;
use PhpMyAdmin\Header;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\I18n\LanguageManager;
use PhpMyAdmin\Message;
use PhpMyAdmin\Template;
use PhpMyAdmin\Theme\ThemeManager;

use function is_array;
use function json_encode;

class ResponseRenderer extends \PhpMyAdmin\ResponseRenderer
{
    /**
     * HTML data to be used in the response
     */
    protected string $htmlString = '';

    /**
     * An array of JSON key-value pairs
     * to be sent back for ajax requests
     *
     * @var mixed[]
     */
    protected array $json = [];

    /**
     * Creates a new class instance
     */
    public function __construct()
    {
        Current::$lang = 'en';
        $config = Config::getInstance();
        $config->selectedServer['pmadb'] = 'phpmyadmin';
        $template = new Template($config);
        $dummyDbi = new DbiDummy();
        $dummyDbi->addSelectDb('phpmyadmin');
        $dbi = DatabaseInterface::getInstanceForTest($dummyDbi, $config);
        $relation = new Relation($dbi, $config);
        $history = new History($dbi, $relation, $config);
        $console = new Console($relation, $template, new BookmarkRepository($dbi, $relation), $history);
        $userPreferences = new UserPreferences($dbi, $relation, $template, $config, new Clock());
        $userPreferencesHandler = new UserPreferencesHandler(
            $config,
            $dbi,
            $userPreferences,
            new LanguageManager($config),
            new ThemeManager(),
        );

        parent::__construct(
            $config,
            $template,
            new Header($template, $console, $config, $dbi, $relation, $userPreferences, $userPreferencesHandler),
            new Footer($template, $config),
            ErrorHandler::getInstance(),
            $dbi,
            ResponseFactory::create(),
        );
    }

    /**
     * Append HTML code to the response stub
     */
    public function addHTML(string $content): void
    {
        $this->htmlString .= $content;
    }

    /**
     * Add JSON code to the response stub
     *
     * @param array-key|array<array-key, mixed> $json  Either a key (string) or an array or key-value pairs
     * @param mixed|null                        $value Null, if passing an array in $json otherwise
     *                                                 it's a string value to the key
     */
    public function addJSON(string|int|array $json, mixed $value = null): void
    {
        if (is_array($json)) {
            foreach ($json as $key => $value) {
                $this->addJSON($key, $value);
            }
        } elseif ($value instanceof Message) {
            $this->json[$json] = $value->getDisplay();
        } else {
            $this->json[$json] = $value;
        }
    }

    /**
     * Return the final concatenated HTML string
     */
    public function getHTMLResult(): string
    {
        return $this->htmlString;
    }

    /**
     * Return the final JSON array
     *
     * @return mixed[]
     */
    public function getJSONResult(): array
    {
        return $this->json;
    }

    /**
     * Current I choose to return PhpMyAdmin\Header object directly because
     * our test has nothing about the Scripts and PhpMyAdmin\Header class.
     */
    public function getHeader(): Header
    {
        return $this->header;
    }

    /**
     * Set the status of an ajax response,
     * whether it is a success or an error
     *
     * @param bool $state Whether the request was successfully processed
     */
    public function setRequestStatus(bool $state): void
    {
        $this->isSuccess = $state;
    }

    /**
     * Get the status of an ajax response.
     */
    public function hasSuccessState(): bool
    {
        return $this->isSuccess;
    }

    /**
     * This function is used to clear all data to this
     * stub after any operations.
     */
    public function clear(): void
    {
        $this->isSuccess = true;
        $this->json = [];
        $this->htmlString = '';
    }

    /**
     * Set the ajax flag to indicate whether
     * we are servicing an ajax request
     *
     * @param bool $isAjax Whether we are servicing an ajax request
     */
    public function setAjax(bool $isAjax): void
    {
        $this->isAjax = $isAjax;
    }

    /**
     * Returns true or false depending on whether
     * we are servicing an ajax request
     */
    public function isAjax(): bool
    {
        return $this->isAjax;
    }

    public function getResponse(): Response
    {
        return $this->response;
    }

    public function response(): Response
    {
        if ($this->isAjax()) {
            $json = $this->getJSONResult();
            if ($this->isSuccess) {
                $json['success'] = true;
            } else {
                $json['success'] = false;
                $json['error'] = $json['message'];
                unset($json['message']);
            }

            $output = (string) json_encode($json);
        } else {
            $output = $this->getHTMLResult();
        }

        $this->response->getBody()->write($output);

        return $this->response;
    }
}
