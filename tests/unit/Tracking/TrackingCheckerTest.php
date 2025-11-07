<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Tracking;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tracking\TrackedTable;
use PhpMyAdmin\Tracking\Tracker;
use PhpMyAdmin\Tracking\TrackingChecker;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionProperty;

#[CoversClass(TrackingChecker::class)]
#[CoversClass(TrackedTable::class)]
final class TrackingCheckerTest extends AbstractTestCase
{
    private TrackingChecker $trackingChecker;

    /**
     * Setup function for test cases
     */
    protected function setUp(): void
    {
        parent::setUp();

        $dbi = $this->createDatabaseInterface();
        DatabaseInterface::$instance = $dbi;

        $relationParameters = RelationParameters::fromArray([
            RelationParameters::DATABASE => 'pmadb',
            RelationParameters::TRACKING => 'tracking',
            RelationParameters::TRACKING_WORK => true,
        ]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);

        $this->trackingChecker = new TrackingChecker(
            $dbi,
            new Relation($dbi),
        );
    }

    public function testGetTrackedTables(): void
    {
        self::assertFalse(Tracker::isEnabled());

        $actual = $this->trackingChecker->getTrackedTables('dummyDb');
        self::assertSame([], $actual);

        Tracker::enable();

        $expectation = [
            0 => new TrackedTable(TableName::from('0'), true),
            'actor' => new TrackedTable(TableName::from('actor'), false),
        ];

        $actual = $this->trackingChecker->getTrackedTables('dummyDb');

        self::assertEquals($expectation, $actual);
    }

    public function testGetUntrackedTableNames(): void
    {
        self::assertFalse(Tracker::isEnabled());

        $expectation = ['0', 'actor', 'untrackedTable'];
        $actual = $this->trackingChecker->getUntrackedTableNames('dummyDb');
        self::assertSame($expectation, $actual);

        Tracker::enable();

        $expectation = ['untrackedTable'];
        $actual = $this->trackingChecker->getUntrackedTableNames('dummyDb');
        self::assertSame($expectation, $actual);
    }
}
