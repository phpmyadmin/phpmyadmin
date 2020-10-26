<?php
/**
 * Controller for table privileges
 */

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Response;
use PhpMyAdmin\Server\Privileges;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;

/**
 * Controller for table privileges
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
     * @param string            $table    Table name
     * @param DatabaseInterface $dbi
     */
    public function __construct($response, Template $template, $db, $table, Privileges $privileges, $dbi)
    {
        parent::__construct($response, $template, $db, $table);
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
            $cfg['DefaultTabTable'],
            'table'
        );

        $privileges = [];
        if ($this->dbi->isSuperUser()) {
            $privileges = $this->privileges->getAllPrivileges(
                $params['checkprivsdb'],
                $params['checkprivstable']
            );
        }

        return $this->template->render('table/privileges/index', [
            'db' => $params['checkprivsdb'],
            'table' => $params['checkprivstable'],
            'is_superuser' => $this->dbi->isSuperUser(),
            'table_url' => $scriptName,
            'theme_image_path' => $PMA_Theme->getImgPath(),
            'text_dir' => $text_dir,
            'is_createuser' => $this->dbi->isCreateUser(),
            'is_grantuser' => $this->dbi->isGrantUser(),
            'privileges' => $privileges,
        ]);
    }
}
