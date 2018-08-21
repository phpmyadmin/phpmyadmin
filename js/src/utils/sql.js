import CodeMirror from 'codemirror';
import 'codemirror/mode/sql/sql.js';
import 'codemirror/addon/runmode/runmode.js';
import 'codemirror/addon/hint/show-hint.js';
import 'codemirror/addon/hint/sql-hint.js';
import 'codemirror/addon/lint/lint.js';
import '../plugins/codemirror/sql-lint';
import { mysql_doc_builtin, mysql_doc_keyword } from '../consts/doclinks';
import CommonParams from '../variables/common_params';
import { GlobalVariables, PMA_Messages as PMA_messages } from '../variables/export_variables';

/**
 * Adds doc link to single highlighted SQL element
 */
function PMA_doc_add ($elm, params) {
    if (typeof mysql_doc_template === 'undefined') {
        return;
    }

    var url = PMA_sprintf(
        decodeURIComponent(GlobalVariables.mysql_doc_template),
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

export var sqlQueryOptions = {
    codemirror_editor: false,
    codemirror_inline_editor: false
};

export function codemirrorAutocompleteOnInputRead (instance) {
    if (!sql_autocomplete_in_progress
        && (!instance.options.hintOptions.tables || !sql_autocomplete)) {
        if (!sql_autocomplete) {
            // Reset after teardown
            instance.options.hintOptions.tables = false;
            instance.options.hintOptions.defaultTable = '';

            sql_autocomplete_in_progress = true;

            var href = 'db_sql_autocomplete.php';
            var params = {
                'ajax_request': true,
                'server': CommonParams.get('server'),
                'db': CommonParams.get('db'),
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
                        sql_autocomplete_default_table = CommonParams.get('table');
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
 * Updates the input fields for the parameters based on the query
 */
export function updateQueryParameters () {
    if ($('#parameterized').is(':checked')) {
        var query = sqlQueryOptions.codemirror_editor
            ? sqlQueryOptions.codemirror_editor.getValue()
            : $('#sqlquery').val();

        var allParameters = query.match(/:[a-zA-Z0-9_]+/g);
        var parameters = [];
        // get unique parameters
        if (allParameters) {
            $.each(allParameters, function (i, parameter) {
                if ($.inArray(parameter, parameters) === -1) {
                    parameters.push(parameter);
                }
            });
        } else {
            $('#parametersDiv').text(PMA_messages.strNoParam);
            return;
        }

        var $temp = $('<div />');
        $temp.append($('#parametersDiv').children());
        $('#parametersDiv').empty();

        $.each(parameters, function (i, parameter) {
            var paramName = parameter.substring(1);
            var $param = $temp.find('#paramSpan_' + paramName);
            if (! $param.length) {
                $param = $('<span class="parameter" id="paramSpan_' + paramName + '" />');
                $('<label for="param_' + paramName + '" />').text(parameter).appendTo($param);
                $('<input type="text" name="parameters[' + parameter + ']" id="param_' + paramName + '" />').appendTo($param);
            }
            $('#parametersDiv').append($param);
        });
    } else {
        $('#parametersDiv').empty();
    }
}
