<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Routing\Fixtures\Controllers\One\Two;

use PhpMyAdmin\Routing\Route;

#[Route('/route-with-var/{variable}', ['GET', 'POST'])]
final class VariableController
{
}
