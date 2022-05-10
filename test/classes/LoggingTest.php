<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Logging;

/**
 * @covers \PhpMyAdmin\Logging
 */
class LoggingTest extends AbstractTestCase
{
    public function testGetLogMessage(): void
    {
        $_SERVER['REMOTE_ADDR'] = '0.0.0.0';
        $log = Logging::getLogMessage('user', 'ok');
        $this->assertEquals('user authenticated: user from 0.0.0.0', $log);
        $log = Logging::getLogMessage('user', 'error');
        $this->assertEquals('user denied: user (error) from 0.0.0.0', $log);
    }
}
