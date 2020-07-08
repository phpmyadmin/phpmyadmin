<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Core;
use PhpMyAdmin\VersionInformation;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use function json_encode;

/**
 * A caching proxy for retrieving version information from https://www.phpmyadmin.net/.
 */
class VersionCheckController extends AbstractController
{
    public function index(Request $request, Response $response): Response
    {
        $_GET['ajax_request'] = 'true';

        // Disabling standard response.
        $this->response->disable();

        // Always send the correct headers
        Core::headerJSON();

        $versionInformation = new VersionInformation();
        $versionDetails = $versionInformation->getLatestVersion();

        if (empty($versionDetails)) {
            echo json_encode([]);

            return $response;
        }

        $latestCompatible = $versionInformation->getLatestCompatibleVersion(
            $versionDetails->releases
        );
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

        return $response;
    }
}
