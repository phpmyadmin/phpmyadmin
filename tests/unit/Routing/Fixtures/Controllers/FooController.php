<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Routing\Fixtures\Controllers;

use PhpMyAdmin\Routing\Route;

#[Route('/foo[/route]', ['GET', 'POST'])]
final class FooController
{
}
