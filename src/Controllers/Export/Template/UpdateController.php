<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Export\Template;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Export\Template as ExportTemplate;
use PhpMyAdmin\Export\TemplateModel;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;

#[Route('/export/template/update', ['POST'])]
final readonly class UpdateController implements InvocableController
{
    public function __construct(
        private ResponseRenderer $response,
        private TemplateModel $model,
        private Relation $relation,
        private Config $config,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $templateId = (int) $request->getParsedBodyParamAsStringOrNull('templateId');
        $templateData = $request->getParsedBodyParamAsString('templateData', '');
        $exportType = $request->getParsedBodyParamAsString('exportType', '');

        $exportTemplatesFeature = $this->relation->getRelationParameters()->exportTemplatesFeature;
        if ($exportTemplatesFeature === null) {
            return $this->response->response();
        }

        $template = ExportTemplate::fromArray([
            'id' => $templateId,
            'exportType' => $exportType,
            'username' => $this->config->selectedServer['user'],
            'data' => $templateData,
        ]);
        $result = $this->model->update(
            $exportTemplatesFeature->database,
            $exportTemplatesFeature->exportTemplates,
            $template,
        );

        if ($result !== '') {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', $result);

            return $this->response->response();
        }

        $this->response->setRequestStatus(true);

        return $this->response->response();
    }
}
