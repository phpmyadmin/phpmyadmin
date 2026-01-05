<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Database;

use PhpMyAdmin\Config;
use PhpMyAdmin\Current;
use PhpMyAdmin\Database\Events;
use PhpMyAdmin\Dbal\DatabaseInterface;
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

        $this->setGlobalConfig();

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
        unset($_POST);
        foreach ($in as $key => $value) {
            if ($value === '') {
                continue;
            }

            $_POST[$key] = $value;
        }

        self::assertEquals($out, $this->events->getDataFromRequest());
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
    public function testGetQueryFromRequest(array $request, string $query, int $numErr): void
    {
        unset($_POST);
        $_POST = $request;

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        DatabaseInterface::$instance = $dbi;

        self::assertSame($query, $this->events->getQueryFromRequest());
        self::assertSame($numErr, $this->events->getErrorCount());
    }

    /**
     * Data provider for testGetQueryFromRequest
     *
     * @return array<array{array<string, string>, string, int}>
     */
    public static function providerGetQueryFromRequest(): array
    {
        return [
            // Testing success
            [
                [ // simple once-off event
                    'item_name' => 's o m e e v e n t\\',
                    'item_type' => 'ONE TIME',
                    'item_execute_at' => '2050-01-01 00:00:00',
                    'item_definition' => 'SET @A=0;',
                ],
                'CREATE EVENT `s o m e e v e n t\` ON SCHEDULE AT \'2050-01-01 ' .
                '00:00:00\' ON COMPLETION NOT PRESERVE DO SET @A=0;',
                0,
            ],
            [
                [ // full once-off event
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
            ],
            [
                [ // simple recurring event
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
            ],
            [
                [ // full recurring event
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
            ],
            // Testing failures
            [
                [], // empty request
                'CREATE EVENT ON SCHEDULE ON COMPLETION NOT PRESERVE DO ',
                3,
            ],
            [
                [
                    'item_name' => 's o m e e v e n t\\',
                    'item_definer' => 'someuser', // invalid definer format
                    'item_type' => 'ONE TIME',
                    'item_execute_at' => '', // no execution time
                    'item_definition' => 'SET @A=0;',
                ],
                'CREATE EVENT `s o m e e v e n t\` ON SCHEDULE ON COMPLETION NOT PRESERVE DO SET @A=0;',
                2,
            ],
            [
                [
                    'item_name' => 'rec_``evn',
                    'item_type' => 'RECURRING',
                    'item_interval_value' => '', // no interval value
                    'item_interval_field' => 'DAY',
                    'item_status' => 'DISABLED',
                    'item_definition' => 'SET @A=0;',
                ],
                'CREATE EVENT `rec_````evn` ON SCHEDULE ON COMPLETION NOT PRESERVE DISABLE DO SET @A=0;',
                1,
            ],
            [
                [ // simple recurring event
                    'item_name' => 'rec_``evn',
                    'item_type' => 'RECURRING',
                    'item_interval_value' => '365',
                    'item_interval_field' => 'CENTURIES', // invalid interval field
                    'item_status' => 'DISABLED',
                    'item_definition' => 'SET @A=0;',
                ],
                'CREATE EVENT `rec_````evn` ON SCHEDULE ON COMPLETION NOT PRESERVE DISABLE DO SET @A=0;',
                1,
            ],
        ];
    }
}
