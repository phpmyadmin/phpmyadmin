<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Server\Status;

use PhpMyAdmin\Server\Status\Processes;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionMethod;

#[CoversClass(Processes::class)]
final class ProcessesTest extends AbstractTestCase
{
    /**
     * `SHOW PROCESSLIST`'s `Host` column always includes the connecting
     * port, but `mysql.user`/`mysql.global_priv`'s `Host` never does — the
     * raw value must stay in `host` (displayed as-is) while `host_without_port`
     * carries the value actually usable to look up the owning grant.
     *
     * DbiDummy::executeQuery() always returns null (no prepared-statement
     * simulation support), which conveniently exercises exactly the
     * real-world fallback this code takes when the current user lacks
     * SELECT on `mysql.user` (or the server has no such table, e.g. some
     * managed MySQL offerings): `host_without_port` stays the plain stripped
     * connecting host instead of a resolved grant pattern, but the page
     * keeps working rather than erroring out.
     */
    public function testGetListStripsPortForThePrivilegeLink(): void
    {
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addResult(
            'SHOW PROCESSLIST',
            [['1', 'root', '10.0.0.5:41414', 'db', 'Query', '0', '', 'SELECT 1']],
            ['Id', 'User', 'Host', 'db', 'Command', 'Time', 'State', 'Info'],
        );
        $dbi = $this->createDatabaseInterface($dbiDummy);
        $processes = new Processes($dbi);

        $list = $processes->getList(false, false, '', '');
        $rows = $list['rows'];
        self::assertIsArray($rows);
        $row = $rows[0];
        self::assertIsArray($row);

        self::assertSame('10.0.0.5:41414', $row['host']);
        self::assertSame('10.0.0.5', $row['host_without_port']);
    }

    public function testStripPortWithIpv4AndPort(): void
    {
        self::assertSame(
            '10.0.0.5',
            (new ReflectionMethod(Processes::class, 'stripPort'))->invoke(null, '10.0.0.5:41414'),
        );
    }

    public function testStripPortWithLocalhostAndNoPort(): void
    {
        self::assertSame(
            'localhost',
            (new ReflectionMethod(Processes::class, 'stripPort'))->invoke(null, 'localhost'),
        );
    }

    public function testStripPortWithBracketedIpv6AndPort(): void
    {
        self::assertSame(
            '::1',
            (new ReflectionMethod(Processes::class, 'stripPort'))->invoke(null, '[::1]:41414'),
        );
    }

    /**
     * A bare IPv6 address (no port) has multiple colons of its own — the
     * last one is not a port separator, and must not be truncated.
     */
    public function testStripPortWithBareIpv6DoesNotTruncate(): void
    {
        $method = new ReflectionMethod(Processes::class, 'stripPort');

        self::assertSame('2001:db8::5', $method->invoke(null, '2001:db8::5'));
        self::assertSame('::1', $method->invoke(null, '::1'));
    }

    public function testStripPortWithHostnameAndPort(): void
    {
        self::assertSame(
            'db.example.com',
            (new ReflectionMethod(Processes::class, 'stripPort'))->invoke(null, 'db.example.com:3306'),
        );
    }

    public function testHostMatchesPatternWithPercentWildcard(): void
    {
        $method = new ReflectionMethod(Processes::class, 'hostMatchesPattern');

        self::assertTrue($method->invoke(null, '10.0.0.5', '%'));
        self::assertTrue($method->invoke(null, '10.0.0.5', '10.0.0.%'));
        self::assertFalse($method->invoke(null, '10.0.1.5', '10.0.0.%'));
    }

    public function testHostMatchesPatternWithUnderscoreWildcard(): void
    {
        $method = new ReflectionMethod(Processes::class, 'hostMatchesPattern');

        self::assertTrue($method->invoke(null, '10.0.0.5', '10.0.0._'));
        self::assertFalse($method->invoke(null, '10.0.0.55', '10.0.0._'));
    }

    public function testHostMatchesPatternIsCaseInsensitive(): void
    {
        self::assertTrue(
            (new ReflectionMethod(Processes::class, 'hostMatchesPattern'))
                ->invoke(null, 'DB.EXAMPLE.COM', 'db.example.com'),
        );
    }

    public function testHostMatchesPatternTreatsDotsInAddressAsLiteral(): void
    {
        // A literal dot in the pattern must not behave as "any character" —
        // only "%"/"_" are wildcards, unlike a raw regex.
        self::assertFalse(
            (new ReflectionMethod(Processes::class, 'hostMatchesPattern'))
                ->invoke(null, '10x0x0x5', '10.0.0.5'),
        );
    }

    /**
     * An exact, non-wildcard grant always wins over a wildcard one for the
     * same user — it is the least ambiguous choice when both exist.
     */
    public function testFindMatchingHostPrefersExactOverWildcard(): void
    {
        $match = (new ReflectionMethod(Processes::class, 'findMatchingHost'))
            ->invoke(null, '10.0.0.5', ['%', '10.0.0.5']);

        self::assertSame('10.0.0.5', $match);
    }

    public function testFindMatchingHostFallsBackToWildcard(): void
    {
        $match = (new ReflectionMethod(Processes::class, 'findMatchingHost'))
            ->invoke(null, '10.0.0.5', ['192.168.%', '10.0.0.%']);

        self::assertSame('10.0.0.%', $match);
    }

    public function testFindMatchingHostReturnsNullWhenNothingMatches(): void
    {
        $match = (new ReflectionMethod(Processes::class, 'findMatchingHost'))
            ->invoke(null, '10.0.0.5', ['192.168.%']);

        self::assertNull($match);
    }
}
