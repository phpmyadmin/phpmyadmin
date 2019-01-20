<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PhpMyAdmin\Server\HtmlPrivilegesTemplateManager
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Server;

use PhpMyAdmin\Core;
use PhpMyAdmin\Relation;
use PhpMyAdmin\RelationCleanup;
use PhpMyAdmin\Server\HtmlPrivilegesTemplateManager;
use PhpMyAdmin\Server\Privileges;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Config;

/**
 * PhpMyAdmin\Tests\Server\HtmlPrivilegesTemplateManagerTest class
 *
 * this class is for testing PhpMyAdmin\Server\HtmlPrivilegesTemplateManager methods
 *
 * @package PhpMyAdmin-test
 */
class HtmlPrivilegesTemplateManagerTest extends TestCase
{
    /**
     * @var HtmlPrivilegesTemplateManager
     */
    private $serverPrivilegesTemplateManager;

    /**
     * @var MockObject Mock of Privileges class
     */
    private $privileges;

    /**
     * Prepares environment for the test.
     *
     * @return void
     */
    protected function setUp()
    {
        //Constants
        if (! defined('PMA_USR_BROWSER_AGENT')) {
            define('PMA_USR_BROWSER_AGENT', 'other');
        }

        //$_REQUEST
        $_REQUEST['log'] = 'index1';
        $_REQUEST['pos'] = 3;
        $_GET['initial'] = null;

        //$GLOBALS
        $GLOBALS['lang'] = 'en';
        $GLOBALS['cfg']['MaxRows'] = 10;
        $GLOBALS['cfg']['SendErrorReports'] = 'never';
        $GLOBALS['cfg']['ServerDefault'] = 'server';
        $GLOBALS['cfg']['RememberSorting'] = true;
        $GLOBALS['cfg']['SQP'] = [];
        $GLOBALS['cfg']['MaxCharactersInDisplayedSQL'] = 1000;
        $GLOBALS['cfg']['ShowSQL'] = true;
        $GLOBALS['cfg']['TableNavigationLinksMode'] = 'icons';
        $GLOBALS['cfg']['LimitChars'] = 100;
        $GLOBALS['cfg']['AllowThirdPartyFraming'] = false;
        $GLOBALS['cfg']['ActionLinksMode'] = 'both';
        $GLOBALS['cfg']['DefaultTabDatabase'] = 'structure';
        $GLOBALS['cfg']['DefaultTabTable'] = 'structure';
        $GLOBALS['cfg']['NavigationTreeDefaultTabTable'] = 'structure';
        $GLOBALS['cfg']['NavigationTreeDefaultTabTable2'] = '';
        $GLOBALS['cfg']['Confirm'] = 'Confirm';
        $GLOBALS['cfg']['ShowHint'] = true;
        $GLOBALS['cfg']['ShowDatabasesNavigationAsTree'] = true;
        $GLOBALS['cfg']['LoginCookieValidity'] = 1440;
        $GLOBALS['cfg']['enable_drag_drop_import'] = true;

        $GLOBALS['cfgRelation'] = [];
        $GLOBALS['cfgRelation']['menuswork'] = false;
        $GLOBALS['table'] = 'table';
        $GLOBALS['PMA_PHP_SELF'] = Core::getenv('PHP_SELF');
        $GLOBALS['pmaThemeImage'] = 'image';
        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'db';
        $GLOBALS['hostname'] = 'hostname';
        $GLOBALS['username'] = 'username';
        $GLOBALS['text_dir'] = 'text_dir';
        $GLOBALS['is_reload_priv'] = true;

        $this->privileges = $this->getMockBuilder(Privileges::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->serverPrivilegesTemplateManager = new HtmlPrivilegesTemplateManager(
            $this->privileges,
            new Template()
        );

        //$_POST
        $_POST['pred_password'] = 'none';
        //$_SESSION
        $_SESSION['relation'][$GLOBALS['server']] = [
            'PMA_VERSION' => PMA_VERSION,
            'db' => 'pmadb',
            'users' => 'users',
            'usergroups' => 'usergroups',
            'menuswork' => true
        ];

        $pmaconfig = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $GLOBALS['PMA_Config'] = $pmaconfig;

        $GLOBALS['is_grantuser'] = true;
        $GLOBALS['is_createuser'] = true;
        $GLOBALS['is_reload_priv'] = true;
    }

    /**
     * Test for getHtmlForColumnPrivileges
     *
     * @return void
     */
    public function testGetColumnPrivileges()
    {
        $columns = [
            'row1' => 'name1',
        ];
        $row = [
            'name_for_select' => 'Y',
        ];
        $nameForSelect = 'name_for_select';
        $privForHeader = 'priv_for_header';
        $name = 'name';
        $nameForDfn = 'name_for_dfn';
        $nameForCurrent = 'name_for_current';

        $html = $this->serverPrivilegesTemplateManager->getColumnPrivileges(
            $columns,
            $row,
            $nameForSelect,
            $privForHeader,
            $name,
            $nameForDfn,
            $nameForCurrent
        );
        //$name
        $this->assertContains(
            $name,
            $html
        );
        //$name_for_dfn
        $this->assertContains(
            $nameForDfn,
            $html
        );
        //$priv_for_header
        $this->assertContains(
            $privForHeader,
            $html
        );
        //$name_for_select
        $this->assertContains(
            $nameForSelect,
            $html
        );
        //$columns and $row
        $this->assertContains(
            htmlspecialchars('row1'),
            $html
        );
        //$columns and $row
        $this->assertContains(
            _pgettext('None privileges', 'None'),
            $html
        );
    }

    /**
     * Test for getRequires
     *
     * @return void
     */
    public function testGetRequires()
    {
        /* Assertion 1 */
        $row = [
            'ssl_type'   => '',
            'ssh_cipher' => '',
        ];

        $html = $this->serverPrivilegesTemplateManager->getRequires(
            $row
        );
        // <legend>SSL</legend>
        $this->assertContains(
            '<legend>SSL</legend>',
            $html
        );
        $this->assertContains(
            'value="NONE" checked="checked"',
            $html
        );
        $this->assertContains(
            'value="ANY"',
            $html
        );
        $this->assertContains(
            'value="X509"',
            $html
        );
        $this->assertContains(
            'value="SPECIFIED"',
            $html
        );

        /* Assertion 2 */
        $row = [
            'ssl_type'   => 'ANY',
            'ssh_cipher' => '',
        ];

        $html = $this->serverPrivilegesTemplateManager->getRequires(
            $row
        );
        // <legend>SSL</legend>
        $this->assertContains(
            '<legend>SSL</legend>',
            $html
        );
        $this->assertContains(
            'value="NONE"',
            $html
        );
        $this->assertContains(
            'value="ANY" checked="checked"',
            $html
        );
        $this->assertContains(
            'value="X509"',
            $html
        );
        $this->assertContains(
            'value="SPECIFIED"',
            $html
        );

        /* Assertion 3 */
        $row = [
            'ssl_type'   => 'X509',
            'ssh_cipher' => '',
        ];

        $html = $this->serverPrivilegesTemplateManager->getRequires(
            $row
        );
        // <legend>SSL</legend>
        $this->assertContains(
            '<legend>SSL</legend>',
            $html
        );
        $this->assertContains(
            'value="NONE"',
            $html
        );
        $this->assertContains(
            'value="ANY"',
            $html
        );
        $this->assertContains(
            'value="X509" checked="checked"',
            $html
        );
        $this->assertContains(
            'value="SPECIFIED"',
            $html
        );

        /* Assertion 4 */
        $row = [
            'ssl_type'   => 'SPECIFIED',
            'ssh_cipher' => '',
        ];

        $html = $this->serverPrivilegesTemplateManager->getRequires(
            $row
        );
        // <legend>SSL</legend>
        $this->assertContains(
            '<legend>SSL</legend>',
            $html
        );
        $this->assertContains(
            'value="NONE"',
            $html
        );
        $this->assertContains(
            'value="ANY"',
            $html
        );
        $this->assertContains(
            'value="X509"',
            $html
        );
        $this->assertContains(
            'value="SPECIFIED" checked="checked"',
            $html
        );
    }

    /**
     * Test for getUserGroupDialog
     *
     * @return void
     */
    public function testGetUserGroupDialog()
    {
        $username = 'pma_username';
        $isMenuswork = true;
        $_GET['edit_user_group_dialog'] = 'edit_user_group_dialog';

        $this->privileges->method('getDataToChooseUserGroup')
            ->willReturn(
                [
                    'all_user_groups' => [
                        '' => '',
                        0 => 'test_used_usergroup',
                        1 => 'test_unused_usergroup',
                    ],
                    'user_group' => 'test_used_usergroup',
                    'params' => ['username' => $username],
                ]
            );

        /* Assertion 1 */
        $html = $this->serverPrivilegesTemplateManager->getUserGroupDialog($username, $isMenuswork);
        $this->assertContains(
            '<form class="ajax" id="changeUserGroupForm"',
            $html
        );
        //Url::getHiddenInputs
        $params = ['username' => $username];
        $htmlOutput = Url::getHiddenInputs($params);
        $this->assertContains(
            $htmlOutput,
            $html
        );
        //__('User group')
        $this->assertContains(
            __('User group'),
            $html
        );

        // Empty default user group
        $this->assertContains(
            '<option value=""></option>',
            $html
        );

        // Current user's group selected
        $this->assertContains(
            '<option value="0" selected="selected">test_used_usergroup</option>',
            $html
        );
    }

    /**
     * Test for getChooseUserGroup
     *
     * @return void
     */
    public function testGetChooseUserGroup()
    {
        $username = 'pma_username';

        $this->privileges->method('getDataToChooseUserGroup')
            ->willReturn(
                [
                    'all_user_groups' => [
                        0 => 'test_used_usergroup',
                        1 => 'test_unused_usergroup',
                    ],
                    'user_group' => 'test_used_usergroup',
                    'params' => ['username' => $username],
                ]
            );

        $html = $this->serverPrivilegesTemplateManager->getChooseUserGroup($username);
        $this->assertContains(
            '<form class="ajax" id="changeUserGroupForm"',
            $html
        );
        //Url::getHiddenInputs
        $params = ['username' => $username];
        $htmlOutput = Url::getHiddenInputs($params);
        $this->assertContains(
            $htmlOutput,
            $html
        );
        //__('User group')
        $this->assertContains(
            __('User group'),
            $html
        );
    }

    /**
     * Test for getResourceLimits
     *
     * @return void
     */
    public function testGetResourceLimits()
    {
        $row = [
            'max_questions' => 'max_questions',
            'max_updates' => 'max_updates',
            'max_connections' => 'max_connections',
            'max_user_connections' => 'max_user_connections',
        ];

        $html = $this->serverPrivilegesTemplateManager->getResourceLimits($row);
        $this->assertContains(
            '<legend>' . __('Resource limits') . '</legend>',
            $html
        );
        $this->assertContains(
            __('Note: Setting these options to 0 (zero) removes the limit.'),
            $html
        );
        $this->assertContains(
            'MAX QUERIES PER HOUR',
            $html
        );
        $this->assertContains(
            $row['max_connections'],
            $html
        );
        $this->assertContains(
            $row['max_updates'],
            $html
        );
        $this->assertContains(
            $row['max_connections'],
            $html
        );
        $this->assertContains(
            $row['max_user_connections'],
            $html
        );
        $this->assertContains(
            __('Limits the number of new connections the user may open per hour.'),
            $html
        );
        $this->assertContains(
            __('Limits the number of simultaneous connections the user may have.'),
            $html
        );
    }

    /**
     * Test for getListForExportUserDefinition
     *
     * @return void
     */
    public function getListForExportUserDefinition()
    {
        $username = 'PMA_username';
        $hostname = 'PMA_hostname';

        list($title, $export)
            = $this->serverPrivilegesTemplateManager->getListForExportUserDefinition($username, $hostname);

        //validate 1: $export
        $this->assertContains(
            'grant user2 delete',
            $export
        );
        $this->assertContains(
            'grant user1 select',
            $export
        );
        $this->assertContains(
            '<textarea class="export"',
            $export
        );

        //validate 2: $title
        $titleUser = __('User') . ' `' . htmlspecialchars($username)
            . '`@`' . htmlspecialchars($hostname) . '`';
        $this->assertContains(
            $titleUser,
            $title
        );
    }

    /**
     * Test for getDisplayPrivilegesTable
     *
     * @return void
     * @group medium
     */
    public function testGetDisplayPrivilegesTableEmptyColumns()
    {
        $GLOBALS['hostname'] = 'hostname';
        $GLOBALS['username'] = 'username';

        $this->privileges->method('getDataToDisplayPrivilegesTable')
            ->willReturn(
                [
                    '*',
                    $GLOBALS['username'],
                    $GLOBALS['hostname'],
                    ['Table_priv' => ''],
                    [],
                ]
            );

        $this->privileges->method('getDataPrivilegeTable')
            ->willReturn(
                [
                    [
                        'Data_right',
                        'DATA_RIGHT',
                        'Fake data right',
                    ],
                ]
            );

        $this->privileges->method('getStructurePrivilegeTable')
            ->willReturn(
                [
                    [
                        'Structure_right',
                        'STRUCUTRE_RIGHT',
                        'Fake structure right',
                    ],
                ]
            );

        $this->privileges->method('getAdministrationPrivilegeTable')
            ->willReturn(
                [
                    [
                        'Administration_right',
                        'ADMIN_RIGHT',
                        'Fake administration right',
                    ],
                ]
            );

        $html = $this->serverPrivilegesTemplateManager->getPrivilegesTable();

        //validate 1: fieldset
        $this->assertContains(
            '<fieldset id="fieldset_user_privtable_footer" ',
            $html
        );

        //validate 2: button
        $this->assertContains(
            __('Go'),
            $html
        );

        //validate 3: getGlobalOrDbSpecificPrivs
        $this->assertContains(
            '<fieldset id="fieldset_user_global_rights"><legend '
            . 'data-submenu-label="' . __('Global') . '">',
            $html
        );
        $this->assertContains(
            __('Global privileges'),
            $html
        );
        $this->assertContains(
            __('Check all'),
            $html
        );
        $this->assertContains(
            __('Note: MySQL privilege names are expressed in English'),
            $html
        );

        //validate 4: getGlobalPrivTableWithCheckboxes items
        $this->assertContains(
            '<input type="checkbox" class="checkall" name="Data_right_priv"',
            $html
        );
        $this->assertContains(
            '<input type="checkbox" class="checkall" name="Structure_right_priv"',
            $html
        );
        $this->assertContains(
            '<input type="checkbox" class="checkall" name="Administration_right_priv"',
            $html
        );

        //validate 5: getResourceLimits
        $this->assertContains(
            '<legend>' . __('Resource limits') . '</legend>',
            $html
        );
        $this->assertContains(
            __('Note: Setting these options to 0 (zero) removes the limit.'),
            $html
        );
    }

    /**
     * Test for getDisplayPrivilegesTable
     *
     * @return void
     * @group medium
     */
    public function testGetDisplayPrivilegesTableFilledColumns()
    {
        $GLOBALS['hostname'] = 'hostname';
        $GLOBALS['username'] = 'username';

        $GLOBALS['strPrivDescTable'] = 'strPrivDescTable';

        $this->privileges->method('getDataToDisplayPrivilegesTable')
            ->willReturn(
                [
                    '*',
                    $GLOBALS['username'],
                    $GLOBALS['hostname'],
                    ['Table_priv' => ''],
                    [
                        'COL1' => [
                            'Select' => false,
                            'Insert' => false,
                            'Update' => false,
                            'References' => false,
                        ],
                        'COL2' => [
                            'Select' => false,
                            'Insert' => false,
                            'Update' => false,
                            'References' => false,
                        ],
                    ],
                ]
            );

        $this->privileges->method('getDataForTableSpecificPrivileges')
            ->willReturn(
                [
                    'COL1' => [
                        'Select' => true,
                        'Insert' => false,
                        'Update' => false,
                        'References' => false,
                    ],
                    'COL2' => [
                        'Select' => false,
                        'Insert' => true,
                        'Update' => true,
                        'References' => false,
                    ],
                ]
            );

        $this->privileges->method('getDataPrivilegeTable')
            ->willReturn(
                [
                    [
                        'Data_right',
                        'DATA_RIGHT',
                        'Fake data right',
                    ],
                ]
            );

        $this->privileges->method('getStructurePrivilegeTable')
            ->willReturn(
                [
                    [
                        'Structure_right',
                        'STRUCUTRE_RIGHT',
                        'Fake structure right',
                    ],
                ]
            );

        $this->privileges->method('getAdministrationPrivilegeTable')
            ->willReturn(
                [
                    [
                        'Administration_right',
                        'ADMIN_RIGHT',
                        'Fake administration right',
                    ],
                ]
            );

        $html = $this->serverPrivilegesTemplateManager->getPrivilegesTable();

        //validate 1: fieldset
        $this->assertContains(
            '<fieldset id="fieldset_user_privtable_footer" ',
            $html
        );

        //validate 2: button
        $this->assertContains(
            __('Go'),
            $html
        );

        //validate 3: getTableSpecificPrivileges
        $this->assertContains(
            '<input type="hidden" name="column_count" value="2">',
            $html
        );
    }

    /**
     * Test for getHtmlForTableSpecificPrivileges
     *
     * @return void
     */
    public function testGetHtmlForTableSpecificPrivileges()
    {
        $GLOBALS['strPrivDescCreate_viewTbl'] = "strPrivDescCreate_viewTbl";
        $GLOBALS['strPrivDescShowViewTbl'] = "strPrivDescShowViewTbl";
        $username = "PMA_username";
        $hostname = "PMA_hostname";
        $db = "PMA_db";
        $table = "PMA_table";
        $columns = [
            'row1' => 'name1',
        ];
        $row = [
            'Select_priv' => 'Y',
            'Insert_priv' => 'Y',
            'Update_priv' => 'Y',
            'References_priv' => 'Y',
            'Create_view_priv' => 'Y',
            'ShowView_priv' => 'Y',
        ];

        $html = $this->serverPrivileges->getHtmlForTableSpecificPrivileges(
            $username,
            $hostname,
            $db,
            $table,
            $columns,
            $row
        );

        //validate 1: getHtmlForAttachedPrivilegesToTableSpecificColumn
        $item = $this->serverPrivileges->getHtmlForAttachedPrivilegesToTableSpecificColumn(
            $columns,
            $row
        );
        $this->assertContains(
            $item,
            $html
        );
        $this->assertContains(
            __('Allows reading data.'),
            $html
        );
        $this->assertContains(
            __('Allows inserting and replacing data'),
            $html
        );
        $this->assertContains(
            __('Allows changing data.'),
            $html
        );
        $this->assertContains(
            __('Has no effect in this MySQL version.'),
            $html
        );

        //validate 2: getHtmlForNotAttachedPrivilegesToTableSpecificColumn
        $item = $this->serverPrivileges->getHtmlForNotAttachedPrivilegesToTableSpecificColumn(
            $row
        );
        $this->assertContains(
            $item,
            $html
        );
        $this->assertContains(
            'Create_view_priv',
            $html
        );
        $this->assertContains(
            'ShowView_priv',
            $html
        );
    }

    /**
     * Test for getHtmlForLoginInformationFields
     *
     * @return void
     */
    public function testGetHtmlForLoginInformationFields()
    {
        $GLOBALS['username'] = 'pma_username';

        $dbi_old = $GLOBALS['dbi'];
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $fields_info = [
            [
                'COLUMN_NAME' => 'Host',
                'CHARACTER_MAXIMUM_LENGTH' => 80,
            ],
            [
                'COLUMN_NAME' => 'User',
                'CHARACTER_MAXIMUM_LENGTH' => 40,
            ],
        ];
        $dbi->expects($this->any())->method('fetchResult')
            ->will($this->returnValue($fields_info));
        $dbi->expects($this->any())
            ->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;
        $this->serverPrivileges->dbi = $dbi;

        $html = $this->serverPrivileges->getHtmlForLoginInformationFields();

        //validate 1: __('Login Information')
        $this->assertContains(
            __('Login Information'),
            $html
        );
        $this->assertContains(
            __('User name:'),
            $html
        );
        $this->assertContains(
            __('Any user'),
            $html
        );
        $this->assertContains(
            __('Use text field'),
            $html
        );

        $output = Util::showHint(
            __(
                'When Host table is used, this field is ignored '
                . 'and values stored in Host table are used instead.'
            )
        );
        $this->assertContains(
            $output,
            $html
        );

        $GLOBALS['dbi'] = $dbi_old;
        $this->serverPrivileges->dbi = $dbi_old;
    }

    /**
     * Test for getHtmlForAddUser
     *
     * @return void
     * @group medium
     */
    public function testGetHtmlForAddUser()
    {
        $dbi_old = $GLOBALS['dbi'];
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $fields_info = [
            [
                'COLUMN_NAME' => 'Host',
                'CHARACTER_MAXIMUM_LENGTH' => 80,
            ],
            [
                'COLUMN_NAME' => 'User',
                'CHARACTER_MAXIMUM_LENGTH' => 40,
            ],
        ];
        $dbi->expects($this->any())->method('fetchResult')
            ->will($this->returnValue($fields_info));
        $dbi->expects($this->any())
            ->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;
        $this->serverPrivileges->dbi = $dbi;

        $dbname = "pma_dbname";

        $html = $this->serverPrivilegesTemplateManager->getHtmlForAddUser($dbname);

        //validate 1: Url::getHiddenInputs
        $this->assertContains(
            Url::getHiddenInputs('', ''),
            $html
        );

        //validate 2: getHtmlForLoginInformationFields
        $this->assertContains(
            $this->serverPrivilegesTemplateManager->getHtmlForLoginInformationFields('new'),
            $html
        );

        //validate 3: Database for user
        $this->assertContains(
            __('Database for user'),
            $html
        );

        $template = new Template();
        $item = $template->render('checkbox', [
            'html_field_name' => 'createdb-2',
            'label' => __('Grant all privileges on wildcard name (username\\_%).'),
            'checked' => false,
            'onclick' => false,
            'html_field_id' => 'createdb-2',
        ]);
        $this->assertContains(
            $item,
            $html
        );

        //validate 4: getDisplayPrivilegesTable
        $this->assertContains(
            $this->serverPrivilegesTemplateManager->getDisplayPrivilegesTable('*', '*', false),
            $html
        );

        //validate 5: button
        $this->assertContains(
            __('Go'),
            $html
        );

        $GLOBALS['dbi'] = $dbi_old;
        $this->serverPrivileges->dbi = $dbi_old;
    }

    /**
     * Test for getHtmlForSpecificDbPrivileges
     *
     * @return void
     */
    public function testGetHtmlForSpecificDbPrivileges()
    {
        $dbi_old = $GLOBALS['dbi'];
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $fields_info = [
            [
                'COLUMN_NAME' => 'Host',
                'CHARACTER_MAXIMUM_LENGTH' => 80,
            ],
            [
                'COLUMN_NAME' => 'User',
                'CHARACTER_MAXIMUM_LENGTH' => 40,
            ],
        ];
        $dbi->expects($this->any())->method('isSuperuser')
            ->will($this->returnValue(true));
        $dbi->expects($this->any())->method('fetchResult')
            ->will($this->returnValue($fields_info));
        $dbi->expects($this->any())
            ->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;
        $this->serverPrivileges->dbi = $dbi;

        $db = "pma_dbname";

        $html = $this->serverPrivileges->getHtmlForSpecificDbPrivileges($db);

        //validate 1: Url::getCommon
        $this->assertContains(
            Url::getCommon(['db' => $db]),
            $html
        );

        //validate 2: htmlspecialchars
        $this->assertContains(
            htmlspecialchars($db),
            $html
        );

        //validate 3: items
        $this->assertContains(
            __('User'),
            $html
        );
        $this->assertContains(
            __('Host'),
            $html
        );
        $this->assertContains(
            __('Type'),
            $html
        );
        $this->assertContains(
            __('Privileges'),
            $html
        );
        $this->assertContains(
            __('Grant'),
            $html
        );
        $this->assertContains(
            __('Action'),
            $html
        );

        //_pgettext('Create new user', 'New')
        $this->assertContains(
            _pgettext('Create new user', 'New'),
            $html
        );
        $this->assertContains(
            Url::getCommon(['checkprivsdb' => $db]),
            $html
        );

        $GLOBALS['dbi'] = $dbi_old;
        $this->serverPrivileges->dbi = $dbi_old;
    }

    /**
     * Test for getHtmlForSpecificTablePrivileges
     *
     * @return void
     */
    public function testGetHtmlForSpecificTablePrivileges()
    {
        $dbi_old = $GLOBALS['dbi'];
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $fields_info = [
            [
                'COLUMN_NAME' => 'Host',
                'CHARACTER_MAXIMUM_LENGTH' => 80,
            ],
            [
                'COLUMN_NAME' => 'User',
                'CHARACTER_MAXIMUM_LENGTH' => 40,
            ],
        ];
        $dbi->expects($this->any())->method('fetchResult')
            ->will($this->returnValue($fields_info));
        $dbi->expects($this->any())
            ->method('escapeString')
            ->will($this->returnArgument(0));
        $dbi->expects($this->any())->method('isSuperuser')
            ->will($this->returnValue(true));

        $GLOBALS['dbi'] = $dbi;
        $this->serverPrivileges->dbi = $dbi;

        $db = "pma_dbname";
        $table = "pma_table";

        $html = $this->serverPrivileges->getHtmlForSpecificTablePrivileges($db, $table);

        //validate 1: $db, $table
        $this->assertContains(
            htmlspecialchars($db) . '.' . htmlspecialchars($table),
            $html
        );

        //validate 2: Url::getCommon
        $item = Url::getCommon(
            [
                'db' => $db,
                'table' => $table,
            ]
        );
        $this->assertContains(
            $item,
            $html
        );

        //validate 3: items
        $this->assertContains(
            __('User'),
            $html
        );
        $this->assertContains(
            __('Host'),
            $html
        );
        $this->assertContains(
            __('Type'),
            $html
        );
        $this->assertContains(
            __('Privileges'),
            $html
        );
        $this->assertContains(
            __('Grant'),
            $html
        );
        $this->assertContains(
            __('Action'),
            $html
        );

        //_pgettext('Create new user', 'New')
        $this->assertContains(
            _pgettext('Create new user', 'New'),
            $html
        );
        $this->assertContains(
            Url::getCommon(
                [
                    'checkprivsdb' => $db,
                    'checkprivstable' => $table,
                ]
            ),
            $html
        );

        $GLOBALS['dbi'] = $dbi_old;
        $this->serverPrivileges->dbi = $dbi_old;
    }

    /**
     * Test for getHtmlTableBodyForSpecificDbOrTablePrivs
     *
     * @return void
     */
    public function testGetHtmlTableBodyForSpecificDbOrTablePrivss()
    {
        $privMap = null;
        $db = "pma_dbname";

        //$privMap = null
        $html = $this->serverPrivileges->getHtmlTableBodyForSpecificDbOrTablePrivs($privMap, $db);
        $this->assertContains(
            __('No user found.'),
            $html
        );

        //$privMap != null
        $privMap = [
            "user1" => [
                "hostname1" => [
                    [
                        'Type' => 'g',
                        'Grant_priv' => 'Y',
                    ],
                    [
                        'Type' => 'd',
                        'Db' => "dbname",
                        'Grant_priv' => 'Y',
                    ],
                    [
                        'Type' => 't',
                        'Grant_priv' => 'N',
                    ],
                ],
            ],
        ];

        $html = $this->serverPrivileges->getHtmlTableBodyForSpecificDbOrTablePrivs($privMap, $db);

        //validate 1: $current_privileges
        $current_privileges = $privMap["user1"]["hostname1"];
        $current_user = "user1";
        $current_host = "hostname1";
        $this->assertContains(
            count($current_privileges) . "",
            $html
        );
        $this->assertContains(
            htmlspecialchars($current_user),
            $html
        );
        $this->assertContains(
            htmlspecialchars($current_host),
            $html
        );

        //validate 2: privileges[0]
        $this->assertContains(
            __('global'),
            $html
        );

        //validate 3: privileges[1]
        $current = $current_privileges[1];
        $this->assertContains(
            __('wildcard'),
            $html
        );
        $this->assertContains(
            htmlspecialchars($current['Db']),
            $html
        );

        //validate 4: privileges[2]
        $this->assertContains(
            __('table-specific'),
            $html
        );
    }

    /**
     * Test for getUserLink
     *
     * @return void
     */
    public function testGetUserLink()
    {
        $username = "pma_username";
        $hostname = "pma_hostname";
        $dbname = "pma_dbname";
        $tablename = "pma_tablename";

        $html = $this->serverPrivileges->getUserLink(
            'edit',
            $username,
            $hostname,
            $dbname,
            $tablename,
            ''
        );

        $url_html = Url::getCommon(
            [
                'username' => $username,
                'hostname' => $hostname,
                'dbname' => $dbname,
                'tablename' => $tablename,
                'routinename' => '',
            ]
        );
        $this->assertContains(
            $url_html,
            $html
        );
        $this->assertContains(
            __('Edit privileges'),
            $html
        );

        $html = $this->serverPrivileges->getUserLink(
            'revoke',
            $username,
            $hostname,
            $dbname,
            $tablename,
            ''
        );

        $url_html = Url::getCommon(
            [
                'username' => $username,
                'hostname' => $hostname,
                'dbname' => $dbname,
                'tablename' => $tablename,
                'routinename' => '',
                'revokeall' => 1,
            ],
            ''
        );
        $this->assertContains(
            $url_html,
            $html
        );
        $this->assertContains(
            __('Revoke'),
            $html
        );

        $html = $this->serverPrivileges->getUserLink('export', $username, $hostname);

        $url_html = Url::getCommon(
            [
                'username' => $username,
                'hostname' => $hostname,
                'initial' => "",
                'export' => 1,
            ]
        );
        $this->assertContains(
            $url_html,
            $html
        );
        $this->assertContains(
            __('Export'),
            $html
        );
    }

    /**
     * Test for getExtraDataForAjaxBehavior
     *
     * @return void
     */
    public function testGetExtraDataForAjaxBehavior()
    {
        $password = "pma_password";
        $sql_query = "pma_sql_query";
        $username = "pma_username";
        $hostname = "pma_hostname";
        $GLOBALS['dbname'] = "pma_dbname";
        $_POST['adduser_submit'] = "adduser_submit";
        $_POST['username'] = "username";
        $_POST['change_copy'] = "change_copy";
        $_GET['validate_username'] = "validate_username";
        $_GET['username'] = "username";
        $_POST['update_privs'] = "update_privs";

        $extra_data = $this->serverPrivileges->getExtraDataForAjaxBehavior(
            $password,
            $sql_query,
            $hostname,
            $username
        );

        //user_exists
        $this->assertEquals(
            false,
            $extra_data['user_exists']
        );

        //db_wildcard_privs
        $this->assertEquals(
            true,
            $extra_data['db_wildcard_privs']
        );

        //user_exists
        $this->assertEquals(
            false,
            $extra_data['db_specific_privs']
        );

        //new_user_initial
        $this->assertEquals(
            'P',
            $extra_data['new_user_initial']
        );

        //sql_query
        $this->assertEquals(
            Util::getMessage(null, $sql_query),
            $extra_data['sql_query']
        );

        //new_user_string
        $this->assertContains(
            htmlspecialchars($hostname),
            $extra_data['new_user_string']
        );
        $this->assertContains(
            htmlspecialchars($username),
            $extra_data['new_user_string']
        );

        //new_privileges
        $this->assertContains(
            join(', ', $this->serverPrivileges->extractPrivInfo(null, true)),
            $extra_data['new_privileges']
        );
    }

    /**
     * Test for getChangeLoginInformationHtmlForm
     *
     * @return void
     */
    public function testGetChangeLoginInformationHtmlForm()
    {
        $username = "pma_username";
        $hostname = "pma_hostname";
        $GLOBALS['cfgRelation']['menuswork'] = true;

        $dbi_old = $GLOBALS['dbi'];
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $fields_info = [
            [
                'COLUMN_NAME' => 'Host',
                'CHARACTER_MAXIMUM_LENGTH' => 80,
            ],
            [
                'COLUMN_NAME' => 'User',
                'CHARACTER_MAXIMUM_LENGTH' => 40,
            ],
        ];
        $dbi->expects($this->any())->method('fetchResult')
            ->will($this->returnValue($fields_info));

        $expected_userGroup = "pma_usergroup";

        $dbi->expects($this->any())->method('fetchValue')
            ->will($this->returnValue($expected_userGroup));
        $dbi->expects($this->any())
            ->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;
        $this->serverPrivileges->dbi = $dbi;

        $html = $this->serverPrivileges->getChangeLoginInformationHtmlForm($username, $hostname);

        //Url::getHiddenInputs
        $this->assertContains(
            Url::getHiddenInputs('', ''),
            $html
        );

        //$username & $hostname
        $this->assertContains(
            htmlspecialchars($username),
            $html
        );
        $this->assertContains(
            htmlspecialchars($hostname),
            $html
        );

        $this->assertContains(
            $this->serverPrivileges->getHtmlForLoginInformationFields('change', $username, $hostname),
            $html
        );

        $this->assertContains(
            '<input type="hidden" name="old_usergroup" value="'
                . $expected_userGroup . '">',
            $html
        );

        //Create a new user with the same privileges
        $this->assertContains(
            "Create a new user account with the same privileges",
            $html
        );

        $GLOBALS['dbi'] = $dbi_old;
        $this->serverPrivileges->dbi = $dbi_old;
    }

    /**
     * Test for getUserGroupForUser
     *
     * @return void
     */
    public function testGetUserGroupForUser()
    {
        $username = "pma_username";
        $GLOBALS['cfgRelation']['menuswork'] = true;

        $dbi_old = $GLOBALS['dbi'];
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $expected_userGroup = "pma_usergroup";

        $dbi->expects($this->any())->method('fetchValue')
            ->will($this->returnValue($expected_userGroup));
        $dbi->expects($this->any())
            ->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;
        $this->serverPrivileges->dbi = $dbi;

        $returned_userGroup = $this->serverPrivileges->getUserGroupForUser($username);

        $this->assertEquals(
            $expected_userGroup,
            $returned_userGroup
        );

        $GLOBALS['dbi'] = $dbi_old;
        $this->serverPrivileges->dbi = $dbi_old;
    }

    /**
     * Test for getLinkToDbAndTable
     *
     * @return void
     */
    public function testGetLinkToDbAndTable()
    {
        $url_dbname = "url_dbname";
        $dbname = "dbname";
        $tablename = "tablename";

        $html = $this->serverPrivileges->getLinkToDbAndTable($url_dbname, $dbname, $tablename);

        //$dbname
        $this->assertContains(
            __('Database'),
            $html
        );
        $this->assertContains(
            Util::getScriptNameForOption(
                $GLOBALS['cfg']['DefaultTabDatabase'],
                'database'
            ),
            $html
        );
        $item = Url::getCommon(
            [
                'db' => $url_dbname,
                'reload' => 1,
            ]
        );
        $this->assertContains(
            $item,
            $html
        );
        $this->assertContains(
            htmlspecialchars($dbname),
            $html
        );

        //$tablename
        $this->assertContains(
            __('Table'),
            $html
        );
        $this->assertContains(
            Util::getScriptNameForOption(
                $GLOBALS['cfg']['DefaultTabTable'],
                'table'
            ),
            $html
        );
        $item = Url::getCommon(
            [
                'db' => $url_dbname,
                'table' => $tablename,
                'reload' => 1,
            ]
        );
        $this->assertContains(
            $item,
            $html
        );
        $this->assertContains(
            htmlspecialchars($tablename),
            $html
        );
        $item = Util::getTitleForTarget(
            $GLOBALS['cfg']['DefaultTabTable']
        );
        $this->assertContains(
            $item,
            $html
        );
    }

    /**
     * Test for getUsersOverview
     *
     * @return void
     */
    public function testGetUsersOverview()
    {
        $result = [];
        $db_rights = [];
        $pmaThemeImage = "pmaThemeImage";
        $text_dir = "text_dir";
        $GLOBALS['cfgRelation']['menuswork'] = true;

        $html = $this->serverPrivileges->getUsersOverview(
            $result,
            $db_rights,
            $pmaThemeImage,
            $text_dir
        );

        //Url::getHiddenInputs
        $this->assertContains(
            Url::getHiddenInputs('', ''),
            $html
        );

        //items
        $this->assertContains(
            __('User'),
            $html
        );
        $this->assertContains(
            __('Host'),
            $html
        );
        $this->assertContains(
            __('Password'),
            $html
        );
        $this->assertContains(
            __('Global privileges'),
            $html
        );

        //Util::showHint
        $this->assertContains(
            Util::showHint(
                __('Note: MySQL privilege names are expressed in English.')
            ),
            $html
        );

        //__('User group')
        $this->assertContains(
            __('User group'),
            $html
        );
        $this->assertContains(
            __('Grant'),
            $html
        );
        $this->assertContains(
            __('Action'),
            $html
        );

        //$pmaThemeImage
        $this->assertContains(
            $pmaThemeImage,
            $html
        );

        //$text_dir
        $this->assertContains(
            $text_dir,
            $html
        );

        $this->assertContains(
            $this->serverPrivileges->getFieldsetForAddDeleteUser(),
            $html
        );
    }

    /**
     * Test for getFieldsetForAddDeleteUser
     *
     * @return void
     */
    public function testGetFieldsetForAddDeleteUser()
    {
        $result = [];
        $db_rights = [];
        $pmaThemeImage = "pmaThemeImage";
        $text_dir = "text_dir";
        $GLOBALS['cfgRelation']['menuswork'] = true;

        $html = $this->serverPrivileges->getUsersOverview(
            $result,
            $db_rights,
            $pmaThemeImage,
            $text_dir
        );

        //Url::getCommon
        $this->assertContains(
            Url::getCommon(['adduser' => 1]),
            $html
        );

        //labels
        $this->assertContains(
            __('Add user account'),
            $html
        );
        $this->assertContains(
            __('Remove selected user accounts'),
            $html
        );
        $this->assertContains(
            __('Drop the databases that have the same names as the users.'),
            $html
        );
        $this->assertContains(
            __('Drop the databases that have the same names as the users.'),
            $html
        );
    }

    /**
     * Test for getAddUserHtmlFieldset
     *
     * @return void
     */
    public function testGetAddUserHtmlFieldset()
    {
        $html = $this->serverPrivileges->getAddUserHtmlFieldset();

        $this->assertContains(
            Url::getCommon(['adduser' => 1]),
            $html
        );
        $this->assertContains(
            Util::getIcon('b_usradd'),
            $html
        );
        $this->assertContains(
            __('Add user'),
            $html
        );
    }

    /**
     * Test for getHtmlHeaderForUserProperties
     *
     * @return void
     */
    public function testGetHtmlHeaderForUserProperties()
    {
        $dbname_is_wildcard = true;
        $url_dbname = "url_dbname";
        $dbname = "dbname";
        $username = "username";
        $hostname = "hostname";
        $tablename = "tablename";
        $_REQUEST['tablename'] = "tablename";

        $html = $this->serverPrivileges->getHtmlHeaderForUserProperties(
            $dbname_is_wildcard,
            $url_dbname,
            $dbname,
            $username,
            $hostname,
            $tablename,
            'table'
        );

        //title
        $this->assertContains(
            __('Edit privileges:'),
            $html
        );
        $this->assertContains(
            __('User account'),
            $html
        );

        //Url::getCommon
        $item = Url::getCommon(
            [
                'username' => $username,
                'hostname' => $hostname,
                'dbname' => '',
                'tablename' => '',
            ]
        );
        $this->assertContains(
            $item,
            $html
        );

        //$username & $hostname
        $this->assertContains(
            htmlspecialchars($username),
            $html
        );
        $this->assertContains(
            htmlspecialchars($hostname),
            $html
        );

        //$dbname_is_wildcard = true
        $this->assertContains(
            __('Databases'),
            $html
        );

        //$dbname_is_wildcard = true
        $this->assertContains(
            __('Databases'),
            $html
        );

        //Url::getCommon
        $item = Url::getCommon(
            [
                'username' => $username,
                'hostname' => $hostname,
                'dbname' => $url_dbname,
                'tablename' => '',
            ]
        );
        $this->assertContains(
            $item,
            $html
        );
        $this->assertContains(
            $dbname,
            $html
        );
    }

    /**
     * Tests for getHtmlForViewUsersError
     *
     * @return void
     */
    public function testGetHtmlForViewUsersError()
    {
        $this->assertContains(
            'Not enough privilege to view users.',
            $this->serverPrivileges->getHtmlForViewUsersError()
        );
    }

    /**
     * Tests for getHtmlForUserProperties
     *
     * @return void
     */
    public function testGetHtmlForUserProperties()
    {
        $actual = $this->serverPrivileges->getHtmlForUserProperties(
            false,
            'db',
            'user',
            'host',
            'db',
            'table'
        );
        $this->assertContains('addUsersForm', $actual);
        $this->assertContains('SELECT', $actual);
        $this->assertContains('Allows reading data.', $actual);
        $this->assertContains('INSERT', $actual);
        $this->assertContains('Allows inserting and replacing data.', $actual);
        $this->assertContains('UPDATE', $actual);
        $this->assertContains('Allows changing data.', $actual);
        $this->assertContains('DELETE', $actual);
        $this->assertContains('Allows deleting data.', $actual);
        $this->assertContains('CREATE', $actual);
        $this->assertContains('Allows creating new tables.', $actual);
    }

    /**
     * Tests for getHtmlForUserOverview
     *
     * @return void
     */
    public function testGetHtmlForUserOverview()
    {
        $actual = $this->serverPrivileges->getHtmlForUserOverview('theme', '');
        $this->assertContains(
            'Note: MySQL privilege names are expressed in English.',
            $actual
        );
        $this->assertContains(
            'Note: phpMyAdmin gets the users’ privileges directly '
            . 'from MySQL’s privilege tables.',
            $actual
        );
    }

    /**
     * Tests for getHtmlForAllTableSpecificRights
     *
     * @return void
     */
    public function testGetHtmlForAllTableSpecificRights()
    {
        // Test case 1
        $actual = $this->serverPrivileges->getHtmlForAllTableSpecificRights('pma', 'host', 'table', 'pmadb');
        $this->assertContains(
            '<input type="hidden" name="username" value="pma">',
            $actual
        );
        $this->assertContains(
            '<input type="hidden" name="hostname" value="host">',
            $actual
        );
        $this->assertContains(
            '<legend data-submenu-label="Table">',
            $actual
        );
        $this->assertContains(
            'Table-specific privileges',
            $actual
        );

        // Test case 2
        $GLOBALS['dblist'] = new \stdClass();
        $GLOBALS['dblist']->databases = [
            'x',
            'y',
            'z',
        ];
        $actual = $this->serverPrivileges->getHtmlForAllTableSpecificRights('pma2', 'host2', 'database', '');
        $this->assertContains(
            '<legend data-submenu-label="Database">',
            $actual
        );
        $this->assertContains(
            'Database-specific privileges',
            $actual
        );
    }

    /**
     * Tests for getHtmlForInitials
     *
     * @return void
     */
    public function testGetHtmlForInitials()
    {
        // Setup for the test
        $GLOBALS['dbi']->expects($this->any())->method('fetchRow')
            ->will($this->onConsecutiveCalls(['-']));
        $this->serverPrivileges->dbi = $GLOBALS['dbi'];
        $actual = $this->serverPrivileges->getHtmlForInitials(['"' => true]);
        $this->assertContains('<td>A</td>', $actual);
        $this->assertContains('<td>Z</td>', $actual);
        $this->assertContains(
            '<a class="ajax" href="server_privileges.php?initial=-&amp;'
            . 'server=1&amp;lang=en">-</a>',
            $actual
        );
        $this->assertContains(
            '<a class="ajax" href="server_privileges.php?initial=%22&amp;'
            . 'server=1&amp;lang=en">"</a>',
            $actual
        );
        $this->assertContains('Show all', $actual);
    }

    /**
     * Test for getHtmlForAuthPluginsDropdown()
     *
     * @return void
     */
    public function testGetHtmlForAuthPluginsDropdown()
    {
        $oldDbi = $GLOBALS['dbi'];

        //Mock DBI
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->any())
            ->method('query')
            ->will($this->onConsecutiveCalls(true, true));

        $plugins = [
            [
                'PLUGIN_NAME' => 'mysql_native_password',
                'PLUGIN_DESCRIPTION' => 'Native MySQL authentication',
            ],
            [
                'PLUGIN_NAME' => 'sha256_password',
                'PLUGIN_DESCRIPTION' => 'SHA256 password authentication',
            ],
        ];
        $dbi->expects($this->any())
            ->method('fetchAssoc')
            ->will(
                $this->onConsecutiveCalls(
                    $plugins[0],
                    $plugins[1],
                    null, /* For Assertion 1 */
                    $plugins[0],
                    $plugins[1],
                    null  /* For Assertion 2 */
                )
            );
        $GLOBALS['dbi'] = $dbi;
        $this->serverPrivileges->dbi = $dbi;

        /* Assertion 1 */
        $actualHtml = $this->serverPrivileges->getHtmlForAuthPluginsDropdown(
            'mysql_native_password',
            'new',
            'new'
        );
        $this->assertEquals(
            '<select name="authentication_plugin" id="select_authentication_plugin">'
            . "\n"
            . '<option value="mysql_native_password" selected="selected">'
            . 'Native MySQL authentication</option>'
            . "\n"
            . '<option value="sha256_password">'
            . 'SHA256 password authentication</option>' . "\n" . '</select>'
            . "\n",
            $actualHtml
        );

        /* Assertion 2 */
        $actualHtml = $this->serverPrivileges->getHtmlForAuthPluginsDropdown(
            'mysql_native_password',
            'change_pw',
            'new'
        );
        $this->assertEquals(
            '<select name="authentication_plugin" '
            . 'id="select_authentication_plugin_cp">'
            . "\n" . '<option '
            . 'value="mysql_native_password" selected="selected">'
            . 'Native MySQL authentication</option>'
            . "\n" . '<option value="sha256_password">'
            . 'SHA256 password authentication</option>' . "\n" . '</select>'
            . "\n",
            $actualHtml
        );

        /* Assertion 3 */
        $actualHtml = $this->serverPrivileges->getHtmlForAuthPluginsDropdown(
            'mysql_native_password',
            'new',
            'old'
        );
        $this->assertEquals(
            '<select name="authentication_plugin" '
            . 'id="select_authentication_plugin">'
            . "\n" . '<option '
            . 'value="mysql_native_password" selected="selected">'
            . 'Native MySQL authentication</option>' . "\n" . '</select>'
            . "\n",
            $actualHtml
        );

        /* Assertion 4 */
        $actualHtml = $this->serverPrivileges->getHtmlForAuthPluginsDropdown(
            'mysql_native_password',
            'change_pw',
            'old'
        );
        $this->assertEquals(
            '<select name="authentication_plugin" '
            . 'id="select_authentication_plugin_cp">'
            . "\n"
            . '<option value="mysql_native_password" selected="selected">'
            . 'Native MySQL authentication</option>'
            . "\n" . '</select>'
            . "\n",
            $actualHtml
        );

        // Restore old DBI
        $GLOBALS['dbi'] = $oldDbi;
        $this->serverPrivileges->dbi = $oldDbi;
    }
}
