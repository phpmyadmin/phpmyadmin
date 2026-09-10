<?php

declare(strict_types=1);

namespace PhpMyAdmin;

enum MessageType
{
    case Success;
    case Notice;
    case Error;
}
