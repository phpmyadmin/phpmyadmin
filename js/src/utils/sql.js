import CodeMirror from 'codemirror';
import '../../../node_modules/codemirror/mode/sql/sql.js';
import '../../../node_modules/codemirror/addon/runmode/runmode.js';
import '../../../node_modules/codemirror/addon/hint/show-hint.js';
import '../../../node_modules/codemirror/addon/hint/sql-hint.js';
import '../../../node_modules/codemirror/addon/lint/lint.js';
// import '../../../node_modules/codemirror/addon/lint/sql-lint.js';
import { mysql_doc_builtin, mysql_doc_keyword } from '../consts/doclinks';

window.cm = CodeMirror;

/**
 * Adds doc link to single highlighted SQL element
 */
function PMA_doc_add ($elm, params) {
    if (typeof mysql_doc_template === 'undefined') {
        return;
    }

    var url = PMA_sprintf(
        decodeURIComponent(mysql_doc_template),
        params[0]
    );
    if (params.length > 1) {
        url += '#' + params[1];
    }
    var content = $elm.text();
    $elm.text('');
    $elm.append('<a target="mysql_doc" class="cm-sql-doc" href="' + url + '">' + content + '</a>');
}

/**
 * Generates doc links for keywords inside highlighted SQL
 */
function PMA_doc_keyword (idx, elm) {
    var $elm = $(elm);
    /* Skip already processed ones */
    if ($elm.find('a').length > 0) {
        return;
    }
    var keyword = $elm.text().toUpperCase();
    var $next = $elm.next('.cm-keyword');
    if ($next) {
        var next_keyword = $next.text().toUpperCase();
        var full = keyword + ' ' + next_keyword;

        var $next2 = $next.next('.cm-keyword');
        if ($next2) {
            var next2_keyword = $next2.text().toUpperCase();
            var full2 = full + ' ' + next2_keyword;
            if (full2 in mysql_doc_keyword) {
                PMA_doc_add($elm, mysql_doc_keyword[full2]);
                PMA_doc_add($next, mysql_doc_keyword[full2]);
                PMA_doc_add($next2, mysql_doc_keyword[full2]);
                return;
            }
        }
        if (full in mysql_doc_keyword) {
            PMA_doc_add($elm, mysql_doc_keyword[full]);
            PMA_doc_add($next, mysql_doc_keyword[full]);
            return;
        }
    }
    if (keyword in mysql_doc_keyword) {
        PMA_doc_add($elm, mysql_doc_keyword[keyword]);
    }
}

/**
 * Generates doc links for builtins inside highlighted SQL
 */
function PMA_doc_builtin (idx, elm) {
    var $elm = $(elm);
    var builtin = $elm.text().toUpperCase();
    if (builtin in mysql_doc_builtin) {
        PMA_doc_add($elm, mysql_doc_builtin[builtin]);
    }
}

/**
 * Higlights SQL using CodeMirror.
 */
export function PMA_highlightSQL ($base) {
    var $elm = $base.find('code.sql');
    $elm.each(function () {
        var $sql = $(this);
        var $pre = $sql.find('pre');
        /* We only care about visible elements to avoid double processing */
        if ($pre.is(':visible')) {
            var $highlight = $('<div class="sql-highlight cm-s-default"></div>');
            $sql.append($highlight);
            if (typeof CodeMirror !== 'undefined') {
                CodeMirror.runMode($sql.text(), 'text/x-mysql', $highlight[0]);
                $pre.hide();
                $highlight.find('.cm-keyword').each(PMA_doc_keyword);
                $highlight.find('.cm-builtin').each(PMA_doc_builtin);
            }
        }
    });
}


/**
 * "inputRead" event handler for CodeMirror SQL query editors for autocompletion
 */
let sql_autocomplete_in_progress = false;
let sql_autocomplete = false;
var sql_autocomplete_default_table = '';
export function codemirrorAutocompleteOnInputRead (instance) {
    if (!sql_autocomplete_in_progress
        && (!instance.options.hintOptions.tables || !sql_autocomplete)) {
        if (!sql_autocomplete) {
            // Reset after teardown
            console.log('ea');
            instance.options.hintOptions.tables = false;
            instance.options.hintOptions.defaultTable = '';

            sql_autocomplete_in_progress = true;

            var href = 'db_sql_autocomplete.php';
            var params = {
                'ajax_request': true,
                'server': PMA_commonParams.get('server'),
                'db': PMA_commonParams.get('db'),
                'no_debug': true
            };

            var columnHintRender = function (elem, self, data) {
                $('<div class="autocomplete-column-name">')
                    .text(data.columnName)
                    .appendTo(elem);
                $('<div class="autocomplete-column-hint">')
                    .text(data.columnHint)
                    .appendTo(elem);
            };

            $.ajax({
                type: 'POST',
                url: href,
                data: params,
                success: function (data) {
                    if (data.success) {
                        var tables = JSON.parse(data.tables);
                        sql_autocomplete_default_table = PMA_commonParams.get('table');
                        sql_autocomplete = [];
                        for (var table in tables) {
                            if (tables.hasOwnProperty(table)) {
                                var columns = tables[table];
                                table = {
                                    text: table,
                                    columns: []
                                };
                                for (var column in columns) {
                                    if (columns.hasOwnProperty(column)) {
                                        var displayText = columns[column].Type;
                                        if (columns[column].Key === 'PRI') {
                                            displayText += ' | Primary';
                                        } else if (columns[column].Key === 'UNI') {
                                            displayText += ' | Unique';
                                        }
                                        table.columns.push({
                                            text: column,
                                            displayText: column + ' | ' +  displayText,
                                            columnName: column,
                                            columnHint: displayText,
                                            render: columnHintRender
                                        });
                                    }
                                }
                            }
                            sql_autocomplete.push(table);
                        }
                        instance.options.hintOptions.tables = sql_autocomplete;
                        instance.options.hintOptions.defaultTable = sql_autocomplete_default_table;
                    }
                },
                complete: function () {
                    sql_autocomplete_in_progress = false;
                }
            });
        } else {
            instance.options.hintOptions.tables = sql_autocomplete;
            instance.options.hintOptions.defaultTable = sql_autocomplete_default_table;
        }
    }
    if (instance.state.completionActive) {
        return;
    }
    var cur = instance.getCursor();
    var token = instance.getTokenAt(cur);
    var string = '';
    if (token.string.match(/^[.`\w@]\w*$/)) {
        string = token.string;
    }
    if (string.length > 0) {
        CodeMirror.commands.autocomplete(instance);
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
    if ($textarea.length > 0 && typeof CodeMirror !== 'undefined') {
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

        if (CodeMirror.sqlLint) {
            console.log('asd');
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
