<?php
/**
 * Controller for table privileges
 */

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\InvalidIdentifier;
use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Server\Privileges;
use PhpMyAdmin\Url;

use function __;
use function mb_strtolower;

/**
 * Controller for table privileges
 */
#[Route('/table/privileges', ['GET'])]
final readonly class PrivilegesController implements InvocableController
{
    public function __construct(
        private ResponseRenderer $response,
        private Privileges $privileges,
        private DatabaseInterface $dbi,
        private Config $config,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        try {
            $db = DatabaseName::from($request->getParam('db'));
            $table = TableName::from($request->getParam('table'));
            if ($this->dbi->getLowerCaseNames() === 1) {
                $db = DatabaseName::from(mb_strtolower($db->getName()));
                $table = TableName::from(mb_strtolower($table->getName()));
            }
        } catch (InvalidIdentifier $exception) {
            $this->response->addHTML(Message::error($exception->getMessage())->getDisplay());

            return $this->response->response();
        }

        $this->response->addScriptFiles(['server/privileges.js', 'vendor/zxcvbn-ts.js']);

        /**
         * Checks if the user is allowed to do what they try to...
         */
        $isGrantUser = $this->dbi->isGrantUser();
        $isCreateUser = $this->dbi->isCreateUser();

        if (! $this->dbi->isSuperUser() && ! $isGrantUser && ! $isCreateUser) {
            $this->response->render('server/sub_page_header', ['type' => 'privileges', 'is_image' => false]);
            $this->response->addHTML(
                Message::error(__('No Privileges'))
                    ->getDisplay(),
            );

            return $this->response->response();
        }

        if (! $isGrantUser && ! $isCreateUser) {
            $this->response->addHTML(Message::notice(
                __('You do not have the privileges to administrate the users!'),
            )->getDisplay());
        }

        $scriptName = Url::getFromRoute($this->config->config->DefaultTabTable);

        $privileges = [];
        if ($this->dbi->isSuperUser()) {
            $privileges = $this->privileges->getAllPrivileges($db, $table);
        }

        $this->response->render('table/privileges/index', [
            'db' => $db->getName(),
            'table' => $table->getName(),
            'is_superuser' => $this->dbi->isSuperUser(),
            'table_url' => $scriptName,
            'is_createuser' => $this->dbi->isCreateUser(),
            'is_grantuser' => $this->dbi->isGrantUser(),
            'privileges' => $privileges,
        ]);
        $this->response->render('export_modal', []);

        return $this->response->response();
    }
}
