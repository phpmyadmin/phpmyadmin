import $ from 'jquery';

/**
 * Create a jQuery UI tooltip
 *
 * @param $elements jQuery object representing the elements
 * @param {string} item the item (see https://api.jqueryui.com/tooltip/#option-items)
 * @param myContent content of the tooltip
 * @param {Object} additionalOptions to override the default options
 */
export default function tooltip ($elements, item, myContent, additionalOptions = {}): void {
    if ($('#no_hint').length > 0) {
        return;
    }

    const defaultOptions = {
        content: myContent,
        items: item,
        tooltipClass: 'tooltip',
        track: true,
        show: false,
        hide: false
    };

    $elements.uiTooltip($.extend(true, defaultOptions, additionalOptions));
}
