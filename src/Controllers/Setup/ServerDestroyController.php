<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Setup;

use PhpMyAdmin\Http\ServerRequest;

use function is_numeric;

class ServerDestroyController extends AbstractController
{
    public function __invoke(ServerRequest $request): void
    {
        $id = $this->getIdParam($request->getQueryParam('id'));
        $hasServer = $id >= 1 && $this->config->get('Servers/' . $id) !== null;
        if (! $hasServer) {
            return;
        }

        $this->config->removeServer($id);
    }

    /** @psalm-return int<0, max> */
    private function getIdParam(mixed $idParam): int
    {
        if (! is_numeric($idParam)) {
            return 0;
        }

        $id = (int) $idParam;

        return $id >= 1 ? $id : 0;
    }
}
