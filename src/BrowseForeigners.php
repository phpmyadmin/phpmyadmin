<?php
/**
 * Contains functions used by browse foreigners
 */

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\Config\Settings;
use PhpMyAdmin\ConfigStorage\ForeignData;
use PhpMyAdmin\Theme\ThemeManager;

use function __;
use function array_keys;
use function asort;
use function ceil;
use function floor;
use function htmlspecialchars;
use function mb_strlen;
use function mb_substr;

/**
 * PhpMyAdmin\BrowseForeigners class
 */
class BrowseForeigners
{
    private Settings $settings;

    public function __construct(public Template $template, Config $config, private readonly ThemeManager $themeManager)
    {
        $this->settings = $config->getSettings();
    }

    /**
     * Function to get html for one relational key
     *
     * @param int             $horizontalCount    the current horizontal count
     * @param string          $header             table header
     * @param string[]|null[] $keys               all the keys
     * @param int             $indexByKeyname     index by keyname
     * @param string[]        $descriptions       descriptions
     * @param int             $indexByDescription index by description
     * @param string          $currentValue       current value on the edit form
     *
     * @return array{string, int, int} the generated html
     */
    private function getHtmlForOneKey(
        int $horizontalCount,
        string $header,
        array $keys,
        int $indexByKeyname,
        array $descriptions,
        int $indexByDescription,
        string $currentValue,
    ): array {
        $horizontalCount++;
        $output = '';

        // whether the key name corresponds to the selected value in the form
        $rightKeynameIsSelected = false;
        $leftKeynameIsSelected = false;

        if ($this->settings->repeatCells > 0 && $horizontalCount > $this->settings->repeatCells) {
            $output .= $header;
            $horizontalCount = 0;
        }

        // key names and descriptions for the left section,
        // sorted by key names
        $leftKeyname = $keys[$indexByKeyname];
        [$leftDescription, $leftDescriptionTitle] = $this->getDescriptionAndTitle($descriptions[$indexByKeyname]);

        // key names and descriptions for the right section,
        // sorted by descriptions
        $rightKeyname = $keys[$indexByDescription];
        [
            $rightDescription,
            $rightDescriptionTitle,
        ] = $this->getDescriptionAndTitle($descriptions[$indexByDescription]);

        $indexByDescription++;

        if ($currentValue !== '') {
            $rightKeynameIsSelected = $rightKeyname == $currentValue;
            $leftKeynameIsSelected = $leftKeyname == $currentValue;
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

        $output .= '<td width="20%"><img src="'
            . $this->themeManager->getThemeImagePath('spacer.png')
            . '" alt="" width="1" height="1"></td>';

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

        return [$output, $horizontalCount, $indexByDescription];
    }

    /**
     * Function to get html for relational field selection
     *
     * @param string $db           current database
     * @param string $table        current table
     * @param string $field        field
     * @param string $fieldKey     field key
     * @param string $currentValue current columns's value
     */
    public function getHtmlForRelationalFieldSelection(
        string $db,
        string $table,
        string $field,
        ForeignData $foreignData,
        string $fieldKey,
        string $currentValue,
        int $pos,
        string $foreignFilter,
        string|null $rownumber,
    ): string {
        $gotoPage = $this->getHtmlForGotoPage($foreignData, $pos);
        $foreignShowAll = '';
        if (
            $foreignData->dispRow !== null &&
            $this->settings->showAll && $foreignData->theTotal > $this->settings->maxRows
        ) {
            $foreignShowAll = $this->template->render('table/browse_foreigners/show_all');
        }

        $output = '<form class="ajax" '
            . 'id="browse_foreign_form" name="browse_foreign_from" action="'
            . Url::getFromRoute('/browse-foreigners')
            . '" method="post"><fieldset class="row g-3 align-items-center mb-3">' . "\n"
            . Url::getHiddenInputs($db, $table) . "\n"
            . '<input type="hidden" name="field" value="' . htmlspecialchars($field) . '">' . "\n"
            . '<input type="hidden" name="fieldkey" value="'
            . htmlspecialchars($fieldKey) . '">' . "\n";

        if ($rownumber !== null) {
            $output .= '<input type="hidden" name="rownumber" value="' . htmlspecialchars($rownumber) . '">';
        }

        $filterValue = htmlspecialchars($foreignFilter);
        $output .= '<div class="col-auto">'
            . '<label class="form-label" for="input_foreign_filter">' . __('Search:') . '</label></div>' . "\n"
            . '<div class="col-auto"><input class="form-control" type="text" name="foreign_filter" '
            . 'id="input_foreign_filter" '
            . 'value="' . $filterValue . '" data-old="' . $filterValue . '">' . "\n"
            . '</div><div class="col-auto">'
            . '<input class="btn btn-primary" type="submit" name="submit_foreign_filter" value="'
            . __('Go') . '">'
            . '</div>' . "\n"
            . '<div class="col-auto">' . $gotoPage . '</div>'
            . '<div class="col-auto">' . $foreignShowAll . '</div>'
            . '</fieldset>'
            . '</form>' . "\n";

        $output .= '<table class="table table-striped table-hover" id="browse_foreign_table">' . "\n";

        if ($foreignData->dispRow === null) {
            return $output . '</tbody>'
                . '</table>';
        }

        $header = '<tr>
            <th>' . __('Keyname') . '</th>
            <th>' . __('Description') . '</th>
            <td width="20%"></td>
            <th>' . __('Description') . '</th>
            <th>' . __('Keyname') . '</th>
        </tr>' . "\n";

        $output .= '<thead>' . $header . '</thead>' . "\n"
            . '<tfoot>' . $header . '</tfoot>' . "\n"
            . '<tbody>' . "\n";

        $descriptions = [];
        $keys = [];
        foreach ($foreignData->dispRow as $relrow) {
            $descriptions[] = $relrow[$foreignData->foreignDisplay] ?? '';
            $keys[] = $relrow[$foreignData->foreignField];
        }

        asort($keys);

        $horizontalCount = 0;
        $indexByDescription = 0;

        foreach (array_keys($keys) as $indexByKeyname) {
            [$html, $horizontalCount, $indexByDescription] = $this->getHtmlForOneKey(
                $horizontalCount,
                $header,
                $keys,
                $indexByKeyname,
                $descriptions,
                $indexByDescription,
                $currentValue,
            );
            $output .= $html . "\n";
        }

        $output .= '</tbody></table>';

        return $output;
    }

    /**
     * Get the description (possibly truncated) and the title
     *
     * @param string $description the key name's description
     *
     * @return array{string, string} the new description and title
     */
    private function getDescriptionAndTitle(string $description): array
    {
        if (mb_strlen($description) <= $this->settings->limitChars) {
            $descriptionTitle = '';
        } else {
            $descriptionTitle = $description;
            $description = mb_substr($description, 0, $this->settings->limitChars) . '...';
        }

        return [$description, $descriptionTitle];
    }

    /**
     * Function to get html for the goto page option
     */
    private function getHtmlForGotoPage(ForeignData $foreignData, int $pos): string
    {
        if ($foreignData->dispRow === null) {
            return '';
        }

        $pageNow = (int) floor($pos / $this->settings->maxRows) + 1;
        $nbTotalPage = (int) ceil($foreignData->theTotal / $this->settings->maxRows);

        if ($foreignData->theTotal > $this->settings->maxRows) {
            return Util::pageselector(
                'pos',
                $this->settings->maxRows,
                $pageNow,
                $nbTotalPage,
                200,
                5,
                5,
                20,
                10,
                __('Page number:'),
            );
        }

        return '';
    }

    public function getForeignLimit(string|null $foreignShowAll, int $pos): string
    {
        if ($foreignShowAll === __('Show all')) {
            return '';
        }

        return 'LIMIT ' . $pos . ', ' . $this->settings->maxRows . ' ';
    }
}
