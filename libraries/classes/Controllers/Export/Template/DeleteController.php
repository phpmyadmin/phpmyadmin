<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Export\Template;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Export\TemplateModel;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

final class DeleteController extends AbstractController
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

        $exportTemplatesFeature = $this->relation->getRelationParameters()->exportTemplatesFeature;
        if ($exportTemplatesFeature === null) {
            return;
        }

        $result = $this->model->delete(
            $exportTemplatesFeature->database,
            $exportTemplatesFeature->exportTemplates,
            $GLOBALS['cfg']['Server']['user'],
            $templateId,
        );

        if ($result !== '') {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', $result);

            return;
        }

        $this->response->setRequestStatus(true);
    }
}
