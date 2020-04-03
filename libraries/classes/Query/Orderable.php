<?php

declare(strict_types=1);

namespace PhpMyAdmin\Query;

use PhpMyAdmin\Util;

/**
 * Represents ORDER BY clauses
 */
trait Orderable
{
    /**
     * @var array<int,string>
     */
    private $constraintsOrderExpressions = [];

    /**
     * Add a normal ORDER BY
     * @param string $key The key name
     */
    public function orderBy(string $key): self
    {
        $this->constraintsOrderExpressions[] = Util::backquote($key);
        return $this;
    }

    public function hasOrderExpressions(): bool
    {
        return count($this->constraintsOrderExpressions) > 0;
    }

    public function buildOrder(): string
    {
        return implode(',', $this->constraintsOrderExpressions);
    }
}
