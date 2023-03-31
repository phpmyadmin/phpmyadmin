import $ from 'jquery';

export default function mainMenuResizerCallback () {
    // 5 px margin for jumping menu in Chrome
    // eslint-disable-next-line compat/compat
    return $(document.body).width() - 5;
}
