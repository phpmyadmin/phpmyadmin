<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database\Structure;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationCleanup;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Controllers\Database\StructureController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\FlashMessages;
use PhpMyAdmin\Message;
use PhpMyAdmin\Operations;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Sql;
use PhpMyAdmin\Template;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Util;
use PhpMyAdmin\Utils\ForeignKey;

use function __;
use function count;

final class EmptyTableController extends AbstractController
{
    /** @var DatabaseInterface */
    private $dbi;

    /** @var Relation */
    private $relation;

    /** @var RelationCleanup */
    private $relationCleanup;

    /** @var Operations */
    private $operations;

    /** @var FlashMessages */
    private $flash;

    /** @var StructureController */
    private $structureController;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        DatabaseInterface $dbi,
        Relation $relation,
        RelationCleanup $relationCleanup,
        Operations $operations,
        FlashMessages $flash,
        StructureController $structureController
    ) {
        parent::__construct($response, $template);
        $this->dbi = $dbi;
        $this->relation = $relation;
        $this->relationCleanup = $relationCleanup;
        $this->operations = $operations;
        $this->flash = $flash;
        $this->structureController = $structureController;
    }

    public function __invoke(): void
    {
        $GLOBALS['message'] = $GLOBALS['message'] ?? null;

        $multBtn = $_POST['mult_btn'] ?? '';
        $selected = $_POST['selected'] ?? [];

        if ($multBtn !== __('Yes')) {
            $this->flash->addMessage('success', __('No change'));
            $this->redirect('/database/structure', ['db' => $GLOBALS['db']]);

            return;
        }

        $defaultFkCheckValue = ForeignKey::handleDisableCheckInit();

        $GLOBALS['sql_query'] = '';
        $selectedCount = count($selected);

        for ($i = 0; $i < $selectedCount; $i++) {
            $aQuery = 'TRUNCATE ';
            $aQuery .= Util::backquote($selected[$i]);

            $GLOBALS['sql_query'] .= $aQuery . ';' . "\n";
            $this->dbi->selectDb($GLOBALS['db']);
            $this->dbi->query($aQuery);
        }

        if (! empty($_REQUEST['pos'])) {
            $sql = new Sql(
                $this->dbi,
                $this->relation,
                $this->relationCleanup,
                $this->operations,
                new Transformations(),
                $this->template
            );

            $_REQUEST['pos'] = $sql->calculatePosForLastPage($GLOBALS['db'], $GLOBALS['table'], $_REQUEST['pos']);
        }

        ForeignKey::handleDisableCheckCleanup($defaultFkCheckValue);

        $GLOBALS['message'] = Message::success();

        if (empty($_POST['message'])) {
            $_POST['message'] = $GLOBALS['message'];
        }

        unset($_POST['mult_btn']);

        ($this->structureController)();
    }
}
