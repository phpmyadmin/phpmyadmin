<?php
/**
 * Base class for phpMyAdmin tests
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Response;
use PHPUnit\Framework\Constraint\StringContains;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionProperty;
use function array_slice;
use function call_user_func_array;
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
        $cfg = [];
        require ROOT_PATH . 'libraries/config.default.php';
        $GLOBALS['cfg'] = $cfg;
    }

    /**
     * Creates mock of Response object for header testing
     *
     * @param mixed[]|string|StringContains ...$param parameter for header method
     */
    public function mockResponse(...$param): MockObject
    {
        $mockResponse = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->setMethods([
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

        $mockResponse->expects($this->any())
            ->method('headersSent')
            ->with()
            ->will($this->returnValue(false));

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

                $header_method = $mockResponse->expects($this->exactly(count($param)))
                    ->method('header');

                call_user_func_array([$header_method, 'withConsecutive'], $param);
            } else {
                $mockResponse->expects($this->once())
                    ->method('header')
                    ->with($param[0]);
            }
        }

        $attrInstance = new ReflectionProperty(Response::class, 'instance');
        $attrInstance->setAccessible(true);
        $attrInstance->setValue($mockResponse);

        return $mockResponse;
    }

    /**
     * Tear down function for mockResponse method
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        $response = new ReflectionProperty(Response::class, 'instance');
        $response->setAccessible(true);
        $response->setValue(null);
        $response->setAccessible(false);
    }
}
