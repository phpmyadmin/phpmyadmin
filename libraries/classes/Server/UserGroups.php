<?php
/**
 * set of functions for user group handling
 */

declare(strict_types=1);

namespace PhpMyAdmin\Server;

use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use function htmlspecialchars;
use function implode;
use function in_array;
use function mb_substr;
use function substr;

/**
 * PhpMyAdmin\Server\UserGroups class
 */
class UserGroups
{
    /**
     * Return HTML to list the users belonging to a given user group
     *
     * @param string $userGroup user group name
     *
     * @return string HTML to list the users belonging to a given user group
     */
    public static function getHtmlForListingUsersofAGroup(string $userGroup): string
    {
        global $dbi;

        $users = [];
        $numRows = 0;
        $relation = new Relation($dbi);

        $userGroupSpecialChars = htmlspecialchars($userGroup);
        $cfgRelation = $relation->getRelationsParam();
        $usersTable = Util::backquote($cfgRelation['db'])
            . '.' . Util::backquote($cfgRelation['users']);
        $sql_query = 'SELECT `username` FROM ' . $usersTable
            . " WHERE `usergroup`='" . $dbi->escapeString($userGroup)
            . "'";
        $result = $relation->queryAsControlUser($sql_query, false);
        if ($result) {
            $numRows = $dbi->numRows($result);
            if ($numRows != 0) {
                $i = 0;
                while ($row = $dbi->fetchRow($result)) {
                    $i++;
                    $user = [];
                    $user['count'] = $i;
                    $user['user'] = $row[0];
                    $users[] = $user;
                }
            }
        }
        $dbi->freeResult($result);
        $template = new Template();

        return $template->render('/server/user_groups/user_listings', [
            'user_group_special_chars' => $userGroupSpecialChars,
            'num_rows' => $numRows,
            'users' => $users,
        ]);
    }

    /**
     * Returns HTML for the 'user groups' table
     *
     * @return string HTML for the 'user groups' table
     */
    public static function getHtmlForUserGroupsTable(): string
    {
        global $dbi;

        $relation = new Relation($dbi);
        $cfgRelation = $relation->getRelationsParam();
        $groupTable = Util::backquote($cfgRelation['db'])
            . '.' . Util::backquote($cfgRelation['usergroups']);
        $sql_query = 'SELECT * FROM ' . $groupTable . ' ORDER BY `usergroup` ASC';
        $result = $relation->queryAsControlUser($sql_query, false);
        $numRows = $dbi->numRows($result);
        $userGroups = [];
        $userGroupsValues = [];
        $action = Url::getFromRoute('/server/privileges');
        $hidden_inputs = null;
        if ($result && $numRows) {
            $hidden_inputs = Url::getHiddenInputs();
            while ($row = $dbi->fetchAssoc($result)) {
                $groupName = $row['usergroup'];
                if (! isset($userGroups[$groupName])) {
                    $userGroups[$groupName] = [];
                }
                $userGroups[$groupName][$row['tab']] = $row['allowed'];
            }

            foreach ($userGroups as $groupName => $tabs) {
                $userGroupVal = [];
                $userGroupVal['name'] = htmlspecialchars((string) $groupName);
                $userGroupVal['serverTab'] = self::getAllowedTabNames($tabs, 'server');
                $userGroupVal['dbTab'] = self::getAllowedTabNames($tabs, 'db');
                $userGroupVal['tableTab'] = self::getAllowedTabNames($tabs, 'table');
                $userGroupVal['userGroupUrl'] = Url::getFromRoute('/server/user-groups');
                $userGroupVal['viewUsersUrl'] = Url::getCommon(
                    [
                        'viewUsers' => 1,
                        'userGroup' => $groupName,
                    ],
                    ''
                );
                $userGroupVal['viewUsersIcon'] = Generator::getIcon('b_usrlist', __('View users'));

                $userGroupVal['editUsersUrl'] = Url::getCommon(
                    [
                        'editUserGroup' => 1,
                        'userGroup' => $groupName,
                    ],
                    ''
                );
                $userGroupVal['editUsersIcon'] = Generator::getIcon('b_edit', __('Edit'));

                $userGroupVal['deleteUsersUrl'] = Url::getCommon(
                    [
                        'deleteUserGroup' => 1,
                        'userGroup' => $groupName,
                    ],
                    ''
                );
                $userGroupVal['deleteUsersIcon'] = Generator::getIcon('b_drop', __('Delete'));
                $userGroupsValues[] = $userGroupVal;
            }
        }
        $addUserUrl = Url::getFromRoute('/server/user-groups', ['addUserGroup' => 1]);
        $addUserIcon = Generator::getIcon('b_usradd');
        $dbi->freeResult($result);
        $template = new Template();

        return $template->render('server/user_groups/user_groups', [
            'action' => $action,
            'hidden_inputs' => $hidden_inputs ?? '',
            'result' => $result,
            'has_rows' => $numRows,
            'user_groups_values' => $userGroupsValues,
            'add_user_url' => $addUserUrl,
            'add_user_icon' => $addUserIcon,
        ]);
    }

    /**
     * Returns the list of allowed menu tab names
     * based on a data row from usergroup table.
     *
     * @param array  $row   row of usergroup table
     * @param string $level 'server', 'db' or 'table'
     *
     * @return string comma separated list of allowed menu tab names
     */
    public static function getAllowedTabNames(array $row, string $level): string
    {
        $tabNames = [];
        $tabs = Util::getMenuTabList($level);
        foreach ($tabs as $tab => $tabName) {
            if (isset($row[$level . '_' . $tab])
                && $row[$level . '_' . $tab] !== 'Y'
            ) {
                continue;
            }

            $tabNames[] = $tabName;
        }

        return implode(', ', $tabNames);
    }

    /**
     * Deletes a user group
     *
     * @param string $userGroup user group name
     */
    public static function delete(string $userGroup): void
    {
        global $dbi;

        $relation = new Relation($dbi);
        $cfgRelation = $relation->getRelationsParam();
        $userTable = Util::backquote($cfgRelation['db'])
            . '.' . Util::backquote($cfgRelation['users']);
        $groupTable = Util::backquote($cfgRelation['db'])
            . '.' . Util::backquote($cfgRelation['usergroups']);
        $sql_query = 'DELETE FROM ' . $userTable
            . " WHERE `usergroup`='" . $dbi->escapeString($userGroup)
            . "'";
        $relation->queryAsControlUser($sql_query, true);
        $sql_query = 'DELETE FROM ' . $groupTable
            . " WHERE `usergroup`='" . $dbi->escapeString($userGroup)
            . "'";
        $relation->queryAsControlUser($sql_query, true);
    }

    /**
     * Returns HTML for add/edit user group dialog
     *
     * @param string $userGroup name of the user group in case of editing
     *
     * @return string HTML for add/edit user group dialog
     */
    public static function getHtmlToEditUserGroup(?string $userGroup = null): string
    {
        global $dbi;

        $relation = new Relation($dbi);
        $urlParams = [];

        $editUserGroupSpecialChars = '';
        if ($userGroup !== null) {
            $editUserGroupSpecialChars = htmlspecialchars($userGroup);
        }
        if ($userGroup !== null) {
            $urlParams['userGroup'] = $userGroup;
            $urlParams['editUserGroupSubmit'] = '1';
        } else {
            $urlParams['addUserGroupSubmit'] = '1';
        }
        $allowedTabs = [
            'server' => [],
            'db' => [],
            'table' => [],
        ];
        if ($userGroup !== null) {
            $cfgRelation = $relation->getRelationsParam();
            $groupTable = Util::backquote($cfgRelation['db'])
                . '.' . Util::backquote($cfgRelation['usergroups']);
            $sql_query = 'SELECT * FROM ' . $groupTable
                . " WHERE `usergroup`='" . $dbi->escapeString($userGroup)
                . "'";
            $result = $relation->queryAsControlUser($sql_query, false);
            if ($result) {
                while ($row = $dbi->fetchAssoc($result)) {
                    $key = $row['tab'];
                    $value = $row['allowed'];
                    if (substr($key, 0, 7) === 'server_' && $value === 'Y') {
                        $allowedTabs['server'][] = mb_substr($key, 7);
                    } elseif (substr($key, 0, 3) === 'db_' && $value === 'Y') {
                        $allowedTabs['db'][] = mb_substr($key, 3);
                    } elseif (substr($key, 0, 6) === 'table_'
                        && $value === 'Y'
                    ) {
                        $allowedTabs['table'][] = mb_substr($key, 6);
                    }
                }
            }
            $dbi->freeResult($result);
        }
        $tabList = self::getTabList(
            __('Server-level tabs'),
            'server',
            $allowedTabs['server']
        );
        $tabList .= self::getTabList(
            __('Database-level tabs'),
            'db',
            $allowedTabs['db']
        );
        $tabList .= self::getTabList(
            __('Table-level tabs'),
            'table',
            $allowedTabs['table']
        );

        $template = new Template();

        return $template->render('/server/user_groups/edit_user_groups', [
            'user_group' => $userGroup,
            'edit_user_group_special_chars' => $editUserGroupSpecialChars,
            'user_group_url' => Url::getFromRoute('/server/user-groups'),
            'hidden_inputs' => Url::getHiddenInputs($urlParams),
            'tab_list' => $tabList,
        ]);
    }

    /**
     * Returns HTML for checkbox groups to choose
     * tabs of 'server', 'db' or 'table' levels.
     *
     * @param string $title    title of the checkbox group
     * @param string $level    'server', 'db' or 'table'
     * @param array  $selected array of selected allowed tabs
     *
     * @return string HTML for checkbox groups
     */
    public static function getTabList(string $title, string $level, array $selected): string
    {
        $tabs = Util::getMenuTabList($level);
        $tabDetails = [];
        foreach ($tabs as $tab => $tabName) {
            $tabDetail = [];
            $tabDetail['in_array'] = (in_array($tab, $selected) ? ' checked="checked"' : '');
            $tabDetail['tab'] = $tab;
            $tabDetail['tab_name'] = $tabName;
            $tabDetails[] = $tabDetail;
        }
        $template = new Template();

        return $template->render('/server/user_groups/tab_list', [
            'title' => $title,
            'level' => $level,
            'tab_details' => $tabDetails,
        ]);
    }

    /**
     * Add/update a user group with allowed menu tabs.
     *
     * @param string $userGroup user group name
     * @param bool   $new       whether this is a new user group
     */
    public static function edit(string $userGroup, bool $new = false): void
    {
        global $dbi;

        $relation = new Relation($dbi);
        $tabs = Util::getMenuTabList();
        $cfgRelation = $relation->getRelationsParam();
        $groupTable = Util::backquote($cfgRelation['db'])
            . '.' . Util::backquote($cfgRelation['usergroups']);

        if (! $new) {
            $sql_query = 'DELETE FROM ' . $groupTable
                . " WHERE `usergroup`='" . $dbi->escapeString($userGroup)
                . "';";
            $relation->queryAsControlUser($sql_query, true);
        }

        $sql_query = 'INSERT INTO ' . $groupTable
            . '(`usergroup`, `tab`, `allowed`)'
            . ' VALUES ';
        $first = true;
        foreach ($tabs as $tabGroupName => $tabGroup) {
            foreach ($tabGroup as $tab => $tabName) {
                if (! $first) {
                    $sql_query .= ', ';
                }
                $tabName = $tabGroupName . '_' . $tab;
                $allowed = isset($_POST[$tabName]) && $_POST[$tabName] === 'Y';
                $sql_query .= "('" . $dbi->escapeString($userGroup) . "', '" . $tabName . "', '"
                    . ($allowed ? 'Y' : 'N') . "')";
                $first = false;
            }
        }
        $sql_query .= ';';
        $relation->queryAsControlUser($sql_query, true);
    }
}
