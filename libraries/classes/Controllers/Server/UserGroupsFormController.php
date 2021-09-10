<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\CheckUserPrivileges;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Relation;
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

    /**
     * @param ResponseRenderer  $response
     * @param DatabaseInterface $dbi
     */
    public function __construct($response, Template $template, Relation $relation, $dbi)
    {
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

        $cfgRelation = $this->relation->getRelationsParam();

        if (! $cfgRelation['menuswork']) {
            $this->response->setRequestStatus(false);
            $this->response->setHttpResponseCode(400);
            $this->response->addJSON('message', __('User groups management is not enabled.'));

            return;
        }

        $form = $this->getHtmlToChooseUserGroup($username, $cfgRelation);

        $this->response->addJSON('message', $form);
    }

    /**
     * Displays a dropdown to select the user group with menu items configured to each of them.
     *
     * @param array<string, mixed> $cfgRelation
     */
    private function getHtmlToChooseUserGroup(string $username, array $cfgRelation): string
    {
        $groupTable = Util::backquote($cfgRelation['db']) . '.' . Util::backquote($cfgRelation['usergroups']);
        $userTable = Util::backquote($cfgRelation['db']) . '.' . Util::backquote($cfgRelation['users']);

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
