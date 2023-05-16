<?php
/**
 * Two authentication factor handling
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins;

use PhpMyAdmin\Core;
use PhpMyAdmin\Message;
use PhpMyAdmin\Template;
use PhpMyAdmin\TwoFactor;

use function __;
use function is_array;
use function parse_url;
use function sprintf;

/**
 * Two factor authentication plugin class
 *
 * This is basic implementation which does no
 * additional authentication, subclasses are expected
 * to implement this.
 */
class TwoFactorPlugin
{
    public static string $id = '';

    /**
     * Whether to show submit button in form
     */
    public static bool $showSubmit = true;

    protected bool $provided = false;

    protected string $message = '';

    public Template $template;

    public function __construct(protected TwoFactor $twofactor)
    {
        $this->template = new Template();
    }

    /**
     * Returns authentication error message
     */
    public function getError(): string
    {
        if ($this->provided) {
            if (! empty($this->message)) {
                return Message::rawError(
                    sprintf(__('Two-factor authentication failed: %s'), $this->message),
                )->getDisplay();
            }

            return Message::rawError(
                __('Two-factor authentication failed.'),
            )->getDisplay();
        }

        return '';
    }

    /**
     * Checks authentication, returns true on success
     */
    public function check(): bool
    {
        return true;
    }

    /**
     * Renders user interface to enter two-factor authentication
     *
     * @return string HTML code
     */
    public function render(): string
    {
        return '';
    }

    /**
     * Renders user interface to configure two-factor authentication
     *
     * @return string HTML code
     */
    public function setup(): string
    {
        return '';
    }

    /**
     * Performs backend configuration
     */
    public function configure(): bool
    {
        return true;
    }

    /**
     * Get user visible name
     */
    public static function getName(): string
    {
        return __('No Two-Factor Authentication');
    }

    /**
     * Get user visible description
     */
    public static function getDescription(): string
    {
        return __('Login using password only.');
    }

    /**
     * Return an application ID
     *
     * Either hostname or hostname with scheme.
     *
     * @param bool $returnUrl Whether to generate URL
     */
    public function getAppId(bool $returnUrl): string
    {
        $GLOBALS['config'] ??= null;

        $url = $GLOBALS['config']->get('PmaAbsoluteUri');
        $parsed = [];
        if (! empty($url)) {
            $parsedUrl = parse_url($url);

            if (is_array($parsedUrl)) {
                $parsed = $parsedUrl;
            }
        }

        if (! isset($parsed['scheme']) || $parsed['scheme'] === '') {
            $parsed['scheme'] = $GLOBALS['config']->isHttps() ? 'https' : 'http';
        }

        if (! isset($parsed['host']) || $parsed['host'] === '') {
            $parsed['host'] = Core::getenv('HTTP_HOST');
        }

        if ($returnUrl) {
            $port = '';
            if (isset($parsed['port'])) {
                $port = ':' . $parsed['port'];
            }

            return sprintf('%s://%s%s', $parsed['scheme'], $parsed['host'], $port);
        }

        return $parsed['host'];
    }
}
