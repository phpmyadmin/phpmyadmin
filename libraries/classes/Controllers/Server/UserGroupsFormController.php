<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\CheckUserPrivileges;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Relation;
use PhpMyAdmin\RelationParameters;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;

use function __;
use function sprintf;
use function strlen;

final class UserGroupsFormController extends AbstractController
{
    /** @var Relation */
    private $relation;

    /** @var DatabaseInterface */
    private $dbi;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        Relation $relation,
        DatabaseInterface $dbi
    ) {
        parent::__construct($response, $template);
        $this->relation = $relation;
        $this->dbi = $dbi;
    }

    public function __invoke(): void
    {
        $this->response->setAjax(true);

        if (! isset($_GET['username']) || strlen((string) $_GET['username']) === 0) {
            $this->response->setRequestStatus(false);
            $this->response->setHttpResponseCode(400);
            $this->response->addJSON('message', __('Missing parameter:') . ' username');

            return;
        }

        $username = $_GET['username'];

        $checkUserPrivileges = new CheckUserPrivileges($this->dbi);
        $checkUserPrivileges->getPrivileges();

        $relationParameters = $this->relation->getRelationParameters();

        if (! $relationParameters->menuswork) {
            $this->response->setRequestStatus(false);
            $this->response->setHttpResponseCode(400);
            $this->response->addJSON('message', __('User groups management is not enabled.'));

            return;
        }

        $form = $this->getHtmlToChooseUserGroup($username, $relationParameters);

        $this->response->addJSON('message', $form);
    }

    /**
     * Displays a dropdown to select the user group with menu items configured to each of them.
     */
    private function getHtmlToChooseUserGroup(string $username, RelationParameters $relationParameters): string
    {
        $groupTable = Util::backquote($relationParameters->db) . '.' . Util::backquote($relationParameters->usergroups);
        $userTable = Util::backquote($relationParameters->db) . '.' . Util::backquote($relationParameters->users);

        $sqlQuery = sprintf(
            'SELECT `usergroup` FROM %s WHERE `username` = \'%s\'',
            $userTable,
            $this->dbi->escapeString($username)
        );
        $userGroup = $this->dbi->fetchValue($sqlQuery, 0, 0, DatabaseInterface::CONNECT_CONTROL);

        $allUserGroups = [];
        $sqlQuery = 'SELECT DISTINCT `usergroup` FROM ' . $groupTable;
        $result = $this->relation->queryAsControlUser($sqlQuery, false);
        if ($result) {
            while ($row = $this->dbi->fetchRow($result)) {
                $allUserGroups[$row[0]] = $row[0];
            }
        }

        $this->dbi->freeResult($result);

        return $this->template->render('server/privileges/choose_user_group', [
            'all_user_groups' => $allUserGroups,
            'user_group' => $userGroup,
            'params' => ['username' => $username],
        ]);
    }
}
