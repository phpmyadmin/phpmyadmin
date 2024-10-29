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

final class UpdateController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly TemplateModel $model,
        private readonly Relation $relation,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $templateId = (int) $request->getParsedBodyParamAsStringOrNull('templateId');
        $templateData = $request->getParsedBodyParamAsString('templateData', '');

        $exportTemplatesFeature = $this->relation->getRelationParameters()->exportTemplatesFeature;
        if ($exportTemplatesFeature === null) {
            return $this->response->response();
        }

        $template = ExportTemplate::fromArray([
            'id' => $templateId,
            'username' => Config::getInstance()->selectedServer['user'],
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
