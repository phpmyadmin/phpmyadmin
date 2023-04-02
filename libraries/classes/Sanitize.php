<?php
/**
 * This class includes various sanitization methods that can be called statically
 */

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\Html\MySQLDocumentation;

use function __;
use function array_keys;
use function array_merge;
use function count;
use function htmlspecialchars;
use function in_array;
use function is_string;
use function json_encode;
use function preg_match;
use function preg_replace;
use function preg_replace_callback;
use function str_starts_with;
use function strlen;
use function strtolower;
use function strtr;
use function substr;

use const JSON_HEX_TAG;

/**
 * This class includes various sanitization methods that can be called statically
 */
class Sanitize
{
    /**
     * Checks whether given link is valid
     *
     * @param string $url   URL to check
     * @param bool   $http  Whether to allow http links
     * @param bool   $other Whether to allow ftp and mailto links
     */
    public static function checkLink(string $url, bool $http = false, bool $other = false): bool
    {
        $url = strtolower($url);
        $validStarts = ['https://', 'index.php?route=/url&url=https%3a%2f%2f', './doc/html/', './index.php?'];
        $isSetup = self::isSetup();
        // Adjust path to setup script location
        if ($isSetup) {
            foreach ($validStarts as $key => $value) {
                if (substr($value, 0, 2) !== './') {
                    continue;
                }

                $validStarts[$key] = '.' . $value;
            }
        }

        if ($other) {
            $validStarts[] = 'mailto:';
            $validStarts[] = 'ftp://';
        }

        if ($http) {
            $validStarts[] = 'http://';
        }

        if ($isSetup) {
            $validStarts[] = '?page=form&';
            $validStarts[] = '?page=servers&';
        }

        foreach ($validStarts as $val) {
            if (substr($url, 0, strlen($val)) == $val) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if we are currently on a setup folder page
     */
    public static function isSetup(): bool
    {
        return $GLOBALS['config'] !== null && $GLOBALS['config']->get('is_setup');
    }

    /**
     * Callback function for replacing [a@link@target] links in bb code.
     *
     * @param mixed[] $found Array of preg matches
     *
     * @return string Replaced string
     */
    public static function replaceBBLink(array $found): string
    {
        /* Check for valid link */
        if (! self::checkLink($found[1])) {
            return $found[0];
        }

        /* a-z and _ allowed in target */
        if (! empty($found[3]) && preg_match('/[^a-z_]+/i', $found[3])) {
            return $found[0];
        }

        /* Construct target */
        $target = '';
        if (! empty($found[3])) {
            $target = ' target="' . $found[3] . '"';
            if ($found[3] === '_blank') {
                $target .= ' rel="noopener noreferrer"';
            }
        }

        /* Construct url */
        if (substr($found[1], 0, 4) === 'http') {
            $url = Core::linkURL($found[1]);
        } else {
            $url = $found[1];
        }

        return '<a href="' . $url . '"' . $target . '>';
    }

    /**
     * Callback function for replacing [doc@anchor] links in bb code.
     *
     * @param string[] $found Array of preg matches
     */
    public static function replaceDocLink(array $found): string
    {
        if (count($found) >= 4) {
            /* doc@page@anchor pattern */
            $page = $found[1];
            $anchor = $found[3];
        } else {
            /* doc@anchor pattern */
            $anchor = $found[1];
            if (str_starts_with($anchor, 'faq')) {
                $page = 'faq';
            } elseif (str_starts_with($anchor, 'cfg')) {
                $page = 'config';
            } else {
                /* Guess */
                $page = 'setup';
            }
        }

        $link = MySQLDocumentation::getDocumentationLink($page, $anchor, self::isSetup() ? '../' : './');

        return '<a href="' . $link . '" target="documentation">';
    }

    /**
     * Sanitizes $message, taking into account our special codes
     * for formatting.
     *
     * If you want to include result in element attribute, you should escape it.
     *
     * Examples:
     *
     * <p><?php echo Sanitize::sanitizeMessage($foo); ?></p>
     *
     * <a title="<?php echo Sanitize::sanitizeMessage($foo, true); ?>">bar</a>
     *
     * @param string $message the message
     * @param bool   $escape  whether to escape html in result
     * @param bool   $safe    whether string is safe (can keep < and > chars)
     */
    public static function sanitizeMessage(string $message, bool $escape = false, bool $safe = false): string
    {
        if (! $safe) {
            $message = strtr($message, ['<' => '&lt;', '>' => '&gt;']);
        }

        /* Interpret bb code */
        $replacePairs = [
            '[em]' => '<em>',
            '[/em]' => '</em>',
            '[strong]' => '<strong>',
            '[/strong]' => '</strong>',
            '[code]' => '<code>',
            '[/code]' => '</code>',
            '[kbd]' => '<kbd>',
            '[/kbd]' => '</kbd>',
            '[br]' => '<br>',
            '[/a]' => '</a>',
            '[/doc]' => '</a>',
            '[sup]' => '<sup>',
            '[/sup]' => '</sup>',
            '[conferr]' => '<iframe src="show_config_errors.php"><a href='
                . '"show_config_errors.php">show_config_errors.php</a></iframe>',
            // used in libraries/Util.php
            '[dochelpicon]' => Html\Generator::getImage('b_help', __('Documentation')),
        ];

        $message = strtr($message, $replacePairs);

        /* Match links in bb code ([a@url@target], where @target is options) */
        $pattern = '/\[a@([^]"@]*)(@([^]"]*))?\]/';

        /* Find and replace all links */
        $message = (string) preg_replace_callback(
            $pattern,
            static fn (array $match) => self::replaceBBLink($match),
            $message,
        );

        /* Replace documentation links */
        $message = (string) preg_replace_callback(
            '/\[doc@([a-zA-Z0-9_-]+)(@([a-zA-Z0-9_-]*))?\]/',
            /** @param string[] $match */
            static fn (array $match): string => self::replaceDocLink($match),
            $message,
        );

        /* Possibly escape result */
        if ($escape) {
            return htmlspecialchars($message);
        }

        return $message;
    }

    /**
     * Sanitize a filename by removing anything besides legit characters
     *
     * Intended usecase:
     *    When using a filename in a Content-Disposition header
     *    the value should not contain ; or "
     *
     *    When exporting, avoiding generation of an unexpected double-extension file
     *
     * @param string $filename    The filename
     * @param bool   $replaceDots Whether to also replace dots
     *
     * @return string  the sanitized filename
     */
    public static function sanitizeFilename(string $filename, bool $replaceDots = false): string
    {
        $pattern = '/[^A-Za-z0-9_';
        // if we don't have to replace dots
        if (! $replaceDots) {
            // then add the dot to the list of legit characters
            $pattern .= '.';
        }

        $pattern .= '-]/';

        return preg_replace($pattern, '_', $filename);
    }

    /**
     * Formats an javascript assignment with proper escaping of a value
     * and support for assigning array of strings.
     *
     * @param string $key   Name of value to set
     * @param mixed  $value Value to set, can be either string or array of strings
     *
     * @return string Javascript code.
     */
    public static function getJsValue(string $key, mixed $value): string
    {
        return $key . ' = ' . json_encode($value, JSON_HEX_TAG) . ";\n";
    }

    /**
     * Removes all variables from request except allowed ones.
     *
     * @param string[] $allowList list of variables to allow
     */
    public static function removeRequestVars(array $allowList): void
    {
        // do not check only $_REQUEST because it could have been overwritten
        // and use type casting because the variables could have become
        // strings
        $keys = array_keys(
            array_merge((array) $_REQUEST, (array) $_GET, (array) $_POST, (array) $_COOKIE),
        );

        foreach ($keys as $key) {
            if (! in_array($key, $allowList)) {
                unset($_REQUEST[$key], $_GET[$key], $_POST[$key]);
                continue;
            }

            // allowed stuff could be compromised so escape it
            // we require it to be a string
            if (isset($_REQUEST[$key]) && ! is_string($_REQUEST[$key])) {
                unset($_REQUEST[$key]);
            }

            if (isset($_POST[$key]) && ! is_string($_POST[$key])) {
                unset($_POST[$key]);
            }

            if (isset($_COOKIE[$key]) && ! is_string($_COOKIE[$key])) {
                unset($_COOKIE[$key]);
            }

            if (! isset($_GET[$key]) || is_string($_GET[$key])) {
                continue;
            }

            unset($_GET[$key]);
        }
    }
}
