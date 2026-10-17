<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Events;

use PhpMyAdmin\Config;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Events\Events;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Events::class)]
class EventsTest extends AbstractTestCase
{
    private Events $events;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->setLanguage();

        DatabaseInterface::$instance = $this->createDatabaseInterface();
        Current::$database = 'db';
        Current::$table = 'table';
        $config = Config::getInstance();
        $config->selectedServer['DisableIS'] = false;

        $this->events = new Events(DatabaseInterface::getInstance(), $config);
    }

    /**
     * Test for getDataFromRequest
     *
     * @param array<string, string> $in  Input
     * @param array<string, string> $out Expected output
     */
    #[DataProvider('providerGetDataFromRequest')]
    public function testGetDataFromRequestEmpty(array $in, array $out): void
    {
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/')
            ->withParsedBody($in);

        self::assertEquals($out, $this->events->getDataFromRequest($request));
    }

    /**
     * Data provider for testGetDataFromRequestEmpty
     *
     * @return array<array{array<string, string>, array<string, string>}>
     */
    public static function providerGetDataFromRequest(): array
    {
        return [
            [
                [
                    'item_name' => '',
                    'item_type' => '',
                    'item_original_name' => '',
                    'item_status' => '',
                    'item_execute_at' => '',
                    'item_interval_value' => '',
                    'item_interval_field' => '',
                    'item_starts' => '',
                    'item_ends' => '',
                    'item_definition' => '',
                    'item_preserve' => '',
                    'item_comment' => '',
                    'item_definer' => '',
                ],
                [
                    'item_name' => '',
                    'item_type' => 'ONE TIME',
                    'item_type_toggle' => 'RECURRING',
                    'item_original_name' => '',
                    'item_status' => '',
                    'item_execute_at' => '',
                    'item_interval_value' => '',
                    'item_interval_field' => '',
                    'item_starts' => '',
                    'item_ends' => '',
                    'item_definition' => '',
                    'item_preserve' => '',
                    'item_comment' => '',
                    'item_definer' => '',
                ],
            ],
            [
                [
                    'item_name' => 'foo',
                    'item_type' => 'RECURRING',
                    'item_original_name' => 'foo',
                    'item_status' => 'foo',
                    'item_execute_at' => 'foo',
                    'item_interval_value' => 'foo',
                    'item_interval_field' => 'foo',
                    'item_starts' => 'foo',
                    'item_ends' => 'foo',
                    'item_definition' => 'foo',
                    'item_preserve' => 'foo',
                    'item_comment' => 'foo',
                    'item_definer' => 'foo',
                ],
                [
                    'item_name' => 'foo',
                    'item_type' => 'RECURRING',
                    'item_type_toggle' => 'ONE TIME',
                    'item_original_name' => 'foo',
                    'item_status' => 'foo',
                    'item_execute_at' => 'foo',
                    'item_interval_value' => 'foo',
                    'item_interval_field' => 'foo',
                    'item_starts' => 'foo',
                    'item_ends' => 'foo',
                    'item_definition' => 'foo',
                    'item_preserve' => 'foo',
                    'item_comment' => 'foo',
                    'item_definer' => 'foo',
                ],
            ],
        ];
    }

    /**
     * Test for getQueryFromRequest
     *
     * @param array<string, string> $request Request
     * @param string                $query   Query
     * @param int                   $numErr  Error number
     */
    #[DataProvider('providerGetQueryFromRequest')]
    public function testGetQueryFromRequest(array $request, string $query, int $numErr, string $error): void
    {
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/')
            ->withParsedBody($request);

        $dbi = $this->createStub(DatabaseInterface::class);
        DatabaseInterface::$instance = $dbi;

        $actual = $this->events->getQueryFromRequest($request);
        if ($error !== '') {
            self::assertNotNull(Current::$message);
            self::assertSame($error, Current::$message->getMessage());
        } else {
            self::assertNull(Current::$message);
        }

        self::assertSame($query, $actual);
        self::assertSame($numErr, $this->events->getErrorCount());
    }

    /**
     * Data provider for testGetQueryFromRequest
     *
     * @return iterable<string, array{array<string, string>, string, int, string}>
     */
    public static function providerGetQueryFromRequest(): iterable
    {
        // Testing success
        yield 'simple once-off event' => [
            [
                'item_name' => 's o m e e v e n t\\',
                'item_type' => 'ONE TIME',
                'item_execute_at' => '2050-01-01 00:00:00',
                'item_definition' => 'SET @A=0;',
            ],
            'CREATE EVENT `s o m e e v e n t\` ON SCHEDULE AT \'2050-01-01 ' .
            '00:00:00\' ON COMPLETION NOT PRESERVE DO SET @A=0;',
            0,
            '',
        ];

        yield 'full once-off event' => [
            [
                'item_name' => 'evn',
                'item_definer' => 'me@home',
                'item_type' => 'ONE TIME',
                'item_execute_at' => '2050-01-01 00:00:00',
                'item_preserve' => 'ON',
                'item_status' => 'ENABLED',
                'item_definition' => 'SET @A=0;',
            ],
            'CREATE DEFINER=`me`@`home` EVENT `evn` ON SCHEDULE AT ' .
            '\'2050-01-01 00:00:00\' ON COMPLETION PRESERVE ENABLE DO SET @A=0;',
            0,
            '',
        ];

        yield 'simple recurring event' => [
            [
                'item_name' => 'rec_``evn',
                'item_type' => 'RECURRING',
                'item_interval_value' => '365',
                'item_interval_field' => 'DAY',
                'item_status' => 'DISABLED',
                'item_definition' => 'SET @A=0;',
            ],
            'CREATE EVENT `rec_````evn` ON SCHEDULE EVERY 365 DAY ON ' .
            'COMPLETION NOT PRESERVE DISABLE DO SET @A=0;',
            0,
            '',
        ];

        yield 'full recurring event' => [
            [
                'item_name' => 'rec_evn2',
                'item_definer' => 'evil``user><\\@work\\',
                'item_type' => 'RECURRING',
                'item_interval_value' => '365',
                'item_interval_field' => 'DAY',
                'item_starts' => '1900-01-01',
                'item_ends' => '2050-01-01',
                'item_preserve' => 'ON',
                'item_status' => 'SLAVESIDE_DISABLED',
                'item_definition' => 'SET @A=0;',
            ],
            'CREATE DEFINER=`evil````user><\`@`work\` EVENT `rec_evn2` ON ' .
            'SCHEDULE EVERY 365 DAY STARTS \'1900-01-01\' ENDS \'2050-01-01\' ' .
            'ON COMPLETION PRESERVE DISABLE ON SLAVE DO SET @A=0;',
            0,
            '',
        ];

        // Testing failures
        yield 'empty request' => [
            [],
            '',
            3,
            '<b>One or more errors have occurred while processing your request:</b> <ul> ' .
            '<li>You must provide an event name!</li> ' .
            '<li>You must provide a valid type for the event.</li> ' .
            '<li>You must provide an event definition.</li> </ul>',
        ];

        yield 'invalid definer and no execution time' => [
            [
                'item_name' => 's o m e e v e n t\\',
                'item_definer' => 'someuser',
                'item_type' => 'ONE TIME',
                'item_execute_at' => '',
                'item_definition' => 'SET @A=0;',
            ],
            '',
            2,
            '<b>One or more errors have occurred while processing your request:</b> <ul> ' .
            '<li>The definer must be in the "username@hostname" format!</li> ' .
            '<li>You must provide a valid execution time for the event.</li> </ul>',
        ];

        yield 'no interval value' => [
            [
                'item_name' => 'rec_``evn',
                'item_type' => 'RECURRING',
                'item_interval_value' => '',
                'item_interval_field' => 'DAY',
                'item_status' => 'DISABLED',
                'item_definition' => 'SET @A=0;',
            ],
            '',
            1,
            '<b>One or more errors have occurred while processing your request:</b> <ul> ' .
            '<li>You must provide a valid interval value for the event.</li> </ul>',
        ];

        yield 'invalid interval field' => [
            [
                'item_name' => 'rec_``evn',
                'item_type' => 'RECURRING',
                'item_interval_value' => '365',
                'item_interval_field' => 'CENTURIES',
                'item_status' => 'DISABLED',
                'item_definition' => 'SET @A=0;',
            ],
            '',
            1,
            '<b>One or more errors have occurred while processing your request:</b> <ul> ' .
            '<li>You must provide a valid interval value for the event.</li> </ul>',
        ];
    }

    #[DataProvider('eventSchedulerStatusProvider')]
    public function testGetEventSchedulerStatus(string $expected, string $variableValue): void
    {
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->removeDefaultResults();
        $dbiDummy->addResult(
            "SHOW GLOBAL VARIABLES LIKE 'event_scheduler'",
            [['event_scheduler', $variableValue]],
            ['Variable_name', 'Value'],
        );

        $config = new Config();
        $events = new Events($this->createDatabaseInterface($dbiDummy, $config), $config);

        self::assertSame($expected, $events->getEventSchedulerStatus());
        $dbiDummy->assertAllQueriesConsumed();
    }

    /** @return iterable<int, array{string, string}> */
    public static function eventSchedulerStatusProvider(): iterable
    {
        yield ['ON', 'ON'];
        yield ['OFF', 'OFF'];
        yield ['DISABLED', 'DISABLED'];
        yield ['ON', 'On'];
        yield ['OFF', 'Off'];
        yield ['DISABLED', 'Disabled'];
        yield ['ON', '1'];
        yield ['OFF', '0'];
        yield ['OFF', 'Unknown'];
    }
}
