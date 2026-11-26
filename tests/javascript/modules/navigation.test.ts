/* eslint-env node, jest */

import $ from 'jquery';
import { CommonParams } from '../../../resources/js/modules/common.ts';
import { Navigation } from '../../../resources/js/modules/navigation.ts';

test('loads the current database from an initially unselected database selector', () => {
    document.body.innerHTML = '<div id="pma_navigation_tree" class="synced">' +
        '<select id="navi_db_select"><option value="mydb" data-apath="option-path">mydb</option></select>' +
        '<div id="pma_navigation_tree_content"><ul><li class="database">' +
        '<div><a class="expander"><span class="paths_nav" data-apath="tree-path"></span></a></div>' +
        '<a>mydb</a></li></ul></div></div>';

    $('#navi_db_select').val('');
    CommonParams.set('db', 'mydb');
    CommonParams.set('table', '');
    $.post = jest.fn().mockImplementation((url, params, callback) => {
        callback({ success: true, message: '<div class="list_container"></div>' });
    });

    Navigation.showCurrent();

    expect($('#navi_db_select').val()).toBe('mydb');
    expect($.post).toHaveBeenCalledWith('index.php?route=/navigation',
        expect.objectContaining({ aPath: 'option-path' }), expect.any(Function));

    expect($('#pma_navigation_tree_content > .list_container')).toHaveLength(1);
});
