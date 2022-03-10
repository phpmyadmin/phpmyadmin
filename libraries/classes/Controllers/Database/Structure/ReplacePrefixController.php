<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database\Structure;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Controllers\Database\StructureController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;

use function count;
use function mb_strlen;
use function mb_substr;

final class ReplacePrefixController extends AbstractController
{
    /** @var DatabaseInterface */
    private $dbi;

    /** @var StructureController */
    private $structureController;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        DatabaseInterface $dbi,
        StructureController $structureController
    ) {
        parent::__construct($response, $template);
        $this->dbi = $dbi;
        $this->structureController = $structureController;
    }

    public function __invoke(): void
    {
        $GLOBALS['message'] = $GLOBALS['message'] ?? null;

        $selected = $_POST['selected'] ?? [];
        $fromPrefix = $_POST['from_prefix'] ?? '';
        $toPrefix = $_POST['to_prefix'] ?? '';

        $GLOBALS['sql_query'] = '';
        $selectedCount = count($selected);

        for ($i = 0; $i < $selectedCount; $i++) {
            $current = $selected[$i];
            $subFromPrefix = mb_substr($current, 0, mb_strlen((string) $fromPrefix));

            if ($subFromPrefix === $fromPrefix) {
                $newTableName = $toPrefix . mb_substr($current, mb_strlen((string) $fromPrefix));
            } else {
                $newTableName = $current;
            }

            $aQuery = 'ALTER TABLE ' . Util::backquote($selected[$i])
                . ' RENAME ' . Util::backquote($newTableName);

            $GLOBALS['sql_query'] .= $aQuery . ';' . "\n";
            $this->dbi->selectDb($GLOBALS['db']);
            $this->dbi->query($aQuery);
        }

        $GLOBALS['message'] = Message::success();

        if (empty($_POST['message'])) {
            $_POST['message'] = $GLOBALS['message'];
        }

        ($this->structureController)();
    }
}
