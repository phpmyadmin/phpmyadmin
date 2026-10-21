<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\Charsets;
use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;

/**
 * Handles viewing character sets and collations
 */
#[Route('/server/collations', ['GET'])]
final class CollationsController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly DatabaseInterface $dbi,
        private readonly Config $config,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $charsets = Charsets::getCharsets($this->dbi, $this->config->selectedServer['DisableIS']);
        $collations = Charsets::getCollations($this->dbi, $this->config->selectedServer['DisableIS']);

        if ($this->dbi->isSuperUser()) {
            $this->dbi->selectDb('mysql');
        }

        $charsetsWithCollations = [];
        foreach ($charsets as $charset) {
            $charsetCollations = [];
            foreach ($collations[$charset->getName()] as $collation) {
                $charsetCollations[] = [
                    'name' => $collation->getName(),
                    'description' => $collation->getDescription(),
                    'is_default' => $collation->isDefault(),
                ];
            }

            $charsetsWithCollations[] = [
                'name' => $charset->getName(),
                'description' => $charset->getDescription(),
                'collations' => $charsetCollations,
            ];
        }

        $this->response->render('server/collations/index', ['charsets' => $charsetsWithCollations]);

        return $this->response->response();
    }
}
