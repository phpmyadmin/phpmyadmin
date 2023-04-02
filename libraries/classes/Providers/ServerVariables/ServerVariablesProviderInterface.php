<?php

declare(strict_types=1);

namespace PhpMyAdmin\Providers\ServerVariables;

interface ServerVariablesProviderInterface
{
    public function getVariableType(string $name): string|null;

    /** @return mixed[] */
    public function getStaticVariables(): array;

    public function getDocLinkByNameMariaDb(string $name): string|null;

    public function getDocLinkByNameMysql(string $name): string|null;
}
