<?php

declare(strict_types=1);

namespace PhpMyAdmin\Favorites;

use JsonSerializable;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\TableName;

final class RecentFavoriteTable implements JsonSerializable
{
    public function __construct(public readonly DatabaseName $db, public readonly TableName $table)
    {
    }

    /** @param array{db:string, table:string} $array */
    public static function fromArray(array $array): self
    {
        return new self(DatabaseName::from($array['db']), TableName::from($array['table']));
    }

    /** @return array{db:string, table:string} */
    public function toArray(): array
    {
        return ['db' => $this->db->getName(), 'table' => $this->table->getName()];
    }

    public function jsonSerialize(): mixed
    {
        return [
            'db' => $this->db->getName(),
            'table' => $this->table->getName(),
        ];
    }
}
