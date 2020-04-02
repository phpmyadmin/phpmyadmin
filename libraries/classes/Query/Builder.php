<?php

declare(strict_types=1);

namespace PhpMyAdmin\Query;

/**
 * Handles making new requests from strings
 */
class Builder
{
    /**
     * Make a new SELECT query
     * @param string[] $selectExpression The columns to select
     */
    public static function select(array $selectExpression = []): SelectQuery
    {
        return new SelectQuery($selectExpression);
    }
}
