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
