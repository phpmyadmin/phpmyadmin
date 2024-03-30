<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Config;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

use function is_numeric;

final class SetConfigController extends AbstractController
{
    public function __construct(ResponseRenderer $response, Template $template, private Config $config)
    {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $value = $request->getParsedBodyParam('value');
        if (! is_numeric($value) || $value < 0) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON(['message' => Message::error()]);

            return;
        }

        $result = $this->config->setUserValue(null, 'NavigationWidth', (int) $value);

        if ($result === true) {
            return;
        }

        $this->response->setRequestStatus(false);
        $this->response->addJSON(['message' => $result]);
    }
}
