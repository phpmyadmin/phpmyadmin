<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\Charsets;
use PhpMyAdmin\Charsets\Charset;
use PhpMyAdmin\Charsets\Collation;
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
    /** @var array<string, Charset> */
    private array $charsets;

    /** @var array<string, array<string, Collation>> */
    private array $collations;

    /**
     * @param array<string, Charset>|null                  $charsets
     * @param array<string, array<string, Collation>>|null $collations
     */
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly DatabaseInterface $dbi,
        Config $config,
        array|null $charsets = null,
        array|null $collations = null,
    ) {
        $this->charsets = $charsets ?? Charsets::getCharsets($this->dbi, $config->selectedServer['DisableIS']);
        $this->collations = $collations ?? Charsets::getCollations($this->dbi, $config->selectedServer['DisableIS']);
    }

    public function __invoke(ServerRequest $request): Response
    {
        if ($this->dbi->isSuperUser()) {
            $this->dbi->selectDb('mysql');
        }

        $charsets = [];
        foreach ($this->charsets as $charset) {
            $charsetCollations = [];
            foreach ($this->collations[$charset->getName()] as $collation) {
                $charsetCollations[] = [
                    'name' => $collation->getName(),
                    'description' => $collation->getDescription(),
                    'is_default' => $collation->isDefault(),
                ];
            }

            $charsets[] = [
                'name' => $charset->getName(),
                'description' => $charset->getDescription(),
                'collations' => $charsetCollations,
            ];
        }

        $this->response->render('server/collations/index', ['charsets' => $charsets]);

        return $this->response->response();
    }
}
