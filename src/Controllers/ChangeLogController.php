<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Config;
use PhpMyAdmin\Core;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;

use function __;
use function array_column;
use function array_keys;
use function basename;
use function file_get_contents;
use function htmlspecialchars;
use function is_readable;
use function ob_get_clean;
use function ob_start;
use function preg_match_all;
use function preg_replace;
use function preg_replace_callback;
use function readgzfile;
use function sprintf;
use function str_ends_with;

use const PREG_SET_ORDER;

#[Route('/changelog', ['GET'])]
final readonly class ChangeLogController implements InvocableController
{
    public function __construct(
        private ResponseRenderer $response,
        private Config $config,
        private ResponseFactory $responseFactory,
        private Template $template,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $response = $this->responseFactory->createResponse();
        foreach ($this->response->getHeader()->getHttpHeaders() as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        $filename = $this->config->getChangeLogFilePath();

        /**
         * Read changelog.
         */
        // Check if the file is available, some distributions remove these.
        if (! @is_readable($filename)) {
            return $response->write(sprintf(
                __('The %s file is not available on this system, please visit %s for more information.'),
                basename($filename),
                '<a href="' . Core::linkURL('https://www.phpmyadmin.net/')
                . '" rel="noopener noreferrer" target="_blank">phpmyadmin.net</a>',
            ));
        }

        // Test if the file is in a compressed format
        if (str_ends_with($filename, '.gz')) {
            ob_start();
            readgzfile($filename);
            $changelog = ob_get_clean();
        } else {
            $changelog = file_get_contents($filename);
        }

        /**
         * Whole changelog in variable.
         */
        $changelog = htmlspecialchars((string) $changelog);

        $githubUrl = 'https://github.com/phpmyadmin/phpmyadmin/';
        $faqUrl = 'https://docs.phpmyadmin.net/en/latest/faq.html';

        preg_match_all('@\n\[(\d+\.\d+\.\d+)]: (' . $githubUrl . '\S+)@', $changelog, $matches, PREG_SET_ORDER);
        $releaseLinks = array_column($matches, 2, 1);
        $changelog = (string) preg_replace_callback(
            '/\n## \[(\d+\.\d+\.\d+)]/',
            static fn (array $matches): string => "\n## [" . $matches[1] . '](' . $releaseLinks[$matches[1]] . ')',
            $changelog,
        );

        $replaces = [
            '/# Changes in phpMyAdmin (\d+\.\d+)/' => '<h1>Changes in phpMyAdmin \\1</h1>',
            '@\[Keep a Changelog\]\(https://keepachangelog.com/\)@' => 'Keep a Changelog',
            '/\n### (Added|Changed|Deprecated|Removed|Fixed|Security)/' => "\n" . '<h3>\\1</h3>',

            // Add link to release title
            '@\n## \[(\d+\.\d+\.\d+)\]\((' . $githubUrl . '\S+)\) \- (\d{4}|YYYY)-(\d{2}|MM)-(\d{2}|DD)\n@' => "\n"
                . '<h2><a href="' . Url::getFromRoute('/url') . '&url=\\2">\\1</a> \\3-\\4-\\5</h2>' . "\n",

            // Add link to GitHub issues/commits
            '@\[(\#\d+|[a-z0-9]+)\]\((' . $githubUrl . '\S+)\)@' => '<a href="'
                . Url::getFromRoute('/url') . '&url=\\2">\\1</a>',

            // FAQ entries
            '/FAQ ([0-9]+)\.([0-9a-z]+)/i' => '<a href="'
                . Url::getFromRoute('/url') . '&url=' . $faqUrl . '#faq\\1-\\2">FAQ \\1.\\2</a>',

            // CVE/CAN entries
            '/((CAN|CVE)-[0-9]+-[0-9]+)/' => '<a href="' . Url::getFromRoute('/url') . '&url='
                . 'https://www.cve.org/CVERecord?id=\\1">\\1</a>',

            // PMASA entries
            '/(PMASA-[0-9]+-[0-9]+)/' => '<a href="'
                . Url::getFromRoute('/url') . '&url=https://www.phpmyadmin.net/security/\\1/">\\1</a>',

            // Links target and rel
            '/a href="/' => 'a target="_blank" rel="noopener noreferrer" href="',

            // Remove release link references
            '@\n\[(\d+\.\d+\.\d+)]: (' . $githubUrl . '\S+)@' => '',

            '/YYYY-MM-DD/' => '(not yet released)',
        ];

        return $response->write($this->template->render('changelog', [
            'changelog' => preg_replace(array_keys($replaces), $replaces, $changelog),
        ]));
    }
}
