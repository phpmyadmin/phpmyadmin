import { sqlQueryOptions } from '../../utils/sql';
import CommonParams from '../../variables/common_params';
import { AJAX } from '../../ajax';
import { codemirrorAutocompleteOnInputRead } from '../../utils/sql';

import CodeMirror from 'codemirror';
import 'codemirror/mode/sql/sql.js';
import 'codemirror/addon/runmode/runmode.js';
import 'codemirror/addon/hint/show-hint.js';
import 'codemirror/addon/hint/sql-hint.js';
import 'codemirror/addon/lint/lint.js';
import '../../plugins/codemirror/sql-lint';

function catchKeypressesFromSqlInlineEdit (event) {
    // ctrl-enter is 10 in chrome and ie, but 13 in ff
    if ((event.ctrlKey || event.metaKey) && (event.keyCode === 13 || event.keyCode === 10)) {
        $('#sql_query_edit_save').trigger('click');
    }
}

/**
 * Creates an SQL editor which supports auto completing etc.
 *
 * @param $textarea   jQuery object wrapping the textarea to be made the editor
 * @param options     optional options for CodeMirror
 * @param resize      optional resizing ('vertical', 'horizontal', 'both')
 * @param lintOptions additional options for lint
 */

export function PMA_getSQLEditor ($textarea, options, resize, lintOptions) {
    if ($textarea.length > 0 && CommonParams.get('CodemirrorEnable') === true) {
        // merge options for CodeMirror
        var defaults = {
            lineNumbers: true,
            matchBrackets: true,
            extraKeys: { 'Ctrl-Space': 'autocomplete' },
            hintOptions: { 'completeSingle': false, 'completeOnSingleClick': true },
            indentUnit: 4,
            mode: 'text/x-mysql',
            lineWrapping: true
        };

        if (CommonParams.get('LintEnable')) {
            $.extend(defaults, {
                gutters: ['CodeMirror-lint-markers'],
                lint: {
                    'getAnnotations': CodeMirror.sqlLint,
                    'async': true,
                    'lintOptions': lintOptions
                }
            });
        }

        $.extend(true, defaults, options);

        // create CodeMirror editor
        var codemirrorEditor = CodeMirror.fromTextArea($textarea[0], defaults);
        // allow resizing
        if (! resize) {
            resize = 'vertical';
        }
        var handles = '';
        if (resize === 'vertical') {
            handles = 's';
        }
        if (resize === 'both') {
            handles = 'all';
        }
        if (resize === 'horizontal') {
            handles = 'e, w';
        }
        $(codemirrorEditor.getWrapperElement())
            .css('resize', resize)
            .resizable({
                handles: handles,
                resize: function () {
                    codemirrorEditor.setSize($(this).width(), $(this).height());
                }
            });
        // enable autocomplete
        codemirrorEditor.on('inputRead', codemirrorAutocompleteOnInputRead);

        // page locking
        codemirrorEditor.on('change', function (e) {
            e.data = {
                value: 3,
                content: codemirrorEditor.isClean(),
            };
            AJAX.lockPageHandler(e);
        });

        return codemirrorEditor;
    }
    return null;
}

/**
 * Binds the CodeMirror to the text area used to inline edit a query.
 */
export function bindCodeMirrorToInlineEditor () {
    var $inline_editor = $('#sql_query_edit');
    if ($inline_editor.length > 0) {
        if (CommonParams.get('CodemirrorEnable') === true) {
            var height = $inline_editor.css('height');
            sqlQueryOptions.codemirror_inline_editor = PMA_getSQLEditor($inline_editor);
            sqlQueryOptions.codemirror_inline_editor.getWrapperElement().style.height = height;
            sqlQueryOptions.codemirror_inline_editor.refresh();
            sqlQueryOptions.codemirror_inline_editor.focus();
            $(sqlQueryOptions.codemirror_inline_editor.getWrapperElement())
                .on('keydown', catchKeypressesFromSqlInlineEdit);
        } else {
            $inline_editor
                .focus()
                .on('keydown', catchKeypressesFromSqlInlineEdit);
        }
    }
}
