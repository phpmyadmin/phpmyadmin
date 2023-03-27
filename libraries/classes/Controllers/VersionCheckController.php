<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Core;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\VersionInformation;

use function header;
use function json_encode;
use function sprintf;

/**
 * A caching proxy for retrieving version information from https://www.phpmyadmin.net/.
 */
class VersionCheckController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private VersionInformation $versionInformation,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $_GET['ajax_request'] = 'true';

        // Disabling standard response.
        $this->response->disable();

        // Always send the correct headers
        foreach (Core::headerJSON() as $name => $value) {
            header(sprintf('%s: %s', $name, $value));
        }

        $versionDetails = $this->versionInformation->getLatestVersion();

        if (empty($versionDetails)) {
            echo json_encode([]);

            return;
        }

        $latestCompatible = $this->versionInformation->getLatestCompatibleVersion($versionDetails->releases);
        $version = '';
        $date = '';
        if ($latestCompatible != null) {
            $version = $latestCompatible['version'];
            $date = $latestCompatible['date'];
        }

        echo json_encode(['version' => ! empty($version) ? $version : '', 'date' => ! empty($date) ? $date : '']);
    }
}
