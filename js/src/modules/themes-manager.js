import $ from 'jquery';

/**
 * @implements EventListener
 */
export const ThemesManager = {
    handleEvent: () => {
        $.get('index.php?route=/themes', data => {
            $('#themesModal .modal-body').html(data.themes);
        });
    }
};
