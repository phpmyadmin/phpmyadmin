<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Routing\Fixtures\Controllers\One;

use PhpMyAdmin\Routing\Route;

#[Route('/bar-route', ['GET'])]
final class BarController
{
}
