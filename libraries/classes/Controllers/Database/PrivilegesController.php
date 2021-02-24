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

    /** @var DatabaseInterface */
    private $dbi;

    /**
     * @param Response          $response
     * @param string            $db       Database name
     * @param DatabaseInterface $dbi
     */
    public function __construct($response, Template $template, $db, Privileges $privileges, $dbi)
    {
        parent::__construct($response, $template, $db);
        $this->privileges = $privileges;
        $this->dbi = $dbi;
    }

    /**
     * @param array $params Request parameters
     */
    public function index(array $params): string
    {
        global $cfg, $text_dir, $PMA_Theme;

        $scriptName = Util::getScriptNameForOption(
            $cfg['DefaultTabDatabase'],
            'database'
        );

        $privileges = [];
        if ($this->dbi->isSuperUser()) {
            $privileges = $this->privileges->getAllPrivileges($params['checkprivsdb']);
        }

        return $this->template->render('database/privileges/index', [
            'is_superuser' => $this->dbi->isSuperUser(),
            'db' => $params['checkprivsdb'],
            'database_url' => $scriptName,
            'theme_image_path' => $PMA_Theme->getImgPath(),
            'text_dir' => $text_dir,
            'is_createuser' => $this->dbi->isCreateUser(),
            'is_grantuser' => $this->dbi->isGrantUser(),
            'privileges' => $privileges,
        ]);
    }
}
