<?php

declare(strict_types=1);

namespace PhpMyAdmin\Identifiers;

use Stringable;

/**
 * @see https://dev.mysql.com/doc/refman/en/identifiers.html
 * @see https://mariadb.com/kb/en/identifier-names/
 *
 * @psalm-immutable
 */
interface Identifier extends Stringable
{
    /**
     * @throws InvalidIdentifier
     *
     * @psalm-assert non-empty-string $name
     */
    public static function from(mixed $name): static;

    public static function tryFrom(mixed $name): static|null;

    /** @psalm-return non-empty-string */
    public function getName(): string;

    /** @psalm-return non-empty-string */
    public function __toString(): string;
}
