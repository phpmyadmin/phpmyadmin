<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PhpMyAdmin\UserPreferencesHeader class
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\Config\Forms\User\UserFormList;
use Throwable;
use Twig_Error_Loader;
use Twig_Error_Runtime;
use Twig_Error_Syntax;

/**
 * Functions for displaying user preferences header
 *
 * @package PhpMyAdmin
 */
class UserPreferencesHeader
{
    /**
     * Get HTML content
     *
     * @param Template $template Template object used to render data
     * @param Relation $relation Relation object
     *
     * @return string
     * @throws Throwable
     * @throws Twig_Error_Loader
     * @throws Twig_Error_Runtime
     * @throws Twig_Error_Syntax
     */
    public static function getContent(Template $template, Relation $relation): string
    {
        return self::displayTabs($template)
            . self::displayConfigurationSavedMessage()
            . self::sessionStorageWarning($relation);
    }

    /**
     * @param Template $template Template object used to render data
     *
     * @return string
     * @throws Throwable
     * @throws Twig_Error_Loader
     * @throws Twig_Error_Runtime
     * @throws Twig_Error_Syntax
     */
    protected static function displayTabs(Template $template): string
    {
        // build user preferences menu
        $content = Util::getHtmlTab(
            [
                'link' => 'prefs_manage.php',
                'text' => __('Manage your settings'),
            ]
        ) . "\n";
        /* Second authentication factor */
        $content .= Util::getHtmlTab(
            [
                'link' => 'prefs_twofactor.php',
                'text' => __('Two-factor authentication'),
            ]
        ) . "\n";

        $content .= self::displayTabsWithIcon();

        return $template->render(
            'list/unordered',
            [
                'id' => 'topmenu2',
                'class' => 'user_prefs_tabs',
                'content' => $content,
            ]
        ) . '<div class="clearfloat"></div>';
    }

    /**
     * @return string
     */
    protected static function displayTabsWithIcon(): string
    {
        $form_param = $_GET['form'] ?? null;
        $tabs_icons = [
            'Features' => 'b_tblops',
            'Sql' => 'b_sql',
            'Navi' => 'b_select',
            'Main' => 'b_props',
            'Import' => 'b_import',
            'Export' => 'b_export',
        ];
        $script_name = basename($GLOBALS['PMA_PHP_SELF']);
        $content = null;
        foreach (UserFormList::getAll() as $formset) {
            $formset_class = UserFormList::get($formset);
            $tab = [
                'link' => 'prefs_forms.php',
                'text' => $formset_class::getName(),
                'icon' => $tabs_icons[$formset],
                'active' => 'prefs_forms.php' === $script_name && $formset === $form_param,
            ];
            $content .= Util::getHtmlTab($tab, ['form' => $formset]) . "\n";
        }
        return $content;
    }

    /**
     * @return string|null
     */
    protected static function displayConfigurationSavedMessage(): ?string
    {
        // show "configuration saved" message and reload navigation panel if needed
        if (! empty($_GET['saved'])) {
            return Message::rawSuccess(__('Configuration has been saved.'))
                ->getDisplay();
        }

        return null;
    }

    /**
     * @param Relation $relation Relation instance
     *
     * @return string|null
     */
    protected static function sessionStorageWarning(Relation $relation): ?string
    {
        // warn about using session storage for settings
        $cfgRelation = $relation->getRelationsParam();
        if (! $cfgRelation['userconfigwork']) {
            $msg = __(
                'Your preferences will be saved for current session only. Storing them '
                . 'permanently requires %sphpMyAdmin configuration storage%s.'
            );
            $msg = Sanitize::sanitizeMessage(
                sprintf($msg, '[doc@linked-tables]', '[/doc]')
            );
            return Message::notice($msg)
                ->getDisplay();
        }

        return null;
    }
}
