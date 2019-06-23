<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PhpMyAdmin\Controllers\Server\CollationsController
 *
 * @package PhpMyAdmin\Controllers
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\Charsets;
use PhpMyAdmin\Charsets\Charset;
use PhpMyAdmin\Charsets\Collation;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;

/**
 * Handles viewing character sets and collations
 *
 * @package PhpMyAdmin\Controllers
 */
class CollationsController extends AbstractController
{
    /**
     * @var array|null
     */
    private $charsets;

    /**
     * @var array|null
     */
    private $collations;

    /**
     * CollationsController constructor.
     *
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

    /**
     * Index action
     *
     * @return string HTML
     */
    public function indexAction(): string
    {
        include_once ROOT_PATH . 'libraries/server_common.inc.php';

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

        return $this->template->render('server/collations/index', [
            'charsets' => $charsets,
        ]);
    }
}
