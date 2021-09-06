<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Export\Template;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Export\TemplateModel;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Relation;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

use function is_string;

final class DeleteController extends AbstractController
{
    /** @var TemplateModel */
    private $model;

    /** @var Relation */
    private $relation;

    /**
     * @param ResponseRenderer $response
     */
    public function __construct(
        $response,
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
        $cfgRelation = $this->relation->getRelationsParam();

        if (! $cfgRelation['exporttemplateswork']) {
            return;
        }

        $result = $this->model->delete(
            $cfgRelation['db'],
            $cfgRelation['export_templates'],
            $cfg['Server']['user'],
            $templateId
        );

        if (is_string($result)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', $result);

            return;
        }

        $this->response->setRequestStatus(true);
    }
}
