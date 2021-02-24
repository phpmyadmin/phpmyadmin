<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Relation;
use PhpMyAdmin\RelationCleanup;
use PHPUnit\Framework\MockObject\MockObject;
use function array_merge;

/**
 * PhpMyAdmin\Tests\RelationCleanupTest class
 *
 * this class is for testing PhpMyAdmin\RelationCleanup methods
 */
class RelationCleanupTest extends AbstractTestCase
{
    /** @var Relation|MockObject */
    private $relation;

    /** @var RelationCleanup */
    private $relationCleanup;

    /**
     * Prepares environment for the test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::defineVersionConstants();
        $GLOBALS['server'] = 1;
        $_SESSION['relation'] = [];
        $_SESSION['relation'][$GLOBALS['server']] = [
            'PMA_VERSION' => PMA_VERSION,
            'relwork' => false,
            'displaywork' => false,
            'bookmarkwork' => false,
            'pdfwork' => false,
            'commwork' => false,
            'mimework' => false,
            'historywork' => false,
            'recentwork' => false,
            'favoritework' => false,
            'uiprefswork' => false,
            'trackingwork' => false,
            'userconfigwork' => false,
            'menuswork' => false,
            'navwork' => false,
            'savedsearcheswork' => false,
            'centralcolumnswork' => false,
            'designersettingswork' => false,
            'exporttemplateswork' => false,
            'allworks' => false,
            'user' => 'user',
            'db' => 'pmadb',
            'bookmark' => 'bookmark',
            'relation' => 'relation',
            'table_info' => 'table_info',
            'table_coords' => 'table_coords',
            'column_info' => 'column_info',
            'pdf_pages' => 'pdf_pages',
            'history' => 'history',
            'recent' => 'recent',
            'favorite' => 'favorite',
            'table_uiprefs' => 'table_uiprefs',
            'tracking' => 'tracking',
            'userconfig' => 'userconfig',
            'users' => 'users',
            'usergroups' => 'usergroups',
            'navigationhiding' => 'navigationhiding',
            'savedsearches' => 'savedsearches',
            'central_columns' => 'central_columns',
            'designer_settings' => 'designer_settings',
            'export_templates' => 'export_templates',
        ];

        $this->relation = $this->getMockBuilder(Relation::class)
            ->disableOriginalConstructor()
            ->setMethods(['queryAsControlUser'])
            ->getMock();
        $this->relationCleanup = new RelationCleanup($GLOBALS['dbi'], $this->relation);
    }

    /**
     * Test for column method
     */
    public function testColumnWithoutRelations(): void
    {
        $this->relation->expects($this->never())
            ->method('queryAsControlUser');

        $this->relationCleanup->column('database', 'table', 'column');
    }

    /**
     * Test for column method
     */
    public function testColumnWithRelations(): void
    {
        $_SESSION['relation'][$GLOBALS['server']] = array_merge(
            $_SESSION['relation'][$GLOBALS['server']],
            [
                'commwork' => true,
                'displaywork' => true,
                'relwork' => true,
            ]
        );

        $this->relation->expects($this->exactly(4))
            ->method('queryAsControlUser')
            ->withConsecutive(
                [
                    $this->equalTo(
                        "DELETE FROM `pmadb`.`column_info` WHERE db_name  = 'database' AND"
                        . " table_name = 'table' AND column_name = 'column'"
                    ),
                ],
                [
                    $this->equalTo(
                        "DELETE FROM `pmadb`.`table_info` WHERE db_name  = 'database' AND"
                        . " table_name = 'table' AND display_field = 'column'"
                    ),
                ],
                [
                    $this->equalTo(
                        "DELETE FROM `pmadb`.`relation` WHERE master_db  = 'database' AND"
                        . " master_table = 'table' AND master_field = 'column'"
                    ),
                ],
                [
                    $this->equalTo(
                        "DELETE FROM `pmadb`.`relation` WHERE foreign_db  = 'database' AND"
                        . " foreign_table = 'table' AND foreign_field = 'column'"
                    ),
                ]
            );

        $this->relationCleanup->column('database', 'table', 'column');
    }

    /**
     * Test for table method
     */
    public function testTableWithoutRelations(): void
    {
        $this->relation->expects($this->never())
            ->method('queryAsControlUser');

        $this->relationCleanup->table('database', 'table');
    }

    /**
     * Test for table method
     */
    public function testTableWithRelations(): void
    {
        $_SESSION['relation'][$GLOBALS['server']] = array_merge(
            $_SESSION['relation'][$GLOBALS['server']],
            [
                'commwork' => true,
                'displaywork' => true,
                'pdfwork' => true,
                'relwork' => true,
                'uiprefswork' => true,
                'navwork' => true,
            ]
        );

        $this->relation->expects($this->exactly(7))
            ->method('queryAsControlUser')
            ->withConsecutive(
                [
                    $this->equalTo(
                        "DELETE FROM `pmadb`.`column_info` WHERE db_name  = 'database' AND table_name = 'table'"
                    ),
                ],
                [
                    $this->equalTo(
                        "DELETE FROM `pmadb`.`table_info` WHERE db_name  = 'database' AND table_name = 'table'"
                    ),
                ],
                [
                    $this->equalTo(
                        "DELETE FROM `pmadb`.`table_coords` WHERE db_name  = 'database' AND table_name = 'table'"
                    ),
                ],
                [
                    $this->equalTo(
                        "DELETE FROM `pmadb`.`relation` WHERE master_db  = 'database' AND master_table = 'table'"
                    ),
                ],
                [
                    $this->equalTo(
                        "DELETE FROM `pmadb`.`relation` WHERE foreign_db  = 'database' AND foreign_table = 'table'"
                    ),
                ],
                [
                    $this->equalTo(
                        "DELETE FROM `pmadb`.`table_uiprefs` WHERE db_name  = 'database' AND table_name = 'table'"
                    ),
                ],
                [
                    $this->equalTo(
                        "DELETE FROM `pmadb`.`navigationhiding` WHERE db_name  = 'database' AND"
                        . " (table_name = 'table' OR (item_name = 'table' AND item_type = 'table'))"
                    ),
                ]
            );

        $this->relationCleanup->table('database', 'table');
    }

    /**
     * Test for database method
     */
    public function testDatabaseWithoutRelations(): void
    {
        $this->relation->expects($this->never())
            ->method('queryAsControlUser');

        $this->relationCleanup->database('database');
    }

    /**
     * Test for database method
     */
    public function testDatabaseWithRelations(): void
    {
        $_SESSION['relation'][$GLOBALS['server']] = array_merge(
            $_SESSION['relation'][$GLOBALS['server']],
            [
                'commwork' => true,
                'bookmarkwork' => true,
                'displaywork' => true,
                'pdfwork' => true,
                'relwork' => true,
                'uiprefswork' => true,
                'navwork' => true,
                'savedsearcheswork' => true,
                'centralcolumnswork' => true,
            ]
        );

        $this->relation->expects($this->exactly(11))
            ->method('queryAsControlUser')
            ->withConsecutive(
                [$this->equalTo("DELETE FROM `pmadb`.`column_info` WHERE db_name  = 'database'")],
                [$this->equalTo("DELETE FROM `pmadb`.`bookmark` WHERE dbase  = 'database'")],
                [$this->equalTo("DELETE FROM `pmadb`.`table_info` WHERE db_name  = 'database'")],
                [$this->equalTo("DELETE FROM `pmadb`.`pdf_pages` WHERE db_name  = 'database'")],
                [$this->equalTo("DELETE FROM `pmadb`.`table_coords` WHERE db_name  = 'database'")],
                [$this->equalTo("DELETE FROM `pmadb`.`relation` WHERE master_db  = 'database'")],
                [$this->equalTo("DELETE FROM `pmadb`.`relation` WHERE foreign_db  = 'database'")],
                [$this->equalTo("DELETE FROM `pmadb`.`table_uiprefs` WHERE db_name  = 'database'")],
                [$this->equalTo("DELETE FROM `pmadb`.`navigationhiding` WHERE db_name  = 'database'")],
                [$this->equalTo("DELETE FROM `pmadb`.`savedsearches` WHERE db_name  = 'database'")],
                [$this->equalTo("DELETE FROM `pmadb`.`central_columns` WHERE db_name  = 'database'")]
            );

        $this->relationCleanup->database('database');
    }

    /**
     * Test for user method
     */
    public function testUserWithoutRelations(): void
    {
        $this->relation->expects($this->never())
            ->method('queryAsControlUser');

        $this->relationCleanup->user('user');
    }

    /**
     * Test for user method
     */
    public function testUserWithRelations(): void
    {
        $_SESSION['relation'][$GLOBALS['server']] = array_merge(
            $_SESSION['relation'][$GLOBALS['server']],
            [
                'bookmarkwork' => true,
                'historywork' => true,
                'recentwork' => true,
                'favoritework' => true,
                'uiprefswork' => true,
                'userconfigwork' => true,
                'menuswork' => true,
                'navwork' => true,
                'savedsearcheswork' => true,
                'designersettingswork' => true,
            ]
        );

        $this->relation->expects($this->exactly(10))
            ->method('queryAsControlUser')
            ->withConsecutive(
                [$this->equalTo("DELETE FROM `pmadb`.`bookmark` WHERE `user`  = 'user'")],
                [$this->equalTo("DELETE FROM `pmadb`.`history` WHERE `username`  = 'user'")],
                [$this->equalTo("DELETE FROM `pmadb`.`recent` WHERE `username`  = 'user'")],
                [$this->equalTo("DELETE FROM `pmadb`.`favorite` WHERE `username`  = 'user'")],
                [$this->equalTo("DELETE FROM `pmadb`.`table_uiprefs` WHERE `username`  = 'user'")],
                [$this->equalTo("DELETE FROM `pmadb`.`userconfig` WHERE `username`  = 'user'")],
                [$this->equalTo("DELETE FROM `pmadb`.`users` WHERE `username`  = 'user'")],
                [$this->equalTo("DELETE FROM `pmadb`.`navigationhiding` WHERE `username`  = 'user'")],
                [$this->equalTo("DELETE FROM `pmadb`.`savedsearches` WHERE `username`  = 'user'")],
                [$this->equalTo("DELETE FROM `pmadb`.`designer_settings` WHERE `username`  = 'user'")]
            );

        $this->relationCleanup->user('user');
    }
}
