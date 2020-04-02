<?php

declare(strict_types=1);

namespace PhpMyAdmin\Query;

use PhpMyAdmin\Util;

/**
 * Represents a select query
 */
final class SelectQuery extends Query
{
    /**
     * @var string[]
     */
    private $expressions;

    /**
     * Make a new SELECT query
     * @param string[] $expressions The columns to select
     */
    public function __construct(array $expressions = [])
    {
        $this->expressions = $expressions;
    }

    public function selectedExpressions(): string
    {
        $expressionParts = [];
        foreach ($this->expressions as $expression) {
            $expressionParts[] = Util::backquote($expression);
        }
        return implode(',', $expressionParts);
    }

    public function toSql(): string
    {
        return 'SELECT ' . $this->selectedExpressions() .
        ' FROM ' . $this->getFromExpression();
    }
}
