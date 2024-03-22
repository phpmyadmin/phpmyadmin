<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database\Structure;

use PhpMyAdmin\Bookmarks\BookmarkRepository;
use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationCleanup;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Controllers\Database\StructureController;
use PhpMyAdmin\Current;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\FlashMessages;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\Operations;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Sql;
use PhpMyAdmin\Table\Table;
use PhpMyAdmin\Template;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Util;
use PhpMyAdmin\Utils\ForeignKey;

use function __;

final class EmptyTableController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private DatabaseInterface $dbi,
        private Relation $relation,
        private RelationCleanup $relationCleanup,
        private Operations $operations,
        private FlashMessages $flash,
        private StructureController $structureController,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $multBtn = $_POST['mult_btn'] ?? '';
        /** @var string[] $selected */
        $selected = $request->getParsedBodyParam('selected', []);

        if ($multBtn !== __('Yes')) {
            $this->flash->addMessage('success', __('No change'));
            $this->redirect('/database/structure', ['db' => Current::$database]);

            return;
        }

        $defaultFkCheckValue = ForeignKey::handleDisableCheckInit();

        $GLOBALS['sql_query'] = '';

        $this->dbi->selectDb(Current::$database);

        foreach ($selected as $selectedValue) {
            if (Table::get($selectedValue, Current::$database, $this->dbi)->isView()) {
                continue;
            }

            $aQuery = 'TRUNCATE ';
            $aQuery .= Util::backquote($selectedValue);

            $GLOBALS['sql_query'] .= $aQuery . ';' . "\n";
            $this->dbi->query($aQuery);
        }

        if (! empty($_REQUEST['pos'])) {
            $sql = new Sql(
                $this->dbi,
                $this->relation,
                $this->relationCleanup,
                $this->operations,
                new Transformations(),
                $this->template,
                new BookmarkRepository($this->dbi, $this->relation),
                Config::getInstance(),
            );

            $_REQUEST['pos'] = $sql->calculatePosForLastPage(Current::$database, Current::$table, $_REQUEST['pos']);
        }

        ForeignKey::handleDisableCheckCleanup($defaultFkCheckValue);

        $GLOBALS['message'] = Message::success();

        unset($_POST['mult_btn']);

        ($this->structureController)($request);
    }
}
