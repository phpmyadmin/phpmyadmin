<?php
/**
 * Controller for table privileges
 */

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\CheckUserPrivileges;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Server\Privileges;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;

use function __;
use function mb_strtolower;

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
        Privileges $privileges,
        DatabaseInterface $dbi
    ) {
        parent::__construct($response, $template);
        $this->privileges = $privileges;
        $this->dbi = $dbi;
    }

    public function __invoke(): void
    {
        $GLOBALS['text_dir'] = $GLOBALS['text_dir'] ?? null;

        $checkUserPrivileges = new CheckUserPrivileges($this->dbi);
        $checkUserPrivileges->getPrivileges();

        $this->addScriptFiles(['server/privileges.js', 'vendor/zxcvbn-ts.js']);

        /**
         * Checks if the user is allowed to do what they try to...
         */
        $isGrantUser = $this->dbi->isGrantUser();
        $isCreateUser = $this->dbi->isCreateUser();

        if (! $this->dbi->isSuperUser() && ! $isGrantUser && ! $isCreateUser) {
            $this->render('server/sub_page_header', [
                'type' => 'privileges',
                'is_image' => false,
            ]);
            $this->response->addHTML(
                Message::error(__('No Privileges'))
                    ->getDisplay()
            );

            return;
        }

        if (! $isGrantUser && ! $isCreateUser) {
            $this->response->addHTML(Message::notice(
                __('You do not have the privileges to administrate the users!')
            )->getDisplay());
        }

        $scriptName = Util::getScriptNameForOption($GLOBALS['cfg']['DefaultTabTable'], 'table');

        $db = $GLOBALS['db'];
        $table = $GLOBALS['table'];
        if ($this->dbi->getLowerCaseNames() === '1') {
            $db = mb_strtolower($GLOBALS['db']);
            $table = mb_strtolower($GLOBALS['table']);
        }

        $privileges = [];
        if ($this->dbi->isSuperUser()) {
            $privileges = $this->privileges->getAllPrivileges($db, $table);
        }

        $this->render('table/privileges/index', [
            'db' => $db,
            'table' => $table,
            'is_superuser' => $this->dbi->isSuperUser(),
            'table_url' => $scriptName,
            'text_dir' => $GLOBALS['text_dir'],
            'is_createuser' => $this->dbi->isCreateUser(),
            'is_grantuser' => $this->dbi->isGrantUser(),
            'privileges' => $privileges,
        ]);
        $this->render('export_modal');
    }
}
