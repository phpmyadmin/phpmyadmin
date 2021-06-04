<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers;

use PhpMyAdmin\Controllers\NormalizationController;
use PhpMyAdmin\Tests\AbstractTestCase;
use function json_encode;

class NormalizationControllerTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        parent::loadDefaultConfig();
        parent::defineVersionConstants();
        parent::setLanguage();
        parent::setTheme();
        parent::setGlobalDbi();
        parent::loadContainerBuilder();
        parent::loadDbiIntoContainerBuilder();
        $GLOBALS['server'] = 1;
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        parent::loadResponseIntoContainerBuilder();
        $GLOBALS['db'] = 'my_db';
        $GLOBALS['table'] = 'test_tbl';
    }

    public function testGetNewTables3NF(): void
    {
        global $containerBuilder;

        $_POST['getNewTables3NF'] = 1;
        $_POST['tables'] = json_encode([
            'test_tbl' => [
                'event',
                'event',
                'event',
                'event',
                'NameOfVenue',
                'event',
                'period',
                'event',
                'event',
            ],
        ]);
        $_POST['pd'] = json_encode([
            '' => [],
            'event' => [
                'TypeOfEvent',
                'period',
                'Start_time',
                'NameOfVenue',
                'LocationOfVenue',
            ],
            'NameOfVenue' => ['DateOfEvent'],
            'period' => ['NumberOfGuests'],
        ]);

        $GLOBALS['goto'] = 'index.php?route=/sql';
        $containerBuilder->setParameter('db', $GLOBALS['db']);
        $containerBuilder->setParameter('table', $GLOBALS['table']);
        /** @var NormalizationController $normalizationController */
        $normalizationController = $containerBuilder->get(NormalizationController::class);
        $normalizationController->index();
        $this->getResponseJsonResult();// Will echo the contents
        $data = (string) json_encode(
            [
                'html' => '<p><b>In order to put the original table \'test_tbl\' into '
                . 'Third normal form we need to create the following tables:</b>'
                . '</p><p><input type="text" name="test_tbl" value="test_tbl">'
                . '( <u>event</u>, TypeOfEvent, period, Start_time, NameOfVenue, LocationOfVenue )'
                . '<p><input type="text" name="table2" value="table2">'
                . '( <u>NameOfVenue</u>, DateOfEvent )<p><input type="text" name="table3" value="table3">'
                . '( <u>period</u>, NumberOfGuests )',
                'newTables' => [
                    'test_tbl' => [
                        'test_tbl' => [
                            'pk' => 'event',
                            'nonpk' => 'TypeOfEvent, period, Start_time, NameOfVenue, LocationOfVenue',
                        ],
                        'table2' => [
                            'pk' => 'NameOfVenue',
                            'nonpk' => 'DateOfEvent',
                        ],
                        'table3' => [
                            'pk' => 'period',
                            'nonpk' => 'NumberOfGuests',
                        ],
                    ],
                ],
                'success' => true,
            ]
        );
        $this->expectOutputString($data);
    }

    public function testGetNewTables2NF(): void
    {
        global $containerBuilder;

        $_POST['getNewTables2NF'] = 1;
        $_POST['pd'] = json_encode([
            'ID, task' => [],
            'task' => ['timestamp'],
        ]);

        $GLOBALS['goto'] = 'index.php?route=/sql';
        $containerBuilder->setParameter('db', $GLOBALS['db']);
        $containerBuilder->setParameter('table', $GLOBALS['table']);
        /** @var NormalizationController $normalizationController */
        $normalizationController = $containerBuilder->get(NormalizationController::class);
        $normalizationController->index();
        $this->expectOutputString(
            '<p><b>In order to put the original table \'test_tbl\' into Second normal'
            . ' form we need to create the following tables:</b></p><p><input type="text" '
            . 'name="ID, task" value="test_tbl">( <u>ID, task</u> )<p><input type="text" name="task"'
            . ' value="table2">( <u>task</u>, timestamp )'
        );
    }

    public function testCreateNewTables2NF(): void
    {
        global $containerBuilder;

        $_POST['createNewTables2NF'] = 1;
        $_POST['pd'] = json_encode([
            'ID, task' => [],
            'task' => ['timestamp'],
        ]);
        $_POST['newTablesName'] = json_encode([
            'ID, task' => 'batch_log2',
            'task' => 'table2',
        ]);

        $GLOBALS['goto'] = 'index.php?route=/sql';
        $containerBuilder->setParameter('db', $GLOBALS['db']);
        $containerBuilder->setParameter('table', $GLOBALS['table']);
        /** @var NormalizationController $normalizationController */
        $normalizationController = $containerBuilder->get(NormalizationController::class);
        $normalizationController->index();
        $this->assertSame(
            $this->getResponseJsonResult(),
            [
                'legendText' => 'End of step',
                'headText' => '<h3>The second step of normalization is complete for table \'test_tbl\'.</h3>',
                'queryError' => false,
                'extra' => '',
            ]
        );
    }

    public function testCreateNewTables3NF(): void
    {
        global $containerBuilder;

        $_POST['createNewTables3NF'] = 1;
        $_POST['newTables'] = json_encode([
            'test_tbl' => [
                'event' => [
                    'pk' => 'eventID',
                    'nonpk' => 'Start_time, DateOfEvent, NumberOfGuests, NameOfVenue, LocationOfVenue',
                ],
                'table2' => [
                    'pk' => 'Start_time',
                    'nonpk' => 'TypeOfEvent, period',
                ],
            ],
        ]);

        $GLOBALS['goto'] = 'index.php?route=/sql';
        $containerBuilder->setParameter('db', $GLOBALS['db']);
        $containerBuilder->setParameter('table', $GLOBALS['table']);
        /** @var NormalizationController $normalizationController */
        $normalizationController = $containerBuilder->get(NormalizationController::class);
        $normalizationController->index();
        $this->assertSame(
            $this->getResponseJsonResult(),
            [
                'legendText' => 'End of step',
                'headText' => '<h3>The third step of normalization is complete.</h3>',
                'queryError' => false,
                'extra' => '',
            ]
        );
    }
}
