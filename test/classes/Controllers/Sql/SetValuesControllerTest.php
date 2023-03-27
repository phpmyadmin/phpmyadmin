<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Sql;

use PhpMyAdmin\Controllers\Sql\SetValuesController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;

/** @covers \PhpMyAdmin\Controllers\Sql\SetValuesController */
class SetValuesControllerTest extends AbstractTestCase
{
    protected DatabaseInterface $dbi;

    protected DbiDummy $dummyDbi;

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

        parent::loadResponseIntoContainerBuilder();
    }

    public function testError(): void
    {
        $this->dummyDbi->addResult('SHOW COLUMNS FROM `cvv`.`enums` LIKE \'set\'', false);

        $request = $this->createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([
            ['ajax_request', null, true],
            ['db', null, 'cvv'],
            ['table', null, 'enums'],
            ['column', null, 'set'],
            ['curr_value', null, 'b&c'],
        ]);

        $GLOBALS['db'] = 'cvv';
        $GLOBALS['table'] = 'enums';

        $GLOBALS['containerBuilder']->setParameter('db', $GLOBALS['db']);
        $GLOBALS['containerBuilder']->setParameter('table', $GLOBALS['table']);
        /** @var SetValuesController $sqlController */
        $sqlController = $GLOBALS['containerBuilder']->get(SetValuesController::class);
        $sqlController($request);

        $this->assertResponseWasNotSuccessfull();

        $this->assertSame(
            ['message' => 'Error in processing request'],
            $this->getResponseJsonResult(),
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
            ['Field', 'Type', 'Null', 'Key', 'Default', 'Extra'],
        );

        $request = $this->createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([
            ['ajax_request', null, true],
            ['db', null, 'cvv'],
            ['table', null, 'enums'],
            ['column', null, 'set'],
            ['curr_value', null, 'b&c'],
        ]);

        $GLOBALS['db'] = 'cvv';
        $GLOBALS['table'] = 'enums';

        $GLOBALS['containerBuilder']->setParameter('db', $GLOBALS['db']);
        $GLOBALS['containerBuilder']->setParameter('table', $GLOBALS['table']);
        /** @var SetValuesController $sqlController */
        $sqlController = $GLOBALS['containerBuilder']->get(SetValuesController::class);
        $sqlController($request);

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
            $this->getResponseJsonResult(),
        );
    }
}
