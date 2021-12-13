<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Export\Template;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Export\Template as ExportTemplate;
use PhpMyAdmin\Export\TemplateModel;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

use function is_string;

final class UpdateController extends AbstractController
{
    /** @var TemplateModel */
    private $model;

    /** @var Relation */
    private $relation;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        TemplateModel $model,
        Relation $relation
    ) {
        parent::__construct($response, $template);
        $this->model = $model;
        $this->relation = $relation;
    }

    public function __invoke(ServerRequest $request): void
    {
        global $cfg;

        $templateId = (int) $request->getParsedBodyParam('templateId');
        /** @var string $templateData */
        $templateData = $request->getParsedBodyParam('templateData', '');
        $relationParameters = $this->relation->getRelationParameters();

        if (
            ! $relationParameters->hasExportTemplatesFeature()
            || $relationParameters->db === null
            || $relationParameters->exportTemplates === null
        ) {
            return;
        }

        $template = ExportTemplate::fromArray([
            'id' => $templateId,
            'username' => $cfg['Server']['user'],
            'data' => $templateData,
        ]);
        $result = $this->model->update($relationParameters->db, $relationParameters->exportTemplates, $template);

        if (is_string($result)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', $result);

            return;
        }

        $this->response->setRequestStatus(true);
    }
}
