<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\TwoFactor;

use PhpMyAdmin\Plugins\TwoFactor\Key;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\TwoFactor;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Key::class)]
class KeyTest extends AbstractTestCase
{
    public function testGetRegistrations(): void
    {
        $twoFactor = self::createStub(TwoFactor::class);
        $twoFactor->config = [
            'backend' => 'key',
            'settings' => [
                'registrations' => [
                    [
                        'keyHandle' => 'keyHandle',
                        'publicKey' => 'publicKey',
                        'certificate' => 'certificate',
                        'counter' => -1,
                    ],
                ],
            ],
        ];
        $key = new Key($twoFactor);
        $actual = $key->getRegistrations();
        $expected = [
            (object) [
                'keyHandle' => 'keyHandle',
                'publicKey' => 'publicKey',
                'certificate' => 'certificate',
                'counter' => -1,
                'index' => 0,
            ],
        ];
        self::assertEquals($expected, $actual);
    }
}
