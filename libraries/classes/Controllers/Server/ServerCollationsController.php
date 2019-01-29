<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PhpMyAdmin\Controllers\Server\ServerCollationsController
 *
 * @package PhpMyAdmin\Controllers
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\Charsets;
use PhpMyAdmin\Controllers\Controller;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Response;

/**
 * Handles viewing character sets and collations
 *
 * @package PhpMyAdmin\Controllers
 */
class ServerCollationsController extends Controller
{
    /**
     * @var array|null
     */
    private $charsets;

    /**
     * @var array|null
     */
    private $charsetsDescriptions;

    /**
     * @var array|null
     */
    private $collations;

    /**
     * @var array|null
     */
    private $defaultCollations;

    /**
     * ServerCollationsController constructor.
     *
     * @param Response          $response             Response object
     * @param DatabaseInterface $dbi                  DatabaseInterface object
     * @param array|null        $charsets             Array of charsets
     * @param array|null        $charsetsDescriptions Array of charsets descriptions
     * @param array|null        $collations           Array of collations
     * @param array|null        $defaultCollations    Array of default collations
     */
    public function __construct(
        $response,
        $dbi,
        ?array $charsets = null,
        ?array $charsetsDescriptions = null,
        ?array $collations = null,
        ?array $defaultCollations = null
    ) {
        global $cfg;

        parent::__construct($response, $dbi);

        $this->charsets = $charsets ?? Charsets::getMySQLCharsets(
            $this->dbi,
            $cfg['Server']['DisableIS']
        );
        $this->charsetsDescriptions = $charsetsDescriptions ?? Charsets::getMySQLCharsetsDescriptions(
            $this->dbi,
            $cfg['Server']['DisableIS']
        );
        $this->collations = $collations ?? Charsets::getMySQLCollations(
            $this->dbi,
            $cfg['Server']['DisableIS']
        );
        $this->defaultCollations = $defaultCollations ?? Charsets::getMySQLCollationsDefault(
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
        foreach ($this->charsets as $charset) {
            $charsetCollations = [];
            foreach ($this->collations[$charset] as $collation) {
                $charsetCollations[] = [
                    'name' => $collation,
                    'description' => Charsets::getCollationDescr($collation),
                    'is_default' => $collation === $this->defaultCollations[$charset],
                ];
            }

            $charsets[] = [
                'name' => $charset,
                'description' => $this->charsetsDescriptions[$charset] ?? '',
                'collations' => $charsetCollations,
            ];
        }

        return $this->template->render('server/collations/index', [
            'charsets' => $charsets,
        ]);
    }
}
