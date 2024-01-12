<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Setup;

use PhpMyAdmin\Config\Validator;
use PhpMyAdmin\Core;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use stdClass;

use function __;
use function explode;
use function implode;
use function is_string;
use function json_decode;
use function json_encode;
use function sprintf;

final class ValidateController
{
    public function __construct(private readonly ResponseFactory $responseFactory)
    {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $response = $this->responseFactory->createResponse();
        foreach (Core::headerJSON() as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        /** @var mixed $id */
        $id = $request->getParsedBodyParam('id');
        $vids = explode(',', is_string($id) ? $id : '');

        /** @var mixed $valuesParam */
        $valuesParam = $request->getParsedBodyParam('values');
        $values = json_decode(is_string($valuesParam) ? $valuesParam : '');
        if (! $values instanceof stdClass) {
            return $response->write((string) json_encode(['success' => false, 'message' => __('Wrong data')]));
        }

        $values = (array) $values;
        $result = Validator::validate($GLOBALS['ConfigFile'], $vids, $values, true);
        if ($result === false) {
            $result = sprintf(
                __('Wrong data or no validation for %s'),
                implode(',', $vids),
            );
        }

        return $response->write($result !== true ? (string) json_encode($result) : '');
    }
}
