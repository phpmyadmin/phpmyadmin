<?php
/**
 * Controller for database privileges
 */

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Response;
use PhpMyAdmin\Server\Privileges;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;

/**
 * Controller for database privileges
 */
class PrivilegesController extends AbstractController
{
    /** @var Privileges */
    private $privileges;

    /**
     * @param Response          $response   Response object
     * @param DatabaseInterface $dbi        DatabaseInterface object
     * @param Template          $template   Template object
     * @param string            $db         Database name
     * @param Privileges        $privileges Privileges object
     */
    public function __construct($response, $dbi, Template $template, $db, Privileges $privileges)
    {
        parent::__construct($response, $dbi, $template, $db);
        $this->privileges = $privileges;
    }

    /**
     * @param array $params Request parameters
     */
    public function index(array $params): string
    {
        global $cfg, $text_dir, $is_createuser, $is_grantuser, $PMA_Theme;

        $scriptName = Util::getScriptNameForOption(
            $cfg['DefaultTabDatabase'],
            'database'
        );

        $privileges = [];
        if ($this->dbi->isSuperuser()) {
            $privileges = $this->privileges->getAllPrivileges($params['checkprivsdb']);
        }

        return $this->template->render('database/privileges/index', [
            'is_superuser' => $this->dbi->isSuperuser(),
            'db' => $params['checkprivsdb'],
            'database_url' => $scriptName,
            'theme_image_path' => $PMA_Theme->getImgPath(),
            'text_dir' => $text_dir,
            'is_createuser' => $is_createuser,
            'is_grantuser' => $is_grantuser,
            'privileges' => $privileges,
        ]);
    }
}
