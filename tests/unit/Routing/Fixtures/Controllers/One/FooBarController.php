<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Routing\Fixtures\Controllers\One;

use PhpMyAdmin\Routing\Route;

#[Route('[/]', ['GET'])]
#[Route('/another-route', ['GET'])]
final class FooBarController
{
}
