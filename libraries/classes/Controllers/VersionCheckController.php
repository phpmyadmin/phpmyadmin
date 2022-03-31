<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Core;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\VersionInformation;

use function json_encode;

/**
 * A caching proxy for retrieving version information from https://www.phpmyadmin.net/.
 */
class VersionCheckController extends AbstractController
{
    /** @var VersionInformation */
    private $versionInformation;

    public function __construct(ResponseRenderer $response, Template $template, VersionInformation $versionInformation)
    {
        parent::__construct($response, $template);
        $this->versionInformation = $versionInformation;
    }

    public function __invoke(): void
    {
        $_GET['ajax_request'] = 'true';

        // Disabling standard response.
        $this->response->disable();

        // Always send the correct headers
        Core::headerJSON();

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

        echo json_encode([
            'version' => ! empty($version) ? $version : '',
            'date' => ! empty($date) ? $date : '',
        ]);
    }
}
