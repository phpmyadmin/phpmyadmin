<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers;

use PhpMyAdmin\Controllers\SqlController;
use PhpMyAdmin\Tests\AbstractTestCase;

class SqlControllerTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        parent::setGlobalDbi();
        parent::loadContainerBuilder();
        parent::loadDbiIntoContainerBuilder();
        parent::loadDefaultConfig();
        $GLOBALS['server'] = 1;
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        parent::defineVersionConstants();
        parent::loadResponseIntoContainerBuilder();
    }

    public function testGetSetValuesError(): void
    {
        global $containerBuilder, $_POST;

        $this->dummyDbi->addResult(
            'SHOW COLUMNS FROM `cvv`.`enums` LIKE \'set\'',
            false
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

        $containerBuilder->setParameter('db', $GLOBALS['db']);
        $containerBuilder->setParameter('table', $GLOBALS['table']);
        /** @var SqlController $sqlController */
        $sqlController = $containerBuilder->get(SqlController::class);
        $sqlController->getSetValues();

        $this->assertResponseWasNotSuccessfull();

        $this->assertSame(
            ['message' => 'Error in processing request'],
            $this->getResponseJsonResult()
        );
    }

    public function testGetSetValuesSuccess(): void
    {
        global $containerBuilder, $_POST;

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

        $containerBuilder->setParameter('db', $GLOBALS['db']);
        $containerBuilder->setParameter('table', $GLOBALS['table']);
        /** @var SqlController $sqlController */
        $sqlController = $containerBuilder->get(SqlController::class);
        $sqlController->getSetValues();

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

    public function testGetEnumValuesError(): void
    {
        global $containerBuilder, $_POST;

        $this->dummyDbi->addResult(
            'SHOW COLUMNS FROM `cvv`.`enums` LIKE \'set\'',
            false
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

        $containerBuilder->setParameter('db', $GLOBALS['db']);
        $containerBuilder->setParameter('table', $GLOBALS['table']);
        /** @var SqlController $sqlController */
        $sqlController = $containerBuilder->get(SqlController::class);
        $sqlController->getEnumValues();

        $this->assertResponseWasNotSuccessfull();

        $this->assertSame(
            ['message' => 'Error in processing request'],
            $this->getResponseJsonResult()
        );
    }

    public function testGetEnumValuesSuccess(): void
    {
        global $containerBuilder, $_POST;

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

        $containerBuilder->setParameter('db', $GLOBALS['db']);
        $containerBuilder->setParameter('table', $GLOBALS['table']);
        /** @var SqlController $sqlController */
        $sqlController = $containerBuilder->get(SqlController::class);
        $sqlController->getEnumValues();

        $this->assertResponseWasSuccessfull();

        $this->assertSame(
            [
                'dropdown' => '<select>' . "\n"
                . '  <option value="">&nbsp;</option>' . "\n"
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
