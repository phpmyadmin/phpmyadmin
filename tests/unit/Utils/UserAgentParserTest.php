<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Utils;

use PhpMyAdmin\Utils\UserAgentParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(UserAgentParser::class)]
class UserAgentParserTest extends TestCase
{
    /**
    * Tests client parsing code.
    *
    * @param string $agent   User agent string
    * @param string $os      Expected parsed OS (or null if none)
    * @param string $browser Expected parsed browser (or null if none)
    * @param string $version Expected browser version (or null if none)
    */
    #[DataProvider('userAgentProvider')]
    public function testCheckClient(
        string $agent,
        string $os,
        string|null $browser = null,
        string|null $version = null,
    ): void {
        $object = new UserAgentParser($agent);
        self::assertSame($os, $object->getClientPlatform());
        self::assertSame($browser, $object->getUserBrowserAgent());

        if ($version === null) {
            return;
        }

        self::assertSame($version, $object->getUserBrowserVersion());
    }

    /**
     * user Agent Provider
     *
     * @return array<array<string>>
     */
    public static function userAgentProvider(): array
    {
        return [
            ['Opera/9.80 (X11; Linux x86_64; U; pl) Presto/2.7.62 Version/11.00', 'Linux', 'OPERA', '9.80'],
            [
                'Mozilla/5.0 (Macintosh; U; Intel Mac OS X; en-US) AppleWebKit/528.16 OmniWeb/622.8.0.112941',
                'Mac',
                'OMNIWEB',
                '622',
            ],
            ['Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1)', 'Win', 'IE', '8.0'],
            ['Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0)', 'Win', 'IE', '9.0'],
            ['Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.2; Win64; x64; Trident/6.0)', 'Win', 'IE', '10.0'],
            [
                'Mozilla/5.0 (IE 11.0; Windows NT 6.3; Trident/7.0; .NET4.0E; .NET4.0C; rv:11.0) like Gecko',
                'Win',
                'IE',
                '11.0',
            ],
            [
                'Mozilla/5.0 (Windows NT 6.3; WOW64; Trident/7.0; .NET4.0E; '
                . '.NET4.0C; .NET CLR 3.5.30729; .NET CLR 2.0.50727; '
                . '.NET CLR 3.0.30729; InfoPath.3; rv:11.0) like Gecko',
                'Win',
                'IE',
                '11',
            ],
            [
                'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.22 (KHTML, '
                . 'like Gecko) Chrome/25.0.1364.172 Safari/537.22',
                'Win',
                'CHROME',
                '25.0.1364.172',
            ],
            [
                'Mozilla/5.0 (Unknown; U; Unix BSD/SYSV system; C -) '
                . 'AppleWebKit/527+ (KHTML, like Gecko, Safari/419.3) Arora/0.10.2',
                'Unix',
                'SAFARI',
                '5.0.419',
            ],
            ['Mozilla/5.0 (Windows; U; Win95; en-US; rv:1.9b) Gecko/20031208', 'Win', 'GECKO', '1.9'],
            [
                'Mozilla/5.0 (compatible; Konqueror/4.5; NetBSD 5.0.2; X11; amd64; en_US) KHTML/4.5.4 (like Gecko)',
                'Other',
                'KONQUEROR',
            ],
            ['Mozilla/5.0 (X11; Linux x86_64; rv:5.0) Gecko/20100101 Firefox/5.0', 'Linux', 'FIREFOX', '5.0'],
            ['Mozilla/5.0 (X11; Linux x86_64; rv:12.0) Gecko/20100101 Firefox/12.0', 'Linux', 'FIREFOX', '12.0'],
            /** @todo Is this version really expected? */
            [
                'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/535.4+ (KHTML, like G'
                . 'ecko) Version/5.0 Safari/535.4+ SUSE/12.1 (3.2.1) Epiphany/3.2.1',
                'Linux',
                'SAFARI',
                '5.0',
            ],
        ];
    }
}
