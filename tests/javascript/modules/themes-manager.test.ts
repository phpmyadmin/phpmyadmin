/* eslint-env node, jest */

import $ from 'jquery';
import { ThemeColorModeToggle } from '../../../resources/js/modules/themes-manager.ts';

describe('ThemeColorModeToggle event listener', () => {
    test('toggles the color mode', () => {
        document.documentElement.dataset.bsTheme = 'color2';
        document.body.innerHTML = '<form action="https://example.com/index.php?route=/themes/set">' +
            '<select name="themeColorMode" id="themeColorModeToggle">' +
            '<option value="color0">Color0</option>' +
            '<option value="color1">Color1</option>' +
            '<option value="color2" selected>Color2</option>' +
            '<option value="color3">Color3</option>' +
            '</select></form>';

        $.post = jest.fn().mockReturnValue({ 'done': (x: any) => x({ 'themeColorMode': 'color3' }) });

        const themeColorModeToggle = document.getElementById('themeColorModeToggle') as HTMLSelectElement;
        themeColorModeToggle.addEventListener('change', ThemeColorModeToggle);
        themeColorModeToggle.selectedIndex = 3;
        themeColorModeToggle.dispatchEvent(new Event('change'));

        expect($.post).toHaveBeenCalledWith(
            'https://example.com/index.php?route=/themes/set',
            { 'ajax_request': '1', 'themeColorMode': 'color3' }
        );

        expect(document.querySelector('html').dataset.bsTheme).toEqual('color3');
    });
});
