<?php
/**
 * @package PhpMyAdmin\Controllers
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Core;
use PhpMyAdmin\VersionInformation;

/**
 * A caching proxy for retrieving version information from https://www.phpmyadmin.net/.
 *
 * @package PhpMyAdmin\Controllers
 */
class VersionCheckController extends AbstractController
{
    /**
     * @return void
     */
    public function index(): void
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
            return;
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
    }
}
