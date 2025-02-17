<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\UserGroups;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;

use function __;
use function is_string;

/**
 * Displays the 'User groups' sub page under 'Users' page.
 */
final class UserGroupsController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly Relation $relation,
        private readonly DatabaseInterface $dbi,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $configurableMenusFeature = $this->relation->getRelationParameters()->configurableMenusFeature;
        if ($configurableMenusFeature === null) {
            return $this->response->response();
        }

        $this->response->addScriptFiles(['server/user_groups.js']);

        /**
         * Only allowed to superuser
         */
        if (! $this->dbi->isSuperUser()) {
            $this->response->addHTML(
                Message::error(__('No Privileges'))->getDisplay(),
            );

            return $this->response->response();
        }

        $this->response->addHTML('<div class="container-fluid">');
        $this->response->render('server/privileges/subnav', [
            'active' => 'user-groups',
            'is_super_user' => $this->dbi->isSuperUser(),
        ]);

        $userGroup = $request->getParsedBodyParamAsStringOrNull('userGroup');
        if ($request->hasBodyParam('deleteUserGroup') && is_string($userGroup) && $userGroup !== '') {
            UserGroups::delete($this->dbi, $configurableMenusFeature, $userGroup);
        }

        /**
         * Add a new user group
         */
        if ($request->hasBodyParam('addUserGroupSubmit')) {
            UserGroups::edit($configurableMenusFeature, $request->getParsedBodyParamAsString('userGroup'), true);
        }

        /**
         * Update a user group
         */
        if ($request->hasBodyParam('editUserGroupSubmit')) {
            UserGroups::edit($configurableMenusFeature, $request->getParsedBodyParamAsString('userGroup'));
        }

        if ($request->hasBodyParam('viewUsers')) {
            // Display users belonging to a user group
            $this->response->addHTML(UserGroups::getHtmlForListingUsersofAGroup(
                $configurableMenusFeature,
                $request->getParsedBodyParamAsString('userGroup'),
            ));
        }

        if ($request->hasQueryParam('addUserGroup')) {
            // Display add user group dialog
            $this->response->addHTML(UserGroups::getHtmlToEditUserGroup($configurableMenusFeature));
        } elseif ($request->hasBodyParam('editUserGroup')) {
            // Display edit user group dialog
            $this->response->addHTML(UserGroups::getHtmlToEditUserGroup(
                $configurableMenusFeature,
                $request->getParsedBodyParamAsStringOrNull('userGroup'),
            ));
        } else {
            // Display user groups table
            $this->response->addHTML(UserGroups::getHtmlForUserGroupsTable($configurableMenusFeature));
        }

        $this->response->addHTML('</div>');

        return $this->response->response();
    }
}
