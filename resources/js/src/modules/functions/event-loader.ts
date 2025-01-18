import $ from 'jquery';
import {
    addDateTimePicker,
    checkboxesChanged,
    checkboxesSel,
    getAddIndexEventHandler,
    getAutoSubmitEventHandler,
    getCheckAllBoxEventHandler,
    getCheckAllCheckboxEventHandler,
    getCheckAllFilterEventHandler,
    getFilterTextEventHandler,
    getKeyboardFormSubmitEventHandler,
    getPageSelectorEventHandler,
    getSslPasswordEventHandler,
    getSubCheckAllBoxEventHandler,
    initializeToggleButtons,
    onloadChangePasswordEvents,
    onloadCodeMirrorEditor,
    onloadCreateTableEvents,
    onloadCreateView,
    onloadEnumSetEditor,
    onloadEnumSetEditorMessage,
    onloadFilterText,
    onloadIdleEvent,
    onloadLockPage,
    onloadLoginForm,
    onloadRecentFavoriteTables,
    onloadSortLinkMouseEvent,
    onloadSqlQueryEditEvents,
    removeAutocompleteInfo,
    showHints,
    subCheckboxesChanged,
    teardownCodeMirrorEditor,
    teardownCreateTableEvents,
    teardownCreateView,
    teardownEnumSetEditor,
    teardownEnumSetEditorMessage,
    teardownIdleEvent,
    teardownRecentFavoriteTables,
    teardownSortLinkMouseEvent,
    teardownSqlQueryEditEvents
} from '../functions.ts';
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
        teardownIdleEvent();
        $(document).off('click', 'input:checkbox.checkall');
        teardownSqlQueryEditEvents();
        removeAutocompleteInfo();
        teardownCreateTableEvents();
        teardownEnumSetEditorMessage();
        teardownEnumSetEditor();
        $(document).off('click', '#index_frm input[type=submit]');
        $('div.toggle-container').off('click');
        $(document).off('change', 'select.pageselector');
        teardownRecentFavoriteTables();
        teardownCodeMirrorEditor();
        $(document).off('change', '.autosubmit');
        document.querySelectorAll('.jsPrintButton').forEach(item => {
            item.removeEventListener('click', PrintPage);
        });

        $(document).off('click', 'a.create_view.ajax');
        teardownCreateView();
        $(document).off('keydown', 'form input, form textarea, form select');
        $(document).off('change', 'input[type=radio][name="pw_hash"]');
        teardownSortLinkMouseEvent();
    };
}

/**
 * @return {function}
 */
export function onloadFunctions () {
    return function () {
        onloadIdleEvent();
        $(document).on('click', 'input:checkbox.checkall', getCheckAllCheckboxEventHandler());
        addDateTimePicker();

        /**
         * Add attribute to text boxes for iOS devices (based on bugID: 3508912)
         */
        if (navigator.userAgent.match(/(iphone|ipod|ipad)/i)) {
            $('input[type=text]').attr('autocapitalize', 'off').attr('autocorrect', 'off');
        }

        onloadSqlQueryEditEvents();
        onloadCreateTableEvents();
        onloadChangePasswordEvents();
        onloadEnumSetEditorMessage();
        onloadEnumSetEditor();
        $(document).on('click', '#index_frm input[type=submit]', getAddIndexEventHandler());
        showHints();
        initializeToggleButtons();
        $(document).on('change', 'select.pageselector', getPageSelectorEventHandler());
        onloadRecentFavoriteTables();
        onloadCodeMirrorEditor();
        onloadLockPage();
        $(document).on('change', '.autosubmit', getAutoSubmitEventHandler());
        document.querySelectorAll('.jsPrintButton').forEach(item => {
            item.addEventListener('click', PrintPage);
        });

        $(document).on('click', 'a.create_view.ajax', function (e) {
            e.preventDefault();
            handleCreateViewModal($(this));
        });

        onloadCreateView();
        $(document).on('change', checkboxesSel, checkboxesChanged);
        $(document).on('change', 'input.checkall_box', getCheckAllBoxEventHandler());
        $(document).on('click', '.checkall-filter', getCheckAllFilterEventHandler());
        $(document).on('change', checkboxesSel + ', input.checkall_box:checkbox:enabled', subCheckboxesChanged);
        $(document).on('change', 'input.sub_checkall_box', getSubCheckAllBoxEventHandler());
        $(document).on('keyup', '#filterText', getFilterTextEventHandler());
        onloadFilterText();
        onloadLoginForm();
        $('form input, form textarea, form select').on('keydown', getKeyboardFormSubmitEventHandler());
        $(document).on('change', 'select#select_authentication_plugin_cp', getSslPasswordEventHandler());
        onloadSortLinkMouseEvent();
    };
}
