<?php
/**
 * Holds the PhpMyAdmin\Controllers\Server\CollationsController
 */

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\Charsets;
use PhpMyAdmin\Charsets\Charset;
use PhpMyAdmin\Charsets\Collation;
use PhpMyAdmin\Common;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;

/**
 * Handles viewing character sets and collations
 */
class CollationsController extends AbstractController
{
    /** @var array|null */
    private $charsets;

    /** @var array|null */
    private $collations;

    /**
     * @param Response          $response   Response object
     * @param DatabaseInterface $dbi        DatabaseInterface object
     * @param Template          $template   Template object
     * @param array|null        $charsets   Array of charsets
     * @param array|null        $collations Array of collations
     */
    public function __construct(
        $response,
        $dbi,
        Template $template,
        ?array $charsets = null,
        ?array $collations = null
    ) {
        global $cfg;

        parent::__construct($response, $dbi, $template);

        $this->charsets = $charsets ?? Charsets::getCharsets(
            $this->dbi,
            $cfg['Server']['DisableIS']
        );
        $this->collations = $collations ?? Charsets::getCollations(
            $this->dbi,
            $cfg['Server']['DisableIS']
        );
    }

    public function index(): void
    {
        Common::server();

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
