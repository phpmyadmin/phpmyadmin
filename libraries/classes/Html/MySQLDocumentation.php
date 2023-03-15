<?php
/**
 * Generate HTML for MySQL Documentation
 */

declare(strict_types=1);

namespace PhpMyAdmin\Html;

use PhpMyAdmin\Core;
use PhpMyAdmin\Util;

use function __;
use function defined;
use function file_exists;
use function htmlspecialchars;

/**
 * Generate HTML for MySQL Documentation
 */
class MySQLDocumentation
{
    /**
     * Displays a link to the official MySQL documentation
     *
     * @param string      $link    contains name of page/anchor that is being linked
     * @param bool        $bigIcon whether to use big icon (like in left frame)
     * @param string|null $url     href attribute
     * @param string|null $text    text of link
     * @param string      $anchor  anchor to page part
     *
     * @return string  the html link
     */
    public static function show(
        string $link,
        bool $bigIcon = false,
        string|null $url = null,
        string|null $text = null,
        string $anchor = '',
    ): string {
        if ($url === null) {
            $url = Util::getMySQLDocuURL($link, $anchor);
        }

        $openLink = '<a href="' . htmlspecialchars($url) . '" target="mysql_doc">';
        $closeLink = '</a>';

        if ($bigIcon) {
            $html = $openLink . Generator::getImage('b_sqlhelp', __('Documentation')) . $closeLink;
        } elseif ($text !== null) {
            $html = $openLink . $text . $closeLink;
        } else {
            $html = Generator::showDocumentationLink($url, 'mysql_doc');
        }

        return $html;
    }

    /**
     * Displays a link to the phpMyAdmin documentation
     *
     * @param string $page            Page in documentation
     * @param string $anchor          Optional anchor in page
     * @param bool   $bbcode          Optional flag indicating whether to output bbcode
     * @param bool   $disableTabIndex Optional flag indicating that a negative tabindex should be set on the link
     *
     * @return string  the html link
     */
    public static function showDocumentation(
        string $page,
        string $anchor = '',
        bool $bbcode = false,
        bool $disableTabIndex = false,
    ): string {
        return Generator::showDocumentationLink(
            self::getDocumentationLink($page, $anchor),
            'documentation',
            $bbcode,
            $disableTabIndex,
        );
    }

    /**
     * Returns link to documentation.
     *
     * @param string $page       Page in documentation
     * @param string $anchor     Optional anchor in page
     * @param string $pathPrefix Optional path in case it is called in a folder (e.g. setup)
     *
     * @return string URL
     */
    public static function getDocumentationLink(string $page, string $anchor = '', string $pathPrefix = './'): string
    {
        /* Construct base URL */
        $url = $page . '.html';
        if (! empty($anchor)) {
            $url .= '#' . $anchor;
        }

        /**
         * Check if we have built local documentation, however
         * provide consistent URL for testsuite
         */
        if (! defined('TESTSUITE') && @file_exists(ROOT_PATH . 'doc/html/index.html')) {
            return $pathPrefix . 'doc/html/' . $url;
        }

        return Core::linkURL('https://docs.phpmyadmin.net/en/latest/' . $url);
    }
}
