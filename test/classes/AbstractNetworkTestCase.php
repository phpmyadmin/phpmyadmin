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
        $settings = new Settings([]);
        $GLOBALS['cfg'] = $settings->asArray();
    }

    /**
     * Creates mock of Response object for header testing
     *
     * @param mixed[]|string|StringContains ...$param parameter for header method
     */
    public function mockResponse(array|string|StringContains ...$param): MockObject
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
                'setMinimalFooter',
                'getHeader',
                'httpResponseCode',
            ])
            ->getMock();

        $mockResponse->expects($this->any())
            ->method('headersSent')
            ->with()
            ->will($this->returnValue(false));

        if ($param !== []) {
            if (is_array($param[0])) {
                if (is_array($param[0][0]) && count($param) === 1) {
                    $param = $param[0];
                    if (is_int(end($param))) {
                        $httpResponseCodeParam = end($param);
                        $param = array_slice($param, 0, -1);

                        $mockResponse->expects($this->once())
                        ->method('httpResponseCode')->with($httpResponseCodeParam);
                    }
                }
            } else {
                $mockResponse->expects($this->once())
                    ->method('header')
                    ->with($param[0]);
            }
        }

        $attrInstance = new ReflectionProperty(ResponseRenderer::class, 'instance');
        $attrInstance->setValue($mockResponse);

        return $mockResponse;
    }

    /**
     * Tear down function for mockResponse method
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $response = new ReflectionProperty(ResponseRenderer::class, 'instance');
        $response->setValue(null);
    }
}
