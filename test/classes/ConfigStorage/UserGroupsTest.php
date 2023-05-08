<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\ConfigStorage;

use Generator;
use PhpMyAdmin\ConfigStorage\Features\ConfigurableMenusFeature;
use PhpMyAdmin\ConfigStorage\UserGroups;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Dbal\DatabaseName;
use PhpMyAdmin\Dbal\ResultInterface;
use PhpMyAdmin\Dbal\TableName;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\DummyResult;
use PhpMyAdmin\Url;

/** @covers \PhpMyAdmin\ConfigStorage\UserGroups */
class UserGroupsTest extends AbstractTestCase
{
    private ConfigurableMenusFeature $configurableMenusFeature;

    /**
     * Prepares environment for the test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['dbi'] = $this->createDatabaseInterface();
        $GLOBALS['db'] = '';
        $GLOBALS['table'] = '';

        $this->configurableMenusFeature = new ConfigurableMenusFeature(
            DatabaseName::fromValue('pmadb'),
            TableName::fromValue('usergroups'),
            TableName::fromValue('users'),
        );
    }

    /**
     * Tests UserGroups::getHtmlForUserGroupsTable() function when there are no user groups
     *
     * @group medium
     */
    public function testGetHtmlForUserGroupsTableWithNoUserGroups(): void
    {
        $expectedQuery = 'SELECT * FROM `pmadb`.`usergroups` ORDER BY `usergroup` ASC';

        $resultStub = $this->createMock(DummyResult::class);

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->once())
            ->method('tryQueryAsControlUser')
            ->with($expectedQuery)
            ->will($this->returnValue($resultStub));
        $resultStub->expects($this->once())
            ->method('numRows')
            ->will($this->returnValue(0));
        $GLOBALS['dbi'] = $dbi;

        $html = UserGroups::getHtmlForUserGroupsTable($this->configurableMenusFeature);
        $this->assertStringNotContainsString('<table id="userGroupsTable">', $html);
        $urlTag = '<a class="btn btn-primary" href="' . Url::getFromRoute('/server/user-groups', ['addUserGroup' => 1]);
        $this->assertStringContainsString($urlTag, $html);
    }

    /**
     * Tests UserGroups::getHtmlForUserGroupsTable() function when there are user groups
     */
    public function testGetHtmlForUserGroupsTableWithUserGroups(): void
    {
        $html = UserGroups::getHtmlForUserGroupsTable($this->configurableMenusFeature);
        $this->assertStringContainsString('<td>user&lt;br&gt;group</td>', $html);
        $urlTag = '<a class="" href="' . Url::getFromRoute('/server/user-groups') . '" data-post="'
            . Url::getCommon(['viewUsers' => 1, 'userGroup' => 'user<br>group'], '');
        $this->assertStringContainsString($urlTag, $html);
        $urlTag = '<a class="" href="' . Url::getFromRoute('/server/user-groups') . '" data-post="'
            . Url::getCommon(['editUserGroup' => 1, 'userGroup' => 'user<br>group'], '');
        $this->assertStringContainsString($urlTag, $html);
        $this->assertStringContainsString(
            '<button type="button" class="btn btn-link" data-bs-toggle="modal"'
            . ' data-bs-target="#deleteUserGroupModal" data-user-group="user&lt;br&gt;group">',
            $html,
        );
    }

    /**
     * Tests UserGroups::delete() function
     */
    public function testDeleteUserGroup(): void
    {
        $userDelQuery = 'DELETE FROM `pmadb`.`users` WHERE `usergroup`=\'ug\'';
        $userGrpDelQuery = 'DELETE FROM `pmadb`.`usergroups` WHERE `usergroup`=\'ug\'';

        $result = $this->createStub(ResultInterface::class);
        $dbi = $this->createMock(DatabaseInterface::class);
        $dbi->expects($this->exactly(2))->method('queryAsControlUser')->willReturnMap([
            [$userDelQuery, $result],
            [$userGrpDelQuery, $result],
        ]);
        $dbi->expects($this->any())->method('quoteString')
            ->will($this->returnCallback(static fn (string $string): string => "'" . $string . "'"));

        UserGroups::delete($dbi, $this->configurableMenusFeature, 'ug');
    }

    /**
     * Tests UserGroups::getHtmlToEditUserGroup() function
     */
    public function testGetHtmlToEditUserGroup(): void
    {
        // adding a user group
        $html = UserGroups::getHtmlToEditUserGroup($this->configurableMenusFeature);
        $this->assertStringContainsString('<input type="hidden" name="addUserGroupSubmit" value="1"', $html);
        $this->assertStringContainsString('<input class="form-control" type="text" name="userGroup"', $html);

        $resultStub = $this->createMock(DummyResult::class);

        $expectedQuery = 'SELECT * FROM `pmadb`.`usergroups` WHERE `usergroup`=\'user<br>group\'';
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->once())
            ->method('tryQueryAsControlUser')
            ->with($expectedQuery)
            ->will($this->returnValue($resultStub));
        $resultStub->expects($this->exactly(1))
            ->method('getIterator')
            ->will($this->returnCallback(static function (): Generator {
                yield from [['usergroup' => 'user<br>group', 'tab' => 'server_sql', 'allowed' => 'Y']];
            }));
        $dbi->expects($this->any())->method('quoteString')
            ->will($this->returnCallback(static fn (string $string): string => "'" . $string . "'"));

        $GLOBALS['dbi'] = $dbi;

        // editing a user group
        $html = UserGroups::getHtmlToEditUserGroup($this->configurableMenusFeature, 'user<br>group');
        $this->assertStringContainsString('Edit user group: \'user&lt;br&gt;group\'', $html);
        $this->assertStringContainsString('<input type="hidden" name="userGroup" value="user&lt;br&gt;group"', $html);
        $this->assertStringContainsString('<input type="hidden" name="editUserGroupSubmit" value="1"', $html);
        $this->assertStringContainsString('<input type="hidden" name="editUserGroupSubmit" value="1"', $html);
        $this->assertStringContainsString(
            '<input class="form-check-input checkall" type="checkbox"'
            . ' checked="checked" name="server_sql" id="server_sql" value="Y">',
            $html,
        );
        $this->assertStringContainsString(
            '<input class="form-check-input checkall" type="checkbox"'
            . ' name="server_databases" id="server_databases" value="Y">',
            $html,
        );
    }

    public function testGetHtmlForListingUsersOfAGroupWithNoUsers(): void
    {
        $dummyDbi = new DbiDummy();
        $dbi = DatabaseInterface::load($dummyDbi);
        $GLOBALS['dbi'] = $dbi;

        $dummyDbi->addResult('SELECT `username` FROM `pmadb`.`users` WHERE `usergroup`=\'user<br>group\'', []);

        $output = UserGroups::getHtmlForListingUsersofAGroup($this->configurableMenusFeature, 'user<br>group');
        $this->assertStringContainsString('Users of \'user&lt;br&gt;group\' user group', $output);
        $this->assertStringContainsString('No users were found belonging to this user group.', $output);
    }

    public function testGetHtmlForListingUsersOfAGroupWithUsers(): void
    {
        $dummyDbi = new DbiDummy();
        $dbi = DatabaseInterface::load($dummyDbi);
        $GLOBALS['dbi'] = $dbi;

        $dummyDbi->addResult(
            'SELECT `username` FROM `pmadb`.`users` WHERE `usergroup`=\'user<br>group\'',
            [['user<br>one'], ['user<br>two']],
            ['username'],
        );

        $output = UserGroups::getHtmlForListingUsersofAGroup($this->configurableMenusFeature, 'user<br>group');
        $this->assertStringContainsString('Users of \'user&lt;br&gt;group\' user group', $output);
        $this->assertStringContainsString('<td>1</td>', $output);
        $this->assertStringContainsString('<td>user&lt;br&gt;one</td>', $output);
        $this->assertStringContainsString('<td>2</td>', $output);
        $this->assertStringContainsString('<td>user&lt;br&gt;two</td>', $output);
        $this->assertStringNotContainsString('No users were found belonging to this user group.', $output);
    }
}
