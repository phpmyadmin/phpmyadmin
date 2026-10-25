<?php
/**
 * Base class for phpMyAdmin tests
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Config\Settings;
use PhpMyAdmin\ResponseRenderer;
use PHPUnit\Framework\Constraint\StringContains;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionProperty;

use function array_slice;
use function count;
use function end;
use function is_array;
use function is_int;
use function method_exists;

use const PHP_VERSION_ID;

/**
 * Base class for phpMyAdmin tests
 */
abstract class AbstractNetworkTestCase extends AbstractTestCase
{
    /**
     * This method is called before the first test of this test class is run.
     */
    public static function setUpBeforeClass(): void
    {
        global $cfg;

        $settings = new Settings([]);
        $cfg = $settings->toArray();
    }

    /**
     * Creates mock of Response object for header testing
     *
     * @param mixed[]|string|StringContains ...$param parameter for header method
     */
    public function mockResponse(...$param): MockObject
    {
        $mockResponse = $this->getMockBuilder(ResponseRenderer::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'header',
                'headersSent',
                'disable',
                'isAjax',
                'setRequestStatus',
                'addJSON',
                'addHTML',
                'getFooter',
                'getHeader',
                'httpResponseCode',
            ])
            ->getMock();

        $mockResponse->method('headersSent')->willReturn(false);

        if (count($param) > 0) {
            if (is_array($param[0])) {
                if (is_array($param[0][0]) && count($param) === 1) {
                    $param = $param[0];
                    if (is_int(end($param))) {
                        $http_response_code_param = end($param);
                        $param = array_slice($param, 0, -1);

                        $mockResponse->expects($this->once())
                        ->method('httpResponseCode')->with($http_response_code_param);
                    }
                }

                $matcher = self::exactly(count($param));
                $mockResponse->expects($matcher)->method('header')
                    ->willReturnCallback(static function (...$parameters) use ($matcher, $param): void {
                        $numberOfInvocations = method_exists($matcher, 'numberOfInvocations')
                            ? $matcher->numberOfInvocations() : $matcher->getInvocationCount();
                        self::assertSame($param[$numberOfInvocations - 1], $parameters);
                    });
            } else {
                $mockResponse->expects($this->once())
                    ->method('header')
                    ->with($param[0]);
            }
        }

        $attrInstance = new ReflectionProperty(ResponseRenderer::class, 'instance');
        if (PHP_VERSION_ID < 80100) {
            $attrInstance->setAccessible(true);
        }

        $attrInstance->setValue(null, $mockResponse);

        return $mockResponse;
    }

    /**
     * Tear down function for mockResponse method
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        $response = new ReflectionProperty(ResponseRenderer::class, 'instance');
        if (PHP_VERSION_ID < 80100) {
            $response->setAccessible(true);
        }

        $response->setValue(null, null);
        if (PHP_VERSION_ID >= 80100) {
            return;
        }

        $response->setAccessible(false);
    }
}
