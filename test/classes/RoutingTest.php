<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use FastRoute\Dispatcher;
use PhpMyAdmin\Controllers\HomeController;
use PhpMyAdmin\Routing;

class RoutingTest extends AbstractTestCase
{
    /**
     * Test for Routing::getDispatcher
     */
    public function testGetDispatcher(): void
    {
        $dispatcher = Routing::getDispatcher();
        $this->assertInstanceOf(Dispatcher::class, $dispatcher);
        $this->assertSame([
            Dispatcher::FOUND,
            [
                HomeController::class,
                'index',
            ],
            [],
        ], $dispatcher->dispatch('GET', '/'));
    }

    /**
     * Test for Routing::getCurrentRoute
     */
    public function testGetCurrentRouteNoParams(): void
    {
        $this->assertSame('/', Routing::getCurrentRoute());
    }

    /**
     * Test for Routing::getCurrentRoute
     */
    public function testGetCurrentRouteGet(): void
    {
        $_GET['route'] = '/test';
        $this->assertSame('/test', Routing::getCurrentRoute());
    }

    /**
     * Test for Routing::getCurrentRoute
     */
    public function testGetCurrentRoutePost(): void
    {
        unset($_GET['route']);
        $_POST['route'] = '/testpost';
        $this->assertSame('/testpost', Routing::getCurrentRoute());
    }

    /**
     * Test for Routing::getCurrentRoute
     */
    public function testGetCurrentRouteGetIsOverPost(): void
    {
        $_GET['route'] = '/testget';
        $_POST['route'] = '/testpost';
        $this->assertSame('/testget', Routing::getCurrentRoute());
    }

    /**
     * Test for Routing::getCurrentRoute
     */
    public function testGetCurrentRouteRedirectDbStructure(): void
    {
        unset($_POST['route']);
        unset($_GET['route']);
        $_GET['db'] = 'testDB';
        $this->assertSame('/database/structure', Routing::getCurrentRoute());
    }

    /**
     * Test for Routing::getCurrentRoute
     */
    public function testGetCurrentRouteRedirectSql(): void
    {
        $_GET['db'] = 'testDB';
        $_GET['table'] = 'tableTest';
        $this->assertSame('/sql', Routing::getCurrentRoute());
    }
}
