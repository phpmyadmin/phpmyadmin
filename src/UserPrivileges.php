<?php

declare(strict_types=1);

namespace PhpMyAdmin;

final class UserPrivileges
{
    /** @param string[]|false $databasesToTest */
    public function __construct(
        public bool $database = false,
        public bool $table = false,
        public bool $column = false,
        public bool $routines = false,
        public bool $isReload = false,
        public bool $isCreateDatabase = false,
        public string $databaseToCreate = '',
        public array|false $databasesToTest = false,
    ) {
    }
}
