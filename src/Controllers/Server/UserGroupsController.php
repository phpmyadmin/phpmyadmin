<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\UserGroups;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

use function __;
use function is_string;

/**
 * Displays the 'User groups' sub page under 'Users' page.
 */
class UserGroupsController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private Relation $relation,
        private DatabaseInterface $dbi,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $configurableMenusFeature = $this->relation->getRelationParameters()->configurableMenusFeature;
        if ($configurableMenusFeature === null) {
            return;
        }

        $this->addScriptFiles(['server/user_groups.js']);

        /**
         * Only allowed to superuser
         */
        if (! $this->dbi->isSuperUser()) {
            $this->response->addHTML(
                Message::error(__('No Privileges'))->getDisplay(),
            );

            return;
        }

        $this->response->addHTML('<div class="container-fluid">');
        $this->render('server/privileges/subnav', [
            'active' => 'user-groups',
            'is_super_user' => $this->dbi->isSuperUser(),
        ]);

        /** @var mixed $userGroup */
        $userGroup = $request->getParsedBodyParam('userGroup');
        if ($request->hasBodyParam('deleteUserGroup') && is_string($userGroup) && $userGroup !== '') {
            UserGroups::delete($this->dbi, $configurableMenusFeature, $userGroup);
        }

        /**
         * Add a new user group
         */
        if ($request->hasBodyParam('addUserGroupSubmit')) {
            UserGroups::edit($configurableMenusFeature, $request->getParsedBodyParam('userGroup'), true);
        }

        /**
         * Update a user group
         */
        if ($request->hasBodyParam('editUserGroupSubmit')) {
            UserGroups::edit($configurableMenusFeature, $request->getParsedBodyParam('userGroup'));
        }

        if ($request->hasBodyParam('viewUsers')) {
            // Display users belonging to a user group
            $this->response->addHTML(UserGroups::getHtmlForListingUsersofAGroup(
                $configurableMenusFeature,
                $request->getParsedBodyParam('userGroup'),
            ));
        }

        if ($request->hasQueryParam('addUserGroup')) {
            // Display add user group dialog
            $this->response->addHTML(UserGroups::getHtmlToEditUserGroup($configurableMenusFeature));
        } elseif ($request->hasBodyParam('editUserGroup')) {
            // Display edit user group dialog
            $this->response->addHTML(UserGroups::getHtmlToEditUserGroup(
                $configurableMenusFeature,
                $request->getParsedBodyParam('userGroup'),
            ));
        } else {
            // Display user groups table
            $this->response->addHTML(UserGroups::getHtmlForUserGroupsTable($configurableMenusFeature));
        }

        $this->response->addHTML('</div>');
    }
}
