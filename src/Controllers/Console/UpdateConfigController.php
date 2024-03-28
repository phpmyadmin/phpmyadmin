<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Console;

use InvalidArgumentException;
use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

use function __;
use function in_array;
use function is_numeric;

final class UpdateConfigController extends AbstractController
{
    public function __construct(ResponseRenderer $response, Template $template, private Config $config)
    {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        try {
            $key = $this->parseKeyParam($request->getParsedBodyParam('key'));
            $value = $this->parseValueParam($key, $request->getParsedBodyParam('value'));
        } catch (InvalidArgumentException $exception) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON(['message' => Message::error($exception->getMessage())]);

            return;
        }

        $result = $this->config->setUserValue(null, 'Console/' . $key, $value);
        if ($result === true) {
            return;
        }

        $this->response->setRequestStatus(false);
        $this->response->addJSON(['message' => $result]);
    }

    /** @psalm-return 'StartHistory'|'AlwaysExpand'|'CurrentQuery'|'EnterExecutes'|'DarkTheme'|'Mode'|'Height'|'GroupQueries'|'OrderBy'|'Order' */
    private function parseKeyParam(mixed $key): string
    {
        if (
            ! in_array($key, [
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
            ], true)
        ) {
            throw new InvalidArgumentException(__('Unexpected parameter value.'));
        }

        return $key;
    }

    /** @psalm-param 'StartHistory'|'AlwaysExpand'|'CurrentQuery'|'EnterExecutes'|'DarkTheme'|'Mode'|'Height'|'GroupQueries'|'OrderBy'|'Order' $key */
    private function parseValueParam(string $key, mixed $value): bool|int|string
    {
        if (
            in_array($key, [
                'StartHistory',
                'AlwaysExpand',
                'CurrentQuery',
                'EnterExecutes',
                'DarkTheme',
                'GroupQueries',
            ], true)
            && in_array($value, ['true', 'false'], true)
        ) {
            return $value === 'true';
        }

        if ($key === 'Mode' && in_array($value, ['show', 'collapse', 'info'], true)) {
            return $value;
        }

        if ($key === 'Height' && is_numeric($value) && $value > 0) {
            return (int) $value;
        }

        if ($key === 'OrderBy' && in_array($value, ['exec', 'time', 'count'], true)) {
            return $value;
        }

        if ($key === 'Order' && in_array($value, ['asc', 'desc'], true)) {
            return $value;
        }

        throw new InvalidArgumentException(__('Unexpected parameter value.'));
    }
}
