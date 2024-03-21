<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Console;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

use function in_array;
use function is_string;
use function json_decode;

final class UpdateConfigController extends AbstractController
{
    public function __construct(ResponseRenderer $response, Template $template, private Config $config)
    {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $validKeys = [
            'StartHistory',
            'AlwaysExpand',
            'CurrentQuery',
            'EnterExecutes',
            'DarkTheme',
            'Mode',
            'Height',
            'GroupQueries',
            'OrderBy',
            'Order',
        ];
        $key = $request->getParsedBodyParam('key');
        $value = $request->getParsedBodyParam('value');
        if (! in_array($key, $validKeys, true) || ! is_string($value)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON(['message' => Message::error()]);

            return;
        }

        $result = $this->config->setUserValue(null, 'Console/' . $key, json_decode($value));
        if ($result === true) {
            return;
        }

        $this->response->setRequestStatus(false);
        $this->response->addJSON(['message' => $result]);
    }
}
