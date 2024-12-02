<?php

declare(strict_types=1);

namespace PhpMyAdmin\Utils;

enum HttpMethod: string
{
    case Get = 'GET';
    case Post = 'POST';
}
