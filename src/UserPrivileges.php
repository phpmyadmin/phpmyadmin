<?php

declare(strict_types=1);

namespace PhpMyAdmin;

final class UserPrivileges
{
    public static bool $database = false;
    public static bool $table = false;
    public static bool $column = false;
    public static bool $routines = false;
    public static bool $isReload = false;
    public static bool $isCreateDatabase = false;
    public static string $databaseToCreate = '';
    /** @var string[]|false */
    public static array|bool $databasesToTest = false;
}
