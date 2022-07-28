<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Sql;

use PhpMyAdmin\Controllers\Sql\SetValuesController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;

/**
 * @covers \PhpMyAdmin\Controllers\Sql\SetValuesController
 */
class SetValuesControllerTest extends AbstractTestCase
{
    /** @var DatabaseInterface */
    protected $dbi;

    /** @var DbiDummy */
    protected $dummyDbi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        $GLOBALS['dbi'] = $this->dbi;
        parent::loadContainerBuilder();
        parent::loadDbiIntoContainerBuilder();
        $GLOBALS['server'] = 1;
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        parent::loadResponseIntoContainerBuilder();
    }

    public function testError(): void
    {
        $this->dummyDbi->addResult('SHOW COLUMNS FROM `cvv`.`enums` LIKE \'set\'', false);

        $_POST = [
            'ajax_request' => true,
            'db' => 'cvv',
            'table' => 'enums',
            'column' => 'set',
            'curr_value' => 'b&c',
        ];
        $GLOBALS['db'] = $_POST['db'];
        $GLOBALS['table'] = $_POST['table'];

        $GLOBALS['containerBuilder']->setParameter('db', $GLOBALS['db']);
        $GLOBALS['containerBuilder']->setParameter('table', $GLOBALS['table']);
        /** @var SetValuesController $sqlController */
        $sqlController = $GLOBALS['containerBuilder']->get(SetValuesController::class);
        $sqlController($this->createStub(ServerRequest::class));

        $this->assertResponseWasNotSuccessfull();

        $this->assertSame(
            ['message' => 'Error in processing request'],
            $this->getResponseJsonResult()
        );
    }

    public function testSuccess(): void
    {
        $this->dummyDbi->addResult(
            'SHOW COLUMNS FROM `cvv`.`enums` LIKE \'set\'',
            [
                [
                    'set',
                    'set(\'<script>alert("ok")</script>\',\'a&b\',\'b&c\',\'vrai&amp\',\'\')',
                    'No',
                    '',
                    'NULL',
                    '',
                ],
            ],
            [
                'Field',
                'Type',
                'Null',
                'Key',
                'Default',
                'Extra',
            ]
        );

        $_POST = [
            'ajax_request' => true,
            'db' => 'cvv',
            'table' => 'enums',
            'column' => 'set',
            'curr_value' => 'b&c',
        ];
        $GLOBALS['db'] = $_POST['db'];
        $GLOBALS['table'] = $_POST['table'];

        $GLOBALS['containerBuilder']->setParameter('db', $GLOBALS['db']);
        $GLOBALS['containerBuilder']->setParameter('table', $GLOBALS['table']);
        /** @var SetValuesController $sqlController */
        $sqlController = $GLOBALS['containerBuilder']->get(SetValuesController::class);
        $sqlController($this->createStub(ServerRequest::class));

        $this->assertResponseWasSuccessfull();

        $this->assertSame(
            [
                'select' => '<select class="resize-vertical" size="5" multiple>' . "\n"
                    . '      <option value="&lt;script&gt;alert(&quot;ok&quot;)&lt;/script&gt;">'
                    . '&lt;script&gt;alert(&quot;ok&quot;)&lt;/script&gt;</option>' . "\n"
                    . '      <option value="a&amp;b">a&amp;b</option>' . "\n"
                    . '      <option value="b&amp;c" selected>b&amp;c</option>' . "\n"
                    . '      <option value="vrai&amp;amp">vrai&amp;amp</option>' . "\n"
                    . '      <option value=""></option>' . "\n"
                    . '  </select>' . "\n",
            ],
            $this->getResponseJsonResult()
        );
    }
}
