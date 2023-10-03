<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Sql;

use PhpMyAdmin\Controllers\Sql\EnumValuesController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(EnumValuesController::class)]
class EnumValuesControllerTest extends AbstractTestCase
{
    protected DatabaseInterface $dbi;

    protected DbiDummy $dummyDbi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        DatabaseInterface::$instance = $this->dbi;

        parent::loadContainerBuilder();

        parent::loadDbiIntoContainerBuilder();

        $GLOBALS['server'] = 1;
        $GLOBALS['text_dir'] = 'ltr';

        parent::loadResponseIntoContainerBuilder();
    }

    public function testGetEnumValuesError(): void
    {
        $this->dummyDbi->addResult('SHOW COLUMNS FROM `cvv`.`enums` LIKE \'set\'', false);
        $this->dummyDbi->addResult('SHOW INDEXES FROM `cvv`.`enums`', false);

        $GLOBALS['db'] = 'cvv';
        $GLOBALS['table'] = 'enums';

        $request = $this->createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([
            ['db', null, 'cvv'],
            ['table', null, 'enums'],
            ['column', null, 'set'],
            ['curr_value', null, 'b&c'],
        ]);

        $GLOBALS['containerBuilder']->setParameter('db', $GLOBALS['db']);
        $GLOBALS['containerBuilder']->setParameter('table', $GLOBALS['table']);
        /** @var EnumValuesController $sqlController */
        $sqlController = $GLOBALS['containerBuilder']->get(EnumValuesController::class);
        $sqlController($request);

        $this->assertResponseWasNotSuccessfull();

        $this->assertSame(
            ['message' => 'Error in processing request'],
            $this->getResponseJsonResult(),
        );
    }

    public function testGetEnumValuesSuccess(): void
    {
        $this->dummyDbi->addResult(
            'SHOW COLUMNS FROM `cvv`.`enums` LIKE \'set\'',
            [
                [
                    'set',
                    "set('<script>alert(\"ok\")</script>','a&b','b&c','vrai&amp','','漢字','''','\\','\\\"\\\\''')",
                    'No',
                    '',
                    'NULL',
                    '',
                ],
            ],
            ['Field', 'Type', 'Null', 'Key', 'Default', 'Extra'],
        );
        $this->dummyDbi->addResult('SHOW INDEXES FROM `cvv`.`enums`', []);

        $GLOBALS['db'] = 'cvv';
        $GLOBALS['table'] = 'enums';

        $request = $this->createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([
            ['db', null, 'cvv'],
            ['table', null, 'enums'],
            ['column', null, 'set'],
            ['curr_value', null, 'b&c'],
        ]);

        $GLOBALS['containerBuilder']->setParameter('db', $GLOBALS['db']);
        $GLOBALS['containerBuilder']->setParameter('table', $GLOBALS['table']);
        /** @var EnumValuesController $sqlController */
        $sqlController = $GLOBALS['containerBuilder']->get(EnumValuesController::class);
        $sqlController($request);

        $this->assertResponseWasSuccessfull();

        $this->assertSame(
            [
                'dropdown' => '<select>' . "\n"
                    . '      <option value="&lt;script&gt;alert(&quot;ok&quot;)&lt;/script&gt;">'
                    . '&lt;script&gt;alert(&quot;ok&quot;)&lt;/script&gt;</option>' . "\n"
                    . '      <option value="a&amp;b">a&amp;b</option>' . "\n"
                    . '      <option value="b&amp;c" selected>b&amp;c</option>' . "\n"
                    . '      <option value="vrai&amp;amp">vrai&amp;amp</option>' . "\n"
                    . '      <option value=""></option>' . "\n"
                    . '      <option value="漢字">漢字</option>' . "\n"
                    . '      <option value="&#039;">&#039;</option>' . "\n"
                    . '      <option value="\">\</option>' . "\n"
                    . '      <option value="\&quot;\&#039;">\&quot;\&#039;</option>' . "\n"
                    . '  </select>' . "\n",
            ],
            $this->getResponseJsonResult(),
        );
    }
}
