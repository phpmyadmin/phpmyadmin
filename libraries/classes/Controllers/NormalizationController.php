<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Normalization;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;

use function __;
use function _pgettext;
use function intval;
use function min;

/**
 * Normalization process (temporarily specific to 1NF).
 */
class NormalizationController extends AbstractController
{
    /** @var Normalization */
    private $normalization;

    public function __construct(ResponseRenderer $response, Template $template, Normalization $normalization)
    {
        parent::__construct($response, $template);
        $this->normalization = $normalization;
    }

    public function __invoke(ServerRequest $request): void
    {
        if (isset($_POST['getColumns'])) {
            $html = '<option selected disabled>' . __('Select one…') . '</option>'
                . '<option value="no_such_col">' . __('No such column') . '</option>';
            //get column whose datatype falls under string category
            $html .= $this->normalization->getHtmlForColumnsList(
                $GLOBALS['db'],
                $GLOBALS['table'],
                _pgettext('string types', 'String')
            );
            echo $html;

            return;
        }

        if (isset($_POST['splitColumn'])) {
            $num_fields = min(4096, intval($_POST['numFields']));
            $html = $this->normalization->getHtmlForCreateNewColumn($num_fields, $GLOBALS['db'], $GLOBALS['table']);
            $html .= Url::getHiddenInputs($GLOBALS['db'], $GLOBALS['table']);
            echo $html;

            return;
        }

        if (isset($_POST['addNewPrimary'])) {
            $num_fields = 1;
            $columnMeta = [
                'Field' => $GLOBALS['table'] . '_id',
                'Extra' => 'auto_increment',
            ];
            $html = $this->normalization->getHtmlForCreateNewColumn(
                $num_fields,
                $GLOBALS['db'],
                $GLOBALS['table'],
                $columnMeta
            );
            $html .= Url::getHiddenInputs($GLOBALS['db'], $GLOBALS['table']);
            echo $html;

            return;
        }

        if (isset($_POST['findPdl'])) {
            $html = $this->normalization->findPartialDependencies($GLOBALS['table'], $GLOBALS['db']);
            echo $html;

            return;
        }

        $this->addScriptFiles(['normalization.js', 'vendor/jquery/jquery.uitablefilter.js']);

        if (isset($_POST['repeatingColumns'])) {
            $repeatingColumns = $_POST['repeatingColumns'];
            $newTable = $_POST['newTable'];
            $newColumn = $_POST['newColumn'];
            $primary_columns = $_POST['primary_columns'];
            $res = $this->normalization->moveRepeatingGroup(
                $repeatingColumns,
                $primary_columns,
                $newTable,
                $newColumn,
                $GLOBALS['table'],
                $GLOBALS['db']
            );
            $this->response->addJSON($res);

            return;
        }

        $this->render('table/normalization/normalization', [
            'db' => $GLOBALS['db'],
            'table' => $GLOBALS['table'],
        ]);
    }
}
