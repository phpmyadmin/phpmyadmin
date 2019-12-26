<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds AdvisorControllerTest
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server\Status;

use PhpMyAdmin\Advisor;
use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\Server\Status\AdvisorController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Response;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Template;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * Tests for AdvisorController class
 *
 * @package PhpMyAdmin-test
 */
class AdvisorControllerTest extends TestCase
{
    /**
     * @return void
     */
    protected function setUp(): void
    {
        $GLOBALS['PMA_Config'] = new Config();
        $GLOBALS['PMA_Config']->enableBc();

        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['cfg']['Server']['host'] = 'localhost';

        require_once ROOT_PATH . 'libraries/replication.inc.php';

        //this data is needed when PhpMyAdmin\Server\Status\Data constructs
        $serverStatus = [
            'Aborted_clients' => '0',
            'Aborted_connects' => '0',
            'Com_delete_multi' => '0',
            'Com_create_function' => '0',
            'Com_empty_query' => '0',
        ];

        $serverVariables = [
            'auto_increment_increment' => '1',
            'auto_increment_offset' => '1',
            'automatic_sp_privileges' => 'ON',
            'back_log' => '50',
            'big_tables' => 'OFF',
            'version' => '8.0.2',
        ];

        $fetchResult = [
            [
                'SHOW GLOBAL STATUS',
                0,
                1,
                DatabaseInterface::CONNECT_USER,
                0,
                $serverStatus,
            ],
            [
                'SHOW GLOBAL VARIABLES',
                0,
                1,
                DatabaseInterface::CONNECT_USER,
                0,
                $serverVariables,
            ],
            [
                "SELECT concat('Com_', variable_name), variable_value "
                . "FROM data_dictionary.GLOBAL_STATEMENTS",
                0,
                1,
                DatabaseInterface::CONNECT_USER,
                0,
                $serverStatus,
            ],
        ];

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->any())->method('fetchResult')
            ->will($this->returnValueMap($fetchResult));

        $GLOBALS['dbi'] = $dbi;
    }

    /**
     * @return void
     */
    public function testIndex(): void
    {
        $controller = new AdvisorController(
            Response::getInstance(),
            $GLOBALS['dbi'],
            new Template(),
            new Data(),
            new Advisor($GLOBALS['dbi'], new ExpressionLanguage())
        );

        $html = $controller->index();

        $this->assertStringContainsString(
            '<a href="#openAdvisorInstructions">',
            $html
        );
        $this->assertStringContainsString(
            '<div id="advisorInstructionsDialog"',
            $html
        );
        $this->assertStringContainsString(
            'The Advisor system can provide recommendations',
            $html
        );
        $this->assertStringContainsString(
            'Do note however that this system provides recommendations',
            $html
        );
        $this->assertStringContainsString(
            '<div id="advisorData" class="hide">',
            $html
        );
        $this->assertStringContainsString(
            htmlspecialchars(json_encode('parse')),
            $html
        );
        $this->assertStringContainsString(
            htmlspecialchars(json_encode('errors')),
            $html
        );
        $this->assertStringContainsString(
            htmlspecialchars(json_encode('run')),
            $html
        );
    }
}
