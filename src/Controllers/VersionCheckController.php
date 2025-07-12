<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Core;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\VersionInformation;

use function json_encode;

/**
 * A caching proxy for retrieving version information from https://www.phpmyadmin.net/.
 */
#[Route('/version-check', ['GET', 'POST'])]
final class VersionCheckController implements InvocableController
{
    public function __construct(
        private readonly VersionInformation $versionInformation,
        private readonly ResponseFactory $responseFactory,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $response = $this->responseFactory->createResponse();
        foreach (Core::headerJSON() as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        $versionDetails = $this->versionInformation->getLatestVersions();

        if ($versionDetails === null) {
            return $response->write((string) json_encode([]));
        }

        $latestCompatible = $this->versionInformation->getLatestCompatibleVersion($versionDetails);
        $version = '';
        $date = '';
        if ($latestCompatible !== null) {
            $version = $latestCompatible->version;
            $date = $latestCompatible->date;
        }

        return $response->write((string) json_encode(['version' => $version, 'date' => $date]));
    }
}
