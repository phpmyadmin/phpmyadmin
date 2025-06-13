<?php

declare(strict_types=1);

namespace PhpMyAdmin\Navigation\Nodes;

use function array_intersect_key;
use function array_merge;

readonly class Link
{
    /** @param array<string, mixed> $params */
    public function __construct(public string $title, public string $route, public array $params = [])
    {
    }

    /** @param array<string, mixed> $params */
    public function withDifferentParams(array $params): self
    {
        $params = array_merge($this->params, array_intersect_key($params, $this->params));

        return new self($this->title, $this->route, $params);
    }
}
