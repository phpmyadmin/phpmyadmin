<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Tracking;

use PhpMyAdmin\Cache;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Dbal\TableName;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tracking\TrackedTable;
use PhpMyAdmin\Tracking\Tracker;
use PhpMyAdmin\Tracking\TrackingChecker;
use ReflectionClass;

/** @covers \PhpMyAdmin\Tracking\TrackingChecker */
class TrackingCheckerTest extends AbstractTestCase
{
    private TrackingChecker $trackingChecker;

    /**
     * Setup function for test cases
     */
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['dbi'] = $this->createDatabaseInterface();

        $relationParameters = RelationParameters::fromArray([
            'db' => 'pmadb',
            'tracking' => 'tracking',
            'trackingwork' => true,
        ]);
        (new ReflectionClass(Relation::class))->getProperty('cache')->setValue(
            [$GLOBALS['server'] => $relationParameters],
        );

        $this->trackingChecker = new TrackingChecker(
            $GLOBALS['dbi'],
            new Relation($GLOBALS['dbi']),
        );
    }

    public function testGetTrackedTables(): void
    {
        $this->assertFalse(
            Cache::has(Tracker::TRACKER_ENABLED_CACHE_KEY),
        );

        $actual = $this->trackingChecker->getTrackedTables('dummyDb');
        $this->assertEquals([], $actual);

        Tracker::enable();

        $expectation = [
            0 => new TrackedTable(TableName::fromValue('0'), true),
            'actor' => new TrackedTable(TableName::fromValue('actor'), false),
        ];

        $actual = $this->trackingChecker->getTrackedTables('dummyDb');

        $this->assertEquals($expectation, $actual);
    }

    public function testGetUntrackedTableNames(): void
    {
        $this->assertFalse(
            Cache::has(Tracker::TRACKER_ENABLED_CACHE_KEY),
        );

        $expectation = ['0', 'actor', 'untrackedTable'];
        $actual = $this->trackingChecker->getUntrackedTableNames('dummyDb');
        $this->assertEquals($expectation, $actual);

        Tracker::enable();

        $expectation = ['untrackedTable'];
        $actual = $this->trackingChecker->getUntrackedTableNames('dummyDb');
        $this->assertEquals($expectation, $actual);
    }
}
