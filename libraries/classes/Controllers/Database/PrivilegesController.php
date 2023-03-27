<?php
/**
 * Controller for database privileges
 */

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\CheckUserPrivileges;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Dbal\DatabaseName;
use PhpMyAdmin\Dbal\InvalidDatabaseName;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Server\Privileges;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;

use function __;
use function mb_strtolower;

/**
 * Controller for database privileges
 */
class PrivilegesController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private Privileges $privileges,
        private DatabaseInterface $dbi,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        try {
            $db = DatabaseName::fromValue($request->getParam('db'));
            if ($this->dbi->getLowerCaseNames() === 1) {
                $db = DatabaseName::fromValue(mb_strtolower($db->getName()));
            }
        } catch (InvalidDatabaseName $exception) {
            $this->response->addHTML(Message::error($exception->getMessage())->getDisplay());

            return;
        }

        $checkUserPrivileges = new CheckUserPrivileges($this->dbi);
        $checkUserPrivileges->getPrivileges();

        $this->addScriptFiles(['server/privileges.js', 'vendor/zxcvbn-ts.js']);

        /**
         * Checks if the user is allowed to do what they try to...
         */
        $isGrantUser = $this->dbi->isGrantUser();
        $isCreateUser = $this->dbi->isCreateUser();

        if (! $this->dbi->isSuperUser() && ! $isGrantUser && ! $isCreateUser) {
            $this->render('server/sub_page_header', ['type' => 'privileges', 'is_image' => false]);
            $this->response->addHTML(
                Message::error(__('No Privileges'))
                    ->getDisplay(),
            );

            return;
        }

        if (! $isGrantUser && ! $isCreateUser) {
            $this->response->addHTML(Message::notice(
                __('You do not have the privileges to administrate the users!'),
            )->getDisplay());
        }

        $scriptName = Util::getScriptNameForOption($GLOBALS['cfg']['DefaultTabDatabase'], 'database');

        $privileges = [];
        if ($this->dbi->isSuperUser()) {
            $privileges = $this->privileges->getAllPrivileges($db);
        }

        $this->render('database/privileges/index', [
            'is_superuser' => $this->dbi->isSuperUser(),
            'db' => $db->getName(),
            'database_url' => $scriptName,
            'text_dir' => $GLOBALS['text_dir'],
            'is_createuser' => $this->dbi->isCreateUser(),
            'is_grantuser' => $this->dbi->isGrantUser(),
            'privileges' => $privileges,
        ]);
        $this->render('export_modal');
    }
}
