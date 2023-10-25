import $ from 'jquery';
import { CommonParams } from './common.ts';

/**
 * @see https://developer.mozilla.org/en-US/docs/Web/API/EventTarget/addEventListener
 */
export const ThemesManager = {
    handleEvent: () => {
        $.get('index.php?route=/themes', { 'server': CommonParams.get('server'), 'ajax_request': true }, data => {
            $('#themesModal .modal-body').html(data.themes);
        });
    }
};

function setColorModeToHtmlTag (themeColorMode: string): void {
    const htmlTag = document.querySelector('html');
    htmlTag.dataset.bsTheme = themeColorMode;
}

export const ThemeColorModeToggle: EventListenerObject = {
    handleEvent: (): void => {
        const toggleSelect = document.getElementById('themeColorModeToggle') as HTMLSelectElement;
        setColorModeToHtmlTag(toggleSelect.options.item(toggleSelect.selectedIndex).value);
        const toggleForm = toggleSelect.form;
        const formData = new FormData(toggleForm);
        formData.set('ajax_request', '1');
        $.post(toggleForm.action, Object.fromEntries(formData.entries())).done(function (data): void {
            setColorModeToHtmlTag(data.themeColorMode);
        });
    }
};
