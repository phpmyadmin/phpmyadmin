<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Server;

use PhpMyAdmin\Config;
use PhpMyAdmin\Providers\ServerVariables\ServerVariablesProvider;
use PhpMyAdmin\Providers\ServerVariables\VoidProvider as ServerVariablesVoidProvider;
use PhpMyAdmin\Server\VariableFormatter;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionProperty;

#[CoversClass(VariableFormatter::class)]
final class VariableFormatterTest extends AbstractTestCase
{
    protected function tearDown(): void
    {
        (new ReflectionProperty(ServerVariablesProvider::class, 'instance'))->setValue(null, null);

        parent::tearDown();
    }

    public function testFormat(): void
    {
        $voidProviderMock = $this->createMock(ServerVariablesVoidProvider::class);
        $voidProviderMock
            ->expects(self::exactly(2))
            ->method('getVariableType')
            ->willReturn('byte', 'string');
        (new ReflectionProperty(ServerVariablesProvider::class, 'instance'))->setValue(null, $voidProviderMock);

        $formatter = new VariableFormatter(new Template(new Config()));

        //name is_numeric and the value type is byte
        self::assertSame(['<abbr title="3">3 B</abbr>', true], $formatter->format('byte_variable', '3'));

        //name is_numeric and the value type is not byte
        self::assertSame(['3', false], $formatter->format('not_a_byte_variable', '3'));

        //value is not a number
        self::assertSame(['value', false], $formatter->format('not_a_byte_variable', 'value'));
    }

    public function testFormatMariaDbMySqlKbs(): void
    {
        if (! ServerVariablesProvider::mariaDbMySqlKbsExists()) {
            self::markTestSkipped('MariaDbMySqlKbs is missing');
        }

        (new ReflectionProperty(ServerVariablesProvider::class, 'instance'))->setValue(null, null);

        $formatter = new VariableFormatter(new Template(new Config()));

        //name is_numeric and the value type is byte
        self::assertSame(['<abbr title="3">3 B</abbr>', true], $formatter->format('wsrep_replicated_bytes', '3'));

        //name is_numeric and the value type is not byte
        self::assertSame(['3', false], $formatter->format('wsrep_thread_count', '3'));

        //value is not a number
        self::assertSame(['value', false], $formatter->format('wsrep_thread_count', 'value'));
    }

    public function testFormatVoidProvider(): void
    {
        (new ReflectionProperty(ServerVariablesProvider::class, 'instance'))
            ->setValue(null, new ServerVariablesVoidProvider());

        $formatter = new VariableFormatter(new Template(new Config()));

        //name is_numeric and the value type is byte
        self::assertSame(['3', false], $formatter->format('wsrep_replicated_bytes', '3'));
    }
}
