<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\Charsets;
use PhpMyAdmin\Charsets\Charset;
use PhpMyAdmin\Charsets\Collation;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;

/**
 * Handles viewing character sets and collations
 */
class CollationsController extends AbstractController
{
    /** @var array|null */
    private $charsets;

    /** @var array|null */
    private $collations;

    /** @var DatabaseInterface */
    private $dbi;

    /**
     * @param Response          $response
     * @param DatabaseInterface $dbi
     * @param array|null        $charsets   Array of charsets
     * @param array|null        $collations Array of collations
     */
    public function __construct(
        $response,
        Template $template,
        $dbi,
        ?array $charsets = null,
        ?array $collations = null
    ) {
        global $cfg;

        parent::__construct($response, $template);
        $this->dbi = $dbi;

        $this->charsets = $charsets ?? Charsets::getCharsets($this->dbi, $cfg['Server']['DisableIS']);
        $this->collations = $collations ?? Charsets::getCollations($this->dbi, $cfg['Server']['DisableIS']);
    }

    public function index(): void
    {
        global $err_url;

        $err_url = Url::getFromRoute('/');

        if ($this->dbi->isSuperUser()) {
            $this->dbi->selectDb('mysql');
        }

        $charsets = [];
        /** @var Charset $charset */
        foreach ($this->charsets as $charset) {
            $charsetCollations = [];
            /** @var Collation $collation */
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

        $this->render('server/collations/index', ['charsets' => $charsets]);
    }
}
