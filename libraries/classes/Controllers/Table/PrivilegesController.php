<?php
/**
 * Controller for table privileges
 */

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\ResponseRenderer;
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

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        string $db,
        string $table,
        Privileges $privileges,
        DatabaseInterface $dbi
    ) {
        parent::__construct($response, $template, $db, $table);
        $this->privileges = $privileges;
        $this->dbi = $dbi;
    }

    /**
     * @param array $params Request parameters
     */
    public function __invoke(array $params): string
    {
        global $cfg, $text_dir;

        $scriptName = Util::getScriptNameForOption($cfg['DefaultTabTable'], 'table');

        $privileges = [];
        if ($this->dbi->isSuperUser()) {
            $privileges = $this->privileges->getAllPrivileges($params['checkprivsdb'], $params['checkprivstable']);
        }

        return $this->template->render('table/privileges/index', [
            'db' => $params['checkprivsdb'],
            'table' => $params['checkprivstable'],
            'is_superuser' => $this->dbi->isSuperUser(),
            'table_url' => $scriptName,
            'text_dir' => $text_dir,
            'is_createuser' => $this->dbi->isCreateUser(),
            'is_grantuser' => $this->dbi->isGrantUser(),
            'privileges' => $privileges,
        ]);
    }
}
