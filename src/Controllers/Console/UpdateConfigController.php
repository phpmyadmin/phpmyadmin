<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Console;

use Fig\Http\Message\StatusCodeInterface;
use InvalidArgumentException;
use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;

use function __;
use function in_array;
use function is_numeric;

#[Route('/console/update-config', ['POST'])]
final class UpdateConfigController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly Config $config,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        try {
            $key = $this->parseKeyParam($request->getParsedBodyParam('key'));
            $value = $this->parseValueParam($key, $request->getParsedBodyParam('value'));
        } catch (InvalidArgumentException $exception) {
            $this->response->setStatusCode(StatusCodeInterface::STATUS_BAD_REQUEST);
            $this->response->setRequestStatus(false);
            $this->response->addJSON(['message' => $exception->getMessage()]);

            return $this->response->response();
        }

        $result = $this->config->setUserValue(null, 'Console/' . $key, $value);
        if ($result !== true) {
            $this->response->setStatusCode(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR);
            $this->response->setRequestStatus(false);
            $this->response->addJSON(['message' => $result->getMessage()]);

            return $this->response->response();
        }

        $this->response->addJSON('message', __('Console settings has been updated successfully.'));

        return $this->response->response();
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
