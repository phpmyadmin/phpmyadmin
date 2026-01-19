<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\ConfigStorage;

use Generator;
use PhpMyAdmin\ConfigStorage\Features\ConfigurableMenusFeature;
use PhpMyAdmin\ConfigStorage\UserGroups;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Dbal\ResultInterface;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DummyResult;
use PhpMyAdmin\Url;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;

#[CoversClass(UserGroups::class)]
#[Medium]
class UserGroupsTest extends AbstractTestCase
{
    private ConfigurableMenusFeature $configurableMenusFeature;

    /**
     * Prepares environment for the test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        DatabaseInterface::$instance = $this->createDatabaseInterface();
        Current::$database = '';
        Current::$table = '';

        $this->configurableMenusFeature = new ConfigurableMenusFeature(
            DatabaseName::from('pmadb'),
            TableName::from('usergroups'),
            TableName::from('users'),
        );
    }

    /**
     * Tests UserGroups::getHtmlForUserGroupsTable() function when there are no user groups
     */
    public function testGetHtmlForUserGroupsTableWithNoUserGroups(): void
    {
        $expectedQuery = 'SELECT * FROM `pmadb`.`usergroups` ORDER BY `usergroup` ASC';

        $resultStub = self::createMock(DummyResult::class);

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects(self::once())
            ->method('tryQueryAsControlUser')
            ->with($expectedQuery)
            ->willReturn($resultStub);
        $resultStub->expects(self::once())
            ->method('numRows')
            ->willReturn(0);
        DatabaseInterface::$instance = $dbi;

        $html = UserGroups::getHtmlForUserGroupsTable($this->configurableMenusFeature);
        self::assertStringNotContainsString('<table id="userGroupsTable">', $html);
        $urlTag = '<a class="btn btn-primary" href="' . Url::getFromRoute('/server/user-groups', ['addUserGroup' => 1]);
        self::assertStringContainsString($urlTag, $html);
    }

    /**
     * Tests UserGroups::getHtmlForUserGroupsTable() function when there are user groups
     */
    public function testGetHtmlForUserGroupsTableWithUserGroups(): void
    {
        $html = UserGroups::getHtmlForUserGroupsTable($this->configurableMenusFeature);
        self::assertStringContainsString('<td>user&lt;br&gt;group</td>', $html);
        $urlTag = '<a class="" href="' . Url::getFromRoute('/server/user-groups') . '" data-post="'
            . Url::getCommon(['viewUsers' => 1, 'userGroup' => 'user<br>group'], '');
        self::assertStringContainsString($urlTag, $html);
        $urlTag = '<a class="" href="' . Url::getFromRoute('/server/user-groups') . '" data-post="'
            . Url::getCommon(['editUserGroup' => 1, 'userGroup' => 'user<br>group'], '');
        self::assertStringContainsString($urlTag, $html);
        self::assertStringContainsString(
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

        $result = self::createStub(ResultInterface::class);
        $dbi = self::createMock(DatabaseInterface::class);
        $dbi->expects(self::exactly(2))->method('queryAsControlUser')->willReturnMap([
            [$userDelQuery, $result],
            [$userGrpDelQuery, $result],
        ]);
        $dbi->expects(self::any())->method('quoteString')
            ->willReturnCallback(static fn (string $string): string => "'" . $string . "'");

        UserGroups::delete($dbi, $this->configurableMenusFeature, 'ug');
    }

    /**
     * Tests UserGroups::getHtmlToEditUserGroup() function
     */
    public function testGetHtmlToEditUserGroup(): void
    {
        // adding a user group
        $html = UserGroups::getHtmlToEditUserGroup($this->configurableMenusFeature);
        self::assertStringContainsString('<input type="hidden" name="addUserGroupSubmit" value="1"', $html);
        self::assertStringContainsString('<input class="form-control" type="text" name="userGroup"', $html);

        $resultStub = self::createMock(DummyResult::class);

        $expectedQuery = 'SELECT * FROM `pmadb`.`usergroups` WHERE `usergroup`=\'user<br>group\'';
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects(self::once())
            ->method('tryQueryAsControlUser')
            ->with($expectedQuery)
            ->willReturn($resultStub);
        $resultStub->expects(self::exactly(1))
            ->method('getIterator')
            ->willReturnCallback(static function (): Generator {
                yield ['usergroup' => 'user<br>group', 'tab' => 'server_sql', 'allowed' => 'Y'];
            });
        $dbi->expects(self::any())->method('quoteString')
            ->willReturnCallback(static fn (string $string): string => "'" . $string . "'");

        DatabaseInterface::$instance = $dbi;

        // editing a user group
        $html = UserGroups::getHtmlToEditUserGroup($this->configurableMenusFeature, 'user<br>group');
        self::assertStringContainsString('Edit user group: \'user&lt;br&gt;group\'', $html);
        self::assertStringContainsString('<input type="hidden" name="userGroup" value="user&lt;br&gt;group"', $html);
        self::assertStringContainsString('<input type="hidden" name="editUserGroupSubmit" value="1"', $html);
        self::assertStringContainsString('<input type="hidden" name="editUserGroupSubmit" value="1"', $html);
        self::assertStringContainsString(
            '<input class="form-check-input checkall" type="checkbox"'
            . ' checked name="server_sql" id="server_sql" value="Y">',
            $html,
        );
        self::assertStringContainsString(
            '<input class="form-check-input checkall" type="checkbox"'
            . ' name="server_databases" id="server_databases" value="Y">',
            $html,
        );
    }

    public function testGetHtmlForListingUsersOfAGroupWithNoUsers(): void
    {
        $dummyDbi = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dummyDbi);
        DatabaseInterface::$instance = $dbi;

        $dummyDbi->addResult('SELECT `username` FROM `pmadb`.`users` WHERE `usergroup`=\'user<br>group\'', []);

        $output = UserGroups::getHtmlForListingUsersofAGroup($this->configurableMenusFeature, 'user<br>group');
        self::assertStringContainsString('Users of \'user&lt;br&gt;group\' user group', $output);
        self::assertStringContainsString('No users were found belonging to this user group.', $output);
    }

    public function testGetHtmlForListingUsersOfAGroupWithUsers(): void
    {
        $dummyDbi = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dummyDbi);
        DatabaseInterface::$instance = $dbi;

        $dummyDbi->addResult(
            'SELECT `username` FROM `pmadb`.`users` WHERE `usergroup`=\'user<br>group\'',
            [['user<br>one'], ['user<br>two']],
            ['username'],
        );

        $output = UserGroups::getHtmlForListingUsersofAGroup($this->configurableMenusFeature, 'user<br>group');
        self::assertStringContainsString('Users of \'user&lt;br&gt;group\' user group', $output);
        self::assertStringContainsString('<td>1</td>', $output);
        self::assertStringContainsString('<td>user&lt;br&gt;one</td>', $output);
        self::assertStringContainsString('<td>2</td>', $output);
        self::assertStringContainsString('<td>user&lt;br&gt;two</td>', $output);
        self::assertStringNotContainsString('No users were found belonging to this user group.', $output);
    }
}
