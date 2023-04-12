<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers;

use PhpMyAdmin\Controllers\GisDataEditorController;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;

/** @covers \PhpMyAdmin\Controllers\GisDataEditorController */
class GisDataEditorControllerTest extends AbstractTestCase
{
    private GisDataEditorController|null $controller = null;

    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['server'] = 1;
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';

        $GLOBALS['dbi'] = $this->createDatabaseInterface();

        $this->controller = new GisDataEditorController(new ResponseRenderer(), new Template());
    }

    /**
     * @param mixed[] $gisData
     * @param mixed[] $expected
     *
     * @group gis
     * @dataProvider providerForTestValidateGisData
     */
    public function testValidateGisData(array $gisData, string $type, string|null $value, array $expected): void
    {
        /** @var mixed[] $gisData */
        $gisData = $this->callFunction(
            $this->controller,
            GisDataEditorController::class,
            'validateGisData',
            [
                $gisData,
                $type,
                $value,
            ],
        );
        $this->assertEquals($expected, $gisData);
    }

    /**
     * @return list<list<mixed[]|string|null>>
     * @psalm-return list<array{0:mixed[],1:string,2:string|null,3:mixed[]}>
     */
    public static function providerForTestValidateGisData(): array
    {
        /** @psalm-var list<array{0:mixed[],1:string,2:string|null,3:mixed[]}> */
        return [
            [
                [],
                'GEOMETRY',
                'GEOMETRYCOLLECTION()',
                ['gis_type' => 'GEOMETRYCOLLECTION'],
            ],
            [
                [],
                'GEOMETRY',
                'GEOMETRYCOLLECTION EMPTY',
                ['gis_type' => 'GEOMETRYCOLLECTION'],
            ],
        ];
    }
}
