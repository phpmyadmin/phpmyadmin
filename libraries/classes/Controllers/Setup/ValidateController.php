<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Setup;

use PhpMyAdmin\Config\Validator;
use PhpMyAdmin\Core;
use PhpMyAdmin\Http\ServerRequest;
use stdClass;

use function __;
use function explode;
use function header;
use function implode;
use function is_string;
use function json_decode;
use function json_encode;
use function sprintf;

final class ValidateController
{
    public function __invoke(ServerRequest $request): void
    {
        foreach (Core::headerJSON() as $name => $value) {
            header(sprintf('%s: %s', $name, $value));
        }

        /** @var mixed $id */
        $id = $request->getParsedBodyParam('id');
        $vids = explode(',', is_string($id) ? $id : '');

        /** @var mixed $valuesParam */
        $valuesParam = $request->getParsedBodyParam('values');
        $values = json_decode(is_string($valuesParam) ? $valuesParam : '');
        if (! ($values instanceof stdClass)) {
            echo json_encode(['success' => false, 'message' => __('Wrong data')]);

            return;
        }

        $values = (array) $values;
        $result = Validator::validate($GLOBALS['ConfigFile'], $vids, $values, true);
        if ($result === false) {
            $result = sprintf(
                __('Wrong data or no validation for %s'),
                implode(',', $vids),
            );
        }

        echo $result !== true ? json_encode($result) : '';
    }
}
