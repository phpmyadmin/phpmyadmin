<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server\Variables;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Providers\ServerVariables\ServerVariablesProvider;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;

use function header;
use function implode;

final class GetVariableController extends AbstractController
{
    public function __construct(ResponseRenderer $response, Template $template, private DatabaseInterface $dbi)
    {
        parent::__construct($response, $template);
    }

    /** @param mixed[] $params Request parameters */
    public function __invoke(ServerRequest $request, array $params): void
    {
        if (! $this->response->isAjax()) {
            return;
        }

        // Send with correct charset
        header('Content-Type: text/html; charset=UTF-8');
        $varValue = $this->dbi->fetchSingleRow(
            'SHOW GLOBAL VARIABLES WHERE Variable_name='
            . $this->dbi->quoteString($params['name']) . ';',
            DatabaseInterface::FETCH_NUM,
        );

        $json = ['message' => $varValue[1]];

        $variableType = ServerVariablesProvider::getImplementation()->getVariableType($params['name']);

        if ($variableType === 'byte') {
            /** @var string[] $bytes */
            $bytes = Util::formatByteDown($varValue[1], 3, 3);
            $json['message'] = implode(' ', $bytes);
        }

        $this->response->addJSON($json);
    }
}
