import $ from 'jquery';

/**
 * @see https://developer.mozilla.org/en-US/docs/Web/API/EventTarget/addEventListener
 */
export const ThemesManager = {
    handleEvent: () => {
        $.get('index.php?route=/themes', data => {
            $('#themesModal .modal-body').html(data.themes);
        });
    }
};
