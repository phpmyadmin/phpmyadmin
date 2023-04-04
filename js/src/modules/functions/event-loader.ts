import $ from 'jquery';
import { Functions } from '../functions.ts';
import handleCreateViewModal from './handleCreateViewModal.ts';

/**
 * @see https://developer.mozilla.org/en-US/docs/Web/API/EventTarget/addEventListener
 */
const PrintPage = {
    handleEvent: () => {
        window.print();
    }
};

/**
 * @return {function}
 */
export function teardownFunctions () {
    return function () {
        Functions.teardownIdleEvent();
        $(document).off('click', 'input:checkbox.checkall');
        Functions.teardownSqlQueryEditEvents();
        Functions.removeAutocompleteInfo();
        Functions.teardownCreateTableEvents();
        Functions.teardownEnumSetEditorMessage();
        Functions.teardownEnumSetEditor();
        $(document).off('click', '#index_frm input[type=submit]');
        $('div.toggle-container').off('click');
        $(document).off('change', 'select.pageselector');
        Functions.teardownRecentFavoriteTables();
        Functions.teardownCodeMirrorEditor();
        $(document).off('change', '.autosubmit');
        document.querySelectorAll('.jsPrintButton').forEach(item => {
            item.removeEventListener('click', PrintPage);
        });

        $(document).off('click', 'a.create_view.ajax');
        Functions.teardownCreateView();
        $(document).off('keydown', 'form input, form textarea, form select');
        $(document).off('change', 'input[type=radio][name="pw_hash"]');
        Functions.teardownSortLinkMouseEvent();
    };
}

/**
 * @return {function}
 */
export function onloadFunctions () {
    return function () {
        Functions.onloadIdleEvent();
        $(document).on('click', 'input:checkbox.checkall', Functions.getCheckAllCheckboxEventHandler());
        Functions.addDateTimePicker();

        /**
         * Add attribute to text boxes for iOS devices (based on bugID: 3508912)
         */
        if (navigator.userAgent.match(/(iphone|ipod|ipad)/i)) {
            $('input[type=text]').attr('autocapitalize', 'off').attr('autocorrect', 'off');
        }

        Functions.onloadSqlQueryEditEvents();
        Functions.onloadCreateTableEvents();
        Functions.onloadChangePasswordEvents();
        Functions.onloadEnumSetEditorMessage();
        Functions.onloadEnumSetEditor();
        $(document).on('click', '#index_frm input[type=submit]', Functions.getAddIndexEventHandler());
        Functions.showHints();
        Functions.initializeToggleButtons();
        $(document).on('change', 'select.pageselector', Functions.getPageSelectorEventHandler());
        Functions.onloadRecentFavoriteTables();
        Functions.onloadCodeMirrorEditor();
        Functions.onloadLockPage();
        $(document).on('change', '.autosubmit', Functions.getAutoSubmitEventHandler());
        document.querySelectorAll('.jsPrintButton').forEach(item => {
            item.addEventListener('click', PrintPage);
        });

        $(document).on('click', 'a.create_view.ajax', function (e) {
            e.preventDefault();
            handleCreateViewModal($(this));
        });

        Functions.onloadCreateView();
        $(document).on('change', Functions.checkboxesSel, Functions.checkboxesChanged);
        $(document).on('change', 'input.checkall_box', Functions.getCheckAllBoxEventHandler());
        $(document).on('click', '.checkall-filter', Functions.getCheckAllFilterEventHandler());
        $(document).on('change', Functions.checkboxesSel + ', input.checkall_box:checkbox:enabled', Functions.subCheckboxesChanged);
        $(document).on('change', 'input.sub_checkall_box', Functions.getSubCheckAllBoxEventHandler());
        $(document).on('keyup', '#filterText', Functions.getFilterTextEventHandler());
        Functions.onloadFilterText();
        Functions.onloadLoginForm();
        $('form input, form textarea, form select').on('keydown', Functions.getKeyboardFormSubmitEventHandler());
        $(document).on('change', 'select#select_authentication_plugin_cp', Functions.getSslPasswordEventHandler());
        Functions.onloadSortLinkMouseEvent();
    };
}
