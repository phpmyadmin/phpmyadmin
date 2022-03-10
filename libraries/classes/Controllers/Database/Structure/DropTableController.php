<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database\Structure;

use PhpMyAdmin\ConfigStorage\RelationCleanup;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Controllers\Database\StructureController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;
use PhpMyAdmin\Utils\ForeignKey;

use function __;
use function count;
use function in_array;

final class DropTableController extends AbstractController
{
    /** @var DatabaseInterface */
    private $dbi;

    /** @var RelationCleanup */
    private $relationCleanup;

    /** @var StructureController */
    private $structureController;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        DatabaseInterface $dbi,
        RelationCleanup $relationCleanup,
        StructureController $structureController
    ) {
        parent::__construct($response, $template);
        $this->dbi = $dbi;
        $this->relationCleanup = $relationCleanup;
        $this->structureController = $structureController;
    }

    public function __invoke(): void
    {
        $GLOBALS['message'] = $GLOBALS['message'] ?? null;

        $GLOBALS['reload'] = $_POST['reload'] ?? $GLOBALS['reload'] ?? null;
        $multBtn = $_POST['mult_btn'] ?? '';
        $selected = $_POST['selected'] ?? [];

        $views = $this->dbi->getVirtualTables($GLOBALS['db']);

        if ($multBtn !== __('Yes')) {
            $GLOBALS['message'] = Message::success(__('No change'));

            if (empty($_POST['message'])) {
                $_POST['message'] = Message::success();
            }

            unset($_POST['mult_btn']);

            ($this->structureController)();

            return;
        }

        $defaultFkCheckValue = ForeignKey::handleDisableCheckInit();
        $GLOBALS['sql_query'] = '';
        $sqlQueryViews = '';
        $selectedCount = count($selected);

        for ($i = 0; $i < $selectedCount; $i++) {
            $this->relationCleanup->table($GLOBALS['db'], $selected[$i]);
            $current = $selected[$i];

            if (! empty($views) && in_array($current, $views)) {
                $sqlQueryViews .= (empty($sqlQueryViews) ? 'DROP VIEW ' : ', ') . Util::backquote($current);
            } else {
                $GLOBALS['sql_query'] .= (empty($GLOBALS['sql_query']) ? 'DROP TABLE ' : ', ')
                    . Util::backquote($current);
            }

            $GLOBALS['reload'] = 1;
        }

        if (! empty($GLOBALS['sql_query'])) {
            $GLOBALS['sql_query'] .= ';';
        } elseif (! empty($sqlQueryViews)) {
            $GLOBALS['sql_query'] = $sqlQueryViews . ';';
            unset($sqlQueryViews);
        }

        // Unset cache values for tables count, issue #14205
        if (isset($_SESSION['tmpval'])) {
            if (isset($_SESSION['tmpval']['table_limit_offset'])) {
                unset($_SESSION['tmpval']['table_limit_offset']);
            }

            if (isset($_SESSION['tmpval']['table_limit_offset_db'])) {
                unset($_SESSION['tmpval']['table_limit_offset_db']);
            }
        }

        $this->dbi->selectDb($GLOBALS['db']);
        $result = $this->dbi->tryQuery($GLOBALS['sql_query']);

        if ($result && ! empty($sqlQueryViews)) {
            $GLOBALS['sql_query'] .= ' ' . $sqlQueryViews . ';';
            $result = $this->dbi->tryQuery($sqlQueryViews);
            unset($sqlQueryViews);
        }

        if (! $result) {
            $GLOBALS['message'] = Message::error($this->dbi->getError());
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
