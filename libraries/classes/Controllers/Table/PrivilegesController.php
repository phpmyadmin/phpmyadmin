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

    /**
     * @param Response          $response   Response object
     * @param DatabaseInterface $dbi        DatabaseInterface object
     * @param Template          $template   Template object
     * @param string            $db         Database name
     * @param string            $table      Table name
     * @param Privileges        $privileges Privileges object
     */
    public function __construct($response, $dbi, Template $template, $db, $table, Privileges $privileges)
    {
        parent::__construct($response, $dbi, $template, $db, $table);
        $this->privileges = $privileges;
    }

    /**
     * @param array $params Request parameters
     */
    public function index(array $params): string
    {
        global $cfg, $pmaThemeImage, $text_dir, $is_createuser, $is_grantuser;

        $scriptName = Util::getScriptNameForOption(
            $cfg['DefaultTabTable'],
            'table'
        );

        $privileges = [];
        if ($this->dbi->isSuperuser()) {
            $privileges = $this->privileges->getAllPrivileges(
                $params['checkprivsdb'],
                $params['checkprivstable']
            );
        }

        return $this->template->render('table/privileges/index', [
            'db' => $params['checkprivsdb'],
            'table' => $params['checkprivstable'],
            'is_superuser' => $this->dbi->isSuperuser(),
            'table_url' => $scriptName,
            'pma_theme_image' => $pmaThemeImage,
            'text_dir' => $text_dir,
            'is_createuser' => $is_createuser,
            'is_grantuser' => $is_grantuser,
            'privileges' => $privileges,
        ]);
    }
}
