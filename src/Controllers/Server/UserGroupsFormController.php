<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\ConfigStorage\Features\ConfigurableMenusFeature;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Dbal\ConnectionType;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;

use function __;
use function sprintf;

#[Route('/server/user-groups/edit-form', ['GET'])]
final class UserGroupsFormController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly Template $template,
        private readonly Relation $relation,
        private readonly DatabaseInterface $dbi,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        if (! $request->isAjax()) {
            return $this->response->response();
        }

        /** @var string $username */
        $username = $request->getQueryParam('username', '');

        if ($username === '') {
            $this->response->setRequestStatus(false);
            $this->response->setStatusCode(StatusCodeInterface::STATUS_BAD_REQUEST);
            $this->response->addJSON('message', __('Missing parameter:') . ' username');

            return $this->response->response();
        }

        $configurableMenusFeature = $this->relation->getRelationParameters()->configurableMenusFeature;
        if ($configurableMenusFeature === null) {
            $this->response->setRequestStatus(false);
            $this->response->setStatusCode(StatusCodeInterface::STATUS_BAD_REQUEST);
            $this->response->addJSON('message', __('User groups management is not enabled.'));

            return $this->response->response();
        }

        $form = $this->getHtmlToChooseUserGroup($username, $configurableMenusFeature);

        $this->response->addJSON('message', $form);

        return $this->response->response();
    }

    /**
     * Displays a dropdown to select the user group with menu items configured to each of them.
     */
    private function getHtmlToChooseUserGroup(
        string $username,
        ConfigurableMenusFeature $configurableMenusFeature,
    ): string {
        $groupTable = Util::backquote($configurableMenusFeature->database)
            . '.' . Util::backquote($configurableMenusFeature->userGroups);
        $userTable = Util::backquote($configurableMenusFeature->database)
            . '.' . Util::backquote($configurableMenusFeature->users);

        $sqlQuery = sprintf(
            'SELECT `usergroup` FROM %s WHERE `username` = %s',
            $userTable,
            $this->dbi->quoteString($username),
        );
        $userGroup = $this->dbi->fetchValue($sqlQuery, 0, ConnectionType::ControlUser);

        $allUserGroups = [];
        $sqlQuery = 'SELECT DISTINCT `usergroup` FROM ' . $groupTable;
        $result = $this->dbi->tryQueryAsControlUser($sqlQuery);
        if ($result) {
            while ($row = $result->fetchRow()) {
                $allUserGroups[$row[0]] = $row[0];
            }
        }

        return $this->template->render('server/privileges/choose_user_group', [
            'all_user_groups' => $allUserGroups,
            'user_group' => $userGroup,
            'params' => ['username' => $username],
        ]);
    }
}
