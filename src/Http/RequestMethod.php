<?php

declare(strict_types=1);

namespace PhpMyAdmin\Http;

enum RequestMethod: string
{
    case Get = 'GET';
    case Post = 'POST';
}
