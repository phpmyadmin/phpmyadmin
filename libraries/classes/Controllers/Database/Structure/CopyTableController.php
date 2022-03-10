<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database\Structure;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Controllers\Database\StructureController;
use PhpMyAdmin\Message;
use PhpMyAdmin\Operations;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Table;
use PhpMyAdmin\Template;

use function count;

final class CopyTableController extends AbstractController
{
    /** @var Operations */
    private $operations;

    /** @var StructureController */
    private $structureController;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        Operations $operations,
        StructureController $structureController
    ) {
        parent::__construct($response, $template);
        $this->operations = $operations;
        $this->structureController = $structureController;
    }

    public function __invoke(): void
    {
        $GLOBALS['message'] = $GLOBALS['message'] ?? null;

        $selected = $_POST['selected'] ?? [];
        $targetDb = $_POST['target_db'] ?? null;
        $selectedCount = count($selected);

        for ($i = 0; $i < $selectedCount; $i++) {
            Table::moveCopy(
                $GLOBALS['db'],
                $selected[$i],
                $targetDb,
                $selected[$i],
                $_POST['what'],
                false,
                'one_table',
                isset($_POST['drop_if_exists']) && $_POST['drop_if_exists'] === 'true'
            );

            if (empty($_POST['adjust_privileges'])) {
                continue;
            }

            $this->operations->adjustPrivilegesCopyTable($GLOBALS['db'], $selected[$i], $targetDb, $selected[$i]);
        }

        $GLOBALS['message'] = Message::success();

        if (empty($_POST['message'])) {
            $_POST['message'] = $GLOBALS['message'];
        }

        ($this->structureController)();
    }
}
