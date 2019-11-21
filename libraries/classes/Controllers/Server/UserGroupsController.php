<?php
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Message;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Response;
use PhpMyAdmin\Server\UserGroups;
use PhpMyAdmin\Server\Users;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;

/**
 * Displays the 'User groups' sub page under 'Users' page.
 */
class UserGroupsController extends AbstractController
{
    /** @var Relation */
    private $relation;

    /**
     * @param Response          $response A Response instance.
     * @param DatabaseInterface $dbi      A DatabaseInterface instance.
     * @param Template          $template A Template instance.
     * @param Relation          $relation A Relation instance.
     */
    public function __construct($response, $dbi, Template $template, Relation $relation)
    {
        parent::__construct($response, $dbi, $template);
        $this->relation = $relation;
    }

    public function index(): void
    {
        $cfgRelation = $this->relation->getRelationsParam();
        if (! $cfgRelation['menuswork']) {
            return;
        }

        $header = $this->response->getHeader();
        $scripts = $header->getScripts();
        $scripts->addFile('dist/server/user_groups.js');

        /**
         * Only allowed to superuser
         */
        if (! $this->dbi->isSuperuser()) {
            $this->response->addHTML(
                Message::error(__('No Privileges'))->getDisplay()
            );
            return;
        }

        $this->response->addHTML('<div class="container-fluid">');
        $this->response->addHTML($this->template->render('server/privileges/subnav', [
            'active' => 'user-groups',
            'is_super_user' => $this->dbi->isSuperuser(),
        ]));

        /**
         * Delete user group
         */
        if (! empty($_POST['deleteUserGroup'])) {
            UserGroups::delete($_POST['userGroup']);
        }

        /**
         * Add a new user group
         */
        if (! empty($_POST['addUserGroupSubmit'])) {
            UserGroups::edit($_POST['userGroup'], true);
        }

        /**
         * Update a user group
         */
        if (! empty($_POST['editUserGroupSubmit'])) {
            UserGroups::edit($_POST['userGroup']);
        }

        if (isset($_POST['viewUsers'])) {
            // Display users belonging to a user group
            $this->response->addHTML(UserGroups::getHtmlForListingUsersofAGroup($_POST['userGroup']));
        }

        if (isset($_GET['addUserGroup'])) {
            // Display add user group dialog
            $this->response->addHTML(UserGroups::getHtmlToEditUserGroup());
        } elseif (isset($_POST['editUserGroup'])) {
            // Display edit user group dialog
            $this->response->addHTML(UserGroups::getHtmlToEditUserGroup($_POST['userGroup']));
        } else {
            // Display user groups table
            $this->response->addHTML(UserGroups::getHtmlForUserGroupsTable());
        }

        $this->response->addHTML('</div>');
    }
}
