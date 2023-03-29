<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers;

use PhpMyAdmin\Controllers\NavigationController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;

use function sprintf;

/** @covers \PhpMyAdmin\Controllers\NavigationController */
class NavigationControllerTest extends AbstractTestCase
{
    protected DatabaseInterface $dbi;

    protected DbiDummy $dummyDbi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        $GLOBALS['dbi'] = $this->dbi;
    }

    public function testIndex(): void
    {
        parent::loadContainerBuilder();

        parent::loadDbiIntoContainerBuilder();

        parent::setLanguage();

        $this->setTheme();

        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'air-balloon_burner_dev2';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['cfg']['Server']['auth_type'] = 'cookie';

        parent::loadResponseIntoContainerBuilder();

        // This example path data has nothing to do with the actual test
        // root.air-balloon_burner_dev2
        $_POST['n0_aPath'] = 'cm9vdA==.YWlyLWJhbGxvb25fYnVybmVyX2RldjI=';
        // root.air-balloon.burner_dev2
        $_POST['n0_vPath'] = 'cm9vdA==.YWlyLWJhbGxvb24=.YnVybmVyX2RldjI=';

        $_GET['ajax_request'] = true;

        $this->dummyDbi->removeDefaultResults();
        $this->dummyDbi->addResult(
            'SELECT CURRENT_USER();',
            [['pma_test@localhost']],
        );
        $this->dummyDbi->addResult(
            'SHOW GRANTS',
            [],
        );
        $this->dummyDbi->addResult(
            'SELECT (COUNT(DB_first_level) DIV 100) * 100 from ('
            . ' SELECT distinct SUBSTRING_INDEX(SCHEMA_NAME, \'_\', 1) DB_first_level '
            . 'FROM INFORMATION_SCHEMA.SCHEMATA WHERE `SCHEMA_NAME` < \'air-balloon_burner_dev2\' ) t',
            [],
        );
        $this->dummyDbi->addResult(
            'SELECT `SCHEMA_NAME` FROM `INFORMATION_SCHEMA`.`SCHEMATA`, '
                . '(SELECT DB_first_level FROM ( SELECT DISTINCT '
                . "SUBSTRING_INDEX(SCHEMA_NAME, '_', 1) DB_first_level "
                . 'FROM INFORMATION_SCHEMA.SCHEMATA WHERE TRUE ) t ORDER BY '
                . 'DB_first_level ASC LIMIT 0, 100) t2 WHERE TRUE AND 1 = LOCATE('
                . "CONCAT(DB_first_level, '_'), CONCAT(SCHEMA_NAME, '_')) "
                . 'ORDER BY SCHEMA_NAME ASC',
            [['air-balloon_burner_dev2']],
            ['SCHEMA_NAME'],
        );
        $sqlCount = 'SELECT COUNT(*) FROM ( SELECT DISTINCT SUBSTRING_INDEX(SCHEMA_NAME, \'_\', 1) '
        . 'DB_first_level FROM INFORMATION_SCHEMA.SCHEMATA WHERE TRUE ) t';
        $this->dummyDbi->addResult(
            $sqlCount,
            [[179]],
        );
        $this->dummyDbi->addResult(
            $sqlCount,
            [[179]],
        );

        $this->dummyDbi->addResult(
            'SELECT COUNT(*) FROM `INFORMATION_SCHEMA`.`TABLES` WHERE `TABLE_SCHEMA`=\'air-balloon_burner_dev2\''
            . ' AND `TABLE_TYPE` IN(\'BASE TABLE\', \'SYSTEM VERSIONED\')',
            [[0]],
        );

        $this->dummyDbi->addResult(
            'SELECT COUNT(*) FROM `INFORMATION_SCHEMA`.`TABLES` WHERE `TABLE_SCHEMA`=\'air-balloon_burner_dev2\''
            . ' AND `TABLE_TYPE` NOT IN(\'BASE TABLE\', \'SYSTEM VERSIONED\')',
            [[0]],
        );

        $this->dummyDbi->addResult('SELECT @@lower_case_table_names', [['0']]);

        $this->dummyDbi->addResult(
            'SELECT COUNT(*) FROM `INFORMATION_SCHEMA`.`ROUTINES` WHERE '
            . '`ROUTINE_SCHEMA` COLLATE utf8_bin=\'air-balloon_burner_dev2\' AND `ROUTINE_TYPE`=\'FUNCTION\'',
            [[0]],
        );

        $this->dummyDbi->addResult(
            'SELECT COUNT(*) FROM `INFORMATION_SCHEMA`.`ROUTINES`'
            . ' WHERE `ROUTINE_SCHEMA` COLLATE utf8_bin=\'air-balloon_burner_dev2\''
            . 'AND `ROUTINE_TYPE`=\'PROCEDURE\'',
            [[0]],
        );

        $this->dummyDbi->addResult(
            'SELECT COUNT(*) FROM `INFORMATION_SCHEMA`.`EVENTS`'
            . ' WHERE `EVENT_SCHEMA` COLLATE utf8_bin=\'air-balloon_burner_dev2\'',
            [[0]],
        );

        /** @var NavigationController $navigationController */
        $navigationController = $GLOBALS['containerBuilder']->get(NavigationController::class);
        $_POST['full'] = '1';
        $this->setResponseIsAjax();
        $navigationController($this->createStub(ServerRequest::class));
        $this->assertResponseWasSuccessfull();

        $responseMessage = $this->getResponseJsonResult()['message'];

        $this->assertStringContainsString('<div id=\'pma_navigation_tree_content\'>', $responseMessage);

        // root.air-balloon_burner_dev2
        // cm9vdA==.YWlyLWJhbGxvb25fYnVybmVyX2RldjI=
        $this->assertStringContainsString(
            '<div id=\'pma_navigation_tree_content\'>' . "\n"
            . '  <ul>' . "\n"
            . '      <li class="first database">' . "\n"
            . '    <div class="block">' . "\n"
            . '      <i class="first"></i>' . "\n"
            . '              <b></b>' . "\n"
            . '        <a class="expander" href="#">' . "\n"
            . '          <span class="hide paths_nav" data-apath="cm9vdA==.YWlyLWJhbGxvb25fYnVybmVyX2RldjI="'
                        . ' data-vpath="cm9vdA==.YWlyLWJhbGxvb25fYnVybmVyX2RldjI="'
                        . ' data-pos="0"></span>' . "\n"
            . '                    <img src="themes/dot.gif" title="Expand/Collapse"'
                                . ' alt="Expand/Collapse" class="icon ic_b_plus">' . "\n"
            . '        </a>' . "\n"
            . '          </div>' . "\n"
            . '    ' . "\n"
            . '          <div class="block second">' . "\n"
            . '                  <a href="index.php?route=/database/operations'
                                . '&db=air-balloon_burner_dev2&lang=en">'
                                . '<img src="themes/dot.gif" title="Database operations"'
                                . ' alt="Database operations" class="icon ic_s_db"></a>' . "\n"
            . '              </div>' . "\n"
            . "\n"
            . '              <a class="hover_show_full"'
                    . ' href="index.php?route=/database/structure&db=air-balloon_burner_dev2&lang=en"'
                    . ' title="Structure">air-balloon_burner_dev2</a>' . "\n"
            . '          ' . "\n"
            . '    ' . "\n"
            . "\n"
            . '    ' . "\n"
            . '    <div class="clearfloat"></div>' . "\n"
            . "\n"
            . "\n"
            . "\n"
            . "\n"
            . '  </ul>' . "\n"
            . '</div>',
            $responseMessage,
        );
        $this->dummyDbi->assertAllQueriesConsumed();
    }

    public function testIndexWithPosAndValue(): void
    {
        parent::loadContainerBuilder();

        parent::loadDbiIntoContainerBuilder();

        parent::setLanguage();

        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'air-balloon_burner_dev2';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['cfg']['Server']['auth_type'] = 'cookie';

        parent::loadResponseIntoContainerBuilder();

        // root.air-balloon_burner_dev2
        $_POST['n0_aPath'] = 'cm9vdA==.YWlyLWJhbGxvb25fYnVybmVyX2RldjI=';
        // root.air-balloon.burner_dev2
        $_POST['n0_vPath'] = 'cm9vdA==.YWlyLWJhbGxvb24=.YnVybmVyX2RldjI=';

        $_POST['n0_pos2_name'] = 'tables';
        $_POST['n0_pos2_value'] = 0;

        $_GET['ajax_request'] = true;

        $this->dummyDbi->removeDefaultResults();
        $this->dummyDbi->addResult(
            'SELECT CURRENT_USER();',
            [['pma_test@localhost']],
        );
        $this->dummyDbi->addResult(
            'SHOW GRANTS',
            [],
        );
        $this->dummyDbi->addResult(
            'SELECT (COUNT(DB_first_level) DIV 100) * 100 from ('
            . ' SELECT distinct SUBSTRING_INDEX(SCHEMA_NAME, \'_\', 1) DB_first_level '
            . 'FROM INFORMATION_SCHEMA.SCHEMATA WHERE `SCHEMA_NAME` < \'air-balloon_burner_dev2\' ) t',
            [],
        );
        $this->dummyDbi->addResult(
            'SELECT `SCHEMA_NAME` FROM `INFORMATION_SCHEMA`.`SCHEMATA`, '
                . '(SELECT DB_first_level FROM ( SELECT DISTINCT '
                . "SUBSTRING_INDEX(SCHEMA_NAME, '_', 1) DB_first_level "
                . 'FROM INFORMATION_SCHEMA.SCHEMATA WHERE TRUE ) t ORDER BY '
                . 'DB_first_level ASC LIMIT 0, 100) t2 WHERE TRUE AND 1 = LOCATE('
                . "CONCAT(DB_first_level, '_'), CONCAT(SCHEMA_NAME, '_')) "
                . 'ORDER BY SCHEMA_NAME ASC',
            [['air-balloon_burner_dev'], ['air-balloon_burner_dev2'], ['air-balloon_dev']],
            ['SCHEMA_NAME'],
        );

        $sqlCount = 'SELECT COUNT(*) FROM ( SELECT DISTINCT SUBSTRING_INDEX(SCHEMA_NAME, \'_\', 1) '
        . 'DB_first_level FROM INFORMATION_SCHEMA.SCHEMATA WHERE TRUE ) t';
        $this->dummyDbi->addResult(
            $sqlCount,
            [[179]],
        );
        $this->dummyDbi->addResult(
            $sqlCount,
            [[179]],
        );

        $this->dummyDbi->addResult(
            'SELECT COUNT(*) FROM `INFORMATION_SCHEMA`.`TABLES` WHERE `TABLE_SCHEMA`=\'air-balloon_burner_dev2\''
            . ' AND `TABLE_TYPE` IN(\'BASE TABLE\', \'SYSTEM VERSIONED\')',
            [[0]],
        );

        $this->dummyDbi->addResult(
            'SELECT COUNT(*) FROM `INFORMATION_SCHEMA`.`TABLES` WHERE `TABLE_SCHEMA`=\'air-balloon_burner_dev2\''
            . ' AND `TABLE_TYPE` NOT IN(\'BASE TABLE\', \'SYSTEM VERSIONED\')',
            [[0]],
        );

        $this->dummyDbi->addResult('SELECT @@lower_case_table_names', [['0']]);

        $this->dummyDbi->addResult(
            'SELECT COUNT(*) FROM `INFORMATION_SCHEMA`.`ROUTINES` WHERE '
            . '`ROUTINE_SCHEMA` COLLATE utf8_bin=\'air-balloon_burner_dev2\' AND `ROUTINE_TYPE`=\'FUNCTION\'',
            [[0]],
        );

        $this->dummyDbi->addResult(
            'SELECT COUNT(*) FROM `INFORMATION_SCHEMA`.`EVENTS` WHERE'
            . ' `EVENT_SCHEMA` COLLATE utf8_bin=\'air-balloon_burner_dev2\'',
            [[0]],
        );

        $this->dummyDbi->addResult(
            'SELECT COUNT(*) FROM `INFORMATION_SCHEMA`.`ROUTINES` WHERE '
            . '`ROUTINE_SCHEMA` COLLATE utf8_bin=\'air-balloon_burner_dev2\'AND `ROUTINE_TYPE`=\'PROCEDURE\'',
            [[0]],
        );

        /** @var NavigationController $navigationController */
        $navigationController = $GLOBALS['containerBuilder']->get(NavigationController::class);
        $_POST['full'] = '1';
        $this->setResponseIsAjax();
        $navigationController($this->createStub(ServerRequest::class));
        $this->assertResponseWasSuccessfull();

        $responseMessage = $this->getResponseJsonResult()['message'];

        $this->assertStringContainsString('<div id=\'pma_navigation_tree_content\'>', $responseMessage);

        $dbTemplate = '  <li class="database database">' . "\n"
            . '    <div class="block">' . "\n"
            . '      <i></i>' . "\n"
            . '              <b></b>' . "\n"
            . '        <a class="expander" href="#">' . "\n"
            . '          <span class="hide paths_nav" data-apath="%s" data-vpath="%s" data-pos="0"></span>' . "\n"
            . '                    <img src="themes/dot.gif" title="Expand/Collapse" alt="Expand/Collapse"'
            . ' class="icon ic_b_plus">' . "\n"
            . '        </a>' . "\n"
            . '          </div>' . "\n"
            . '    ' . "\n"
            . '          <div class="block second">' . "\n"
            . '                  <a href="index.php?route=/database/operations&db=%s&lang=en">'
            . '<img src="themes/dot.gif" title="Database operations" alt="Database operations"'
            . ' class="icon ic_s_db"></a>' . "\n"
            . '              </div>' . "\n"
            . "\n"
            . '              <a class="hover_show_full" href="index.php?route=/database/structure&db=%s&lang=en"'
            . ' title="Structure">%s</a>' . "\n"
            . '          ' . "\n"
            . '    ' . "\n"
            . "\n"
            . '    ' . "\n"
            . '    <div class="clearfloat"></div>' . "\n"
            . "\n"
            . "\n"
            . "\n"
            . '  </li>';

        $dbTemplateLast = '  <li class="database last database">' . "\n"// "last" class added
            . '    <div class="block">' . "\n"
            . '      <i></i>' . "\n"
            . '              ' . "\n"// <b> node is removed
            . '        <a class="expander" href="#">' . "\n"
            . '          <span class="hide paths_nav" data-apath="%s" data-vpath="%s" data-pos="0"></span>' . "\n"
            . '                    <img src="themes/dot.gif" title="Expand/Collapse" alt="Expand/Collapse"'
            . ' class="icon ic_b_plus">' . "\n"
            . '        </a>' . "\n"
            . '          </div>' . "\n"
            . '    ' . "\n"
            . '          <div class="block second">' . "\n"
            . '                  <a href="index.php?route=/database/operations&db=%s&lang=en">'
            . '<img src="themes/dot.gif" title="Database operations" alt="Database operations"'
            . ' class="icon ic_s_db"></a>' . "\n"
            . '              </div>' . "\n"
            . "\n"
            . '              <a class="hover_show_full" href="index.php?route=/database/structure&db=%s&lang=en"'
            . ' title="Structure">%s</a>' . "\n"
            . '          ' . "\n"
            . '    ' . "\n"
            . "\n"
            . '    ' . "\n"
            . '    <div class="clearfloat"></div>' . "\n"
            . "\n"
            . "\n"
            . "\n"
            . '  </li>';
        $dbTemplateExpanded = '  <li class="database database">' . "\n"
            . '    <div class="block">' . "\n"
            . '      <i></i>' . "\n"
            . '              <b></b>' . "\n"
            . '        <a class="expander loaded" href="#">' . "\n"
            . '          <span class="hide paths_nav" data-apath="%s" data-vpath="%s" data-pos="0"></span>' . "\n"
            . '                    <img src="themes/dot.gif" title="" alt=""'// title and alt changes
            . ' class="icon ic_b_minus">' . "\n"// Icon changes
            . '        </a>' . "\n"
            . '          </div>' . "\n"
            . '    ' . "\n"
            . '          <div class="block second">' . "\n"
            . '                  <a href="index.php?route=/database/operations&db=%s&lang=en">'
            . '<img src="themes/dot.gif" title="Database operations" alt="Database operations"'
            . ' class="icon ic_s_db"></a>' . "\n"
            . '              </div>' . "\n"
            . "\n"
            . '              <a class="hover_show_full" href="index.php?route=/database/structure&db=%s&lang=en"'
            . ' title="Structure">%s</a>' . "\n"
            . '          ' . "\n"
            . '    ' . "\n"
            . "\n"
            . '    ' . "\n"
            . '    <div class="clearfloat"></div>' . "\n"
            . "\n"
            . "\n"
            . "\n"
            . '  </li>';

        // root.air-balloon_burner_dev2
        // cm9vdA==.YWlyLWJhbGxvb25fYnVybmVyX2RldjI=
        $this->assertStringContainsString(
            '<div id=\'pma_navigation_tree_content\'>' . "\n"
            . '  <ul>' . "\n"
            . '      <li class="first navGroup">' . "\n"
            . '    <div class="block">' . "\n"
            . '      <i class="first"></i>' . "\n"
            . '              <b></b>' . "\n"
            . '        <a class="expander loaded container" href="#">' . "\n"
            . '          <span class="hide paths_nav" data-apath="cm9vdA=="'
                        . ' data-vpath="cm9vdA==.YWlyLWJhbGxvb24="'
                        . ' data-pos="0"></span>' . "\n"
            . '                    <img src="themes/dot.gif" title="" alt="" class="icon ic_b_minus">' . "\n"
            . '        </a>' . "\n"
            . '          </div>' . "\n"
            . '          <div class="fst-italic">' . "\n"
            . '    ' . "\n"
            . '          <div class="block second">' . "\n"
            . '        <u><img src="themes/dot.gif" title="Groups" alt="Groups" class="icon ic_b_group"></u>' . "\n"
            . '      </div>' . "\n"
            . '      &nbsp;air-balloon' . "\n"
            . '    ' . "\n"
            . '    ' . "\n"
            . "\n"
            . '          </div>' . "\n"
            . '    ' . "\n"
            . '    <div class="clearfloat"></div>' . "\n"
            . "\n"
            . '  <div class="list_container">' . "\n"
            . '    <ul>' . "\n"
                    . sprintf(
                        $dbTemplate,
                        'cm9vdA==.YWlyLWJhbGxvb25fYnVybmVyX2Rldg==',
                        'cm9vdA==.YWlyLWJhbGxvb24=.YnVybmVyX2Rldg==',
                        'air-balloon_burner_dev',
                        'air-balloon_burner_dev',
                        'air-balloon_burner_dev',
                    ) . "\n"
                    . sprintf(
                        $dbTemplateExpanded,
                        'cm9vdA==.YWlyLWJhbGxvb25fYnVybmVyX2RldjI=',
                        'cm9vdA==.YWlyLWJhbGxvb24=.YnVybmVyX2RldjI=',
                        'air-balloon_burner_dev2',
                        'air-balloon_burner_dev2',
                        'air-balloon_burner_dev2',
                    ) . "\n"
                    . sprintf(
                        $dbTemplateLast,
                        'cm9vdA==.YWlyLWJhbGxvb25fZGV2',
                        'cm9vdA==.YWlyLWJhbGxvb24=.ZGV2',
                        'air-balloon_dev',
                        'air-balloon_dev',
                        'air-balloon_dev',
                    ) . "\n"
            . "\n"
            . '    </ul>' . "\n"
            . '  </div>' . "\n"
            . "\n"
            . "\n"
            . '  </ul>' . "\n"
            . '</div>' . "\n",
            $responseMessage,
        );
        $this->dummyDbi->assertAllQueriesConsumed();
    }
}
