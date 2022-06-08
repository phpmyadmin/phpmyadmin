<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Table\ChangeController;
use PhpMyAdmin\FileListing;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\InsertEdit;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Transformations;

/**
 * @covers \PhpMyAdmin\Controllers\Table\ChangeController
 */
class ChangeControllerTest extends AbstractTestCase
{
    public function testChangeController(): void
    {
        $GLOBALS['db'] = 'test_db';
        $GLOBALS['table'] = 'test_table';

        $dummyDbi = $this->createDbiDummy();
        $dummyDbi->addSelectDb('test_db');
        $dummyDbi->addResult('SHOW TABLES LIKE \'test_table\';', [['test_table']]);
        $dummyDbi->addResult(
            'SELECT * FROM `test_db`.`test_table` LIMIT 1;',
            [['1', 'abcd', '2011-01-20 02:00:02']],
            ['id', 'name', 'datetimefield']
        );
        $dummyDbi->addSelectDb('test_db');
        $dbi = $this->createDatabaseInterface($dummyDbi);
        $GLOBALS['dbi'] = $dbi;

        $response = new ResponseRenderer();
        $pageSettings = new PageSettings('Edit');

        $request = $this->createStub(ServerRequest::class);

        $relation = new Relation($dbi);
        $template = new Template();
        (new ChangeController(
            $response,
            $template,
            new InsertEdit($dbi, $relation, new Transformations(), new FileListing(), $template),
            $relation
        ))($request);
        $actual = $response->getHTMLResult();

        $this->assertStringContainsString($pageSettings->getHTML(), $actual);
        $this->assertStringContainsString(
            '<input type="text" name="fields[multi_edit][0][b80bb7740288fda1f201890375a60c8f]" value="NULL"'
            . ' size="4" min="-2147483648" max="2147483647" data-type="INT" class="textfield"'
            . ' onchange="return'
            . ' verificationsAfterFieldChange(\'b80bb7740288fda1f201890375a60c8f\', \'0\',\'int(11)\')"'
            . ' tabindex="1" id="field_1_3"><input type="hidden"'
            . ' name="auto_increment[multi_edit][0][b80bb7740288fda1f201890375a60c8f]" value="1">',
            $actual
        );
        $this->assertStringContainsString(
            '<input type="text" name="fields[multi_edit][0][b068931cc450442b63f5b3d276ea4297]" value="NULL" size="20"'
            . ' data-maxlength="20" data-type="CHAR" class="textfield" onchange="return'
            . ' verificationsAfterFieldChange(\'b068931cc450442b63f5b3d276ea4297\', \'0\',\'varchar(20)\')"'
            . ' tabindex="2" id="field_2_3">',
            $actual
        );
        $this->assertStringContainsString(
            '<input type="text" name="fields[multi_edit][0][a55dbdcc1a45ed90dbee68864d566b99]" value="NULL.000000"'
            . ' size="4" data-type="DATE" class="textfield datetimefield" onchange="return'
            . ' verificationsAfterFieldChange(\'a55dbdcc1a45ed90dbee68864d566b99\', \'0\',\'datetime\')"'
            . ' tabindex="3" id="field_3_3"><input type="hidden"'
            . ' name="fields_type[multi_edit][0][a55dbdcc1a45ed90dbee68864d566b99]" value="datetime">',
            $actual
        );
    }
}
