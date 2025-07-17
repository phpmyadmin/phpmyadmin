<?php

declare(strict_types=1);

namespace PhpMyAdmin\Routing;

use Attribute;
use Fig\Http\Message\RequestMethodInterface;

/** @immutable */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final readonly class Route
{
    /**
     * @param non-empty-string                       $path
     * @param list<RequestMethodInterface::METHOD_*> $methods
     */
    public function __construct(public string $path, public array $methods)
    {
    }
}
