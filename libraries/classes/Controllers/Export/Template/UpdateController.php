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

final class UpdateController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private TemplateModel $model,
        private Relation $relation,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $templateId = (int) $request->getParsedBodyParam('templateId');
        /** @var string $templateData */
        $templateData = $request->getParsedBodyParam('templateData', '');

        $exportTemplatesFeature = $this->relation->getRelationParameters()->exportTemplatesFeature;
        if ($exportTemplatesFeature === null) {
            return;
        }

        $template = ExportTemplate::fromArray([
            'id' => $templateId,
            'username' => $GLOBALS['cfg']['Server']['user'],
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

            return;
        }

        $this->response->setRequestStatus(true);
    }
}
