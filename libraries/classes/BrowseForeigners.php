<?php
/**
 * Contains functions used by browse foreigners
 */

declare(strict_types=1);

namespace PhpMyAdmin;

use function asort;
use function ceil;
use function floor;
use function htmlspecialchars;
use function is_array;
use function mb_strlen;
use function mb_substr;

/**
 * PhpMyAdmin\BrowseForeigners class
 */
class BrowseForeigners
{
    /** @var int */
    private $limitChars;
    /** @var int */
    private $maxRows;
    /** @var int */
    private $repeatCells;
    /** @var bool */
    private $showAll;
    /** @var string */
    private $themeImage;

    /** @var Template */
    public $template;

    /**
     * @param Template $template Template object
     */
    public function __construct(Template $template)
    {
        global $cfg, $PMA_Theme;

        $this->template = $template;

        $this->limitChars = (int) $cfg['LimitChars'];
        $this->maxRows = (int) $cfg['MaxRows'];
        $this->repeatCells = (int) $cfg['RepeatCells'];
        $this->showAll = (bool) $cfg['ShowAll'];
        $this->themeImage = $PMA_Theme->getImgPath();
    }

    /**
     * Function to get html for one relational key
     *
     * @param int    $horizontal_count   the current horizontal count
     * @param string $header             table header
     * @param array  $keys               all the keys
     * @param int    $indexByKeyname     index by keyname
     * @param array  $descriptions       descriptions
     * @param int    $indexByDescription index by description
     * @param string $current_value      current value on the edit form
     *
     * @return array the generated html
     */
    private function getHtmlForOneKey(
        int $horizontal_count,
        string $header,
        array $keys,
        int $indexByKeyname,
        array $descriptions,
        int $indexByDescription,
        string $current_value
    ): array {
        $horizontal_count++;
        $output = '';

        // whether the key name corresponds to the selected value in the form
        $rightKeynameIsSelected = false;
        $leftKeynameIsSelected = false;

        if ($this->repeatCells > 0 && $horizontal_count > $this->repeatCells) {
            $output .= $header;
            $horizontal_count = 0;
        }

        // key names and descriptions for the left section,
        // sorted by key names
        $leftKeyname = $keys[$indexByKeyname];
        [
            $leftDescription,
            $leftDescriptionTitle,
        ] = $this->getDescriptionAndTitle($descriptions[$indexByKeyname]);

        // key names and descriptions for the right section,
        // sorted by descriptions
        $rightKeyname = $keys[$indexByDescription];
        [
            $rightDescription,
            $rightDescriptionTitle,
        ] = $this->getDescriptionAndTitle($descriptions[$indexByDescription]);

        $indexByDescription++;

        if (! empty($current_value)) {
            $rightKeynameIsSelected = $rightKeyname == $current_value;
            $leftKeynameIsSelected = $leftKeyname == $current_value;
        }

        $output .= '<tr class="noclick">';

        $output .= $this->template->render('table/browse_foreigners/column_element', [
            'keyname' => $leftKeyname,
            'description' => $leftDescription,
            'title' => $leftDescriptionTitle,
            'is_selected' => $leftKeynameIsSelected,
            'nowrap' => true,
        ]);
        $output .= $this->template->render('table/browse_foreigners/column_element', [
            'keyname' => $leftKeyname,
            'description' => $leftDescription,
            'title' => $leftDescriptionTitle,
            'is_selected' => $leftKeynameIsSelected,
            'nowrap' => false,
        ]);

        $output .= '<td width="20%">'
            . '<img src="' . $this->themeImage . 'spacer.png" alt=""'
            . ' width="1" height="1"></td>';

        $output .= $this->template->render('table/browse_foreigners/column_element', [
            'keyname' => $rightKeyname,
            'description' => $rightDescription,
            'title' => $rightDescriptionTitle,
            'is_selected' => $rightKeynameIsSelected,
            'nowrap' => false,
        ]);
        $output .= $this->template->render('table/browse_foreigners/column_element', [
            'keyname' => $rightKeyname,
            'description' => $rightDescription,
            'title' => $rightDescriptionTitle,
            'is_selected' => $rightKeynameIsSelected,
            'nowrap' => true,
        ]);

        $output .= '</tr>';

        return [
            $output,
            $horizontal_count,
            $indexByDescription,
        ];
    }

    /**
     * Function to get html for relational field selection
     *
     * @param string      $db            current database
     * @param string      $table         current table
     * @param string      $field         field
     * @param array       $foreignData   foreign column data
     * @param string|null $fieldkey      field key
     * @param string      $current_value current columns's value
     */
    public function getHtmlForRelationalFieldSelection(
        string $db,
        string $table,
        string $field,
        array $foreignData,
        ?string $fieldkey,
        string $current_value
    ): string {
        $gotopage = $this->getHtmlForGotoPage($foreignData);
        $foreignShowAll = $this->template->render('table/browse_foreigners/show_all', [
            'foreign_data' => $foreignData,
            'show_all' => $this->showAll,
            'max_rows' => $this->maxRows,
        ]);

        $output = '<form class="ajax" '
            . 'id="browse_foreign_form" name="browse_foreign_from" action="'
            . Url::getFromRoute('/browse-foreigners')
            . '" method="post"><fieldset>'
            . Url::getHiddenInputs($db, $table)
            . '<input type="hidden" name="field" value="' . htmlspecialchars($field)
            . '">'
            . '<input type="hidden" name="fieldkey" value="'
            . (isset($fieldkey) ? htmlspecialchars($fieldkey) : '') . '">';

        if (isset($_POST['rownumber'])) {
            $output .= '<input type="hidden" name="rownumber" value="'
                . htmlspecialchars((string) $_POST['rownumber']) . '">';
        }
        $filter_value = (isset($_POST['foreign_filter'])
            ? htmlspecialchars($_POST['foreign_filter'])
            : '');
        $output .= '<span class="formelement">'
            . '<label for="input_foreign_filter">' . __('Search:') . '</label>'
            . '<input type="text" name="foreign_filter" '
            . 'id="input_foreign_filter" '
            . 'value="' . $filter_value . '" data-old="' . $filter_value . '" '
            . '>'
            . '<input class="btn btn-primary" type="submit" name="submit_foreign_filter" value="'
            . __('Go') . '">'
            . '</span>'
            . '<span class="formelement">' . $gotopage . '</span>'
            . '<span class="formelement">' . $foreignShowAll . '</span>'
            . '</fieldset>'
            . '</form>';

        $output .= '<table class="pma-table" width="100%" id="browse_foreign_table">';

        if (! is_array($foreignData['disp_row'])) {
            return $output . '</tbody>'
                . '</table>';
        }

        $header = '<tr>
            <th>' . __('Keyname') . '</th>
            <th>' . __('Description') . '</th>
            <td width="20%"></td>
            <th>' . __('Description') . '</th>
            <th>' . __('Keyname') . '</th>
        </tr>';

        $output .= '<thead>' . $header . '</thead>' . "\n"
            . '<tfoot>' . $header . '</tfoot>' . "\n"
            . '<tbody>' . "\n";

        $descriptions = [];
        $keys   = [];
        foreach ($foreignData['disp_row'] as $relrow) {
            if ($foreignData['foreign_display'] != false) {
                $descriptions[] = $relrow[$foreignData['foreign_display']] ?? '';
            } else {
                $descriptions[] = '';
            }

            $keys[] = $relrow[$foreignData['foreign_field']];
        }

        asort($keys);

        $horizontal_count = 0;
        $indexByDescription = 0;

        foreach ($keys as $indexByKeyname => $value) {
            [
                $html,
                $horizontal_count,
                $indexByDescription,
            ] = $this->getHtmlForOneKey(
                $horizontal_count,
                $header,
                $keys,
                $indexByKeyname,
                $descriptions,
                $indexByDescription,
                $current_value
            );
            $output .= $html;
        }

        $output .= '</tbody>'
            . '</table>';

        return $output;
    }

    /**
     * Get the description (possibly truncated) and the title
     *
     * @param string $description the key name's description
     *
     * @return array the new description and title
     */
    private function getDescriptionAndTitle(string $description): array
    {
        if (mb_strlen($description) <= $this->limitChars) {
            $descriptionTitle = '';
        } else {
            $descriptionTitle = $description;
            $description = mb_substr(
                $description,
                0,
                $this->limitChars
            )
            . '...';
        }

        return [
            $description,
            $descriptionTitle,
        ];
    }

    /**
     * Function to get html for the goto page option
     *
     * @param array|null $foreignData foreign data
     */
    private function getHtmlForGotoPage(?array $foreignData): string
    {
        $gotopage = '';
        isset($_POST['pos']) ? $pos = $_POST['pos'] : $pos = 0;
        if ($foreignData === null || ! is_array($foreignData['disp_row'])) {
            return $gotopage;
        }

        $pageNow = (int) floor($pos / $this->maxRows) + 1;
        $nbTotalPage = (int) ceil($foreignData['the_total'] / $this->maxRows);

        if ($foreignData['the_total'] > $this->maxRows) {
            $gotopage = Util::pageselector(
                'pos',
                $this->maxRows,
                $pageNow,
                $nbTotalPage,
                200,
                5,
                5,
                20,
                10,
                __('Page number:')
            );
        }

        return $gotopage;
    }

    /**
     * Function to get foreign limit
     *
     * @param string|null $foreignShowAll foreign navigation
     */
    public function getForeignLimit(?string $foreignShowAll): ?string
    {
        if (isset($foreignShowAll) && $foreignShowAll == __('Show all')) {
            return null;
        }
        isset($_POST['pos']) ? $pos = $_POST['pos'] : $pos = 0;

        return 'LIMIT ' . $pos . ', ' . $this->maxRows . ' ';
    }
}
